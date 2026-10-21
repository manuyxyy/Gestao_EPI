<?php
require_once 'conexao.php';
require_once 'layout.php';

// Tenta incluir auth.php se existir
if (file_exists('auth.php')) {
    require_once 'auth.php';
}

// Declara a função exigir_perfil caso ainda não esteja definida em nenhum ficheiro
if (!function_exists('exigir_perfil')) {
    function exigir_perfil(array $perfisPermitidos): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $perfilAtual = $_SESSION['perfil'] ?? $_SESSION['usuario']['perfil'] ?? null;

        if (!$perfilAtual || !in_array($perfilAtual, $perfisPermitidos, true)) {
            header('Location: login.php?erro=acesso_negado');
            exit;
        }
    }
}

/** @var PDO $conn */
exigir_perfil(['administrador']);

$mensagem = '';
$tipoMensagem = 'sucesso'; // Controla a classe CSS do alerta (sucesso ou erro)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nome   = trim($_POST['nome'] ?? '');
        $login  = trim($_POST['login'] ?? '');
        $senha  = $_POST['senha'] ?? '';
        $perfil = $_POST['perfil'] ?? 'funcionario';
        $setor  = trim($_POST['setor'] ?? '');

        if ($nome === '' || $login === '' || $senha === '') {
            throw new Exception('Preencha nome, login e senha.');
        }

        $q = $conn->prepare("
            INSERT INTO usuarios
            (nome, login, senha, perfil, setor, status)
            VALUES (?, ?, ?, ?, ?, 'Ativo')
        ");

        $q->execute([
            $nome,
            $login,
            password_hash($senha, PASSWORD_DEFAULT),
            $perfil,
            $setor
        ]);

        $mensagem = 'Usuário cadastrado com sucesso.';
        $tipoMensagem = 'sucesso';

    } catch (Throwable $e) {
        $mensagem = 'Não foi possível cadastrar. Verifique se o login já existe e preencha os campos.';
        $tipoMensagem = 'erro';
    }
}

$linhas = buscar_todos(
    $conn,
    "SELECT id, nome, login, setor, perfil, status
     FROM usuarios
     ORDER BY nome"
);

cabecalho(
    'Usuários e permissões',
    'Gerencie os acessos ao sistema'
);
?>

<?php if ($mensagem): ?>
    <p class="alerta <?= h($tipoMensagem) ?>"><?= h($mensagem) ?></p>
<?php endif; ?>

<div class="content-card">

    <h2>Novo usuário</h2>

    <form method="POST" class="form-grid">

        <div>
            <label>Nome</label>
            <input name="nome" required>
        </div>

        <div>
            <label>Login</label>
            <input name="login" required>
        </div>

        <div>
            <label>Senha</label>
            <input type="password" name="senha" required>
        </div>

        <div>
            <label>Setor</label>
            <input name="setor">
        </div>

        <div>
            <label>Perfil</label>
            <select name="perfil">
                <option value="gestor">Gestor</option>
                <option value="funcionario">Funcionário</option>
                <option value="almoxarifado">Almoxarifado</option>
                <option value="administrador">Administrador</option>
            </select>
        </div>

        <div class="form-action">
            <button class="botao" type="submit">
                + Novo usuário
            </button>
        </div>

    </form>
</div>

<div class="content-card">

    <h2>Usuários cadastrados</h2>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Login</th>
                    <th>Setor</th>
                    <th>Perfil</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!$linhas): ?>
                    <tr>
                        <td colspan="5">
                            Nenhum usuário cadastrado.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($linhas as $l): ?>
                        <tr>
                            <td><?= h($l['nome']) ?></td>
                            <td><?= h($l['login']) ?></td>
                            <td><?= h($l['setor'] ?: '-') ?></td>
                            <td><?= h(ucfirst($l['perfil'])) ?></td>
                            <td>
                                <span class="status <?= strtolower($l['status']) === 'ativo' ? 'status-ok' : 'status-alerta' ?>">
                                    <?= h($l['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php rodape(); ?>