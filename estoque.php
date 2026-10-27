<?php
require_once 'conexao.php';
require_once 'funcoes.php';   // buscar_todos()
require_once 'layout.php';

/** @var PDO $pdo */

// Itens com estoque baixo aparecem primeiro
$linhas = buscar_todos($pdo, "
    SELECT
        e.id,
        e.nome,
        e.numero_ca AS ca,
        s.quantidade,
        s.quantidade_minima AS minimo
    FROM estoque s
    INNER JOIN epis e ON e.id = s.id_epi
    ORDER BY (s.quantidade <= s.quantidade_minima) DESC, e.nome
");

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
                            <th>CA</th>
                            <th>Quantidade</th>
                            <th>Estoque mínimo</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!$linhas): ?>
                            <tr>
                                <td colspan="5">
                                    Nenhum item de estoque cadastrado.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($linhas as $l): ?>
                                <?php
                                $qtd    = (int)$l['quantidade'];
                                $minimo = (int)$l['minimo'];

                                if ($qtd <= 0) {
                                    $classeStatus = 'status-alerta';
                                    $textoStatus  = 'Sem estoque';
                                } elseif ($qtd <= $minimo) {
                                    $classeStatus = 'status-aviso';
                                    $textoStatus  = 'Estoque baixo';
                                } else {
                                    $classeStatus = 'status-ok';
                                    $textoStatus  = 'Normal';
                                }
                                ?>
                                <tr>
                                    <td><?= h($l['nome']) ?></td>
                                    <td><?= h($l['ca'] ?: '-') ?></td>
                                    <td><?= h($qtd) ?></td>
                                    <td><?= h($minimo) ?></td>
                                    <td>
                                        <span class="status <?= $classeStatus ?>">
                                            <?= h($textoStatus) ?>
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