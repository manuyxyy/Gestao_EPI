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
    return ['Administrador', 'Gestor', 'Almoxarifado', 'Tecnico'];
}

/**
 * Converte qualquer variação gravada no banco (antiga ou nova) em um dos
 * perfis canônicos: Administrador, Gestor, Almoxarifado ou Tecnico.
 */
function normalizar_perfil(?string $valor): string
{
    $v = mb_strtolower(trim((string)$valor));

    return match (true) {
        in_array($v, ['administrador', 'admin', 'adm'], true) => 'Administrador',
        in_array($v, ['gestor', 'gerente'], true)             => 'Gestor',
        in_array($v, ['almoxarifado', 'almoxarife'], true)    => 'Almoxarifado',
        default                                               => 'Tecnico',
    };
}

function rotulo_perfil(?string $perfil): string
{
    return match (normalizar_perfil($perfil)) {
        'Administrador' => 'Administrador',
        'Gestor'        => 'Gestor',
        'Almoxarifado'  => 'Almoxarifado',
        default         => 'Técnico',
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

    if ($validado) {
        return;
    }

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

function exigir_perfil(array $perfisPermitidos): void
{
    exigir_login();

    $permitidos = array_map('normalizar_perfil', $perfisPermitidos);

    if (!in_array(perfil_atual(), $permitidos, true)) {
        http_response_code(403);
        flash('erro', 'Você não tem permissão para acessar essa página.');
        redirecionar('dashboard_admin.php');
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

    return [true, ''];
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
