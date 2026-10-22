<?php
require_once 'conexao.php';
require_once 'layout.php';

/** @var PDO $conn */ // Informa o editor sobre a origem da variável $conn

cabecalho(
    'Controle de estoque',
    'Acompanhe a quantidade disponível de cada EPI'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="estoque.css">
</head>
<body>
    
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

    </body>
</html>
<?php rodape(); ?>