<?php
/**
 * Cadastro de novos usuários do EpiSafe.
 *
 * A conta é gravada na tabela `usuarios` com o perfil escolhido. Depois do
 * cadastro o usuário é levado para a tela de login.
 */
require_once __DIR__ . '/auth.php';

if (usuario_logado()) {
    redirecionar(pagina_inicial());
}

$erro = '';

$nome       = trim((string)($_POST['nome'] ?? ''));
$email      = mb_strtolower(trim((string)($_POST['email'] ?? '')));
$tipoAcesso = (string)($_POST['tipo_acesso'] ?? 'Almoxarifado');
$setor      = trim((string)($_POST['setor'] ?? ''));
$telefone   = trim((string)($_POST['telefone'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha     = (string)($_POST['senha'] ?? '');
    $confirmar = (string)($_POST['senha_confirmar'] ?? '');

    if (!csrf_validar()) {
        $erro = 'Sessão expirada. Atualize a página e tente novamente.';
    } elseif (mb_strlen($nome) < 3 || mb_strlen($nome) > 100) {
        $erro = 'Informe o nome completo (entre 3 e 100 caracteres).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
        $erro = 'Informe um e-mail válido.';
    } elseif (!in_array($tipoAcesso, perfis_validos(), true)) {
        $erro = 'Selecione um perfil de acesso válido.';
    } elseif (mb_strlen($setor) > 100) {
        $erro = 'O setor deve ter no máximo 100 caracteres.';
    } elseif (mb_strlen($telefone) > 20) {
        $erro = 'O telefone deve ter no máximo 20 caracteres.';
    } elseif (mb_strlen($senha) < 8) {
        $erro = 'A senha deve ter pelo menos 8 caracteres.';
    } elseif ($senha !== $confirmar) {
        $erro = 'A confirmação da senha não corresponde à senha informada.';
    } else {
        global $pdo;

        try {
            $existe = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
            $existe->execute([$email]);

            if ((int)$existe->fetchColumn() > 0) {
                $erro = 'Já existe uma conta cadastrada com esse e-mail.';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO usuarios
                        (nome, email, senha, tipo_acesso, funcao, setor, telefone, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Ativo')
                ");
                $stmt->execute([
                    $nome,
                    $email,
                    password_hash($senha, PASSWORD_DEFAULT),
                    $tipoAcesso,
                    $tipoAcesso,
                    $setor !== '' ? $setor : null,
                    $telefone !== '' ? $telefone : null,
                ]);

                $_SESSION['cadastro_sucesso'] = 'Cadastro realizado com sucesso! Agora entre com o seu e-mail e a senha.';
                $_SESSION['cadastro_email']   = $email;

                redirecionar('index.php?aviso=cadastro');
            }
        } catch (PDOException $e) {
            error_log('Falha no cadastro de usuário: ' . $e->getMessage());

            $erro = ((string)$e->getCode() === '23000')
                ? 'Já existe uma conta cadastrada com esse e-mail.'
                : 'Não foi possível concluir o cadastro. Verifique os dados e tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1d2127">
    <meta name="description" content="Crie sua conta no EpiSafe — sistema de gestão de EPIs.">
    <title>Criar conta | EpiSafe</title>
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
            <p class="home-eyebrow">Primeiro acesso</p>
            <h1 id="marca-titulo">Crie sua conta e acesse só o que precisa.</h1>
            <p class="home-lead">
                Informe seus dados e escolha o perfil. Cada perfil abre diretamente
                as páginas da sua rotina, sem telas desnecessárias.
            </p>

            <ul class="home-features">
                <li>
                    <span class="home-feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                             stroke-linecap="round" stroke-linejoin="round"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                    </span>
                    <span><strong>Cadastro salvo no banco</strong><small>Seus dados ficam registrados para os próximos acessos.</small></span>
                </li>
                <li>
                    <span class="home-feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                             stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z"/><path d="M9 12l2 2 4-4"/></svg>
                    </span>
                    <span><strong>Perfis de acesso</strong><small>Administrador, gestor e almoxarifado.</small></span>
                </li>
                <li>
                    <span class="home-feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                             stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                    </span>
                    <span><strong>Login direto ao trabalho</strong><small>Sem menu de acesso rápido: você cai na sua página principal.</small></span>
                </li>
            </ul>
        </div>

        <footer class="home-brand-foot">
            <span>Ambiente interno · Gestão de EPIs</span>
            <span>&copy; <?= date('Y') ?> EpiSafe</span>
        </footer>
    </section>

    <!-- ------------------------------------------------ Cadastro (direita) -->
    <main class="home-login">
        <div class="login-card cadastro-card">
            <header class="login-head">
                <span class="login-head-badge" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>
                        <path d="M19 8v6"/>
                        <path d="M22 11h-6"/>
                    </svg>
                </span>
                <h2>Criar conta</h2>
                <p>Preencha os dados abaixo. O perfil escolhido define as páginas que você poderá acessar.</p>
            </header>

            <?php if ($erro !== ''): ?>
                <div class="login-alert error" role="alert">
                    <span><?= h($erro) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="cadastro.php" class="login-form cadastro-form" autocomplete="off">
                <?= csrf_campo() ?>

                <div class="form-group">
                    <label for="nome">Nome completo <span class="obrigatorio">*</span></label>
                    <div class="input-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>
                            <path d="M4 21a8 8 0 0 1 16 0"/>
                        </svg>
                        <input type="text" id="nome" name="nome" required minlength="3" maxlength="100"
                               value="<?= h($nome) ?>" placeholder="Ex.: Maria Silva Santos"
                               autocomplete="name" autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">E-mail <span class="obrigatorio">*</span></label>
                    <div class="input-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/>
                            <path d="M22 6l-10 7L2 6"/>
                        </svg>
                        <input type="email" id="email" name="email" required maxlength="100"
                               value="<?= h($email) ?>" placeholder="seu.email@empresa.com.br"
                               autocomplete="email">
                    </div>
                    <p class="field-help">Esse será o e-mail usado para entrar no sistema.</p>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="tipo_acesso">Perfil de acesso <span class="obrigatorio">*</span></label>
                        <select id="tipo_acesso" name="tipo_acesso" required>
                            <?php foreach (perfis_validos() as $perfil): ?>
                                <option value="<?= h($perfil) ?>" <?= $tipoAcesso === $perfil ? 'selected' : '' ?>>
                                    <?= h(rotulo_perfil($perfil)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="setor">Setor</label>
                        <input type="text" id="setor" name="setor" maxlength="100"
                               value="<?= h($setor) ?>" placeholder="Ex.: Produção"
                               autocomplete="organization-title">
                    </div>
                </div>

                <div class="form-group">
                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone" maxlength="20"
                           value="<?= h($telefone) ?>" placeholder="(00) 00000-0000"
                           autocomplete="tel">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="senha">Senha <span class="obrigatorio">*</span></label>
                        <div class="input-icon input-senha">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M19 11H5a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2z"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            <input type="password" id="senha" name="senha" required minlength="8"
                                   placeholder="Mínimo 8 caracteres" autocomplete="new-password">
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

                    <div class="form-group">
                        <label for="senha_confirmar">Confirmar senha <span class="obrigatorio">*</span></label>
                        <div class="input-icon input-senha">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M19 11H5a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2z"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            <input type="password" id="senha_confirmar" name="senha_confirmar" required minlength="8"
                                   placeholder="Repita a senha" autocomplete="new-password">
                            <button type="button" class="toggle-senha" data-toggle-senha="senha_confirmar"
                                    aria-label="Mostrar senha" aria-pressed="false">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
                                    <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <p class="field-help senha-ajuda">Use pelo menos 8 caracteres. A senha é gravada criptografada no banco.</p>

                <button type="submit" class="btn-login">
                    Criar minha conta
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14"/><path d="M13 6l6 6-6 6"/>
                    </svg>
                </button>
            </form>

            <p class="login-foot">
                Já tem uma conta?
                <a class="login-link" href="index.php">Entrar no sistema</a>
            </p>

            <div class="login-security">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                <span>Conexão protegida · dados salvos no banco</span>
            </div>
        </div>
    </main>

</div>

<script>
(function () {
    'use strict';

    document.querySelectorAll('[data-toggle-senha]').forEach(function (botao) {
        botao.addEventListener('click', function () {
            var campo = document.getElementById(botao.getAttribute('data-toggle-senha'));
            if (!campo) return;
            var mostrar = campo.type === 'password';
            campo.type = mostrar ? 'text' : 'password';
            botao.setAttribute('aria-pressed', mostrar ? 'true' : 'false');
            botao.setAttribute('aria-label', mostrar ? 'Ocultar senha' : 'Mostrar senha');
            campo.focus();
        });
    });

    var senha = document.getElementById('senha');
    var confirmar = document.getElementById('senha_confirmar');

    function conferirSenhas() {
        if (!senha || !confirmar) return;
        confirmar.setCustomValidity(
            confirmar.value !== '' && confirmar.value !== senha.value
                ? 'As senhas não coincidem.'
                : ''
        );
    }

    if (senha && confirmar) {
        senha.addEventListener('input', conferirSenhas);
        confirmar.addEventListener('input', conferirSenhas);
    }
})();
</script>
</body>
</html>
