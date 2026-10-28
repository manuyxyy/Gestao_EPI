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
        $nome  = trim((string)($_POST['nome'] ?? ''));
        $cpf   = preg_replace('/\D/', '', (string)($_POST['cpf'] ?? ''));
        $setor = trim((string)($_POST['setor'] ?? ''));
        $cargo = trim((string)($_POST['cargo'] ?? ''));
        $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));

        if ($acao === 'excluir') {
            $id = (int)($_POST['id'] ?? 0);

            $stmt = $pdo->prepare("SELECT nome FROM funcionarios WHERE id = ?");
            $stmt->execute([$id]);
            $nomeFuncionario = $stmt->fetchColumn();

            if (!$nomeFuncionario) {
                throw new RuntimeException('Funcionário não encontrado.');
            }

            $vinculos = $pdo->prepare("SELECT COUNT(*) FROM entregas WHERE id_funcionario = ?");
            $vinculos->execute([$id]);

            if ((int)$vinculos->fetchColumn() > 0) {
                throw new InvalidArgumentException(
                    'Não é possível excluir: existem entregas registradas para este funcionário.'
                );
            }

            $pdo->prepare("DELETE FROM funcionarios WHERE id = ?")->execute([$id]);

            flash('sucesso', 'Funcionário "' . $nomeFuncionario . '" excluído.');
            redirecionar('funcionarios.php');
        }

        if ($nome === '') {
            throw new InvalidArgumentException('Informe o nome do funcionário.');
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Informe um e-mail válido.');
        }

        if ($cpf !== '' && strlen($cpf) !== 11) {
            throw new InvalidArgumentException('O CPF deve ter 11 dígitos.');
        }

        $cpfFormatado = $cpf !== ''
            ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf)
            : null;

        if ($cpfFormatado !== null) {
            $verifica = $pdo->prepare("SELECT COUNT(*) FROM funcionarios WHERE cpf = ? AND id <> ?");
            $verifica->execute([$cpfFormatado, (int)($_POST['id'] ?? 0)]);

            if ((int)$verifica->fetchColumn() > 0) {
                throw new InvalidArgumentException('Já existe um funcionário com esse CPF.');
            }
        }

        if ($acao === 'atualizar') {
            $id = (int)($_POST['id'] ?? 0);

            $pdo->prepare("
                UPDATE funcionarios
                   SET nome = ?, cpf = ?, setor = ?, cargo = ?, email = ?
                 WHERE id = ?
            ")->execute([
                $nome,
                $cpfFormatado,
                $setor !== '' ? $setor : null,
                $cargo !== '' ? $cargo : null,
                $email !== '' ? $email : null,
                $id,
            ]);

            flash('sucesso', 'Funcionário "' . $nome . '" atualizado.');
            redirecionar('funcionarios.php');
        }

        $pdo->prepare("
            INSERT INTO funcionarios (nome, cpf, setor, cargo, email)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([
            $nome,
            $cpfFormatado,
            $setor !== '' ? $setor : null,
            $cargo !== '' ? $cargo : null,
            $email !== '' ? $email : null,
        ]);

        flash('sucesso', 'Funcionário "' . $nome . '" cadastrado com sucesso.');
        redirecionar('funcionarios.php');
    } catch (InvalidArgumentException | RuntimeException $e) {
        flash('erro', $e->getMessage());
        redirecionar('funcionarios.php');
    } catch (PDOException $e) {
        error_log('Erro em funcionários: ' . $e->getMessage());
        flash('erro', 'Não foi possível salvar. Confira os dados (o CPF pode já estar cadastrado).');
        redirecionar('funcionarios.php');
    }
}

/* =========================================================================
 * Consultas
 * ====================================================================== */
$editando = null;

if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM funcionarios WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$_GET['editar']]);
    $editando = $stmt->fetch() ?: null;
}

