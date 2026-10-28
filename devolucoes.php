<?php
require_once 'conexao.php';
require_once 'funcoes.php';
require_once __DIR__ . '/layout.php';

/** @var PDO $pdo */

exigir_login();

const ESTADOS_DEVOLUCAO = ['Bom', 'Danificado', 'Descartado'];

/* =========================================================================
 * Registrar devolução
 * ====================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_proteger();

    $entregaId  = (int)($_POST['entrega_id'] ?? 0);
    $quantidade = (int)($_POST['quantidade'] ?? 0);
    $estado     = (string)($_POST['estado'] ?? 'Bom');
    $observacao = trim((string)($_POST['observacao'] ?? ''));

    try {
        if ($entregaId <= 0) {
            throw new InvalidArgumentException('Selecione a entrega original.');
        }
        if ($quantidade <= 0) {
            throw new InvalidArgumentException('Informe uma quantidade maior que zero.');
        }
        if (!in_array($estado, ESTADOS_DEVOLUCAO, true)) {
            throw new InvalidArgumentException('Estado do EPI inválido.');
        }

        $pdo->beginTransaction();

        $consulta = $pdo->prepare("
            SELECT
                en.id_epi,
                en.quantidade - COALESCE(
                    (SELECT SUM(d.quantidade) FROM devolucoes d WHERE d.id_entrega = en.id), 0
                ) AS pendente,
                f.nome AS funcionario,
                p.nome AS epi
            FROM entregas en
            LEFT JOIN funcionarios f ON f.id = en.id_funcionario
            LEFT JOIN epis p ON p.id = en.id_epi
            WHERE en.id = ?
            FOR UPDATE
        ");
        $consulta->execute([$entregaId]);
        $entrega = $consulta->fetch();

        if (!$entrega) {
            throw new InvalidArgumentException('Entrega original não encontrada.');
        }

        $pendente = (int)$entrega['pendente'];

        if ($pendente <= 0) {
            throw new InvalidArgumentException('Essa entrega já foi devolvida por completo.');
        }

        if ($quantidade > $pendente) {
            throw new InvalidArgumentException(
                'A quantidade excede o pendente: faltam devolver ' . $pendente . ' unidade(s).'
            );
        }

        $pdo->prepare("
            INSERT INTO devolucoes (id_entrega, data_devolucao, quantidade, estado, observacao)
            VALUES (?, CURDATE(), ?, ?, ?)
        ")->execute([
            $entregaId,
            $quantidade,
            $estado,
            $observacao !== '' ? $observacao : null,
        ]);

        // Só volta ao estoque se o equipamento estiver em bom estado.
        if ($estado === 'Bom') {
            $pdo->prepare("
                UPDATE estoque SET quantidade = quantidade + ? WHERE id_epi = ?
            ")->execute([$quantidade, $entrega['id_epi']]);
        }

        $pdo->prepare("
            INSERT INTO movimentacoes (id_epi, tipo, quantidade, observacao)
            VALUES (?, 'Devolucao', ?, ?)
        ")->execute([
            $entrega['id_epi'],
            $quantidade,
            'Devolução da entrega #' . $entregaId . ' (' . $entrega['funcionario'] . ') — estado: ' . $estado,
        ]);

        $pdo->commit();

        flash(
            'sucesso',
            'Devolução registrada: ' . $quantidade . ' un. de "' . $entrega['epi']
            . '" — estado ' . mb_strtolower($estado) . '.'
        );
        redirecionar('devolucoes.php');
    } catch (InvalidArgumentException | RuntimeException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('erro', $e->getMessage());
        redirecionar('devolucoes.php');
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Erro em devoluções: ' . $e->getMessage());
        flash('erro', 'Não foi possível registrar a devolução.');
        redirecionar('devolucoes.php');
    }
}

/* =========================================================================
 * Consultas
 * ====================================================================== */
