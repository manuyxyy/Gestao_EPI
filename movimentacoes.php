<?php
require_once 'conexao.php';
require_once 'funcoes.php';   // buscar_todos()
require_once 'layout.php';

/** @var PDO $pdo */

$linhas = buscar_todos($pdo, "
    SELECT
        m.data_movimentacao,
        m.tipo,
        e.nome AS epi,
        m.quantidade,
        m.observacao AS descricao
    FROM movimentacoes m
    LEFT JOIN epis e ON e.id = m.id_epi
    ORDER BY m.data_movimentacao DESC, m.id DESC
    LIMIT 200
");

cabecalho(
    'Movimentações',
    'Histórico de entradas, entregas e devoluções'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="movimentacoes.css">
</head>
<body>
        <div class="content-card">
            <h2>Histórico de movimentações</h2>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>EPI</th>
                            <th>Quantidade</th>
                            <th>Descrição</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$linhas): ?>
                            <tr>
                                <td colspan="5">Nenhuma movimentação registrada.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($linhas as $linha): ?>
                                <tr>
                                    <td><?= h(date('d/m/Y H:i', strtotime($linha['data_movimentacao']))) ?></td>
                                    <td><?= h($linha['tipo']) ?></td>
                                    <td><?= h($linha['epi'] ?? '-') ?></td>
                                    <td><?= h($linha['quantidade']) ?></td>
                                    <td><?= h($linha['descricao'] ?? '') ?></td>
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