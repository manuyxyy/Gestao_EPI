<?php
require_once 'conexao.php';
require_once 'funcoes.php';
require_once __DIR__ . '/layout.php';

/** @var PDO $pdo */

exigir_login();

/* ---------------------------------------------------------------- Indicadores */
$totalEntregas = (int)$pdo->query(
    "SELECT COALESCE(SUM(quantidade), 0) FROM entregas"
)->fetchColumn();

$registrosEntrega = (int)$pdo->query("SELECT COUNT(*) FROM entregas")->fetchColumn();

$totalDevolucoes = (int)$pdo->query(
    "SELECT COALESCE(SUM(quantidade), 0) FROM devolucoes"
)->fetchColumn();

$totalEstoque = (int)$pdo->query(
    "SELECT COALESCE(SUM(quantidade), 0) FROM estoque"
)->fetchColumn();

$vencidos = (int)$pdo->query("
    SELECT COUNT(*) FROM epis
     WHERE validade IS NOT NULL AND validade < CURDATE()
")->fetchColumn();

$estoqueBaixo = (int)$pdo->query("
    SELECT COUNT(*) FROM estoque WHERE quantidade <= quantidade_minima
")->fetchColumn();

/* ---------------------------------------------------------------- Relatórios */
$porEpi = buscar_todos($pdo, "
    SELECT p.nome, COUNT(en.id) AS registros, COALESCE(SUM(en.quantidade), 0) AS total
    FROM entregas en
    INNER JOIN epis p ON p.id = en.id_epi
    GROUP BY p.id, p.nome
    ORDER BY total DESC, p.nome
");

$porFuncionario = buscar_todos($pdo, "
    SELECT f.nome, f.setor, COUNT(en.id) AS registros, COALESCE(SUM(en.quantidade), 0) AS total
    FROM entregas en
    INNER JOIN funcionarios f ON f.id = en.id_funcionario
    GROUP BY f.id, f.nome, f.setor
    ORDER BY total DESC, f.nome
    LIMIT 10
");

$porSetor = buscar_todos($pdo, "
    SELECT COALESCE(NULLIF(f.setor, ''), 'Sem setor') AS setor,
           COUNT(en.id) AS registros,
           COALESCE(SUM(en.quantidade), 0) AS total
    FROM entregas en
    INNER JOIN funcionarios f ON f.id = en.id_funcionario
    GROUP BY COALESCE(NULLIF(f.setor, ''), 'Sem setor')
    ORDER BY total DESC
");

$porEstado = buscar_todos($pdo, "
    SELECT COALESCE(estado, 'Não informado') AS estado,
           COUNT(*) AS registros,
           COALESCE(SUM(quantidade), 0) AS total
    FROM devolucoes
    GROUP BY COALESCE(estado, 'Não informado')
    ORDER BY total DESC
");

$estoqueCritico = buscar_todos($pdo, "
    SELECT e.nome, e.numero_ca AS ca, s.quantidade, s.quantidade_minima
    FROM estoque s
    INNER JOIN epis e ON e.id = s.id_epi
    WHERE s.quantidade <= s.quantidade_minima
    ORDER BY s.quantidade ASC, e.nome
");

cabecalho(
    'Relatórios e indicadores',
    'Consolidado de entregas, devoluções, estoque e validade',
    '<button class="botao" type="button" onclick="window.print()">' . icone('imprimir', 17) . ' Imprimir relatório</button>'
);
?>

<div class="stats-grid">
    <article class="stat-card orange">
        <div class="stat-top">
            <span class="stat-label">Unidades entregues</span>
            <span class="stat-icon"><?= icone('entregas', 19) ?></span>
        </div>
        <strong><?= $totalEntregas ?></strong>
        <small><?= $registrosEntrega ?> registro(s) de entrega</small>
    </article>

    <article class="stat-card yellow">
        <div class="stat-top">
            <span class="stat-label">Unidades devolvidas</span>
            <span class="stat-icon"><?= icone('devolucoes', 19) ?></span>
        </div>
        <strong><?= $totalDevolucoes ?></strong>
        <small>Total de retornos registrados</small>
    </article>

    <article class="stat-card blue">
        <div class="stat-top">
            <span class="stat-label">Unidades em estoque</span>
            <span class="stat-icon"><?= icone('estoque', 19) ?></span>
        </div>
        <strong><?= $totalEstoque ?></strong>
        <small><?= $estoqueBaixo ?> item(ns) em nível crítico</small>
    </article>

    <article class="stat-card <?= $vencidos > 0 ? 'red' : 'pale' ?>">
        <div class="stat-top">
            <span class="stat-label">CAs vencidos</span>
            <span class="stat-icon"><?= icone('alerta', 19) ?></span>
        </div>
        <strong><?= $vencidos ?></strong>
        <small>EPIs fora da validade</small>
    </article>
</div>

<div class="dashboard-grid">
    <!-- Entregas por EPI -->
    <div class="content-card">
        <div class="card-heading">
            <div>
                <h2>Entregas por EPI</h2>
                <p class="card-sub">Volume de saída por equipamento.</p>
            </div>
        </div>

        <?php if (!$porEpi): ?>
            <div class="empty-state compact">
                <strong>Sem dados de entrega</strong>
                <p>Registre entregas para gerar este relatório.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>EPI</th>
                            <th class="num">Registros</th>
                            <th class="num">Unidades</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($porEpi as $l): ?>
                            <tr>
                                <td><?= h($l['nome']) ?></td>
                                <td class="num"><?= (int)$l['registros'] ?></td>
                                <td class="num"><strong><?= (int)$l['total'] ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>Total</td>
                            <td class="num"><?= $registrosEntrega ?></td>
                            <td class="num"><?= $totalEntregas ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Entregas por setor -->
    <div class="content-card">
        <div class="card-heading">
            <div>
                <h2>Entregas por setor</h2>
                <p class="card-sub">Distribuição por área da empresa.</p>
            </div>
        </div>

        <?php if (!$porSetor): ?>
            <div class="empty-state compact">
                <strong>Sem dados por setor</strong>
                <p>Informe o setor dos funcionários para visualizar.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Setor</th>
                            <th class="num">Registros</th>
                            <th class="num">Unidades</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($porSetor as $l): ?>
                            <tr>
                                <td><?= h($l['setor']) ?></td>
                                <td class="num"><?= (int)$l['registros'] ?></td>
                                <td class="num"><strong><?= (int)$l['total'] ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="dashboard-grid">
    <!-- Top funcionários -->
    <div class="content-card">
        <div class="card-heading">
            <div>
                <h2>Top 10 funcionários</h2>
                <p class="card-sub">Quem mais recebeu EPIs.</p>
            </div>
        </div>

        <?php if (!$porFuncionario): ?>
            <div class="empty-state compact">
                <strong>Sem dados de funcionários</strong>
                <p>Registre entregas para gerar o ranking.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Funcionário</th>
                            <th>Setor</th>
                            <th class="num">Entregas</th>
                            <th class="num">Unidades</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($porFuncionario as $l): ?>
                            <tr>
                                <td><?= h($l['nome']) ?></td>
                                <td class="cell-muted"><?= h($l['setor'] ?: '—') ?></td>
                                <td class="num"><?= (int)$l['registros'] ?></td>
                                <td class="num"><strong><?= (int)$l['total'] ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Devoluções por estado -->
    <div class="content-card">
        <div class="card-heading">
            <div>
                <h2>Devoluções por estado</h2>
                <p class="card-sub">Condição dos equipamentos devolvidos.</p>
            </div>
        </div>

        <?php if (!$porEstado): ?>
            <div class="empty-state compact">
                <strong>Sem devoluções registradas</strong>
                <p>Os retornos aparecerão aqui quando forem registrados.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Estado</th>
                            <th class="num">Registros</th>
                            <th class="num">Unidades</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($porEstado as $l): ?>
                            <?php
                            $classe = match ($l['estado']) {
                                'Bom'        => 'status-ok',
                                'Danificado' => 'status-aviso',
                                default      => 'status-neutro',
                            };
                            ?>
                            <tr>
                                <td><span class="status <?= $classe ?>"><?= h($l['estado']) ?></span></td>
                                <td class="num"><?= (int)$l['registros'] ?></td>
                                <td class="num"><strong><?= (int)$l['total'] ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Estoque crítico -->
<div class="content-card">
    <div class="card-heading">
        <div>
            <h2>Estoque crítico</h2>
            <p class="card-sub">Itens no limite mínimo ou abaixo — priorize a reposição.</p>
        </div>
    </div>

    <?php if (!$estoqueCritico): ?>
        <p class="inline-note success">Nenhum item abaixo do estoque mínimo.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>EPI</th>
                        <th>CA</th>
                        <th class="num">Quantidade atual</th>
                        <th class="num">Mínimo</th>
                        <th class="num">Faltam</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($estoqueCritico as $item): ?>
                        <?php $faltam = max(0, (int)$item['quantidade_minima'] - (int)$item['quantidade']); ?>
                        <tr>
                            <td><?= h($item['nome']) ?></td>
                            <td class="cell-mono"><?= h($item['ca'] ?: '—') ?></td>
                            <td class="num"><strong><?= (int)$item['quantidade'] ?></strong></td>
                            <td class="num"><?= (int)$item['quantidade_minima'] ?></td>
                            <td class="num">
                                <?php if ($faltam > 0): ?>
                                    <span class="status status-alerta"><?= $faltam ?></span>
                                <?php else: ?>
                                    <span class="cell-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<p class="report-foot">
    Relatório gerado em <?= h(date('d/m/Y \à\s H:i')) ?> por <?= h(usuario_atual()['nome']) ?>.
</p>

<?php rodape(); ?>
