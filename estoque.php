<?php
require_once 'conexao.php';
require_once 'funcoes.php';
require_once __DIR__ . '/layout.php';

/** @var PDO $pdo */

exigir_login();

/* =========================================================================
 * Ajuste de estoque
 * ====================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_proteger();

    try {
        $epiId      = (int)($_POST['epi_id'] ?? 0);
        $tipo       = (string)($_POST['tipo'] ?? 'Entrada');
        $quantidade = (int)($_POST['quantidade'] ?? 0);
        $observacao = trim((string)($_POST['observacao'] ?? ''));

        if ($epiId <= 0) {
            throw new InvalidArgumentException('Selecione o EPI.');
        }
        if (!in_array($tipo, ['Entrada', 'Saida'], true)) {
            throw new InvalidArgumentException('Tipo de movimentação inválido.');
        }
        if ($quantidade <= 0) {
            throw new InvalidArgumentException('Informe uma quantidade maior que zero.');
        }

        $nomeEpi = $pdo->prepare("SELECT nome FROM epis WHERE id = ?");
        $nomeEpi->execute([$epiId]);
        $nomeEpi = $nomeEpi->fetchColumn();

        if (!$nomeEpi) {
            throw new RuntimeException('EPI não encontrado.');
        }

        $pdo->beginTransaction();

        $consulta = $pdo->prepare("SELECT id, quantidade FROM estoque WHERE id_epi = ? FOR UPDATE");
        $consulta->execute([$epiId]);
        $estoque = $consulta->fetch();

        if (!$estoque) {
            $pdo->prepare("INSERT INTO estoque (id_epi, quantidade, quantidade_minima) VALUES (?, 0, 5)")
                ->execute([$epiId]);
            $saldo = 0;
        } else {
            $saldo = (int)$estoque['quantidade'];
        }

        if ($tipo === 'Saida' && $quantidade > $saldo) {
            throw new InvalidArgumentException(
                'Saldo insuficiente: há apenas ' . $saldo . ' unidade(s) em estoque.'
            );
        }

        $operacao = $tipo === 'Entrada' ? '+' : '-';

        $pdo->prepare("
            UPDATE estoque
               SET quantidade = quantidade {$operacao} ?
             WHERE id_epi = ?
        ")->execute([$quantidade, $epiId]);

        $texto = $tipo === 'Entrada'
            ? 'Ajuste manual de entrada'
            : 'Ajuste manual de saída';

        $pdo->prepare("
            INSERT INTO movimentacoes (id_epi, tipo, quantidade, observacao)
            VALUES (?, ?, ?, ?)
        ")->execute([
            $epiId,
            $tipo,
            $quantidade,
            $texto . ($observacao !== '' ? ' — ' . $observacao : ''),
        ]);

        $pdo->commit();

        flash(
            'sucesso',
            ($tipo === 'Entrada' ? 'Entrada' : 'Saída') . ' de ' . $quantidade
            . ' unidade(s) de "' . $nomeEpi . '" registrada.'
        );
        redirecionar('estoque.php');
    } catch (InvalidArgumentException | RuntimeException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('erro', $e->getMessage());
        redirecionar('estoque.php');
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Erro no estoque: ' . $e->getMessage());
        flash('erro', 'Não foi possível ajustar o estoque.');
        redirecionar('estoque.php');
    }
}

/* =========================================================================
 * Consultas
 * ====================================================================== */
$selecionado = (int)($_GET['epi'] ?? 0);

