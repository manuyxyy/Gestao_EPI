<?php
require_once 'conexao.php';
require_once 'funcoes.php';
require_once __DIR__ . '/layout.php';

/** @var PDO $pdo */

exigir_login();

/* =========================================================================
 * Ações
 * ====================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_proteger();

    $acao = (string)($_POST['acao'] ?? 'criar');

    try {
        if ($acao === 'excluir') {
            $id = (int)($_POST['id'] ?? 0);

            $stmt = $pdo->prepare("SELECT nome FROM epis WHERE id = ?");
            $stmt->execute([$id]);
            $nomeEpi = $stmt->fetchColumn();

            if (!$nomeEpi) {
                throw new RuntimeException('EPI não encontrado.');
            }

            $entregas = $pdo->prepare("SELECT COUNT(*) FROM entregas WHERE id_epi = ?");
            $entregas->execute([$id]);

            $saldo = $pdo->prepare("SELECT COALESCE(SUM(quantidade), 0) FROM estoque WHERE id_epi = ?");
            $saldo->execute([$id]);

            if ((int)$entregas->fetchColumn() > 0) {
                throw new InvalidArgumentException(
                    'Não é possível excluir: existem entregas registradas para este EPI.'
                );
            }

            if ((int)$saldo->fetchColumn() > 0) {
                throw new InvalidArgumentException(
                    'Não é possível excluir: ainda há unidades deste EPI no estoque.'
                );
            }

            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM epis WHERE id = ?")->execute([$id]);
            $pdo->commit();

            flash('sucesso', 'EPI "' . $nomeEpi . '" excluído.');
            redirecionar('epis.php');
        }

        $nome      = trim((string)($_POST['nome'] ?? ''));
        $descricao = trim((string)($_POST['descricao'] ?? ''));
        $ca        = trim((string)($_POST['ca'] ?? ''));
        $validade  = trim((string)($_POST['validade_ca'] ?? ''));

        if ($nome === '') {
            throw new InvalidArgumentException('Informe o nome do EPI.');
        }

        if ($validade !== '') {
            $data = DateTime::createFromFormat('Y-m-d', $validade);

            if (!$data || $data->format('Y-m-d') !== $validade) {
                throw new InvalidArgumentException('Data de validade inválida.');
            }
        }

        if ($acao === 'atualizar') {
            $id = (int)($_POST['id'] ?? 0);

            $pdo->prepare("
                UPDATE epis
                   SET nome = ?, descricao = ?, numero_ca = ?, validade = ?
                 WHERE id = ?
            ")->execute([
                $nome,
                $descricao !== '' ? $descricao : null,
                $ca !== '' ? $ca : null,
                $validade !== '' ? $validade : null,
                $id,
            ]);

            flash('sucesso', 'EPI "' . $nome . '" atualizado.');
            redirecionar('epis.php');
        }

        $qtdInicial = max(0, (int)($_POST['quantidade'] ?? 0));
        $minimo     = max(0, (int)($_POST['minimo'] ?? 5));

        $pdo->beginTransaction();

        $pdo->prepare("
            INSERT INTO epis (nome, descricao, numero_ca, validade)
            VALUES (?, ?, ?, ?)
        ")->execute([
            $nome,
            $descricao !== '' ? $descricao : null,
            $ca !== '' ? $ca : null,
            $validade !== '' ? $validade : null,
        ]);

        $epiId = (int)$pdo->lastInsertId();

        $pdo->prepare("
            INSERT INTO estoque (id_epi, quantidade, quantidade_minima)
            VALUES (?, ?, ?)
        ")->execute([$epiId, $qtdInicial, $minimo]);

        if ($qtdInicial > 0) {
            $pdo->prepare("
                INSERT INTO movimentacoes (id_epi, tipo, quantidade, observacao)
                VALUES (?, 'Entrada', ?, 'Entrada inicial de cadastro')
            ")->execute([$epiId, $qtdInicial]);
        }

        $pdo->commit();

        flash('sucesso', 'EPI "' . $nome . '" cadastrado e adicionado ao estoque.');
        redirecionar('epis.php');
    } catch (InvalidArgumentException | RuntimeException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('erro', $e->getMessage());
        redirecionar('epis.php');
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Erro em EPIs: ' . $e->getMessage());
        flash('erro', 'Não foi possível salvar o EPI. Confira os dados informados.');
        redirecionar('epis.php');
    }
}

/* =========================================================================
 * Consultas
 * ====================================================================== */
