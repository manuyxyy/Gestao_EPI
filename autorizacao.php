<?php
/**
 * Etapa 2 do cadastro: autorização da conta.
 *
 * A conta já foi criada pelo cadastro.php com status "Inativo" e só é ativada
 * quando a pessoa informa um código válido gerado pelo administrador na tela
 * "Códigos de acesso". Nenhum perfil é escolhido aqui: ele vem do link inicial.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/acessibilidade.php';

if (usuario_logado()) {
    redirecionar(pagina_inicial());
}

$pendente = $_SESSION['cadastro_pendente'] ?? null;

if (!is_array($pendente) || empty($pendente['id']) || empty($pendente['email'])) {
    flash('aviso', 'Crie sua conta antes de informar o código de autorização.');
    redirecionar('cadastro.php');
}

$usuarioId = (int)$pendente['id'];
$email     = mb_strtolower(trim((string)$pendente['email']));
$perfil    = normalizar_perfil($pendente['perfil'] ?? null);
$rotulo    = rotulo_perfil($perfil);

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? LIMIT 1");
$stmt->execute([$usuarioId]);
$conta = $stmt->fetch();

if (!$conta || mb_strtolower((string)$conta['email']) !== $email) {
    unset($_SESSION['cadastro_pendente']);
    flash('erro', 'Não foi possível localizar a conta aguardando autorização. Crie o cadastro novamente.');
    redirecionar('cadastro.php');
}

if (status_ativo($conta['status'] ?? 'Inativo')) {
    unset($_SESSION['cadastro_pendente']);
    flash('sucesso', 'Esta conta já está autorizada. Entre no sistema.');
    redirecionar('index.php');
}

$erro   = '';
$codigo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = mb_strtoupper(trim((string)($_POST['codigo'] ?? '')));

    if (!csrf_validar()) {
        $erro = 'Sessão expirada. Atualize a página e tente novamente.';
    } elseif ($codigo === '') {
        $erro = 'Informe o código de autorização recebido do administrador.';
    } else {
        $convite = convite_validar($codigo, $perfil);

        if (!$convite) {
            $erro = 'Código inválido, já usado, revogado, expirado ou não liberado para o perfil '
                  . $rotulo . '. Peça um novo código ao administrador.';
        } else {
            try {
                $pdo->beginTransaction();

                $verificar = $pdo->prepare(
                    "SELECT * FROM usuarios WHERE id = ? AND status <> 'Ativo' LIMIT 1"
                );
                $verificar->execute([$usuarioId]);
                $liberar = $verificar->fetch();

                if (!$liberar) {
                    throw new RuntimeException('A conta já foi autorizada.');
                }

                $ativar = $pdo->prepare("UPDATE usuarios SET status = 'Ativo' WHERE id = ?");
                $ativar->execute([$usuarioId]);

                if (!convite_consumir((int)$convite['id'], $usuarioId)) {
                    throw new RuntimeException('O código já foi utilizado.');
                }

                $pdo->commit();

                unset($_SESSION['cadastro_pendente']);
                iniciar_sessao_usuario($liberar);
                flash('sucesso', 'Conta autorizada! Bem-vindo(a) ao EpiSafe.');
                redirecionar(pagina_inicial());
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Falha na autorização de cadastro: ' . $e->getMessage());
                $erro = 'Não foi possível concluir a autorização. Tente novamente ou peça outro código ao administrador.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="acess-alto-contraste acess-sem-movimento">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#05070a">
    <link rel="icon" type="image/svg+xml" href="assets/logo-episafe.svg">
    <link rel="apple-touch-icon" type="image/svg+xml" href="assets/logo-episafe.svg">
    <meta name="description" content="Informe o código de autorização do EpiSafe.">
    <title>Autorizar conta | EpiSafe</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700&family=Public+Sans:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="index.css">
    <script><?= acessibilidade_script_inicial() ?></script>
    <link rel="stylesheet" href="acessibilidade.css">
    <script src="acessibilidade.js" defer></script>
</head>
<body class="home-body">

<?= ferramentas_acessibilidade() ?>
<a class="skip-link" href="#form-autorizacao">Pular para o formulário de autorização</a>

<main class="login-page">
    <div class="login-card cadastro-card">
        <header class="login-head">
            <a class="login-logo" href="index.php" aria-label="EpiSafe — página inicial">
                <span class="login-logo-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z"/>
                        <path d="M9 12l2 2 4-4"/>
                    </svg>
                </span>
                <span>Epi<span>Safe</span></span>
            </a>
            <h1>Autorização da conta</h1>
            <p>Etapa 2 de 2: informe o código gerado pelo administrador para liberar seu acesso.</p>
        </header>

        <div class="autorizacao-resumo">
            <dl>
                <dt>Conta criada</dt>
                <dd><?= h($conta['nome']) ?> — <?= h($email) ?></dd>
                <dt>Área</dt>
                <dd><?= h($rotulo) ?> (setor definido automaticamente)</dd>
                <dt>Situação</dt>
                <dd><strong>Aguardando autorização</strong></dd>
            </dl>
        </div>

        <?php if ($erro !== ''): ?>
            <div class="login-alert error" role="alert">
                <span><?= h($erro) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="autorizacao.php"
              class="login-form cadastro-form" id="form-autorizacao" tabindex="-1" autocomplete="off">
            <?= csrf_campo() ?>

            <div class="form-group">
                <label for="codigo">Código de autorização <span class="obrigatorio">*</span></label>
                <input type="text" id="codigo" name="codigo" required maxlength="20"
                       class="codigo-autorizacao" value="<?= h($codigo) ?>"
                       placeholder="AL-XXXXXXXX" autocomplete="off" autocapitalize="characters"
                       spellcheck="false" autofocus>
                <p class="field-help">
                    O código é gerado pelo administrador em <strong>Códigos de acesso</strong>.
                    Ele começa com <strong>AL</strong> (Almoxarifado), <strong>GE</strong> (Gestor)
                    ou <strong>AD</strong> (Administrador) e deve corresponder à sua área.
                </p>
            </div>

            <button type="submit" class="btn-login">
                Autorizar e entrar
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14"/><path d="M13 6l6 6-6 6"/>
                </svg>
            </button>
        </form>

        <p class="login-foot">
            Precisa corrigir seus dados?
            <a class="login-link" href="cadastro.php?perfil=<?= h(mb_strtolower($perfil)) ?>">Voltar ao cadastro</a>
            · <a class="login-link" href="index.php">Entrar</a>
        </p>

        <div class="login-security">
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
            <span>Somente o administrador autoriza cadastros · o gestor não libera acessos</span>
        </div>
    </div>
</main>

<script>
(function () {
    'use strict';
    var campo = document.getElementById('codigo');
    if (!campo) return;
    campo.addEventListener('input', function () {
        var inicio = campo.selectionStart;
        campo.value = campo.value.toUpperCase();
        campo.setSelectionRange(inicio, inicio);
    });
})();
</script>
</body>
</html>
