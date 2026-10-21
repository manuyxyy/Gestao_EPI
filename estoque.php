<?php
require_once 'conexao.php';
require_once 'layout.php';

/** @var PDO $conn */ // Informa o editor sobre a origem da variável $conn

$linhas = buscar_todos(
    $conn,
    "SELECT p.nome, p.categoria, p.ca,
            s.quantidade, s.minimo, s.localizacao
     FROM estoque s
     JOIN epis p ON p.id = s.epi_id
     ORDER BY p.nome"
);

cabecalho(
    'Controle de estoque',
    'Acompanhe a quantidade disponível de cada EPI'
);
?>

<div class="content-card">

    <div class="card-heading">
        <h2>Estoque atual</h2>
        <a class="botao" href="epis.php">Cadastrar EPI</a>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>EPI</th>
                    <th>Categoria</th>
                    <th>CA</th>
                    <th>Quantidade</th>
                    <th>Estoque mínimo</th>
                    <th>Localização</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!$linhas): ?>
                    <tr>
                        <td colspan="7">
                            Nenhum item de estoque cadastrado.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($linhas as $l): ?>
                        <?php $baixoEstoque = (int)$l['quantidade'] <= (int)$l['minimo']; ?>
                        <tr>
                            <td><?= h($l['nome']) ?></td>
                            <td><?= h($l['categoria'] ?: '-') ?></td>
                            <td><?= h($l['ca'] ?: '-') ?></td>
                            <td><?= h($l['quantidade']) ?></td>
                            <td><?= h($l['minimo']) ?></td>
                            <td><?= h($l['localizacao'] ?: '-') ?></td>
                            <td>
                                <span class="status <?= $baixoEstoque ? 'status-alerta' : 'status-ok' ?>">
                                    <?= $baixoEstoque ? 'Estoque baixo' : 'Normal' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php rodape(); ?>