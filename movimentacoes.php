<?php
require_once 'conexao.php';
require_once 'layout.php';

/** @var PDO $conn */ // Informa o editor sobre a origem da variável $conn (substitua por mysqli se aplicável)
$linhas = buscar_todos(
    $conn,
    "SELECT m.data_movimentacao,
            m.tipo,
            m.quantidade,
            m.descricao,
            p.nome AS epi
     FROM movimentacoes m
     LEFT JOIN epis p ON p.id = m.epi_id
     ORDER BY m.data_movimentacao DESC, m.id DESC"
);

cabecalho(
    'Movimentações',
    'Histórico de entradas, entregas e devoluções'
);
?>

<div class="content-card">
    <h2>Histórico de movimentações</h2>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>EPI</th>
                    <th>Quantidade</th>
                    <th>Descrição</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$linhas): ?>
                    <tr>
                        <td colspan="5">Nenhuma movimentação registrada.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($linhas as $linha): ?>
                        <tr>
                            <td><?= htmlspecialchars($linha['data_movimentacao']) ?></td>
                            <td><?= htmlspecialchars($linha['tipo']) ?></td>
                            <td><?= htmlspecialchars($linha['epi'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($linha['quantidade']) ?></td>
                            <td><?= htmlspecialchars($linha['descricao'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>