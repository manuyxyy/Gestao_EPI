<?php
require_once 'conexao.php';
require_once 'layout.php';

/** @var PDO $conn */ // Informa o editor sobre a origem da variável $conn

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$mensagem = '';
$tipoMensagem = 'sucesso'; // Define a classe do alerta (sucesso ou erro)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $funcionario = (int)($_POST['funcionario_id'] ?? 0);
    $epi         = (int)($_POST['epi_id'] ?? 0);
    $quantidade  = max(1, (int)($_POST['quantidade'] ?? 1));

    try {
        $conn->beginTransaction();

        $q = $conn->prepare(
            "SELECT quantidade FROM estoque
             WHERE epi_id = ? FOR UPDATE"
        );
        $q->execute([$epi]);
        $saldo = $q->fetchColumn();

        if ($saldo === false || (int)$saldo < $quantidade) {
            throw new Exception(
                'Não há quantidade suficiente no estoque.'
            );
        }

        $ins = $conn->prepare("
            INSERT INTO entregas
            (funcionario_id, epi_id, quantidade,
             data_entrega, responsavel, observacao)
            VALUES (?, ?, ?, CURDATE(), ?, ?)
        ");

        $ins->execute([
            $funcionario,
            $epi,
            $quantidade,
            $_SESSION['usuario'] ?? 'Sistema',
            trim($_POST['observacao'] ?? '')
        ]);

        $upd = $conn->prepare(
            "UPDATE estoque
             SET quantidade = quantidade - ?
             WHERE epi_id = ?"
        );
        $upd->execute([$quantidade, $epi]);

        $mov = $conn->prepare("
            INSERT INTO movimentacoes
            (epi_id, tipo, quantidade, descricao)
            VALUES (?, 'Entrega', ?, ?)
        ");

        $mov->execute([
            $epi,
            $quantidade,
            'Entrega para funcionário ID ' . $funcionario
        ]);

        $conn->commit();
        $mensagem = 'Entrega registrada com sucesso.';
        $tipoMensagem = 'sucesso';

    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        $mensagem = $e->getMessage();
        $tipoMensagem = 'erro';
    }
}

cabecalho(
    'Entregas de EPIs',
    'Registre e acompanhe as entregas aos funcionários'
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
    <link rel="stylesheet" href="entregas.css">
</head>
<body>
        <div class="content-card">

            <h2>Registrar entrega</h2>

            <form method="POST" class="form-grid">

                <div>
                    <label>Funcionário</label>
                    <select name="funcionario_id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($funcionarios as $f): ?>
                            <option value="<?= h($f['id']) ?>">
                                <?= h($f['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label>EPI</label>
                    <select name="epi_id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($epis as $p): ?>
                            <option value="<?= h($p['id']) ?>">
                                <?= h($p['nome']) ?>
                                (estoque: <?= h($p['quantidade']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label>Quantidade</label>
                    <input type="number" name="quantidade"
                        min="1" value="1" required>
                </div>

                <div>
                    <label>Observação</label>
                    <input type="text" name="observacao"
                        placeholder="Opcional">
                </div>

                <div class="form-action">
                    <button class="botao" type="submit">
                        + Nova entrega
                    </button>
                </div>

            </form>
        </div>

        <div class="content-card">

            <h2>Entregas registradas</h2>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Funcionário</th>
                            <th>EPI</th>
                            <th>Quantidade</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!$linhas): ?>
                            <tr>
                                <td colspan="4">
                                    Nenhuma entrega cadastrada.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($linhas as $l): ?>
                                <tr>
                                    <td><?= h($l['data_entrega']) ?></td>
                                    <td><?= h($l['funcionario'] ?? '-') ?></td>
                                    <td><?= h($l['epi'] ?? '-') ?></td>
                                    <td><?= h($l['quantidade']) ?></td>
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