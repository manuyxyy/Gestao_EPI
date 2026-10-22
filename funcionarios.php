<?php
require_once 'conexao.php';
require_once 'layout.php';

/** @var PDO $conn */ // Informa o editor sobre a origem da variável $conn

$mensagem = '';
$tipoMensagem = 'sucesso'; // Controla a classe CSS do alerta (sucesso ou erro)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $q = $conn->prepare("
            INSERT INTO funcionarios
            (nome, cpf, setor, cargo, status)
            VALUES (?, ?, ?, ?, 'Ativo')
        ");

        $q->execute([
            trim($_POST['nome'] ?? ''),
            trim($_POST['cpf'] ?? ''),
            trim($_POST['setor'] ?? ''),
            trim($_POST['cargo'] ?? '')
        ]);

        $mensagem = 'Funcionário cadastrado com sucesso.';
        $tipoMensagem = 'sucesso';

    } catch (Throwable $e) {
        $mensagem = 'Não foi possível cadastrar. Verifique os dados.';
        $tipoMensagem = 'erro';
    }
}


cabecalho(
    'Funcionários',
    'Cadastre e consulte os funcionários da empresa'
);
?>

<?php if ($mensagem): ?>
    <p class="alerta <?= h($tipoMensagem) ?>"><?= h($mensagem) ?></p>
<?php endif; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="funcionario.css">
</head>
<body>

        <div class="content-card">

            <h2>Cadastrar funcionário</h2>

            <form method="POST" class="form-grid">

                <div>
                    <label>Nome completo</label>
                    <input name="nome" required>
                </div>

                <div>
                    <label>CPF</label>
                    <input name="cpf">
                </div>

                <div>
                    <label>Setor</label>
                    <input name="setor">
                </div>

                <div>
                    <label>Cargo</label>
                    <input name="cargo">
                </div>

                <div class="form-action">
                    <button class="botao" type="submit">
                        + Novo funcionário
                    </button>
                </div>

            </form>
        </div>

        <div class="content-card">

            <h2>Funcionários cadastrados</h2>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Setor</th>
                            <th>Cargo</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!$linhas): ?>
                            <tr>
                                <td colspan="5">
                                    Nenhum funcionário cadastrado.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($linhas as $l): ?>
                                <tr>
                                    <td><?= h($l['nome']) ?></td>
                                    <td><?= h($l['cpf'] ?: '-') ?></td>
                                    <td><?= h($l['setor'] ?: '-') ?></td>
                                    <td><?= h($l['cargo'] ?: '-') ?></td>
                                    <td><?= h($l['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>

    </body>
</html>
<?php rodape(); ?>