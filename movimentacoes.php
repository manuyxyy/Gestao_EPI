<?php
require_once 'conexao.php';
require_once 'layout.php';

/** @var PDO $conn */ // Informa o editor sobre a origem da variável $conn (substitua por mysqli se aplicável)

cabecalho(
    'Movimentações',
    'Histórico de entradas, entregas e devoluções'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="movimentacoes.css">
</head>
<body>
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
        
</body>
</html>