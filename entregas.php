<?php
require_once 'conexao.php';
require_once 'funcoes.php';
require_once __DIR__ . '/layout.php';

/** @var PDO $pdo */

exigir_login();

/* =========================================================================
 * Registrar entrega
 * ====================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_proteger();

    $funcionario = (int)($_POST['funcionario_id'] ?? 0);
    $epi         = (int)($_POST['epi_id'] ?? 0);
    $quantidade  = (int)($_POST['quantidade'] ?? 0);
    $dataEntrega = trim((string)($_POST['data_entrega'] ?? date('Y-m-d')));
    $observacao  = trim((string)($_POST['observacao'] ?? ''));

    try {
        if ($funcionario <= 0 || $epi <= 0) {
            throw new InvalidArgumentException('Selecione o funcionário e o EPI.');
        }
        if ($quantidade <= 0) {
            throw new InvalidArgumentException('Informe uma quantidade maior que zero.');
        }

        $dataObj = DateTime::createFromFormat('Y-m-d', $dataEntrega);
        if (!$dataObj || $dataObj->format('Y-m-d') !== $dataEntrega) {
            throw new InvalidArgumentException('Data de entrega inválida.');
        }
        if ($dataEntrega > date('Y-m-d')) {
            throw new InvalidArgumentException('A data de entrega não pode estar no futuro.');
        }

        $nomeFuncionario = $pdo->prepare("SELECT nome FROM funcionarios WHERE id = ?");
        $nomeFuncionario->execute([$funcionario]);
        $nomeFuncionario = $nomeFuncionario->fetchColumn();

        if (!$nomeFuncionario) {
            throw new RuntimeException('Funcionário não encontrado.');
        }

        $nomeEpi = $pdo->prepare("SELECT nome FROM epis WHERE id = ?");
        $nomeEpi->execute([$epi]);
        $nomeEpi = $nomeEpi->fetchColumn();

        if (!$nomeEpi) {
            throw new RuntimeException('EPI não encontrado.');
        }

        $pdo->beginTransaction();

        // Trava a linha do estoque enquanto confere o saldo.
        $consulta = $pdo->prepare(
            "SELECT quantidade FROM estoque WHERE id_epi = ? FOR UPDATE"
        );
        $consulta->execute([$epi]);
        $saldo = $consulta->fetchColumn();

        if ($saldo === false || (int)$saldo < $quantidade) {
            throw new InvalidArgumentException(
                'Estoque insuficiente: há apenas ' . (int)$saldo . ' unidade(s) disponível(is).'
            );
        }

        $pdo->prepare("
            INSERT INTO entregas
                (id_funcionario, id_epi, id_usuario, quantidade, data_entrega, observacao)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([
            $funcionario,
            $epi,
            usuario_id(),
            $quantidade,
            $dataEntrega,
            $observacao !== '' ? $observacao : null,
        ]);

        $pdo->prepare(
            "UPDATE estoque SET quantidade = quantidade - ? WHERE id_epi = ?"
        )->execute([$quantidade, $epi]);

        $pdo->prepare("
            INSERT INTO movimentacoes (id_epi, tipo, quantidade, observacao)
            VALUES (?, 'Saida', ?, ?)
        ")->execute([
            $epi,
            $quantidade,
            'Entrega para ' . $nomeFuncionario,
        ]);

        $pdo->commit();

        flash(
            'sucesso',
            'Entrega registrada: ' . $quantidade . ' un. de "' . $nomeEpi
            . '" para ' . $nomeFuncionario . '.'
        );
        redirecionar('entregas.php');
    } catch (InvalidArgumentException | RuntimeException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('erro', $e->getMessage());
        redirecionar('entregas.php');
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Erro em entregas: ' . $e->getMessage());
        flash('erro', 'Não foi possível registrar a entrega.');
        redirecionar('entregas.php');
    }
}

/* =========================================================================
 * Consultas
 * ====================================================================== */
