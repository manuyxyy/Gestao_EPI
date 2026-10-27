<?php
require_once 'conexao.php';
require_once 'funcoes.php';   // buscar_todos()
require_once 'layout.php';

/** @var PDO $pdo */

$mensagem = '';
$tipoMensagem = 'sucesso'; // Controla a classe CSS do alerta (sucesso ou erro)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nome = trim($_POST['nome'] ?? '');

        if ($nome === '') {
            throw new Exception('Informe o nome do EPI.');
        }

        $pdo->beginTransaction();

        $q = $pdo->prepare("
            INSERT INTO epis
            (nome, descricao, numero_ca, validade)
            VALUES (?, ?, ?, ?)
        ");

        $q->execute([
            $nome,
            trim($_POST['descricao'] ?? '') ?: null,
            trim($_POST['ca'] ?? '')        ?: null,
            trim($_POST['validade_ca'] ?? '') ?: null
        ]);

        $epiId = (int)$pdo->lastInsertId();
        $qtdInicial = max(0, (int)($_POST['quantidade'] ?? 0));

        $estoque = $pdo->prepare("
            INSERT INTO estoque
            (id_epi, quantidade, quantidade_minima)
            VALUES (?, ?, ?)
        ");

        $estoque->execute([
            $epiId,
            $qtdInicial,
            max(0, (int)($_POST['minimo'] ?? 5))
        ]);

        // Registra a movimentação inicial caso tenha sido cadastrada uma quantidade > 0
        if ($qtdInicial > 0) {
            $mov = $pdo->prepare("
                INSERT INTO movimentacoes
                (id_epi, tipo, quantidade, observacao)
                VALUES (?, 'Entrada', ?, 'Entrada inicial de cadastro')
            ");
            $mov->execute([$epiId, $qtdInicial]);
        }

        $pdo->commit();
        $mensagem = 'EPI cadastrado e adicionado ao estoque com sucesso.';
        $tipoMensagem = 'sucesso';

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $mensagem = ($e instanceof PDOException)
            ? 'Não foi possível cadastrar o EPI. Confira os campos.'
            : $e->getMessage();
        $tipoMensagem = 'erro';
    }
}

// Consultado depois do POST para já mostrar o novo EPI
$linhas = buscar_todos($pdo, "
    SELECT
        e.id,
        e.nome,
        e.numero_ca AS ca,
        e.validade  AS validade_ca,
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

cabecalho(
    'Cadastro de EPIs',
    'Gerencie os equipamentos de proteção individual'
);
?>

<?php if ($mensagem): ?>
    <p class="alerta <?= h($tipoMensagem) ?>"><?= h($mensagem) ?></p>
<?php endif; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="epis.css">
</head>
<body>
        <div class="content-card">

            <h2>Novo EPI</h2>

            <form method="POST" class="form-grid">

                <div>
                    <label>Nome do EPI</label>
                    <input name="nome" required>
                </div>

                <div>
                    <label>Certificado de Aprovação (CA)</label>
                    <input name="ca">
                </div>

                <div>
                    <label>Validade do CA</label>
                    <input type="date" name="validade_ca">
                </div>

                <div>
                    <label>Quantidade inicial</label>
                    <input type="number" name="quantidade"
                        min="0" value="0">
                </div>

                <div>
                    <label>Estoque mínimo</label>
                    <input type="number" name="minimo"
                        min="0" value="5">
                </div>

                <div>
                    <label>Descrição</label>
                    <input name="descricao">
                </div>

                <div class="form-action">
                    <button class="botao" type="submit">
                        + Cadastrar EPI
                    </button>
                </div>

            </form>
        </div>

        <div class="content-card">

            <h2>EPIs cadastrados</h2>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CA</th>
                            <th>Validade</th>
                            <th>Quantidade</th>
                            <th>Situação</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!$linhas): ?>
                            <tr>
                                <td colspan="5">Nenhum EPI cadastrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($linhas as $l): ?>
                                <?php
                                // Situação calculada a partir da validade e do estoque
                                $classeStatus = 'status-ok';
                                $textoStatus  = 'Válido';

                                if ($l['validade_ca']) {
                                    $dias = (int)floor(
                                        (strtotime($l['validade_ca']) - strtotime(date('Y-m-d'))) / 86400
                                    );
                                } else {
                                    $dias = null;
                                }

                                if ($dias !== null && $dias < 0) {
                                    $classeStatus = 'status-alerta';
                                    $textoStatus  = 'CA vencido';
                                } elseif ((int)$l['quantidade'] <= (int)$l['minimo']) {
                                    $classeStatus = 'status-aviso';
                                    $textoStatus  = 'Estoque baixo';
                                } elseif ($dias !== null && $dias <= 30) {
                                    $classeStatus = 'status-aviso';
                                    $textoStatus  = 'CA vence em breve';
                                } elseif ($dias === null) {
                                    $textoStatus  = 'Sem validade';
                                }
                                ?>
                                <tr>
                                    <td><?= h($l['nome']) ?></td>
                                    <td><?= h($l['ca'] ?: '-') ?></td>
                                    <td><?= h($l['validade_ca'] ? date('d/m/Y', strtotime($l['validade_ca'])) : '-') ?></td>
                                    <td><?= h($l['quantidade']) ?></td>
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