<?php
require_once 'conexao.php';
require_once 'funcoes.php';   // onde está o buscar_todos()
require_once 'layout.php';

/** @var PDO $pdo */

$linhas = buscar_todos($pdo, "
    SELECT
        nome,
        NULL AS categoria,
        numero_ca AS ca,
        validade AS validade_ca
    FROM epis
    WHERE validade IS NOT NULL
    ORDER BY validade ASC
");

cabecalho(
    'Controle de validade',
    'Confira os Certificados de Aprovação e suas datas'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="validade.css">
</head>
<body>

        <div class="content-card">

            <h2>Validade dos EPIs</h2>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>EPI</th>
                            <th>Categoria</th>
                            <th>CA</th>
                            <th>Validade</th>
                            <th>Dias restantes</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!$linhas): ?>
                            <tr>
                                <td colspan="6">
                                    Nenhuma validade cadastrada.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($linhas as $l): ?>
                                <?php
                                $dias = (int)floor(
                                    (strtotime($l['validade_ca']) - strtotime(date('Y-m-d'))) / 86400
                                );

                                if ($dias < 0) {
                                    $classeStatus = 'status-alerta';
                                    $textoStatus  = 'Vencido';
                                } elseif ($dias <= 30) {
                                    $classeStatus = 'status-aviso';
                                    $textoStatus  = 'Próximo do vencimento';
                                } else {
                                    $classeStatus = 'status-ok';
                                    $textoStatus  = 'Válido';
                                }
                                ?>

                                <tr>
                                    <td><?= h($l['nome']) ?></td>
                                    <td><?= h($l['categoria'] ?: '-') ?></td>
                                    <td><?= h($l['ca'] ?: '-') ?></td>
                                    <td><?= h(date('d/m/Y', strtotime($l['validade_ca']))) ?></td>
                                    <td><?= h($dias) ?></td>
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