$editando = null;

if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM epis WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$_GET['editar']]);
    $editando = $stmt->fetch() ?: null;
}

$linhas = buscar_todos($pdo, "
    SELECT
        e.id,
        e.nome,
        e.descricao,
        e.numero_ca AS ca,
        e.validade,
        COALESCE(s.quantidade, 0)        AS quantidade,
        COALESCE(s.quantidade_minima, 5) AS minimo
    FROM epis e
    LEFT JOIN (
        SELECT id_epi,
               SUM(quantidade)        AS quantidade,
               MAX(quantidade_minima) AS quantidade_minima
          FROM estoque
         GROUP BY id_epi
    ) s ON s.id_epi = e.id
    ORDER BY e.nome
");

// Calcula a situação de cada item (usada na tabela e nos indicadores).
$vencidos   = 0;
$vencendo   = 0;
$baixoTotal = 0;
$semCa      = 0;

foreach ($linhas as &$linha) {
    $dias = null;

    if (!empty($linha['validade'])) {
        $dias = (int)floor(
            (strtotime($linha['validade']) - strtotime(date('Y-m-d'))) / 86400
        );
    }

    $linha['dias'] = $dias;

    if (empty($linha['ca'])) {
        $semCa++;
    }

    if ($dias !== null && $dias < 0) {
        $linha['situacao'] = 'vencido';
        $linha['rotulo']   = 'CA vencido';
        $vencidos++;
    } elseif ((int)$linha['quantidade'] <= (int)$linha['minimo']) {
        $linha['situacao'] = 'baixo';
        $linha['rotulo']   = 'Estoque baixo';
        $baixoTotal++;
    } elseif ($dias !== null && $dias <= 30) {
        $linha['situacao'] = 'aviso';
        $linha['rotulo']   = 'CA vence em breve';
        $vencendo++;
    } elseif ($dias === null) {
        $linha['situacao'] = 'sem-validade';
        $linha['rotulo']   = 'Sem validade';
    } else {
        $linha['situacao'] = 'ok';
        $linha['rotulo']   = 'Regular';
    }
}
unset($linha);

$total = count($linhas);

cabecalho(
    'Cadastro de EPIs',
    'Gerencie o catálogo de Equipamentos de Proteção Individual',
    '<a class="botao botao-sec" href="estoque.php">' . icone('estoque', 17) . ' Ver estoque</a>'
);
?>

<div class="stats-grid">
    <article class="stat-card blue">
        <div class="stat-top">
            <span class="stat-label">EPIs no catálogo</span>
            <span class="stat-icon"><?= icone('epis', 19) ?></span>
        </div>
        <strong><?= $total ?></strong>
        <small>Equipamentos cadastrados</small>
    </article>

    <article class="stat-card <?= $vencidos > 0 ? 'red' : 'pale' ?>">
        <div class="stat-top">
            <span class="stat-label">CA vencido</span>
            <span class="stat-icon"><?= icone('alerta', 19) ?></span>
        </div>
        <strong><?= $vencidos ?></strong>
        <small>Certificados fora da validade</small>
    </article>

    <article class="stat-card yellow">
        <div class="stat-top">
            <span class="stat-label">Vence em 30 dias</span>
            <span class="stat-icon"><?= icone('validade', 19) ?></span>
        </div>
        <strong><?= $vencendo ?></strong>
        <small>Renovação necessária</small>
    </article>

    <article class="stat-card orange">
        <div class="stat-top">
            <span class="stat-label">Estoque baixo</span>
            <span class="stat-icon"><?= icone('caixa', 19) ?></span>
        </div>
        <strong><?= $baixoTotal ?></strong>
        <small>No limite mínimo ou abaixo</small>
    </article>
</div>

<?php if ($editando): ?>
    <div class="content-card">
        <div class="card-heading">
            <div>
                <h2>Editar EPI</h2>
                <p class="card-sub"><?= h($editando['nome']) ?></p>
            </div>
            <a class="botao botao-sec botao-sm" href="epis.php"><?= icone('x', 15) ?> Cancelar</a>
        </div>

        <form method="POST" class="form-grid">
            <?= csrf_campo() ?>
            <input type="hidden" name="acao" value="atualizar">
            <input type="hidden" name="id" value="<?= (int)$editando['id'] ?>">

            <div class="campo-largo">
                <label for="edit-nome">Nome do EPI <span class="obrigatorio">*</span></label>
                <input id="edit-nome" name="nome" required value="<?= h($editando['nome']) ?>">
            </div>

            <div>
                <label for="edit-ca">Certificado de Aprovação (CA)</label>
                <input id="edit-ca" name="ca" value="<?= h($editando['numero_ca'] ?? '') ?>">
            </div>

            <div>
                <label for="edit-validade">Validade do CA</label>
                <input id="edit-validade" type="date" name="validade_ca"
                       value="<?= h($editando['validade'] ?? '') ?>">
            </div>

            <div class="campo-largo">
                <label for="edit-descricao">Descrição</label>
                <input id="edit-descricao" name="descricao" value="<?= h($editando['descricao'] ?? '') ?>">
            </div>

            <div class="campo-largo form-actions">
                <button class="botao" type="submit"><?= icone('salvar', 17) ?> Salvar alterações</button>
                <a class="botao botao-ghost" href="epis.php">Cancelar</a>
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="content-card">
        <div class="card-heading">
            <div>
                <h2>Novo EPI</h2>
                <p class="card-sub">Ao cadastrar, o item já entra no controle de estoque.</p>
            </div>
        </div>

        <form method="POST" class="form-grid">
            <?= csrf_campo() ?>
            <input type="hidden" name="acao" value="criar">

            <div class="campo-largo">
                <label for="nome">Nome do EPI <span class="obrigatorio">*</span></label>
                <input id="nome" name="nome" required maxlength="100"
                       placeholder="Ex.: Luva de segurança em vaqueta">
            </div>

            <div>
                <label for="ca">Certificado de Aprovação (CA)</label>
                <input id="ca" name="ca" maxlength="30" placeholder="Ex.: 31469">
            </div>

            <div>
                <label for="validade_ca">Validade do CA</label>
                <input id="validade_ca" type="date" name="validade_ca">
            </div>

            <div>
                <label for="quantidade">Quantidade inicial</label>
                <input id="quantidade" type="number" name="quantidade" min="0" value="0">
            </div>

            <div>
                <label for="minimo">Estoque mínimo</label>
                <input id="minimo" type="number" name="minimo" min="0" value="5">
                <p class="ajuda">Abaixo desse valor o item entra em alerta.</p>
            </div>

            <div class="campo-largo">
                <label for="descricao">Descrição</label>
                <input id="descricao" name="descricao" maxlength="255"
                       placeholder="Material, tamanho, observações de uso...">
            </div>

            <div class="campo-largo form-actions">
                <button class="botao" type="submit"><?= icone('mais', 17) ?> Cadastrar EPI</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<div class="content-card">
    <div class="card-heading">
        <div>
            <h2>EPIs cadastrados</h2>
            <p class="card-sub">Situação calculada pela validade do CA e pelo estoque.</p>
        </div>
        <span class="table-count" data-table-count="tabela-epis"><?= $total ?> registros</span>
    </div>

    <div class="table-tools">
        <label class="search-box">
            <?= icone('busca', 17) ?>
            <input type="search" placeholder="Buscar por nome ou CA..."
                   data-table-search="tabela-epis" aria-label="Buscar EPIs">
        </label>

        <select class="table-filter" data-table-filter="tabela-epis"
                data-filter-key="situacao" aria-label="Filtrar por situação">
            <option value="">Todas as situações</option>
            <option value="ok">Regular</option>
            <option value="aviso">CA vence em breve</option>
            <option value="baixo">Estoque baixo</option>
            <option value="vencido">CA vencido</option>
            <option value="sem-validade">Sem validade</option>
        </select>
    </div>

    <div class="table-wrap">
        <table id="tabela-epis" data-table>
            <thead>
                <tr>
                    <th data-sort="texto">EPI</th>
                    <th data-sort="texto">CA</th>
                    <th data-sort="texto">Validade do CA</th>
                    <th data-sort="num">Estoque</th>
                    <th data-sort="num">Mínimo</th>
                    <th data-sort="texto">Situação</th>
                    <th class="center">Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!$linhas): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <span class="empty-icon"><?= icone('epis', 24) ?></span>
                                <strong>Nenhum EPI cadastrado</strong>
                                <p>Cadastre o primeiro equipamento para começar a controlar o estoque.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($linhas as $epi): ?>
                        <?php
                        $classe = match ($epi['situacao']) {
                            'ok'           => 'status-ok',
                            'aviso'        => 'status-aviso',
                            'baixo'        => 'status-aviso',
                            'vencido'      => 'status-alerta',
                            default        => 'status-neutro',
                        };

                        $qtd = (int)$epi['quantidade'];
                        $min = max(1, (int)$epi['minimo']);
                        $pct = min(100, (int)round(($qtd / $min) * 100));
                        $classeBarra = $qtd <= 0 ? 'zerado' : ($qtd <= $min ? 'baixo' : '');
                        ?>
                        <tr data-row data-situacao="<?= h($epi['situacao']) ?>">
                            <td>
                                <div class="cell-user">
                                    <span class="avatar avatar-azul"><?= icone('epis', 16) ?></span>
                                    <span>
                                        <strong><?= h($epi['nome']) ?></strong>
                                        <small><?= h($epi['descricao'] ?: 'Sem descrição') ?></small>
                                    </span>
                                </div>
                            </td>
                            <td class="cell-mono"><?= h($epi['ca'] ?: '—') ?></td>
                            <td>
                                <?= h(data_br($epi['validade'])) ?>
                                <?php if ($epi['dias'] !== null): ?>
                                    <small class="cell-muted">
                                        <?= $epi['dias'] < 0
                                            ? 'vencido há ' . abs($epi['dias']) . ' dia(s)'
                                            : $epi['dias'] . ' dia(s) restantes' ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td class="num">
                                <div class="barra">
                                    <span class="barra-trilho"><span class="<?= $classeBarra ?>" style="width:<?= $pct ?>%"></span></span>
                                    <span class="barra-valor"><?= $qtd ?> un.</span>
                                </div>
                            </td>
                            <td class="num"><?= (int)$epi['minimo'] ?></td>
                            <td><span class="status <?= $classe ?>"><?= h($epi['rotulo']) ?></span></td>
                            <td>
                                <div class="acoes">
                                    <a class="botao botao-sec" href="epis.php?editar=<?= (int)$epi['id'] ?>">
                                        <?= icone('editar', 15) ?> Editar
                                    </a>
                                    <form method="POST" data-confirm="Excluir o EPI <?= h($epi['nome']) ?>?">
                                        <?= csrf_campo() ?>
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="id" value="<?= (int)$epi['id'] ?>">
                                        <button type="submit" class="botao botao-perigo">
                                            <?= icone('excluir', 15) ?> Excluir
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <tr data-empty-filtro hidden>
                        <td colspan="7">
                            <div class="empty-state compact">
                                <strong>Nenhum EPI encontrado</strong>
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
