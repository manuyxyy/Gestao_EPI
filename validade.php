<?php
require_once 'conexao.php';
require_once 'funcoes.php';
require_once __DIR__ . '/layout.php';

/** @var PDO $pdo */

exigir_login();

$linhas = buscar_todos($pdo, "
    SELECT
        e.id,
        e.nome,
        e.numero_ca AS ca,
        e.validade,
        COALESCE(s.quantidade, 0) AS quantidade
    FROM epis e
    LEFT JOIN estoque s ON s.id_epi = e.id
    WHERE e.validade IS NOT NULL
    ORDER BY e.validade ASC
");

$semValidade = (int)$pdo->query("
    SELECT COUNT(*) FROM epis WHERE validade IS NULL
")->fetchColumn();

$vencidos  = 0;
$vencendo  = 0;
$validos   = 0;

foreach ($linhas as &$linha) {
    $linha['dias'] = (int)floor(
        (strtotime($linha['validade']) - strtotime(date('Y-m-d'))) / 86400
    );

    if ($linha['dias'] < 0) {
        $linha['situacao'] = 'vencido';
        $linha['rotulo']   = 'Vencido';
        $vencidos++;
    } elseif ($linha['dias'] <= 30) {
        $linha['situacao'] = 'aviso';
        $linha['rotulo']   = 'Próximo do vencimento';
        $vencendo++;
    } else {
        $linha['situacao'] = 'ok';
        $linha['rotulo']   = 'Válido';
        $validos++;
    }
}
unset($linha);

$total = count($linhas);

cabecalho(
    'Validade dos CAs',
    'Acompanhe os Certificados de Aprovação e planeje as renovações',
    '<button class="botao botao-sec" type="button" onclick="window.print()">' . icone('imprimir', 17) . ' Imprimir</button>'
);
?>

<div class="stats-grid">
    <article class="stat-card green">
        <div class="stat-top">
            <span class="stat-label">CAs válidos</span>
            <span class="stat-icon"><?= icone('check', 19) ?></span>
        </div>
        <strong><?= $validos ?></strong>
        <small>Sem risco de vencimento</small>
    </article>

    <article class="stat-card yellow">
        <div class="stat-top">
            <span class="stat-label">Vencem em 30 dias</span>
            <span class="stat-icon"><?= icone('validade', 19) ?></span>
        </div>
        <strong><?= $vencendo ?></strong>
        <small>Renove o quanto antes</small>
    </article>

    <article class="stat-card red">
        <div class="stat-top">
            <span class="stat-label">CAs vencidos</span>
            <span class="stat-icon"><?= icone('alerta', 19) ?></span>
        </div>
        <strong><?= $vencidos ?></strong>
        <small>Fora da validade legal</small>
    </article>

    <article class="stat-card pale">
        <div class="stat-top">
            <span class="stat-label">Sem validade</span>
            <span class="stat-icon"><?= icone('x', 19) ?></span>
        </div>
        <strong><?= $semValidade ?></strong>
        <small>EPIs sem data de CA</small>
    </article>
</div>

<?php if ($vencidos > 0 || $vencendo > 0): ?>
    <div class="alerta aviso" role="status">
        <span class="alerta-icon"><?= icone('alerta', 18) ?></span>
        <span>
            <?php if ($vencidos > 0): ?>
                <strong><?= $vencidos ?> CA(s) vencido(s)</strong> precisam de regularização.
            <?php endif; ?>
            <?php if ($vencendo > 0): ?>
                <?= $vencidos > 0 ? ' Além disso, ' : '' ?><strong><?= $vencendo ?> CA(s)</strong>
                vencem nos próximos 30 dias.
            <?php endif; ?>
        </span>
        <button type="button" class="alerta-fechar" data-dismiss aria-label="Fechar aviso"><?= icone('x', 16) ?></button>
    </div>
<?php endif; ?>

<div class="content-card">
    <div class="card-heading">
        <div>
            <h2>Certificados cadastrados</h2>
            <p class="card-sub">Ordenados pela data de vencimento.</p>
        </div>
        <span class="table-count" data-table-count="tabela-validade"><?= $total ?> registros</span>
    </div>

    <div class="table-tools">
        <label class="search-box">
            <?= icone('busca', 17) ?>
            <input type="search" placeholder="Buscar por EPI ou CA..."
                   data-table-search="tabela-validade" aria-label="Buscar certificados">
        </label>

        <select class="table-filter" data-table-filter="tabela-validade"
                data-filter-key="situacao" aria-label="Filtrar por situação">
            <option value="">Todas as situações</option>
            <option value="ok">Válidos</option>
            <option value="aviso">Próximos do vencimento</option>
            <option value="vencido">Vencidos</option>
        </select>
    </div>

    <div class="table-wrap">
        <table id="tabela-validade" data-table>
            <thead>
                <tr>
                    <th data-sort="texto">EPI</th>
                    <th data-sort="texto">CA</th>
                    <th data-sort="texto">Validade</th>
                    <th data-sort="num">Dias restantes</th>
                    <th data-sort="num">Estoque</th>
                    <th data-sort="texto">Situação</th>
                    <th class="center">Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!$linhas): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <span class="empty-icon"><?= icone('validade', 24) ?></span>
                                <strong>Nenhuma validade cadastrada</strong>
                                <p>Informe a validade do CA ao cadastrar ou editar um EPI.</p>
                                <a class="botao botao-sm" href="epis.php">Ir para EPIs</a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($linhas as $l): ?>
                        <?php
                        $classe = match ($l['situacao']) {
                            'ok'      => 'status-ok',
                            'aviso'   => 'status-aviso',
                            default   => 'status-alerta',
                        };
                        ?>
                        <tr data-row data-situacao="<?= h($l['situacao']) ?>">
                            <td><?= h($l['nome']) ?></td>
                            <td class="cell-mono"><?= h($l['ca'] ?: '—') ?></td>
                            <td class="cell-mono"><?= h(data_br($l['validade'])) ?></td>
                            <td class="num">
                                <?php if ($l['dias'] < 0): ?>
                                    <strong>−<?= abs($l['dias']) ?></strong> dias
                                <?php else: ?>
                                    <strong><?= $l['dias'] ?></strong> dias
                                <?php endif; ?>
                            </td>
                            <td class="num"><?= (int)$l['quantidade'] ?></td>
                            <td><span class="status <?= $classe ?>"><?= h($l['rotulo']) ?></span></td>
                            <td>
                                <div class="acoes">
                                    <a class="botao botao-sec" href="epis.php?editar=<?= (int)$l['id'] ?>">
                                        <?= icone('editar', 15) ?> Editar CA
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <tr data-empty-filtro hidden>
                        <td colspan="7">
                            <div class="empty-state compact">
                                <strong>Nenhum certificado encontrado</strong>
                                <p>Ajuste a busca ou o filtro de situação.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php rodape(); ?>
