<?php
require_once 'conexao.php';
require_once 'funcoes.php';
require_once __DIR__ . '/layout.php';

/** @var PDO $pdo */

exigir_login();

$linhas = buscar_todos($pdo, "
    SELECT
        m.id,
        m.data_movimentacao,
        m.tipo,
        m.quantidade,
        m.observacao AS descricao,
        e.nome AS epi,
        e.numero_ca AS ca
    FROM movimentacoes m
    LEFT JOIN epis e ON e.id = m.id_epi
    ORDER BY m.data_movimentacao DESC, m.id DESC
    LIMIT 300
");

$total       = count($linhas);
$entradas    = count(array_filter($linhas, fn($m) => $m['tipo'] === 'Entrada'));
$saidas      = count(array_filter($linhas, fn($m) => $m['tipo'] === 'Saida'));
$devolucoes  = count(array_filter($linhas, fn($m) => $m['tipo'] === 'Devolucao'));
$unidades    = array_sum(array_map(fn($m) => (int)$m['quantidade'], $linhas));

cabecalho(
    'Movimentações de estoque',
    'Histórico completo de entradas, saídas e devoluções de EPIs'
);
?>

<div class="stats-grid">
    <article class="stat-card blue">
        <div class="stat-top">
            <span class="stat-label">Movimentações</span>
            <span class="stat-icon"><?= icone('movimentacoes', 19) ?></span>
        </div>
        <strong><?= $total ?></strong>
        <small>Últimos 300 registros</small>
    </article>

    <article class="stat-card green">
        <div class="stat-top">
            <span class="stat-label">Entradas</span>
            <span class="stat-icon"><?= icone('mais', 19) ?></span>
        </div>
        <strong><?= $entradas ?></strong>
        <small>Compras e ajustes positivos</small>
    </article>

    <article class="stat-card orange">
        <div class="stat-top">
            <span class="stat-label">Saídas</span>
            <span class="stat-icon"><?= icone('entregas', 19) ?></span>
        </div>
        <strong><?= $saidas ?></strong>
        <small>Entregas e ajustes negativos</small>
    </article>

    <article class="stat-card yellow">
        <div class="stat-top">
            <span class="stat-label">Devoluções</span>
            <span class="stat-icon"><?= icone('devolucoes', 19) ?></span>
        </div>
        <strong><?= $devolucoes ?></strong>
        <small><?= $unidades ?> unidade(s) movimentada(s)</small>
    </article>
</div>

<div class="content-card">
    <div class="card-heading">
        <div>
            <h2>Histórico de movimentações</h2>
            <p class="card-sub">Registro automático de todas as operações de estoque.</p>
        </div>
        <span class="table-count" data-table-count="tabela-movimentacoes"><?= $total ?> registros</span>
    </div>

    <div class="table-tools">
        <label class="search-box">
            <?= icone('busca', 17) ?>
            <input type="search" placeholder="Buscar por EPI, tipo ou descrição..."
                   data-table-search="tabela-movimentacoes" aria-label="Buscar movimentações">
        </label>

        <select class="table-filter" data-table-filter="tabela-movimentacoes"
                data-filter-key="tipo" aria-label="Filtrar por tipo">
            <option value="">Todos os tipos</option>
            <option value="Entrada">Entradas</option>
            <option value="Saida">Saídas</option>
            <option value="Devolucao">Devoluções</option>
        </select>
    </div>

    <div class="table-wrap">
        <table id="tabela-movimentacoes" data-table>
            <thead>
                <tr>
                    <th data-sort="texto">Data / hora</th>
                    <th data-sort="texto">Tipo</th>
                    <th data-sort="texto">EPI</th>
                    <th data-sort="num">Quantidade</th>
                    <th>Descrição</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!$linhas): ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <span class="empty-icon"><?= icone('movimentacoes', 24) ?></span>
                                <strong>Nenhuma movimentação registrada</strong>
                                <p>Entradas, entregas e devoluções aparecem aqui automaticamente.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($linhas as $m): ?>
                        <?php
                        [$classeTipo, $rotuloTipo, $sinal] = match ($m['tipo']) {
                            'Entrada'   => ['status-ok', 'Entrada', '+'],
                            'Devolucao' => ['status-info', 'Devolução', '+'],
                            default     => ['status-aviso', 'Saída', '−'],
                        };
                        ?>
                        <tr data-row data-tipo="<?= h($m['tipo']) ?>">
                            <td class="cell-mono"><?= h(data_br($m['data_movimentacao'], true)) ?></td>
                            <td><span class="status <?= $classeTipo ?>"><?= h($rotuloTipo) ?></span></td>
                            <td>
                                <?= h($m['epi'] ?? 'EPI removido') ?>
                                <?php if (!empty($m['ca'])): ?>
                                    <small class="cell-muted">CA <?= h($m['ca']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="num"><strong><?= h($sinal . (int)$m['quantidade']) ?></strong></td>
                            <td class="cell-muted"><?= h($m['descricao'] ?: '—') ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <tr data-empty-filtro hidden>
                        <td colspan="5">
                            <div class="empty-state compact">
                                <strong>Nenhuma movimentação encontrada</strong>
                                <p>Ajuste a busca ou o filtro de tipo.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php rodape(); ?>
