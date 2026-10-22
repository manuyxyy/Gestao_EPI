<?php
require_once 'conexao.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if ($email === '' || $senha === '') {
        $erro = "Preencha todos os campos.";
    } else {
        // Buscar usuário pelo email
        $stmt = $pdo->prepare("SELECT id, nome, senha, funcao FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        // Verificar senha
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id']   = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_funcao'] = $usuario['funcao'];   

            // Redirecionar conforme função
            switch ($usuario['funcao']) {
                case 'Administrador':
                    header("Location: dashboard_admin.php");
                    break;
                case 'Gestor':
                    header("Location: dashboard_gestor.php");
                    break;
                case 'Funcionario':
                    header("Location: dashboard_funcionario.php");
                    break;
                case 'Almoxarifado':
                    header("Location: dashboard_almoxarifado.php");
                    break;
                default:
                    header("Location: dashboard.php");
            }
            exit;
        } else {
            $erro = "Usuário ou senha inválidos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <?php if (!empty($erro)): ?>
            <div class="alert-error"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" name="senha" id="senha" required>
            </div>
            <button type="submit" class="btn-login">Entrar</button>
        </form>
    </div>
</body>
</html>
