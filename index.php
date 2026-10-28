<?php
/**
 * Página inicial / login do EpiSafe.
 */
require_once __DIR__ . '/auth.php';

// Usuário já autenticado vai direto para o painel.
if (usuario_logado()) {
    redirecionar('dashboard_admin.php');
}

$erro          = '';
$email         = trim((string)($_POST['email'] ?? $_COOKIE['episafe_email'] ?? ''));
$lembrar       = isset($_POST['lembrar']) || isset($_COOKIE['episafe_email']);
$bloqueio      = login_bloqueado_por();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $bloqueio === 0) {
    if (!csrf_validar()) {
        $erro = 'Sessão expirada. Atualize a página e tente novamente.';
    } else {
        $senha = (string)($_POST['senha'] ?? '');

        if ($email === '' || $senha === '') {
            $erro = 'Informe o e-mail e a senha.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'Informe um e-mail válido.';
        } else {
            [$ok, $mensagem] = autenticar($email, $senha);

            if ($ok) {
                if ($lembrar) {
                    setcookie('episafe_email', $email, [
                        'expires'  => time() + 60 * 60 * 24 * 30,
                        'path'     => '/',
                        'httponly' => true,
                        'samesite' => 'Lax',
                    ]);
                } else {
                    setcookie('episafe_email', '', [
                        'expires'  => time() - 3600,
                        'path'     => '/',
                        'httponly' => true,
                        'samesite' => 'Lax',
                    ]);
                }

                redirecionar('dashboard_admin.php?boas_vindas=1');
            }

            $erro     = $mensagem;
            $bloqueio = login_bloqueado_por();
        }
    }
}

// Mensagens vindas de redirecionamentos (sessão expirada, logout, etc.).
$aviso = '';
$avisoParam = (string)($_GET['aviso'] ?? '');
$erroParam  = (string)($_GET['erro'] ?? '');

if ($avisoParam === 'logout') {
    $aviso = 'Você saiu do sistema com segurança.';
} elseif ($avisoParam === 'sessao' || $avisoParam === 'login') {
    $aviso = 'Sua sessão terminou. Entre novamente para continuar.';
} elseif ($erroParam === 'acesso_negado') {
    $erro = 'Seu perfil não tem permissão para acessar essa página.';
}

