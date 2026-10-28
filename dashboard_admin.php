<?php
require_once 'conexao.php';
require_once 'funcoes.php';
require_once __DIR__ . '/layout.php';

/** @var PDO $pdo */

exigir_login();

if (isset($_GET['boas_vindas'])) {
    flash('sucesso', 'Bem-vindo(a), ' . usuario_atual()['nome'] . '! Sessão iniciada com sucesso.');
}

/* ---------------------------------------------------------------- Indicadores */
$totalEpis         = (int)$pdo->query("SELECT COUNT(*) FROM epis")->fetchColumn();
$totalFuncionarios = (int)$pdo->query("SELECT COUNT(*) FROM funcionarios")->fetchColumn();
$totalEntregas     = (int)$pdo->query("SELECT COUNT(*) FROM entregas")->fetchColumn();
$totalUsuarios     = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE status = 'Ativo'")->fetchColumn();
$baixoEstoque      = (int)$pdo->query("SELECT COUNT(*) FROM estoque WHERE quantidade <= quantidade_minima")->fetchColumn();
$zerados           = (int)$pdo->query("SELECT COUNT(*) FROM estoque WHERE quantidade <= 0")->fetchColumn();

/* ---------------------------------------------------------------- Últimas entregas */
$ultimas = buscar_todos($pdo, "
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
    LEFT JOIN epis p         ON p.id = en.id_epi
    LEFT JOIN usuarios u     ON u.id = en.id_usuario
    ORDER BY en.data_entrega DESC, en.id DESC
    LIMIT 8
");

/* ---------------------------------------------------------------- Estoque crítico */
$estoqueCritico = buscar_todos($pdo, "
    SELECT e.nome, e.numero_ca AS ca, s.quantidade, s.quantidade_minima
    FROM estoque s
    INNER JOIN epis e ON e.id = s.id_epi
    WHERE s.quantidade <= s.quantidade_minima
    ORDER BY s.quantidade ASC, e.nome
    LIMIT 6
");

/* ---------------------------------------------------------------- CA vencendo/vencido */
$vencimentos = buscar_todos($pdo, "
    SELECT nome, numero_ca AS ca, validade
    FROM epis
    WHERE validade IS NOT NULL
      AND validade <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)
    ORDER BY validade ASC
    LIMIT 6
");

$acoes = '';
if (e_admin()) {
    $acoes .= '<a class="botao botao-sec" href="usuarios.php">' . icone('usuarios', 17) . ' Usuários</a>';
}
$acoes .= '<a class="botao" href="entregas.php">' . icone('mais', 17) . ' Nova entrega</a>';

cabecalho(
    'Painel de controle',
    'Visão geral do estoque, das entregas e dos acessos ao sistema',
    $acoes
);
?>

<div class="stats-grid">
    <article class="stat-card blue">
        <div class="stat-top">
            <span class="stat-label">EPIs cadastrados</span>
            <span class="stat-icon"><?= icone('epis', 19) ?></span>
        </div>
        <strong><?= $totalEpis ?></strong>
        <small>Equipamentos no catálogo</small>
    </article>

    <article class="stat-card green">
        <div class="stat-top">
            <span class="stat-label">Funcionários</span>
            <span class="stat-icon"><?= icone('funcionarios', 19) ?></span>
        </div>
        <strong><?= $totalFuncionarios ?></strong>
        <small>Colaboradores cadastrados</small>
    </article>

    <article class="stat-card orange">
        <div class="stat-top">
            <span class="stat-label">Entregas realizadas</span>
            <span class="stat-icon"><?= icone('entregas', 19) ?></span>
        </div>
        <strong><?= $totalEntregas ?></strong>
        <small>Registros de saída de EPI</small>
    </article>

    <article class="stat-card <?= $baixoEstoque > 0 ? 'red' : 'pale' ?>">
        <div class="stat-top">
            <span class="stat-label">Estoque crítico</span>
            <span class="stat-icon"><?= icone('alerta', 19) ?></span>
        </div>
        <strong><?= $baixoEstoque ?></strong>
        <small>
            <?php if ($zerados > 0): ?>
                <b><?= $zerados ?></b> sem nenhuma unidade
            <?php else: ?>
                No limite mínimo ou abaixo
            <?php endif; ?>
        </small>
    </article>

    <?php if (e_admin()): ?>
        <article class="stat-card yellow">
            <div class="stat-top">
                <span class="stat-label">Usuários ativos</span>
                <span class="stat-icon"><?= icone('usuarios', 19) ?></span>
            </div>
            <strong><?= $totalUsuarios ?></strong>
            <small>Com acesso liberado</small>
        </article>
    <?php endif; ?>
</div>

<!-- Atalhos -->
<div class="content-card">
    <div class="card-heading">
        <div>
            <h2>Ações rápidas</h2>
            <p class="card-sub">As tarefas mais usadas do dia a dia.</p>
        </div>
    </div>

    <div class="quick-grid">
        <a class="quick-card" href="entregas.php">
            <span class="stat-icon"><?= icone('entregas', 19) ?></span>
            <span><strong>Registrar entrega</strong><small>Baixa automática no estoque</small></span>
        </a>
        <a class="quick-card" href="devolucoes.php">
            <span class="stat-icon"><?= icone('devolucoes', 19) ?></span>
            <span><strong>Registrar devolução</strong><small>Retorno de equipamentos</small></span>
        </a>
        <a class="quick-card" href="estoque.php">
            <span class="stat-icon"><?= icone('estoque', 19) ?></span>
            <span><strong>Conferir estoque</strong><small>Quantidades e limites</small></span>
        </a>
        <a class="quick-card" href="validade.php">
            <span class="stat-icon"><?= icone('validade', 19) ?></span>
            <span><strong>Validar CAs</strong><small>Vencimentos e alertas</small></span>
        </a>
        <?php if (e_admin()): ?>
            <a class="quick-card" href="usuarios.php">
                <span class="stat-icon"><?= icone('usuarios', 19) ?></span>
                <span><strong>Gerenciar usuários</strong><small>Criar e revisar acessos</small></span>
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="dashboard-grid">

    <!-- Últimas entregas -->
    <div class="content-card">
        <div class="card-heading">
            <div>
                <h2>Últimas entregas</h2>
                <p class="card-sub">Os 8 registros mais recentes.</p>
            </div>
            <a class="botao botao-sec botao-sm" href="entregas.php">Ver todas</a>
        </div>

        <?php if (!$ultimas): ?>
            <div class="empty-state">
                <span class="empty-icon"><?= icone('entregas', 26) ?></span>
                <strong>Nenhuma entrega registrada</strong>
                <p>Quando uma entrega for feita, ela aparece aqui com o responsável pelo registro.</p>
                <a class="botao botao-sm" href="entregas.php">Registrar a primeira entrega</a>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Funcionário</th>
                            <th>EPI</th>
                            <th class="num">Qtd.</th>
                            <th>Responsável</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimas as $entrega): ?>
                            <tr>
                                <td class="cell-mono"><?= h(data_br($entrega['data_entrega'])) ?></td>
                                <td>
                                    <div class="cell-user">
                                        <span class="avatar avatar-azul"><?= h(mb_strtoupper(mb_substr((string)$entrega['funcionario'], 0, 1))) ?></span>
                                        <span>
                                            <strong><?= h($entrega['funcionario'] ?? 'Funcionário removido') ?></strong>
                                            <small><?= h($entrega['setor_funcionario'] ?: 'Sem setor') ?></small>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <?= h($entrega['epi'] ?? 'EPI removido') ?>
                                    <?php if (!empty($entrega['ca'])): ?>
                                        <small class="cell-muted">CA <?= h($entrega['ca']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="num"><strong><?= h($entrega['quantidade']) ?></strong></td>
                                <td class="cell-muted"><?= h($entrega['responsavel'] ?: '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Atenção -->
    <div class="content-card">
        <div class="card-heading">
            <div>
                <h2>Precisa de atenção</h2>
                <p class="card-sub">Estoque e validade.</p>
            </div>
        </div>

        <div class="attention-group">
            <h3 class="section-title"><?= icone('caixa', 16) ?> Estoque baixo</h3>

            <?php if (!$estoqueCritico): ?>
                <p class="inline-note success">Nenhum item abaixo do mínimo. Estoque saudável.</p>
            <?php else: ?>
                <ul class="attention-list">
                    <?php foreach ($estoqueCritico as $item): ?>
                        <?php $qtd = (int)$item['quantidade']; ?>
                        <li class="attention-item">
                            <span class="attention-dot <?= $qtd <= 0 ? 'red' : 'yellow' ?>"></span>
                            <span class="attention-text">
                                <strong><?= h($item['nome']) ?></strong>
                                <small>
                                    <?= $qtd <= 0 ? 'Sem estoque' : $qtd . ' un. restantes' ?>
                                    · mínimo <?= h($item['quantidade_minima']) ?>
                                </small>
                            </span>
                            <a class="botao botao-sec botao-sm" href="estoque.php">Repor</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="attention-group">
            <h3 class="section-title"><?= icone('validade', 16) ?> Certificados de Aprovação</h3>

            <?php if (!$vencimentos): ?>
                <p class="inline-note success">Nenhum CA vencido ou vencendo nos próximos 60 dias.</p>
            <?php else: ?>
                <ul class="attention-list">
                    <?php foreach ($vencimentos as $item): ?>
                        <?php
                        $dias = (int)floor((strtotime($item['validade']) - strtotime(date('Y-m-d'))) / 86400);
                        ?>
                        <li class="attention-item">
                            <span class="attention-dot <?= $dias < 0 ? 'red' : 'yellow' ?>"></span>
                            <span class="attention-text">
                                <strong><?= h($item['nome']) ?></strong>
                                <small>
                                    CA <?= h($item['ca'] ?: '—') ?> ·
                                    <?= $dias < 0
                                        ? 'vencido há ' . abs($dias) . ' dia(s)'
                                        : 'vence em ' . $dias . ' dia(s)' ?>
                                    (<?= h(data_br($item['validade'])) ?>)
                                </small>
                            </span>
                            <a class="botao botao-sec botao-sm" href="validade.php">Ver</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php rodape(); ?>
