<?php
/**
 * Cadastro de novos usuários do EpiSafe.
 *
 * O perfil NUNCA é aceito pelo formulário: ele é fixado pelo link de cadastro
 * (?perfil=almoxarifado|gestor|administrador). TODOS os perfis exigem o código
 * de autorização emitido somente pelo administrador — o gestor não autoriza.
 * Após o cadastro, a pessoa entra direto na área do seu perfil.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/acessibilidade.php';

if (usuario_logado()) {
    redirecionar(pagina_inicial());
}

$perfilDestino = perfil_de_cadastro($_GET['perfil'] ?? '');
$rotuloSetor   = rotulo_perfil($perfilDestino);
$erro          = '';

$nome     = trim((string)($_POST['nome'] ?? ''));
$email    = mb_strtolower(trim((string)($_POST['email'] ?? '')));
$telefone = trim((string)($_POST['telefone'] ?? ''));
$chave    = trim((string)($_POST['chave_convite'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha     = (string)($_POST['senha'] ?? '');
    $confirmar = (string)($_POST['senha_confirmar'] ?? '');

    if (!csrf_validar()) {
        $erro = 'Sessão expirada. Atualize a página e tente novamente.';
    } elseif (mb_strlen($nome) < 3 || mb_strlen($nome) > 100) {
        $erro = 'Informe o nome completo (entre 3 e 100 caracteres).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
        $erro = 'Informe um e-mail válido.';
    } elseif (cadastro_exige_convite($perfilDestino)
              && perfil_por_convite($chave) !== $perfilDestino) {
        $erro = 'Autorização inválida para o perfil ' . $rotuloSetor
              . '. Solicite o código ao administrador do sistema.';
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
                    $perfilDestino,
                    $perfilDestino,
                    $rotuloSetor,
                    $telefone !== '' ? $telefone : null,
                ]);

                $_SESSION['cadastro_sucesso'] = 'Cadastro realizado com sucesso!';
                $_SESSION['cadastro_email']   = $email;

                // O cadastro já direciona para a área do perfil escolhido.
                $entrou = autenticar($email, $senha);

                if ($entrou[0]) {
                    unset($_SESSION['cadastro_sucesso'], $_SESSION['cadastro_email']);
                    flash('sucesso', 'Conta criada! Você foi direcionado à área de ' . $rotuloSetor . '.');
                    redirecionar(pagina_inicial());
                }

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

$opcoesCadastro = [
    'almoxarifado'   => ['Almoxarifado', 'Estoque, entregas e movimentações', 'Autorização do adm'],
    'gestor'         => ['Gestor', 'Relatórios, validade e indicadores', 'Autorização do adm'],
    'administrador'  => ['Administrador', 'Acesso completo ao sistema', 'Autorização do adm'],
];
$perfilAtivo = mb_strtolower($perfilDestino);
?>
<!DOCTYPE html>
<html lang="pt-BR" class="acess-alto-contraste acess-sem-movimento">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#05070a">
    <link rel="icon" type="image/svg+xml" href="assets/logo-episafe.svg">
    <link rel="apple-touch-icon" href="assets/logo-episafe.svg">
    <meta name="description" content="Crie sua conta no EpiSafe — sistema de gestão de EPIs.">
    <title>Criar conta | EpiSafe</title>
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
<a class="skip-link" href="#form-cadastro">Pular para o formulário de cadastro</a>

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
            <h1>Criar conta</h1>
            <p>Escolha sua área. Todos os cadastros precisam de autorização do administrador.</p>
        </header>

        <div class="cadastro-perfis" role="group" aria-label="Área do cadastro">
            <?php foreach ($opcoesCadastro as $chaveUrl => $info): ?>
                <?php $ativo = ($perfilAtivo === $chaveUrl); ?>
                <a class="cadastro-perfil<?= $ativo ? ' ativo' : '' ?>"
                   href="cadastro.php?perfil=<?= h($chaveUrl) ?>"
                   <?= $ativo ? 'aria-current="page"' : '' ?>>
                    <strong><?= h($info[0]) ?></strong>
                    <small><?= h($info[1]) ?></small>
                    <span><?= h($info[2]) ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($erro !== ''): ?>
            <div class="login-alert error" role="alert">
                <span><?= h($erro) ?></span>
            </div>
        <?php endif; ?>

        <p class="field-help cadastro-setor-fixo">
            Setor definido: <strong><?= h($rotuloSetor) ?></strong>. Ele não pode ser alterado neste formulário.
        </p>

        <form method="POST" action="cadastro.php?perfil=<?= h($perfilAtivo) ?>"
              class="login-form cadastro-form" id="form-cadastro" tabindex="-1" autocomplete="off">
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

            <div class="form-group">
                <label for="telefone">Telefone</label>
                <input type="text" id="telefone" name="telefone" maxlength="20"
                       value="<?= h($telefone) ?>" placeholder="(00) 00000-0000"
                       autocomplete="tel">
            </div>

            <div class="form-group">
                <label for="chave_convite">Código de autorização <span class="obrigatorio">*</span></label>
                <input type="text" id="chave_convite" name="chave_convite" required
                       value="<?= h($chave) ?>" placeholder="Informe o código recebido"
                       autocomplete="off">
                <p class="field-help">
                    Somente o administrador libera este código. O gestor não autoriza cadastros.
                </p>
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
            <span>Conexão protegida · cadastro autorizado pelo administrador</span>
        </div>
    </div>
</main>

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
