<?php
require_once 'conexao.php';
require_once 'funcoes.php';   // buscar_todos()
require_once 'layout.php';

/** @var PDO $pdo */

$mensagem = '';
$tipoMensagem = 'sucesso'; // Define a classe do alerta (sucesso ou erro)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entregaId  = (int)($_POST['entrega_id'] ?? 0);
    $quantidade = max(1, (int)($_POST['quantidade'] ?? 1));
    $estado     = trim($_POST['estado'] ?? 'Bom');

    try {
        $pdo->beginTransaction();

        // Busca a entrega e quanto ainda falta devolver dela
        $q = $pdo->prepare("
            SELECT
                en.id_epi,
                en.quantidade - COALESCE(
                    (SELECT SUM(d.quantidade)
                     FROM devolucoes d
                     WHERE d.id_entrega = en.id), 0
                ) AS pendente
            FROM entregas en
            WHERE en.id = ?
            FOR UPDATE
        ");
        $q->execute([$entregaId]);
        $entrega = $q->fetch();

        if (!$entrega || $quantidade > (int)$entrega['pendente']) {
            throw new Exception(
                'Confira a entrega e a quantidade informada.'
            );
        }

        $ins = $pdo->prepare("
            INSERT INTO devolucoes
            (id_entrega, data_devolucao, quantidade, estado, observacao)
            VALUES (?, CURDATE(), ?, ?, ?)
        ");

        $ins->execute([
            $entregaId,
            $quantidade,
            $estado,
            trim($_POST['observacao'] ?? '')
        ]);

        // Só volta para o estoque se o EPI estiver em bom estado
        if (strtolower($estado) === 'bom') {
            $upd = $pdo->prepare(
                "UPDATE estoque
                 SET quantidade = quantidade + ?
                 WHERE id_epi = ?"
            );
            $upd->execute([
                $quantidade,
                $entrega['id_epi']
            ]);
        }

        $mov = $pdo->prepare("
            INSERT INTO movimentacoes
            (id_epi, tipo, quantidade, observacao)
            VALUES (?, 'Devolucao', ?, ?)
        ");

        $mov->execute([
            $entrega['id_epi'],
            $quantidade,
            'Devolução da entrega ID ' . $entregaId .
            ' - estado: ' . $estado
        ]);

        $pdo->commit();
        $mensagem = 'Devolução registrada com sucesso.';
        $tipoMensagem = 'sucesso';

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $mensagem = $e->getMessage();
        $tipoMensagem = 'erro';
    }
}

// Entregas que ainda têm unidades a devolver (a quantidade mostrada é o que falta devolver)
$entregas = buscar_todos($pdo, "
    SELECT
        en.id,
        f.nome AS funcionario,
        p.nome AS epi,
        en.quantidade - COALESCE(dv.devolvido, 0) AS quantidade
    FROM entregas en
    LEFT JOIN funcionarios f ON f.id = en.id_funcionario
    LEFT JOIN epis p ON p.id = en.id_epi
    LEFT JOIN (
        SELECT id_entrega, SUM(quantidade) AS devolvido
        FROM devolucoes
        GROUP BY id_entrega
    ) dv ON dv.id_entrega = en.id
    WHERE en.quantidade - COALESCE(dv.devolvido, 0) > 0
    ORDER BY en.id DESC
");

// Histórico de devoluções
$linhas = buscar_todos($pdo, "
    SELECT
        d.data_devolucao,
        f.nome AS funcionario,
        p.nome AS epi,
        d.quantidade,
        d.estado
    FROM devolucoes d
    INNER JOIN entregas en ON en.id = d.id_entrega
    LEFT JOIN funcionarios f ON f.id = en.id_funcionario
    LEFT JOIN epis p ON p.id = en.id_epi
    ORDER BY d.data_devolucao DESC, d.id DESC
    LIMIT 50
");

cabecalho(
    'Devolução de EPIs',
    'Registre o retorno dos equipamentos entregues'
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
    <link rel="stylesheet" href="devolucao.css">
</head>
<body>

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
                                (a devolver: <?= h($e['quantidade']) ?>)
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
                                    <td><?= h(date('d/m/Y', strtotime($l['data_devolucao']))) ?></td>
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

</body>
</html>
<?php rodape(); ?>