$entregasPendentes = buscar_todos($pdo, "
    SELECT
        en.id,
        en.data_entrega,
        f.nome AS funcionario,
        p.nome AS epi,
        en.quantidade - COALESCE(dv.devolvido, 0) AS pendente
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

$linhas = buscar_todos($pdo, "
    SELECT
        d.id,
        d.data_devolucao,
        d.quantidade,
        d.estado,
        d.observacao,
        en.id AS entrega_id,
        f.nome AS funcionario,
        p.nome AS epi
    FROM devolucoes d
    INNER JOIN entregas en ON en.id = d.id_entrega
    LEFT JOIN funcionarios f ON f.id = en.id_funcionario
    LEFT JOIN epis p ON p.id = en.id_epi
    ORDER BY d.data_devolucao DESC, d.id DESC
    LIMIT 200
");

$totalDevolucoes = count($linhas);
$totalUnidades   = array_sum(array_map(fn($d) => (int)$d['quantidade'], $linhas));
$totalPendente   = array_sum(array_map(fn($e) => (int)$e['pendente'], $entregasPendentes));
$totalBons       = count(array_filter($linhas, fn($d) => $d['estado'] === 'Bom'));

cabecalho(
    'Devolução de EPIs',
    'Registre o retorno dos equipamentos entregues e o estado de conservação',
    '<a class="botao botao-sec" href="entregas.php">' . icone('entregas', 17) . ' Ver entregas</a>'
);
?>

<div class="stats-grid">
    <article class="stat-card blue">
        <div class="stat-top">
            <span class="stat-label">Devoluções registradas</span>
            <span class="stat-icon"><?= icone('devolucoes', 19) ?></span>
        </div>
        <strong><?= $totalDevolucoes ?></strong>
        <small>Últimos 200 registros</small>
    </article>

    <article class="stat-card green">
        <div class="stat-top">
            <span class="stat-label">Em bom estado</span>
            <span class="stat-icon"><?= icone('check', 19) ?></span>
        </div>
        <strong><?= $totalBons ?></strong>
        <small>Voltaram ao estoque</small>
    </article>

    <article class="stat-card orange">
        <div class="stat-top">
            <span class="stat-label">A devolver</span>
            <span class="stat-icon"><?= icone('caixa', 19) ?></span>
        </div>
        <strong><?= count($entregasPendentes) ?></strong>
        <small><?= $totalPendente ?> unidade(s) pendente(s)</small>
    </article>

    <article class="stat-card yellow">
        <div class="stat-top">
            <span class="stat-label">Unidades devolvidas</span>
            <span class="stat-icon"><?= icone('relatorios', 19) ?></span>
        </div>
        <strong><?= $totalUnidades ?></strong>
        <small>Total no período listado</small>
    </article>
</div>

<!-- ===================================================== Formulário -->
<div class="content-card">
    <div class="card-heading">
        <div>
            <h2>Registrar devolução</h2>
            <p class="card-sub">Somente itens em bom estado retornam automaticamente ao estoque.</p>
        </div>
    </div>

    <?php if (!$entregasPendentes): ?>
        <div class="empty-state compact">
            <span class="empty-icon"><?= icone('check', 24) ?></span>
            <strong>Nenhuma devolução pendente</strong>
            <p>Todas as entregas registradas já foram devolvidas.</p>
        </div>
    <?php else: ?>
        <form method="POST" class="form-grid">
            <?= csrf_campo() ?>

            <div class="campo-largo">
                <label for="entrega_id">Entrega original <span class="obrigatorio">*</span></label>
                <select id="entrega_id" name="entrega_id" required
                        data-max-select="quantidade">
                    <option value="">Selecione</option>
                    <?php foreach ($entregasPendentes as $e): ?>
                        <option value="<?= (int)$e['id'] ?>" data-max="<?= (int)$e['pendente'] ?>">
                            #<?= (int)$e['id'] ?> — <?= h($e['funcionario'] ?? 'Funcionário removido') ?>
                            / <?= h($e['epi'] ?? 'EPI removido') ?>
                            (a devolver: <?= (int)$e['pendente'] ?>) · <?= h(data_br($e['data_entrega'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="quantidade">Quantidade devolvida <span class="obrigatorio">*</span></label>
                <input id="quantidade" type="number" name="quantidade" min="1" value="1" required>
            </div>

            <div>
                <label for="estado">Estado do EPI <span class="obrigatorio">*</span></label>
                <select id="estado" name="estado" required>
                    <option value="Bom" selected>Bom — volta ao estoque</option>
                    <option value="Danificado">Danificado — não volta</option>
                    <option value="Descartado">Descartado — não volta</option>
                </select>
            </div>

            <div class="campo-largo">
                <label for="observacao">Observação</label>
                <input id="observacao" name="observacao" maxlength="255"
                       placeholder="Ex.: equipamento com fissura na lateral">
            </div>

            <div class="campo-largo form-actions">
                <button class="botao" type="submit"><?= icone('devolucoes', 17) ?> Registrar devolução</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<!-- ===================================================== Histórico -->
<div class="content-card">
    <div class="card-heading">
        <div>
            <h2>Devoluções registradas</h2>
            <p class="card-sub">Clique nos títulos das colunas para ordenar.</p>
        </div>
        <span class="table-count" data-table-count="tabela-devolucoes"><?= $totalDevolucoes ?> registros</span>
    </div>

    <div class="table-tools">
        <label class="search-box">
            <?= icone('busca', 17) ?>
            <input type="search" placeholder="Buscar por funcionário, EPI ou estado..."
                   data-table-search="tabela-devolucoes" aria-label="Buscar devoluções">
        </label>
    </div>

    <div class="table-wrap">
        <table id="tabela-devolucoes" data-table>
            <thead>
                <tr>
                    <th data-sort="texto">Data</th>
                    <th data-sort="texto">Funcionário</th>
                    <th data-sort="texto">EPI</th>
                    <th data-sort="num">Qtd.</th>
                    <th data-sort="texto">Estado</th>
                    <th>Observação</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!$linhas): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <span class="empty-icon"><?= icone('devolucoes', 24) ?></span>
                                <strong>Nenhuma devolução registrada</strong>
                                <p>As devoluções aparecerão aqui com o estado de conservação do EPI.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($linhas as $d): ?>
                        <?php
                        $classeEstado = match ($d['estado']) {
                            'Bom'        => 'status-ok',
                            'Danificado' => 'status-aviso',
                            default      => 'status-alerta',
                        };
                        ?>
                        <tr data-row>
                            <td class="cell-mono"><?= h(data_br($d['data_devolucao'])) ?></td>
                            <td>
                                <div class="cell-user">
                                    <span class="avatar avatar-azul"><?= h(mb_strtoupper(mb_substr((string)$d['funcionario'], 0, 1))) ?></span>
                                    <span>
                                        <strong><?= h($d['funcionario'] ?? 'Funcionário removido') ?></strong>
                                        <small>Entrega #<?= (int)$d['entrega_id'] ?></small>
                                    </span>
                                </div>
                            </td>
                            <td><?= h($d['epi'] ?? 'EPI removido') ?></td>
                            <td class="num"><strong><?= (int)$d['quantidade'] ?></strong></td>
                            <td><span class="status <?= $classeEstado ?>"><?= h($d['estado'] ?: 'Não informado') ?></span></td>
                            <td class="cell-muted"><?= h($d['observacao'] ?: '—') ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <tr data-empty-filtro hidden>
                        <td colspan="6">
                            <div class="empty-state compact">
                                <strong>Nenhuma devolução encontrada</strong>
                                <p>Ajuste os termos da busca.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php rodape(); ?>
