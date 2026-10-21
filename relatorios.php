<?php
require_once 'conexao.php';
require_once 'layout.php';

/** @var PDO $conn */ // Informa o editor sobre a origem da variável $conn

$entregas = (int)$conn->query(
    "SELECT COALESCE(SUM(quantidade), 0) FROM entregas"
)->fetchColumn();

$devolucoes = (int)$conn->query(
    "SELECT COALESCE(SUM(quantidade), 0) FROM devolucoes"
)->fetchColumn();

$estoque = (int)$conn->query(
    "SELECT COALESCE(SUM(quantidade), 0) FROM estoque"
)->fetchColumn();

$validade = (int)$conn->query(
    "SELECT COUNT(*) FROM epis
     WHERE validade_ca IS NOT NULL AND validade_ca < CURDATE()"
)->fetchColumn();

$porEpi = buscar_todos(
    $conn,
    "SELECT p.nome,
            COALESCE(SUM(e.quantidade), 0) AS total
     FROM epis p
     LEFT JOIN entregas e ON e.epi_id = p.id
     GROUP BY p.id, p.nome
     ORDER BY total DESC"
);

cabecalho(
    'Relatórios e indicadores',
    'Visão geral das entregas, devoluções e estoque'
);
?>

<div class="stats-grid">

    <article class="stat-card orange">
        <span>Unidades entregues</span>
        <strong><?= h($entregas) ?></strong>
    </article>

    <article class="stat-card yellow">
        <span>Unidades devolvidas</span>
        <strong><?= h($devolucoes) ?></strong>
    </article>

    <article class="stat-card pale">
        <span>Unidades em estoque</span>
        <strong><?= h($estoque) ?></strong>
    </article>

    <article class="stat-card warning">
        <span>EPIs com validade vencida</span>
        <strong><?= h($validade) ?></strong>
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

<?php rodape(); ?>