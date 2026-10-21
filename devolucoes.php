<?php
require_once 'conexao.php';
require_once 'layout.php';

/** @var PDO $conn */ // Informa o editor sobre a origem da variável $conn

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entregaId  = (int)($_POST['entrega_id'] ?? 0);
    $quantidade = max(1, (int)($_POST['quantidade'] ?? 1));
    $estado     = trim($_POST['estado'] ?? 'Bom');

    try {
        $q = $conn->prepare(
            "SELECT funcionario_id, epi_id, quantidade
             FROM entregas WHERE id = ?"
        );
        $q->execute([$entregaId]);
        $entrega = $q->fetch();

        if (!$entrega || $quantidade > (int)$entrega['quantidade']) {
            throw new Exception(
                'Confira a entrega e a quantidade informada.'
            );
        }

        $conn->beginTransaction();

        $ins = $conn->prepare("
            INSERT INTO devolucoes
            (entrega_id, funcionario_id, epi_id, quantidade,
             data_devolucao, estado, observacao)
            VALUES (?, ?, ?, ?, CURDATE(), ?, ?)
        ");

        $ins->execute([
            $entregaId,
            $entrega['funcionario_id'],
            $entrega['epi_id'],
            $quantidade,
            $estado,
            trim($_POST['observacao'] ?? '')
        ]);

        if (strtolower($estado) === 'bom') {
            $upd = $conn->prepare(
                "UPDATE estoque
                 SET quantidade = quantidade + ?
                 WHERE epi_id = ?"
            );
            $upd->execute([
                $quantidade,
                $entrega['epi_id']
            ]);
        }

        $mov = $conn->prepare("
            INSERT INTO movimentacoes
            (epi_id, tipo, quantidade, descricao)
            VALUES (?, 'Devolução', ?, ?)
        ");

        $mov->execute([
            $entrega['epi_id'],
            $quantidade,
            'Devolução da entrega ID ' . $entregaId .
            ' - estado: ' . $estado
        ]);

        $conn->commit();
        $mensagem = 'Devolução registrada.';

    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        $mensagem = $e->getMessage();
    }
}

$entregas = buscar_todos(
    $conn,
    "SELECT e.id, f.nome AS funcionario,
            p.nome AS epi, e.quantidade, e.data_entrega
     FROM entregas e
     LEFT JOIN funcionarios f ON f.id = e.funcionario_id
     LEFT JOIN epis p ON p.id = e.epi_id
     ORDER BY e.id DESC"
);

$linhas = buscar_todos(
    $conn,
    "SELECT d.data_devolucao, f.nome AS funcionario,
            p.nome AS epi, d.quantidade, d.estado
     FROM devolucoes d
     LEFT JOIN funcionarios f ON f.id = d.funcionario_id
     LEFT JOIN epis p ON p.id = d.epi_id
     ORDER BY d.id DESC"
);

cabecalho(
    'Devolução de EPIs',
    'Registre o retorno dos equipamentos entregues'
);
?>

<?php if ($mensagem): ?>
    <p class="alerta sucesso"><?= h($mensagem) ?></p>
<?php endif; ?>

<div class="content-card">

    <h2>Registrar devolução</h2>

    <form method="POST" class="form-grid">

        <div>
            <label>Entrega original</label>
            <select name="entrega_id" required>
                <option value="">Selecione</option>
                <?php foreach ($entregas as $e): ?>
                    <option value="<?= h($e['id']) ?>">
                        #<?= h($e['id']) ?> -
                        <?= h($e['funcionario']) ?> /
                        <?= h($e['epi']) ?>
                        (<?= h($e['quantidade']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label>Quantidade devolvida</label>
            <input type="number" name="quantidade"
                   min="1" value="1" required>
        </div>

        <div>
            <label>Estado do EPI</label>
            <select name="estado">
                <option>Bom</option>
                <option>Danificado</option>
                <option>Descartado</option>
            </select>
        </div>

        <div>
            <label>Observação</label>
            <input name="observacao" placeholder="Opcional">
        </div>

        <div class="form-action">
            <button class="botao" type="submit">
                Registrar devolução
            </button>
        </div>

    </form>
</div>

<div class="content-card">

    <h2>Devoluções registradas</h2>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Funcionário</th>
                    <th>EPI</th>
                    <th>Quantidade</th>
                    <th>Estado</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!$linhas): ?>
                    <tr>
                        <td colspan="5">
                            Nenhuma devolução cadastrada.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($linhas as $l): ?>
                        <tr>
                            <td><?= h($l['data_devolucao']) ?></td>
                            <td><?= h($l['funcionario'] ?? '-') ?></td>
                            <td><?= h($l['epi'] ?? '-') ?></td>
                            <td><?= h($l['quantidade']) ?></td>
                            <td><?= h($l['estado']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php rodape(); ?>