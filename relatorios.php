<?php
require_once 'conexao.php';
require_once 'funcoes.php';   // buscar_todos()
require_once 'layout.php';

/** @var PDO $pdo */

// Indicadores
$totalEntregas = (int)$pdo->query(
    "SELECT COALESCE(SUM(quantidade), 0) FROM entregas"
)->fetchColumn();

$totalDevolucoes = (int)$pdo->query(
    "SELECT COALESCE(SUM(quantidade), 0) FROM devolucoes"
)->fetchColumn();

$totalEstoque = (int)$pdo->query(
    "SELECT COALESCE(SUM(quantidade), 0) FROM estoque"
)->fetchColumn();

$totalVencidos = (int)$pdo->query(
    "SELECT COUNT(*) FROM epis
     WHERE validade IS NOT NULL AND validade < CURDATE()"
)->fetchColumn();

// Entregas agrupadas por tipo de EPI
$porEpi = buscar_todos($pdo, "
    SELECT
        p.nome,
        SUM(en.quantidade) AS total
    FROM entregas en
    INNER JOIN epis p ON p.id = en.id_epi
    GROUP BY p.id, p.nome
    ORDER BY total DESC, p.nome
");

cabecalho(
    'Relatórios e indicadores',
    'Visão geral das entregas, devoluções e estoque'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="relatorios.css">
</head>
<body>

        <div class="stats-grid">

            <article class="stat-card orange">
                <span>Unidades entregues</span>
                <strong><?= h($totalEntregas) ?></strong>
            </article>

            <article class="stat-card yellow">
                <span>Unidades devolvidas</span>
                <strong><?= h($totalDevolucoes) ?></strong>
            </article>

            <article class="stat-card pale">
                <span>Unidades em estoque</span>
                <strong><?= h($totalEstoque) ?></strong>
            </article>

            <article class="stat-card warning">
                <span>EPIs com validade vencida</span>
                <strong><?= h($totalVencidos) ?></strong>
            </article>

        </div>

        <div class="content-card">

            <div class="card-heading">
                <h2>Entregas por tipo de EPI</h2>

                <button class="botao" onclick="window.print()">
                    Imprimir relatório
                </button>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>EPI</th>
                            <th>Total de unidades entregues</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!$porEpi): ?>
                            <tr>
                                <td colspan="2">Sem dados para exibir.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($porEpi as $l): ?>
                                <tr>
                                    <td><?= h($l['nome']) ?></td>
                                    <td><?= h($l['total']) ?></td>
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