<?php
require_once 'conexao.php';
require_once __DIR__ . '/layout.php';
require_once 'funcoes.php';

/** @var PDO $pdo */

$totalEpis = (int)$pdo->query("SELECT COUNT(*) FROM epis")->fetchColumn();
$totalFuncionarios = (int)$pdo->query("SELECT COUNT(*) FROM funcionarios")->fetchColumn();
$totalEntregas = (int)$pdo->query("SELECT COUNT(*) FROM entregas")->fetchColumn();
$baixoEstoque = (int)$pdo->query("SELECT COUNT(*) FROM estoque WHERE quantidade <= quantidade_minima")->fetchColumn();

$ultimas = buscar_todos($pdo, "
    SELECT
        e.data_entrega,
        f.nome AS funcionario,
        p.nome AS epi,
        e.quantidade
    FROM entregas e
    LEFT JOIN funcionarios f ON f.id = e.id_funcionario
    LEFT JOIN epis p ON p.id = e.id_epi
    ORDER BY e.data_entrega DESC
    LIMIT 5
");

cabecalho('Dashboard', 'Visão geral do sistema de EPIs');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="app.css">
</head>
<body>
        <div class="stats-grid">
            <article class="stat-card orange">
                <span>EPIs cadastrados</span>
                <strong><?= $totalEpis ?></strong>
                <small>Equipamentos no sistema</small>
            </article>

            <article class="stat-card blue">
                <span>Funcionários</span>
                <strong><?= $totalFuncionarios ?></strong>
                <small>Funcionários ativos</small>
            </article>

            <article class="stat-card green">
                <span>Entregas realizadas</span>
                <strong><?= $totalEntregas ?></strong>
                <small>Total de saídas</small>
            </article>

            <article class="stat-card red">
                <span>Baixo estoque</span>
                <strong><?= $baixoEstoque ?></strong>
                <small>Abaixo do limite mínimo</small>
            </article>
        </div>

        <div class="content-card">
            <h2>Últimas entregas</h2>
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
                        <?php if (!$ultimas): ?>
                            <tr>
                                <td colspan="4">Nenhuma entrega registrada.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ultimas as $entrega): ?>
                                <tr>
                                    <td><?= htmlspecialchars($entrega['data_entrega']) ?></td>
                                    <td><?= htmlspecialchars($entrega['funcionario'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($entrega['epi'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($entrega['quantidade']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </body>
</html>