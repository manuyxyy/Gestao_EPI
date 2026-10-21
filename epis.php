<?php
require_once 'conexao.php';
require_once 'layout.php';

/** @var PDO $conn */ // Informa o editor sobre a origem da variável $conn

$mensagem = '';
$tipoMensagem = 'sucesso'; // Controla a classe CSS do alerta (sucesso ou erro)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        $q = $conn->prepare("
            INSERT INTO epis
            (nome, categoria, ca, validade_ca, descricao, status)
            VALUES (?, ?, ?, ?, ?, 'Ativo')
        ");

        $validade = trim($_POST['validade_ca'] ?? '');

        $q->execute([
            trim($_POST['nome'] ?? ''),
            trim($_POST['categoria'] ?? ''),
            trim($_POST['ca'] ?? ''),
            $validade ?: null,
            trim($_POST['descricao'] ?? '')
        ]);

        $epiId = (int)$conn->lastInsertId();
        $qtdInicial = max(0, (int)($_POST['quantidade'] ?? 0));

        $estoque = $conn->prepare("
            INSERT INTO estoque
            (epi_id, quantidade, minimo, localizacao)
            VALUES (?, ?, ?, ?)
        ");

        $estoque->execute([
            $epiId,
            $qtdInicial,
            max(0, (int)($_POST['minimo'] ?? 5)),
            trim($_POST['localizacao'] ?? 'Almoxarifado')
        ]);

        // Registra a movimentação inicial caso tenha sido cadastrada uma quantidade > 0
        if ($qtdInicial > 0) {
            $mov = $conn->prepare("
                INSERT INTO movimentacoes
                (epi_id, tipo, quantidade, descricao)
                VALUES (?, 'Entrada', ?, 'Entrada inicial de cadastro')
            ");
            $mov->execute([$epiId, $qtdInicial]);
        }

        $conn->commit();
        $mensagem = 'EPI cadastrado e adicionado ao estoque com sucesso.';
        $tipoMensagem = 'sucesso';

    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        $mensagem = 'Não foi possível cadastrar o EPI. Confira os campos.';
        $tipoMensagem = 'erro';
    }
}

$linhas = buscar_todos(
    $conn,
    "SELECT p.nome, p.categoria, p.ca, p.validade_ca,
            p.status, COALESCE(s.quantidade, 0) AS quantidade
     FROM epis p
     LEFT JOIN estoque s ON s.epi_id = p.id
     ORDER BY p.nome"
);

cabecalho(
    'Cadastro de EPIs',
    'Gerencie os equipamentos de proteção individual'
);
?>

<?php if ($mensagem): ?>
    <p class="alerta <?= h($tipoMensagem) ?>"><?= h($mensagem) ?></p>
<?php endif; ?>

<div class="content-card">

    <h2>Novo EPI</h2>

    <form method="POST" class="form-grid">

        <div>
            <label>Nome do EPI</label>
            <input name="nome" required>
        </div>

        <div>
            <label>Categoria</label>
            <input name="categoria">
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
            <label>Localização</label>
            <input name="localizacao" value="Almoxarifado">
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
                    <th>Categoria</th>
                    <th>CA</th>
                    <th>Validade</th>
                    <th>Quantidade</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!$linhas): ?>
                    <tr>
                        <td colspan="6">Nenhum EPI cadastrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($linhas as $l): ?>
                        <tr>
                            <td><?= h($l['nome']) ?></td>
                            <td><?= h($l['categoria'] ?: '-') ?></td>
                            <td><?= h($l['ca'] ?: '-') ?></td>
                            <td><?= h($l['validade_ca'] ? date('d/m/Y', strtotime($l['validade_ca'])) : '-') ?></td>
                            <td><?= h($l['quantidade']) ?></td>
                            <td><?= h($l['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php rodape(); ?>