$funcionarios = buscar_todos($pdo, "
    SELECT f.id, f.nome, f.setor
    FROM funcionarios f
    ORDER BY f.nome
");

$epis = buscar_todos($pdo, "
    SELECT e.id, e.nome, e.numero_ca AS ca, COALESCE(s.quantidade, 0) AS quantidade
    FROM epis e
    LEFT JOIN estoque s ON s.id_epi = e.id
    ORDER BY e.nome
");

$linhas = buscar_todos($pdo, "
    SELECT
        en.id,
        en.data_entrega,
        en.quantidade,
        en.observacao,
        f.nome AS funcionario,
        f.setor AS setor_funcionario,
        p.nome AS epi,
        p.numero_ca AS ca,
        u.nome AS responsavel
    FROM entregas en
    LEFT JOIN funcionarios f ON f.id = en.id_funcionario
    LEFT JOIN epis p ON p.id = en.id_epi
    LEFT JOIN usuarios u ON u.id = en.id_usuario
    ORDER BY en.data_entrega DESC, en.id DESC
    LIMIT 200
");

$totalEntregas = count($linhas);
$totalUnidades = array_sum(array_map(fn($l) => (int)$l['quantidade'], $linhas));
$hoje = date('Y-m-d');
$entregasHoje = count(array_filter($linhas, fn($l) => $l['data_entrega'] === $hoje));
$inicioMes = date('Y-m-01');
$entregasMes = count(array_filter($linhas, fn($l) => $l['data_entrega'] >= $inicioMes));

cabecalho(
    'Entregas de EPIs',
    'Registre a saída de equipamentos e acompanhe o histórico',
    '<a class="botao botao-sec" href="devolucoes.php">' . icone('devolucoes', 17) . ' Registrar devolução</a>'
);
?>

<div class="stats-grid">
    <article class="stat-card blue">
        <div class="stat-top">
            <span class="stat-label">Entregas registradas</span>
            <span class="stat-icon"><?= icone('entregas', 19) ?></span>
        </div>
        <strong><?= $totalEntregas ?></strong>
        <small>Últimos 200 registros</small>
    </article>

    <article class="stat-card green">
        <div class="stat-top">
            <span class="stat-label">Unidades entregues</span>
            <span class="stat-icon"><?= icone('caixa', 19) ?></span>
        </div>
        <strong><?= $totalUnidades ?></strong>
        <small>Somatório do período listado</small>
    </article>

    <article class="stat-card orange">
        <div class="stat-top">
            <span class="stat-label">Entregas hoje</span>
            <span class="stat-icon"><?= icone('calendario', 19) ?></span>
        </div>
        <strong><?= $entregasHoje ?></strong>
        <small><?= h(date('d/m/Y')) ?></small>
    </article>

    <article class="stat-card yellow">
        <div class="stat-top">
            <span class="stat-label">No mês</span>
            <span class="stat-icon"><?= icone('relatorios', 19) ?></span>
        </div>
        <strong><?= $entregasMes ?></strong>
        <small>Desde <?= h(date('d/m/Y', strtotime($inicioMes))) ?></small>
    </article>
</div>

<!-- ===================================================== Formulário -->
<div class="content-card">
    <div class="card-heading">
        <div>
            <h2>Registrar entrega</h2>
            <p class="card-sub">A baixa no estoque é feita automaticamente.</p>
        </div>
    </div>

    <?php if (!$funcionarios): ?>
        <div class="empty-state compact">
            <span class="empty-icon"><?= icone('funcionarios', 24) ?></span>
            <strong>Nenhum funcionário cadastrado</strong>
            <p>Cadastre um funcionário antes de registrar entregas.</p>
            <a class="botao botao-sm" href="funcionarios.php">Cadastrar funcionário</a>
        </div>
    <?php elseif (!$epis): ?>
        <div class="empty-state compact">
            <span class="empty-icon"><?= icone('epis', 24) ?></span>
            <strong>Nenhum EPI cadastrado</strong>
            <p>Cadastre um EPI e informe a quantidade em estoque.</p>
            <a class="botao botao-sm" href="epis.php">Cadastrar EPI</a>
        </div>
    <?php else: ?>
        <form method="POST" class="form-grid">
            <?= csrf_campo() ?>

            <div>
                <label for="funcionario_id">Funcionário <span class="obrigatorio">*</span></label>
                <select id="funcionario_id" name="funcionario_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($funcionarios as $f): ?>
                        <option value="<?= (int)$f['id'] ?>">
                            <?= h($f['nome']) ?><?= $f['setor'] ? ' — ' . h($f['setor']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="epi_id">EPI <span class="obrigatorio">*</span></label>
                <select id="epi_id" name="epi_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($epis as $p): ?>
                        <?php $semEstoque = (int)$p['quantidade'] <= 0; ?>
                        <option value="<?= (int)$p['id'] ?>" <?= $semEstoque ? 'disabled' : '' ?>>
                            <?= h($p['nome']) ?>
                            (<?= $semEstoque ? 'sem estoque' : 'estoque: ' . (int)$p['quantidade'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="quantidade">Quantidade <span class="obrigatorio">*</span></label>
                <input id="quantidade" type="number" name="quantidade" min="1" value="1" required>
            </div>

            <div>
                <label for="data_entrega">Data da entrega <span class="obrigatorio">*</span></label>
                <input id="data_entrega" type="date" name="data_entrega"
                       value="<?= h(date('Y-m-d')) ?>" max="<?= h(date('Y-m-d')) ?>" required>
            </div>

            <div class="campo-largo">
                <label for="observacao">Observação</label>
                <input id="observacao" name="observacao" maxlength="255"
                       placeholder="Tamanho, motivo da troca, setor de destino...">
            </div>

            <div class="campo-largo form-actions">
                <button class="botao" type="submit"><?= icone('mais', 17) ?> Registrar entrega</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<!-- ===================================================== Histórico -->
<div class="content-card">
    <div class="card-heading">
        <div>
            <h2>Entregas registradas</h2>
            <p class="card-sub">Clique nos títulos das colunas para ordenar.</p>
        </div>
        <span class="table-count" data-table-count="tabela-entregas"><?= $totalEntregas ?> registros</span>
    </div>

    <div class="table-tools">
        <label class="search-box">
            <?= icone('busca', 17) ?>
            <input type="search" placeholder="Buscar por funcionário, EPI ou responsável..."
                   data-table-search="tabela-entregas" aria-label="Buscar entregas">
        </label>
    </div>

    <div class="table-wrap">
        <table id="tabela-entregas" data-table>
            <thead>
                <tr>
                    <th data-sort="texto">Data</th>
                    <th data-sort="texto">Funcionário</th>
                    <th data-sort="texto">EPI</th>
                    <th data-sort="num">Qtd.</th>
                    <th data-sort="texto">Responsável</th>
                    <th>Observação</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!$linhas): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <span class="empty-icon"><?= icone('entregas', 24) ?></span>
                                <strong>Nenhuma entrega registrada</strong>
                                <p>As entregas feitas aparecerão aqui com data, responsável e observações.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($linhas as $l): ?>
                        <tr data-row>
                            <td class="cell-mono"><?= h(data_br($l['data_entrega'])) ?></td>
                            <td>
                                <div class="cell-user">
                                    <span class="avatar avatar-azul"><?= h(mb_strtoupper(mb_substr((string)$l['funcionario'], 0, 1))) ?></span>
                                    <span>
                                        <strong><?= h($l['funcionario'] ?? 'Funcionário removido') ?></strong>
                                        <small><?= h($l['setor_funcionario'] ?: 'Sem setor') ?></small>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <?= h($l['epi'] ?? 'EPI removido') ?>
                                <?php if (!empty($l['ca'])): ?>
                                    <small class="cell-muted">CA <?= h($l['ca']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="num"><strong><?= (int)$l['quantidade'] ?></strong></td>
                            <td class="cell-muted"><?= h($l['responsavel'] ?: '—') ?></td>
                            <td class="cell-muted"><?= h($l['observacao'] ?: '—') ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <tr data-empty-filtro hidden>
                        <td colspan="6">
                            <div class="empty-state compact">
                                <strong>Nenhuma entrega encontrada</strong>
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
