<?php
/**
 * Núcleo de autenticação, sessão, permissões, CSRF e mensagens do EpiSafe.
 *
 * Todas as páginas incluem este arquivo (direto ou via layout.php).
 */

require_once __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

/* -------------------------------------------------------------------------
 * Sessão
 * ---------------------------------------------------------------------- */

function sessao_iniciar(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $https = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => $https,
        'samesite' => 'Lax',
    ]);

    session_name('EPISAFE_SESSID');
    session_start();
}

sessao_iniciar();

/* -------------------------------------------------------------------------
 * Saída segura
 * ---------------------------------------------------------------------- */

function h($valor): string
{
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* -------------------------------------------------------------------------
 * Perfis de acesso
 * ---------------------------------------------------------------------- */

function perfis_validos(): array
{
    return ['Administrador', 'Gestor', 'Almoxarifado'];
}

/* -------------------------------------------------------------------------
 * Códigos de autorização (gerados SOMENTE pelo administrador)
 *
 * Os códigos ficam na tabela `convites`, criados pela tela "Códigos de acesso".
 * Todos os perfis — Almoxarifado, Gestor e Administrador — precisam informar
 * um código válido. O Gestor não autoriza ninguém.
 * ---------------------------------------------------------------------- */

/** Prefixo do código por perfil (AL, GE, AD). */
function prefixo_convite(string $perfil): string
{
    return match (normalizar_perfil($perfil)) {
        'Administrador' => 'AD',
        'Gestor'        => 'GE',
        default         => 'AL',
    };
}

/** Situação exibida na tela do administrador. */
function convite_situacao(array $convite): string
{
    if (!empty($convite['usado_em'])) {
        return 'Usado';
    }
    if (!empty($convite['revogado_em'])) {
        return 'Revogado';
    }
    if (!empty($convite['expira_em']) && strtotime((string)$convite['expira_em']) < time()) {
        return 'Expirado';
    }

    return 'Disponível';
}

/** Gera um código novo para o perfil. Retorna o código pronto para entrega. */
function convite_gerar(string $perfil, int $diasValidade = 7): string
{
    global $pdo;

    $perfil = normalizar_perfil($perfil);
    $prefixo = prefixo_convite($perfil);
    $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // sem 0/O/1/I para evitar confusão

    do {
        $codigo = $prefixo . '-';
        for ($i = 0; $i < 8; $i++) {
            $codigo .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }

        $existe = $pdo->prepare("SELECT COUNT(*) FROM convites WHERE codigo = ?");
        $existe->execute([$codigo]);
    } while ((int)$existe->fetchColumn() > 0);

    $expira = $diasValidade > 0
        ? date('Y-m-d H:i:s', time() + $diasValidade * 86400)
        : null;

    $stmt = $pdo->prepare("
        INSERT INTO convites (codigo, perfil, criado_por, criado_em, expira_em)
        VALUES (?, ?, ?, NOW(), ?)
    ");
    $stmt->execute([$codigo, $perfil, usuario_id(), $expira]);

    return $codigo;
}

/**
 * Valida um código digitado pelo usuário.
 *
 * @return array|null Dados do convite quando disponível para o perfil informado.
 */
function convite_validar(?string $codigo, ?string $perfil = null): ?array
{
    global $pdo;

    $codigo = mb_strtoupper(trim((string)$codigo));
    if ($codigo === '') {
        return null;
    }

    $stmt = $pdo->prepare("SELECT * FROM convites WHERE codigo = ? LIMIT 1");
    $stmt->execute([$codigo]);
    $convite = $stmt->fetch();

    if (!$convite || !hash_equals((string)$convite['codigo'], $codigo)) {
        return null;
    }
    if (convite_situacao($convite) !== 'Disponível') {
        return null;
    }
    if ($perfil !== null && normalizar_perfil($convite['perfil']) !== normalizar_perfil($perfil)) {
        return null;
    }

    return $convite;
}

/** Marca o código como usado por uma conta recém-autorizada. */
function convite_consumir(int $id, int $usuarioId): bool
{
    global $pdo;

    $stmt = $pdo->prepare("
        UPDATE convites
           SET usado_em = NOW(), usado_por = ?
         WHERE id = ? AND usado_em IS NULL AND revogado_em IS NULL
    ");
    $stmt->execute([$usuarioId, $id]);

    return $stmt->rowCount() > 0;
}

/** Cancela um código que ainda não foi usado. */
function convite_revogar(int $id): bool
{
    global $pdo;

    $stmt = $pdo->prepare("
        UPDATE convites
           SET revogado_em = NOW()
         WHERE id = ? AND usado_em IS NULL AND revogado_em IS NULL
    ");
    $stmt->execute([$id]);

    return $stmt->rowCount() > 0;
}

function convite_excluir(int $id): bool
{
    global $pdo;

    $stmt = $pdo->prepare("DELETE FROM convites WHERE id = ?");
    $stmt->execute([$id]);

    return $stmt->rowCount() > 0;
}

/** Lista os códigos com os nomes de quem criou e de quem usou. */
function convites_listar(): array
{
    global $pdo;

    $stmt = $pdo->query("
        SELECT c.*,
               criador.nome AS criado_por_nome,
               usuario.nome AS usado_por_nome
          FROM convites c
          LEFT JOIN usuarios criador ON criador.id = c.criado_por
          LEFT JOIN usuarios usuario ON usuario.id = c.usado_por
         ORDER BY c.id DESC
    ");

    return $stmt->fetchAll();
}

/** Perfil fixado pelo link de cadastro (nunca aceito via POST). */
function perfil_de_cadastro(?string $param): string
{
    return match (mb_strtolower(trim((string)$param))) {
        'adm', 'admin', 'administrador' => 'Administrador',
        'gestor', 'gerente'            => 'Gestor',
        default                        => 'Almoxarifado',
    };
}

function cadastro_exige_convite(string $perfil): bool
{
    // Somente o administrador autoriza: TODOS os perfis exigem o código.
    return in_array(normalizar_perfil($perfil), perfis_validos(), true);
}

/**
 * Converte qualquer variação gravada no banco (antiga ou nova) em um dos
 * perfis canônicos: Administrador, Gestor ou Almoxarifado.
 *
 * Perfis antigos ("Tecnico" / "Funcionario") são tratados como Almoxarifado
 * para que continuem funcionando sem precisar recriar o usuário.
 */
function normalizar_perfil(?string $valor): string
{
    $v = mb_strtolower(trim((string)$valor));

    return match (true) {
        in_array($v, ['administrador', 'admin', 'adm'], true)              => 'Administrador',
        in_array($v, ['gestor', 'gerente'], true)                          => 'Gestor',
        in_array($v, ['almoxarifado', 'almoxarife', 'tecnico', 'técnico'], true) => 'Almoxarifado',
        default                                                            => 'Almoxarifado',
    };
}

function rotulo_perfil(?string $perfil): string
{
    return match (normalizar_perfil($perfil)) {
        'Administrador' => 'Administrador',
        'Gestor'        => 'Gestor',
        default         => 'Almoxarifado',
    };
}

/** Classe CSS usada no "chip" de perfil. */
function classe_perfil(?string $perfil): string
{
    return 'perfil-' . mb_strtolower(normalizar_perfil($perfil));
}

function status_ativo(?string $status): bool
{
    return mb_strtolower(trim((string)$status)) === 'ativo';
}

/* -------------------------------------------------------------------------
 * Dados do usuário logado
 * ---------------------------------------------------------------------- */

function usuario_logado(): bool
{
    return !empty($_SESSION['usuario_id']);
}

function usuario_id(): int
{
    return (int)($_SESSION['usuario_id'] ?? 0);
}

function perfil_atual(): string
{
    return normalizar_perfil(
        $_SESSION['usuario_perfil']
        ?? $_SESSION['perfil']
        ?? $_SESSION['usuario_funcao']
        ?? null
    );
}

function usuario_atual(): array
{
    return [
        'id'     => usuario_id(),
        'nome'   => (string)($_SESSION['usuario_nome'] ?? 'Usuário'),
        'email'  => (string)($_SESSION['usuario_email'] ?? ''),
        'perfil' => perfil_atual(),
        'setor'  => (string)($_SESSION['usuario_setor'] ?? ''),
    ];
}

function e_admin(): bool
{
    return perfil_atual() === 'Administrador';
}

/* -------------------------------------------------------------------------
 * Permissões por página
 * ---------------------------------------------------------------------- */

/** Todas as páginas internas do sistema. */
function paginas_do_sistema(): array
{
    return [
        'dashboard_admin.php',
        'entregas.php',
        'devolucoes.php',
        'movimentacoes.php',
        'epis.php',
        'funcionarios.php',
        'estoque.php',
        'usuarios.php',
        'convites.php',
        'validade.php',
        'relatorios.php',
        'perfil.php',
        'suporte.php',
    ];
}

/**
 * Páginas que cada perfil pode abrir.
 *
 * O administrador acessa tudo. Os demais perfis têm acesso apenas às
 * rotinas que realmente usam — isso evita erros e alterações indevidas.
 */
function paginas_por_perfil(?string $perfil = null): array
{
    $perfil = normalizar_perfil($perfil ?? perfil_atual());

    return match ($perfil) {
        'Administrador' => paginas_do_sistema(),
        'Gestor' => [
            'dashboard_admin.php',
            'epis.php',
            'validade.php',
            'relatorios.php',
            'perfil.php',
            'suporte.php',
        ],
        default => [ // Almoxarifado
            'dashboard_admin.php',
            'entregas.php',
            'devolucoes.php',
            'movimentacoes.php',
            'epis.php',
            'estoque.php',
            'validade.php',
            'perfil.php',
            'suporte.php',
        ],
    };
}

function perfil_pode_acessar(string $pagina, ?string $perfil = null): bool
{
    $pagina = basename($pagina);

    if ($pagina === '') {
        return true;
    }

    // Páginas novas/desconhecidas ficam restritas ao administrador até serem
    // incluídas explicitamente na matriz de permissões.
    if (!in_array($pagina, paginas_do_sistema(), true)) {
        return normalizar_perfil($perfil ?? perfil_atual()) === 'Administrador';
    }

    return in_array($pagina, paginas_por_perfil($perfil), true);
}

/** Página inicial de cada perfil, usada após o login. */
function pagina_inicial(?string $perfil = null): string
{
    return match (normalizar_perfil($perfil ?? perfil_atual())) {
        'Administrador' => 'dashboard_admin.php',
        'Gestor'        => 'relatorios.php',
        default         => 'estoque.php',
    };
}

/* -------------------------------------------------------------------------
 * Redirecionamento e proteção de páginas
 * ---------------------------------------------------------------------- */

function redirecionar(string $destino): void
{
    header('Location: ' . $destino);
    exit;
}

/**
 * Garante que exista uma sessão válida e que o usuário ainda esteja ativo no
 * banco (se o administrador desativar a conta, o acesso cai na hora).
 */
function exigir_login(): void
{
    if (!usuario_logado()) {
        flash('aviso', 'Faça login para continuar.');
        redirecionar('index.php?aviso=login');
    }

    static $validado = false;

    if (!$validado) {
        global $pdo;

        try {
            $stmt = $pdo->prepare(
                "SELECT id, nome, email, tipo_acesso, funcao, setor, status
                   FROM usuarios
                  WHERE id = ?
                  LIMIT 1"
            );
            $stmt->execute([usuario_id()]);
            $u = $stmt->fetch();
        } catch (Throwable $e) {
            error_log('Falha ao validar sessão: ' . $e->getMessage());
            $u = null;
        }

        if (!$u || !status_ativo($u['status'] ?? 'Ativo')) {
            encerrar_sessao();
            flash('erro', 'Sua sessão expirou ou a conta foi desativada. Entre novamente.');
            redirecionar('index.php?aviso=sessao');
        }

        // Mantém a sessão sincronizada com o banco.
        $_SESSION['usuario_nome']   = $u['nome'];
        $_SESSION['usuario_email']  = $u['email'];
        $_SESSION['usuario_setor']  = $u['setor'] ?? '';
        $_SESSION['usuario_perfil'] = normalizar_perfil($u['tipo_acesso'] ?? $u['funcao'] ?? null);
        $_SESSION['usuario_funcao'] = $u['funcao'] ?? $_SESSION['usuario_perfil'];
        $_SESSION['perfil']         = $_SESSION['usuario_perfil'];

        $validado = true;
    }

    // Bloqueia páginas que não pertencem ao perfil do usuário.
    $pagina = basename((string)($_SERVER['PHP_SELF'] ?? ''));

    if (!perfil_pode_acessar($pagina)) {
        flash('erro', 'Seu perfil não tem permissão para acessar essa página.');
        redirecionar(pagina_inicial());
    }
}

function exigir_perfil(array $perfisPermitidos): void
{
    exigir_login();

    $permitidos = array_map('normalizar_perfil', $perfisPermitidos);

    if (!in_array(perfil_atual(), $permitidos, true)) {
        http_response_code(403);
        flash('erro', 'Você não tem permissão para acessar essa página.');
        redirecionar(pagina_inicial());
    }
}

/* -------------------------------------------------------------------------
 * CSRF
 * ---------------------------------------------------------------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function csrf_validar(): bool
{
    $enviado = $_POST['csrf_token'] ?? '';

    return is_string($enviado)
        && $enviado !== ''
        && hash_equals(csrf_token(), $enviado);
}

/**
 * Deve ser chamado no início de qualquer página que processe POST.
 * Em caso de token inválido, avisa e devolve o usuário para a mesma tela.
 */
function csrf_proteger(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    if (!csrf_validar()) {
        flash('erro', 'Sessão expirada. Envie o formulário novamente.');
        redirecionar(basename($_SERVER['PHP_SELF'] ?? 'index.php'));
    }
}

/* -------------------------------------------------------------------------
 * Mensagens (flash)
 * ---------------------------------------------------------------------- */

function flash(string $tipo, string $mensagem): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

function consumir_flashes(): array
{
    $mensagens = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $mensagens;
}

/* -------------------------------------------------------------------------
 * Login
 * ---------------------------------------------------------------------- */

function ip_do_usuario(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

/** Segundos restantes de bloqueio por excesso de tentativas (0 = liberado). */
function login_bloqueado_por(): int
{
    $ate = (int)($_SESSION['login_bloqueado_ate'] ?? 0);

    return max(0, $ate - time());
}

function registrar_falha_login(): void
{
    $falhas = (int)($_SESSION['login_falhas'] ?? 0) + 1;
    $_SESSION['login_falhas'] = $falhas;

    if ($falhas >= 5) {
        $_SESSION['login_bloqueado_ate'] = time() + 300; // 5 minutos
        $_SESSION['login_falhas'] = 0;
    }
}

function limpar_falhas_login(): void
{
    unset($_SESSION['login_falhas'], $_SESSION['login_bloqueado_ate']);
}

/**
 * Valida as credenciais e cria a sessão.
 *
 * @return array{0: bool, 1: string} [sucesso, mensagem de erro]
 */
function autenticar(string $email, string $senha): array
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $u = $stmt->fetch();

    if (!$u || !password_verify($senha, (string)$u['senha'])) {
        registrar_falha_login();

        return [false, 'E-mail ou senha inválidos.'];
    }

    if (!status_ativo($u['status'] ?? 'Ativo')) {
        return [false, 'Sua conta está desativada. Procure o administrador.'];
    }

    // Atualiza o hash caso o algoritmo padrão do PHP tenha mudado.
    if (password_needs_rehash((string)$u['senha'], PASSWORD_DEFAULT)) {
        try {
            $up = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
            $up->execute([password_hash($senha, PASSWORD_DEFAULT), $u['id']]);
        } catch (Throwable $e) {
            error_log('Falha ao atualizar hash de senha: ' . $e->getMessage());
        }
    }

    iniciar_sessao_usuario($u);

    return [true, ''];
}

/**
 * Cria a sessão a partir de um usuário já validado (login ou autorização).
 */
function iniciar_sessao_usuario(array $u): void
{
    global $pdo;

    $perfil = normalizar_perfil($u['tipo_acesso'] ?? $u['funcao'] ?? null);

    session_regenerate_id(true);
    limpar_falhas_login();

    $_SESSION['usuario_id']     = (int)$u['id'];
    $_SESSION['usuario_nome']   = $u['nome'];
    $_SESSION['usuario_email']  = $u['email'];
    $_SESSION['usuario_setor']  = $u['setor'] ?? '';
    $_SESSION['usuario_perfil'] = $perfil;
    $_SESSION['usuario_funcao'] = $u['funcao'] ?? $perfil;
    $_SESSION['perfil']         = $perfil;
    $_SESSION['ultimo_login']   = $u['ultimo_acesso'] ?? null;

    // Guarda o último acesso (data/hora/IP) para exibir no perfil.
    try {
        $up = $pdo->prepare(
            "UPDATE usuarios
                SET ultimo_acesso = NOW(), ultimo_ip = ?
              WHERE id = ?"
        );
        $up->execute([ip_do_usuario(), (int)$u['id']]);
    } catch (Throwable $e) {
        error_log('Falha ao registrar último acesso: ' . $e->getMessage());
    }
}

function encerrar_sessao(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => (bool)$params['secure'],
                'httponly' => (bool)$params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]
        );
    }

    session_destroy();
}

/* -------------------------------------------------------------------------
 * Utilidades de data
 * ---------------------------------------------------------------------- */

function data_br(?string $data, bool $comHora = false): string
{
    if (empty($data) || $data === '0000-00-00' || $data === '0000-00-00 00:00:00') {
        return '—';
    }

    $ts = strtotime($data);

    if ($ts === false) {
        return '—';
    }

    return date($comHora ? 'd/m/Y H:i' : 'd/m/Y', $ts);
}