if ($bloqueio > 0) {
    $minutos = (int)ceil($bloqueio / 60);
    $erro = "Muitas tentativas sem sucesso. Aguarde {$minutos} minuto(s) para tentar novamente.";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1d2127">
    <meta name="description" content="EpiSafe — sistema de gestão de Equipamentos de Proteção Individual.">
    <title>EpiSafe | Gestão de EPIs</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700&family=Public+Sans:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="index.css">
</head>
<body class="home-body">

<div class="home-wrap">

    <!-- ------------------------------------------------ Marca (esquerda) -->
    <section class="home-brand" aria-labelledby="marca-titulo">
        <div class="home-brand-top">
            <span class="home-logo" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z"/>
                    <path d="M9 12l2 2 4-4"/>
                </svg>
            </span>
            <div class="home-brand-name">Epi<span>Safe</span></div>
        </div>

        <div class="home-brand-copy">
            <p class="home-eyebrow">Segurança do trabalho, sem planilhas</p>
            <h1 id="marca-titulo">Controle os EPIs da sua equipe em um só lugar.</h1>
            <p class="home-lead">
                Entregas, devoluções, estoque, validade dos CAs e usuários — tudo
                registrado e auditável, do cadastro à devolução.
            </p>

            <ul class="home-features">
                <li>
                    <span class="home-feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                             stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5"/><path d="M12 13v8"/></svg>
                    </span>
                    <span><strong>Entregas e devoluções</strong><small>Baixa automática do estoque e histórico completo.</small></span>
                </li>
                <li>
                    <span class="home-feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                             stroke-linecap="round" stroke-linejoin="round"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z"/><path d="M12 7v5l3 2"/></svg>
                    </span>
                    <span><strong>Validade dos CAs</strong><small>Alerta de certificados vencidos ou perto do vencimento.</small></span>
                </li>
                <li>
                    <span class="home-feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                             stroke-linecap="round" stroke-linejoin="round"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                    </span>
                    <span><strong>Perfis de acesso</strong><small>Administrador, gestor, almoxarifado e técnico.</small></span>
                </li>
            </ul>
        </div>

        <footer class="home-brand-foot">
            <span>Ambiente interno · Gestão de EPIs</span>
            <span>&copy; <?= date('Y') ?> EpiSafe</span>
        </footer>
    </section>

    <!-- ------------------------------------------------ Login (direita) -->
    <main class="home-login">
        <div class="login-card">
            <header class="login-head">
                <span class="login-head-badge" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <path d="M16 17l5-5-5-5"/>
                        <path d="M21 12H9"/>
                    </svg>
                </span>
                <h2>Acessar o sistema</h2>
                <p>Entre com o e-mail e a senha cadastrados pelo administrador.</p>
            </header>

            <?php if ($aviso !== ''): ?>
                <div class="login-alert info" role="status">
                    <span><?= htmlspecialchars($aviso, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endif; ?>

            <?php if ($erro !== ''): ?>
                <div class="login-alert error" role="alert">
                    <span><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php" class="login-form" autocomplete="on" novalidate>
                <?= csrf_campo() ?>

                <div class="form-group">
                    <label for="email">E-mail</label>
                    <div class="input-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/>
                            <path d="M22 6l-10 7L2 6"/>
                        </svg>
                        <input type="email" id="email" name="email" inputmode="email"
                               value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="seu.email@empresa.com.br"
                               autocomplete="username" autofocus required <?= $bloqueio > 0 ? 'disabled' : '' ?>>
                    </div>
                </div>

                <div class="form-group">
                    <div class="label-row">
                        <label for="senha">Senha</label>
                        <span class="caps-warning" data-caps-message hidden>Caps Lock ativado</span>
                    </div>
                    <div class="input-icon input-senha">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 11H5a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2z"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <input type="password" id="senha" name="senha" placeholder="Sua senha"
                               autocomplete="current-password" required data-caps-warning
                               <?= $bloqueio > 0 ? 'disabled' : '' ?>>
                        <button type="button" class="toggle-senha" data-toggle-senha="senha"
                                aria-label="Mostrar senha" aria-pressed="false">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
                                <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="login-options">
                    <label class="check">
                        <input type="checkbox" name="lembrar" value="1" <?= $lembrar ? 'checked' : '' ?>
                               <?= $bloqueio > 0 ? 'disabled' : '' ?>>
                        <span>Lembrar meu e-mail</span>
                    </label>
                    <span class="login-help" title="Solicite a redefinição ao administrador do sistema">
                        Esqueceu a senha?
                    </span>
                </div>

                <button type="submit" class="btn-login" <?= $bloqueio > 0 ? 'disabled' : '' ?>>
                    Entrar no sistema
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14"/><path d="M13 6l6 6-6 6"/>
                    </svg>
                </button>
            </form>

            <p class="login-foot">
                Não tem acesso? Fale com o administrador responsável pelo sistema.
            </p>

            <div class="login-security">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                <span>Conexão protegida · senhas criptografadas</span>
            </div>
        </div>
    </main>

</div>

<script>
(function () {
    'use strict';

    var botao = document.querySelector('[data-toggle-senha]');
    if (botao) {
        botao.addEventListener('click', function () {
            var campo = document.getElementById(botao.getAttribute('data-toggle-senha'));
            if (!campo) return;
            var mostrar = campo.type === 'password';
            campo.type = mostrar ? 'text' : 'password';
            botao.setAttribute('aria-pressed', mostrar ? 'true' : 'false');
            botao.setAttribute('aria-label', mostrar ? 'Ocultar senha' : 'Mostrar senha');
            campo.focus();
        });
    }

    var campoCaps = document.querySelector('[data-caps-warning]');
    var avisoCaps = document.querySelector('[data-caps-message]');
    if (campoCaps && avisoCaps) {
        campoCaps.addEventListener('keyup', function (evento) {
            if (typeof evento.getModifierState !== 'function') return;
            avisoCaps.hidden = !evento.getModifierState('CapsLock');
        });
    }
})();
</script>
</body>
</html>