$linhas = buscar_todos($pdo, "
    SELECT f.id, f.nome, f.cpf, f.setor, f.cargo, f.email,
           COUNT(en.id) AS total_entregas
      FROM funcionarios f
      LEFT JOIN entregas en ON en.id_funcionario = f.id
     GROUP BY f.id, f.nome, f.cpf, f.setor, f.cargo, f.email
     ORDER BY f.nome
");

$total    = count($linhas);
$setores  = count(array_unique(array_filter(array_column($linhas, 'setor'))));
$comEmail = count(array_filter($linhas, fn($f) => !empty($f['email'])));

cabecalho(
    'Funcionários',
    'Cadastre e consulte os colaboradores que recebem EPIs',
    '<a class="botao botao-sec" href="entregas.php">' . icone('entregas', 17) . ' Registrar entrega</a>'
);
?>

<div class="stats-grid">
    <article class="stat-card blue">
        <div class="stat-top">
            <span class="stat-label">Funcionários</span>
            <span class="stat-icon"><?= icone('funcionarios', 19) ?></span>
        </div>
        <strong><?= $total ?></strong>
        <small>Colaboradores cadastrados</small>
    </article>

    <article class="stat-card green">
        <div class="stat-top">
            <span class="stat-label">Setores atendidos</span>
            <span class="stat-icon"><?= icone('setor', 19) ?></span>
        </div>
        <strong><?= $setores ?></strong>
        <small>Setores distintos</small>
    </article>

    <article class="stat-card pale">
        <div class="stat-top">
            <span class="stat-label">Com e-mail</span>
            <span class="stat-icon"><?= icone('email', 19) ?></span>
        </div>
        <strong><?= $comEmail ?></strong>
        <small>Contatos registrados</small>
    </article>
</div>

<?php if ($editando): ?>
    <div class="content-card">
        <div class="card-heading">
            <div>
                <h2>Editar funcionário</h2>
                <p class="card-sub"><?= h($editando['nome']) ?></p>
            </div>
            <a class="botao botao-sec botao-sm" href="funcionarios.php"><?= icone('x', 15) ?> Cancelar</a>
        </div>

        <form method="POST" class="form-grid">
            <?= csrf_campo() ?>
            <input type="hidden" name="acao" value="atualizar">
            <input type="hidden" name="id" value="<?= (int)$editando['id'] ?>">

            <div class="campo-largo">
                <label for="edit-nome">Nome completo <span class="obrigatorio">*</span></label>
                <input id="edit-nome" name="nome" required value="<?= h($editando['nome']) ?>">
            </div>

            <div>
                <label for="edit-cpf">CPF</label>
                <input id="edit-cpf" name="cpf" maxlength="14" placeholder="000.000.000-00"
                       value="<?= h($editando['cpf'] ?? '') ?>">
            </div>

            <div>
                <label for="edit-setor">Setor</label>
                <input id="edit-setor" name="setor" value="<?= h($editando['setor'] ?? '') ?>">
            </div>

            <div>
                <label for="edit-cargo">Cargo</label>
                <input id="edit-cargo" name="cargo" value="<?= h($editando['cargo'] ?? '') ?>">
            </div>

            <div>
                <label for="edit-email">E-mail</label>
                <input id="edit-email" type="email" name="email" value="<?= h($editando['email'] ?? '') ?>">
            </div>

            <div class="campo-largo form-actions">
                <button class="botao" type="submit"><?= icone('salvar', 17) ?> Salvar alterações</button>
                <a class="botao botao-ghost" href="funcionarios.php">Cancelar</a>
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="content-card">
        <div class="card-heading">
            <div>
                <h2>Cadastrar funcionário</h2>
                <p class="card-sub">O CPF é opcional, mas não pode se repetir.</p>
            </div>
        </div>

        <form method="POST" class="form-grid">
            <?= csrf_campo() ?>
            <input type="hidden" name="acao" value="criar">

            <div class="campo-largo">
                <label for="nome">Nome completo <span class="obrigatorio">*</span></label>
                <input id="nome" name="nome" required maxlength="100" placeholder="Ex.: João Pereira Lima">
            </div>

            <div>
                <label for="cpf">CPF</label>
                <input id="cpf" name="cpf" maxlength="14" inputmode="numeric"
                       placeholder="000.000.000-00" data-mascara-cpf>
            </div>

            <div>
                <label for="setor">Setor</label>
                <input id="setor" name="setor" maxlength="100" placeholder="Ex.: Manutenção">
            </div>

            <div>
                <label for="cargo">Cargo</label>
                <input id="cargo" name="cargo" maxlength="100" placeholder="Ex.: Técnico de segurança">
            </div>

            <div>
                <label for="email">E-mail</label>
                <input id="email" type="email" name="email" maxlength="100" placeholder="joao@empresa.com.br">
            </div>

            <div class="campo-largo form-actions">
                <button class="botao" type="submit"><?= icone('mais', 17) ?> Cadastrar funcionário</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<div class="content-card">
    <div class="card-heading">
        <div>
            <h2>Funcionários cadastrados</h2>
            <p class="card-sub">Clique nos títulos das colunas para ordenar.</p>
        </div>
        <span class="table-count" data-table-count="tabela-funcionarios"><?= $total ?> registros</span>
    </div>

    <div class="table-tools">
        <label class="search-box">
            <?= icone('busca', 17) ?>
            <input type="search" placeholder="Buscar por nome, CPF, setor, cargo ou e-mail..."
                   data-table-search="tabela-funcionarios" aria-label="Buscar funcionários">
        </label>
    </div>

    <div class="table-wrap">
        <table id="tabela-funcionarios" data-table>
            <thead>
                <tr>
                    <th data-sort="texto">Funcionário</th>
                    <th data-sort="texto">CPF</th>
                    <th data-sort="texto">Setor</th>
                    <th data-sort="texto">Cargo</th>
                    <th data-sort="num">Entregas</th>
                    <th class="center">Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!$linhas): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <span class="empty-icon"><?= icone('funcionarios', 24) ?></span>
                                <strong>Nenhum funcionário cadastrado</strong>
                                <p>Cadastre o primeiro colaborador para poder registrar entregas de EPI.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($linhas as $f): ?>
                        <tr data-row>
                            <td>
                                <div class="cell-user">
                                    <span class="avatar avatar-azul"><?= h(mb_strtoupper(mb_substr((string)$f['nome'], 0, 1))) ?></span>
                                    <span>
                                        <strong><?= h($f['nome']) ?></strong>
                                        <small><?= h($f['email'] ?: 'Sem e-mail cadastrado') ?></small>
                                    </span>
                                </div>
                            </td>
                            <td class="cell-mono"><?= h($f['cpf'] ?: '—') ?></td>
                            <td><?= h($f['setor'] ?: '—') ?></td>
                            <td><?= h($f['cargo'] ?: '—') ?></td>
                            <td class="num">
                                <?php if ((int)$f['total_entregas'] > 0): ?>
                                    <span class="status status-info"><?= (int)$f['total_entregas'] ?></span>
                                <?php else: ?>
                                    <span class="cell-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="acoes">
                                    <a class="botao botao-sec" href="funcionarios.php?editar=<?= (int)$f['id'] ?>">
                                        <?= icone('editar', 15) ?> Editar
                                    </a>
                                    <form method="POST" data-confirm="Excluir o funcionário <?= h($f['nome']) ?>?">
                                        <?= csrf_campo() ?>
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                                        <button type="submit" class="botao botao-perigo">
                                            <?= icone('excluir', 15) ?> Excluir
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <tr data-empty-filtro hidden>
                        <td colspan="6">
                            <div class="empty-state compact">
                                <strong>Nenhum funcionário encontrado</strong>
                                <p>Ajuste os termos da busca.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    var campo = document.querySelector('[data-mascara-cpf]');
    if (!campo) return;
    campo.addEventListener('input', function () {
        var v = campo.value.replace(/\D/g, '').slice(0, 11);
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        campo.value = v;
    });
})();
</script>

<?php rodape(); ?>