$linhas = buscar_todos($pdo, "
    SELECT
        e.id,
        e.nome,
        e.numero_ca AS ca,
        COALESCE(s.quantidade, 0)        AS quantidade,
        COALESCE(s.quantidade_minima, 5) AS minimo,
        (SELECT COUNT(*) FROM entregas en WHERE en.id_epi = e.id) AS total_entregas
    FROM epis e
    LEFT JOIN estoque s ON s.id_epi = e.id
    ORDER BY (COALESCE(s.quantidade, 0) <= COALESCE(s.quantidade_minima, 5)) DESC,
             COALESCE(s.quantidade, 0) ASC,
             e.nome
");

$totalUnidades = 0;
$baixos        = 0;
$zerados       = 0;

foreach ($linhas as &$linha) {
    $qtd = (int)$linha['quantidade'];
    $min = (int)$linha['minimo'];

    $totalUnidades += $qtd;

    if ($qtd <= 0) {
        $linha['situacao'] = 'zerado';
        $linha['rotulo']   = 'Sem estoque';
        $zerados++;
        $baixos++;
    } elseif ($qtd <= $min) {
        $linha['situacao'] = 'baixo';
        $linha['rotulo']   = 'Estoque baixo';
        $baixos++;
    } else {
        $linha['situacao'] = 'ok';
        $linha['rotulo']   = 'Normal';
    }
}
unset($linha);

$totalItens = count($linhas);

cabecalho(
    'Controle de estoque',
    'Acompanhe as quantidades disponíveis e registre entradas e saídas manuais',
    '<a class="botao botao-sec" href="epis.php">' . icone('mais', 17) . ' Cadastrar EPI</a>'
);
?>

<div class="stats-grid">
    <article class="stat-card blue">
        <div class="stat-top">
            <span class="stat-label">Itens controlados</span>
            <span class="stat-icon"><?= icone('caixa', 19) ?></span>
        </div>
        <strong><?= $totalItens ?></strong>
        <small>EPIs com linha de estoque</small>
    </article>

    <article class="stat-card green">
        <div class="stat-top">
            <span class="stat-label">Unidades em estoque</span>
            <span class="stat-icon"><?= icone('estoque', 19) ?></span>
        </div>
        <strong><?= $totalUnidades ?></strong>
        <small>Soma de todas as quantidades</small>
    </article>

    <article class="stat-card <?= $baixos > 0 ? 'orange' : 'pale' ?>">
        <div class="stat-top">
            <span class="stat-label">Estoque baixo</span>
            <span class="stat-icon"><?= icone('alerta', 19) ?></span>
        </div>
        <strong><?= $baixos ?></strong>
        <small>No mínimo ou abaixo</small>
    </article>

    <article class="stat-card <?= $zerados > 0 ? 'red' : 'pale' ?>">
        <div class="stat-top">
            <span class="stat-label">Sem estoque</span>
            <span class="stat-icon"><?= icone('x', 19) ?></span>
        </div>
        <strong><?= $zerados ?></strong>
        <small>Nenhuma unidade disponível</small>
    </article>
</div>

<!-- ===================================================== Ajuste -->
<div class="content-card" id="ajustar">
    <div class="card-heading">
        <div>
            <h2>Ajustar estoque</h2>
            <p class="card-sub">Use para registrar compras (entrada) ou perdas e correções (saída).</p>
        </div>
    </div>

    <?php if (!$linhas): ?>
        <div class="empty-state compact">
            <span class="empty-icon"><?= icone('estoque', 24) ?></span>
            <strong>Nenhum EPI cadastrado</strong>
            <p>Cadastre um EPI primeiro para poder controlar o estoque.</p>
            <a class="botao botao-sm" href="epis.php">Cadastrar EPI</a>
        </div>
    <?php else: ?>
        <form method="POST" class="form-grid">
            <?= csrf_campo() ?>

            <div>
                <label for="epi_id">EPI <span class="obrigatorio">*</span></label>
                <select id="epi_id" name="epi_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($linhas as $item): ?>
                        <option value="<?= (int)$item['id'] ?>" <?= $selecionado === (int)$item['id'] ? 'selected' : '' ?>>
                            <?= h($item['nome']) ?> — <?= (int)$item['quantidade'] ?> un. em estoque
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="tipo">Tipo <span class="obrigatorio">*</span></label>
                <select id="tipo" name="tipo" required>
                    <option value="Entrada">Entrada (compra)</option>
                    <option value="Saida">Saída (perda)</option>
                </select>
            </div>

            <div>
                <label for="quantidade">Quantidade <span class="obrigatorio">*</span></label>
                <input id="quantidade" type="number" name="quantidade" min="1" value="1" required>
            </div>

            <div>
                <label for="observacao">Observação</label>
                <input id="observacao" name="observacao" maxlength="255" placeholder="Opcional">
            </div>

            <div class="form-action">
                <button class="botao" type="submit"><?= icone('salvar', 17) ?> Registrar ajuste</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<!-- ===================================================== Lista -->
<div class="content-card">
    <div class="card-heading">
        <div>
            <h2>Estoque atual</h2>
            <p class="card-sub">Itens críticos aparecem primeiro.</p>
        </div>
        <span class="table-count" data-table-count="tabela-estoque"><?= $totalItens ?> itens</span>
    </div>

    <div class="table-tools">
        <label class="search-box">
            <?= icone('busca', 17) ?>
            <input type="search" placeholder="Buscar por EPI ou CA..."
                   data-table-search="tabela-estoque" aria-label="Buscar no estoque">
        </label>

        <select class="table-filter" data-table-filter="tabela-estoque"
                data-filter-key="situacao" aria-label="Filtrar por situação">
            <option value="">Todas as situações</option>
            <option value="ok">Normal</option>
            <option value="baixo">Estoque baixo</option>
            <option value="zerado">Sem estoque</option>
        </select>
    </div>

    <div class="table-wrap">
        <table id="tabela-estoque" data-table>
            <thead>
                <tr>
                    <th data-sort="texto">EPI</th>
                    <th data-sort="texto">CA</th>
                    <th data-sort="num">Quantidade</th>
                    <th data-sort="num">Mínimo</th>
                    <th data-sort="num">Entregas</th>
                    <th data-sort="texto">Situação</th>
                    <th class="center">Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!$linhas): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <span class="empty-icon"><?= icone('estoque', 24) ?></span>
                                <strong>Nenhum item no estoque</strong>
                                <p>Cadastre um EPI para iniciar o controle de quantidades.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($linhas as $item): ?>
                        <?php
                        $qtd  = (int)$item['quantidade'];
                        $min  = max(1, (int)$item['minimo']);
                        $pct  = min(100, (int)round(($qtd / $min) * 100));
                        $classeBarra = $qtd <= 0 ? 'zerado' : ($qtd <= $min ? 'baixo' : '');
                        $classeStatus = match ($item['situacao']) {
                            'ok'     => 'status-ok',
                            'baixo'  => 'status-aviso',
                            default  => 'status-alerta',
                        };
                        ?>
                        <tr data-row data-situacao="<?= h($item['situacao']) ?>">
                            <td>
                                <div class="cell-user">
                                    <span class="avatar avatar-azul"><?= icone('epis', 16) ?></span>
                                    <span><strong><?= h($item['nome']) ?></strong></span>
                                </div>
                            </td>
                            <td class="cell-mono"><?= h($item['ca'] ?: '—') ?></td>
                            <td class="num">
                                <div class="barra">
                                    <span class="barra-trilho"><span class="<?= $classeBarra ?>" style="width:<?= $pct ?>%"></span></span>
                                    <span class="barra-valor"><?= $qtd ?></span>
                                </div>
                            </td>
                            <td class="num"><?= (int)$item['minimo'] ?></td>
                            <td class="num"><?= (int)$item['total_entregas'] ?></td>
                            <td><span class="status <?= $classeStatus ?>"><?= h($item['rotulo']) ?></span></td>
                            <td>
                                <div class="acoes">
                                    <a class="botao botao-sec" href="estoque.php?epi=<?= (int)$item['id'] ?>#ajustar">
                                        <?= icone('refresh', 15) ?> Ajustar
                                    </a>
                                    <a class="botao botao-ghost" href="epis.php?editar=<?= (int)$item['id'] ?>" title="Editar EPI">
                                        <?= icone('editar', 15) ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <tr data-empty-filtro hidden>
                        <td colspan="7">
                            <div class="empty-state compact">
                                <strong>Nenhum item encontrado</strong>
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
