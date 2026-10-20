<?php
session_start();
require_once 'conexao.php';

$mensagem_erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if (!empty($email) && !empty($senha)) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND ativo = 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        // Para ambiente de teste/produção: password_verify($senha, $usuario['senha'])
        if ($usuario && $senha === $usuario['senha']) {
            $_SESSION['usuario_id'] = $usuario['id_usuario'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_perfil'] = $usuario['perfil'];

            header('Location: index.php');
            exit;
        } else {
            $mensagem_erro = "E-mail ou palavra-passe incorretos!";
        }
    } else {
        $mensagem_erro = "Por favor, preencha todos os campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gestão de EPIs</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>

    <div class="login-card">
        <div class="login-header">
            <h2>Sistema EPI</h2>
            <p>Aceda ao controlo de Equipamentos de Proteção Individual</p>
        </div>

        <?php if (!empty($mensagem_erro)): ?>
            <div class="alert-error">
                <?= htmlspecialchars($mensagem_erro); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="login-form">
            <div class="form-group">
                <label for="email">E-mail Corporativo</label>
                <input type="email" id="email" name="email" placeholder="seu.email@empresa.com" required>
            </div>

            <div class="form-group">
                <label for="senha">Palavra-passe</label>
                <input type="password" id="senha" name="senha" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-submit">Entrar no Sistema</button>
        </form>

        <div class="login-footer">
            <p>Dúvidas ou suporte? Entre em contacto com a TI.</p>
        </div>
    </div>

</body>
</html>