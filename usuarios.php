<?php
require_once 'conexao.php';
require_once 'funcoes.php';
require_once __DIR__ . '/layout.php';

/** @var PDO $pdo */

exigir_perfil(['Administrador']);

const STATUS_VALIDOS = ['Ativo', 'Inativo'];

$erros = [];

/* =========================================================================
 * Ações (POST)
 * ====================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_proteger();

    $acao = (string)($_POST['acao'] ?? '');

    try {
        /* ------------------------------------------------------------ Criar */
        if ($acao === 'criar') {
            $nome       = trim((string)($_POST['nome'] ?? ''));
            $email      = mb_strtolower(trim((string)($_POST['email'] ?? '')));
            $senha      = (string)($_POST['senha'] ?? '');
            $tipoAcesso = (string)($_POST['tipo_acesso'] ?? 'Tecnico');
            $setor      = trim((string)($_POST['setor'] ?? ''));
            $telefone   = trim((string)($_POST['telefone'] ?? ''));
            $status     = (string)($_POST['status'] ?? 'Ativo');

            if (mb_strlen($nome) < 3) {
                throw new InvalidArgumentException('Informe o nome completo (mínimo 3 caracteres).');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Informe um e-mail válido.');
            }
            if (!in_array($tipoAcesso, perfis_validos(), true)) {
                throw new InvalidArgumentException('Perfil de acesso inválido.');
            }
            if (!in_array($status, STATUS_VALIDOS, true)) {
                throw new InvalidArgumentException('Situação inválida.');
            }
            if (mb_strlen($senha) < 8) {
                throw new InvalidArgumentException('A senha deve ter pelo menos 8 caracteres.');
            }

            $existe = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
            $existe->execute([$email]);

            if ((int)$existe->fetchColumn() > 0) {
                throw new InvalidArgumentException('Já existe um usuário com esse e-mail.');
            }

            $stmt = $pdo->prepare("
                INSERT INTO usuarios
                    (nome, email, senha, tipo_acesso, funcao, setor, telefone, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $nome,
                $email,
                password_hash($senha, PASSWORD_DEFAULT),
                $tipoAcesso,
                $tipoAcesso,
                $setor !== '' ? $setor : null,
                $telefone !== '' ? $telefone : null,
                $status,
            ]);

            flash('sucesso', 'Usuário "' . $nome . '" cadastrado com sucesso.');
            redirecionar('usuarios.php');
        }

        /* --------------------------------------------------------- Atualizar */
        if ($acao === 'atualizar') {
            $id         = (int)($_POST['id'] ?? 0);
            $nome       = trim((string)($_POST['nome'] ?? ''));
            $email      = mb_strtolower(trim((string)($_POST['email'] ?? '')));
            $senha      = (string)($_POST['senha'] ?? '');
            $tipoAcesso = (string)($_POST['tipo_acesso'] ?? 'Tecnico');
            $setor      = trim((string)($_POST['setor'] ?? ''));
            $telefone   = trim((string)($_POST['telefone'] ?? ''));
            $status     = (string)($_POST['status'] ?? 'Ativo');

            if ($id <= 0) {
                throw new InvalidArgumentException('Usuário inválido.');
            }
            if (mb_strlen($nome) < 3) {
                throw new InvalidArgumentException('Informe o nome completo (mínimo 3 caracteres).');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Informe um e-mail válido.');
            }
            if (!in_array($tipoAcesso, perfis_validos(), true) || !in_array($status, STATUS_VALIDOS, true)) {
                throw new InvalidArgumentException('Dados de perfil inválidos.');
            }
            if ($senha !== '' && mb_strlen($senha) < 8) {
                throw new InvalidArgumentException('A nova senha deve ter pelo menos 8 caracteres.');
            }

            $atual = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? LIMIT 1");
            $atual->execute([$id]);
            $usuario = $atual->fetch();

            if (!$usuario) {
                throw new RuntimeException('Usuário não encontrado.');
            }

            // Não permite que o administrador logado se tranque para fora.
            if ($id === usuario_id()) {
                if ($tipoAcesso !== 'Administrador' || $status !== 'Ativo') {
                    throw new InvalidArgumentException(
                        'Você não pode remover o próprio acesso de administrador nem desativar sua conta.'
                    );
                }
            }

            // Não pode ficar sem nenhum administrador ativo.
            if (
                normalizar_perfil($usuario['tipo_acesso']) === 'Administrador'
                && status_ativo($usuario['status'])
                && ($tipoAcesso !== 'Administrador' || $status !== 'Ativo')
            ) {
                $admins = (int)$pdo->query(
                    "SELECT COUNT(*) FROM usuarios
                      WHERE tipo_acesso = 'Administrador' AND status = 'Ativo'"
                )->fetchColumn();

                if ($admins <= 1) {
                    throw new InvalidArgumentException(
                        'O sistema precisa de pelo menos um administrador ativo.'
                    );
                }
            }

            $existe = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ? AND id <> ?");
            $existe->execute([$email, $id]);

            if ((int)$existe->fetchColumn() > 0) {
                throw new InvalidArgumentException('Já existe outro usuário com esse e-mail.');
            }

            $sql = "
                UPDATE usuarios
                   SET nome = ?, email = ?, tipo_acesso = ?, funcao = ?,
                       setor = ?, telefone = ?, status = ?
            ";
            $params = [
                $nome,
                $email,
                $tipoAcesso,
                $tipoAcesso,
                $setor !== '' ? $setor : null,
                $telefone !== '' ? $telefone : null,
                $status,
            ];

            if ($senha !== '') {
                $sql .= ", senha = ?";
                $params[] = password_hash($senha, PASSWORD_DEFAULT);
            }

            $sql .= " WHERE id = ?";
            $params[] = $id;

            $pdo->prepare($sql)->execute($params);

            // Se o administrador editou o próprio nome/e-mail, atualiza a sessão.
            if ($id === usuario_id()) {
                $_SESSION['usuario_nome']  = $nome;
                $_SESSION['usuario_email'] = $email;
                $_SESSION['usuario_perfil'] = $tipoAcesso;
                $_SESSION['perfil'] = $tipoAcesso;
            }

            flash('sucesso', 'Dados de "' . $nome . '" atualizados.');
            redirecionar('usuarios.php');
        }

        /* ------------------------------------------------- Alternar situação */
        if ($acao === 'alternar') {
            $id = (int)($_POST['id'] ?? 0);

            if ($id === usuario_id()) {
                throw new InvalidArgumentException('Você não pode desativar a própria conta.');
            }

            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $usuario = $stmt->fetch();

            if (!$usuario) {
                throw new RuntimeException('Usuário não encontrado.');
            }

            $novoStatus = status_ativo($usuario['status']) ? 'Inativo' : 'Ativo';

            if (
                $novoStatus === 'Inativo'
                && normalizar_perfil($usuario['tipo_acesso']) === 'Administrador'
            ) {
                $admins = (int)$pdo->query(
                    "SELECT COUNT(*) FROM usuarios
                      WHERE tipo_acesso = 'Administrador' AND status = 'Ativo'"
                )->fetchColumn();

                if ($admins <= 1) {
                    throw new InvalidArgumentException(
                        'O sistema precisa de pelo menos um administrador ativo.'
                    );
                }
            }

            $pdo->prepare("UPDATE usuarios SET status = ? WHERE id = ?")
                ->execute([$novoStatus, $id]);

            flash('sucesso', 'Usuário "' . $usuario['nome'] . '" agora está ' . mb_strtolower($novoStatus) . '.');
            redirecionar('usuarios.php');
        }

        /* ------------------------------------------------------- Redefinir senha */
        if ($acao === 'redefinir') {
            $id = (int)($_POST['id'] ?? 0);

            $stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            $nomeUsuario = $stmt->fetchColumn();

            if (!$nomeUsuario) {
                throw new RuntimeException('Usuário não encontrado.');
            }

            $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
            $temporaria = '';
            for ($i = 0; $i < 10; $i++) {
                $temporaria .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
            }

            $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?")
                ->execute([password_hash($temporaria, PASSWORD_DEFAULT), $id]);

            flash(
                'aviso',
                'Senha temporária de ' . $nomeUsuario . ': ' . $temporaria
                . ' — repasse com segurança e peça a troca no primeiro acesso.'
            );
            redirecionar('usuarios.php');
        }

        /* ------------------------------------------------------------ Excluir */
        if ($acao === 'excluir') {
            $id = (int)($_POST['id'] ?? 0);

            if ($id === usuario_id()) {
                throw new InvalidArgumentException('Você não pode excluir a própria conta.');
            }

            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $usuario = $stmt->fetch();

            if (!$usuario) {
                throw new RuntimeException('Usuário não encontrado.');
            }

            if (
                normalizar_perfil($usuario['tipo_acesso']) === 'Administrador'
                && status_ativo($usuario['status'])
            ) {
                $admins = (int)$pdo->query(
                    "SELECT COUNT(*) FROM usuarios
                      WHERE tipo_acesso = 'Administrador' AND status = 'Ativo'"
                )->fetchColumn();

                if ($admins <= 1) {
                    throw new InvalidArgumentException(
                        'Não é possível excluir o último administrador ativo.'
                    );
                }
            }

            $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$id]);

            flash('sucesso', 'Usuário "' . $usuario['nome'] . '" excluído.');
            redirecionar('usuarios.php');
        }

        throw new InvalidArgumentException('Ação não reconhecida.');
    } catch (InvalidArgumentException | RuntimeException $e) {
        flash('erro', $e->getMessage());
        redirecionar('usuarios.php');
    } catch (PDOException $e) {
        error_log('Erro em usuários: ' . $e->getMessage());
        flash('erro', 'Não foi possível concluir a operação. Verifique os dados e tente novamente.');
        redirecionar('usuarios.php');
    }
}

/* =========================================================================
 * Consultas da tela
 * ====================================================================== */
$editando = null;

if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$_GET['editar']]);
    $editando = $stmt->fetch() ?: null;
}

$linhas = buscar_todos($pdo, "
    SELECT id, nome, email, tipo_acesso, funcao, setor, telefone, status,
           ultimo_acesso, criado_em
      FROM usuarios
     ORDER BY (status = 'Ativo') DESC, nome
");

$total    = count($linhas);
$ativos   = count(array_filter($linhas, fn($u) => status_ativo($u['status'])));
$inativos = $total - $ativos;
$admins   = count(array_filter(
    $linhas,
    fn($u) => normalizar_perfil($u['tipo_acesso']) === 'Administrador' && status_ativo($u['status'])
));

$acoesTopo = '<a class="botao botao-sec" href="perfil.php">' . icone('perfil', 17) . ' Meu perfil</a>';

cabecalho(
    'Usuários e permissões',
    'Cadastre, edite e controle quem pode acessar o sistema',
    $acoesTopo
);
?>

<div class="stats-grid">
    <article class="stat-card blue">
        <div class="stat-top">
            <span class="stat-label">Total de usuários</span>
            <span class="stat-icon"><?= icone('usuarios', 19) ?></span>
        </div>
        <strong><?= $total ?></strong>
        <small>Contas cadastradas</small>
    </article>

    <article class="stat-card green">
        <div class="stat-top">
            <span class="stat-label">Ativos</span>
            <span class="stat-icon"><?= icone('check', 19) ?></span>
        </div>
        <strong><?= $ativos ?></strong>
        <small>Com acesso liberado</small>
    </article>

    <article class="stat-card <?= $inativos > 0 ? 'orange' : 'pale' ?>">
        <div class="stat-top">
            <span class="stat-label">Inativos</span>
            <span class="stat-icon"><?= icone('x', 19) ?></span>
        </div>
        <strong><?= $inativos ?></strong>
        <small>Acesso bloqueado</small>
    </article>

    <article class="stat-card yellow">
        <div class="stat-top">
            <span class="stat-label">Administradores</span>
            <span class="stat-icon"><?= icone('escudo', 19) ?></span>
        </div>
        <strong><?= $admins ?></strong>
        <small>Com acesso total</small>
    </article>
</div>

<?php if ($editando): ?>
    <!-- ===================================================== Editar usuário -->
    <div class="content-card form-card">
        <div class="card-heading">
            <div>
                <h2>Editar usuário</h2>
                <p class="card-sub">
                    <?= h($editando['nome']) ?> · <?= h($editando['email']) ?> ·
                    cadastrado em <?= h(data_br($editando['criado_em'] ?? null)) ?>
                </p>
            </div>
            <a class="botao botao-sec botao-sm" href="usuarios.php"><?= icone('x', 15) ?> Cancelar edição</a>
        </div>

        <form method="POST" class="form-grid" autocomplete="off">
            <?= csrf_campo() ?>
            <input type="hidden" name="acao" value="atualizar">
            <input type="hidden" name="id" value="<?= (int)$editando['id'] ?>">

            <div class="campo-largo">
                <label for="edit-nome">Nome completo <span class="obrigatorio">*</span></label>
                <input id="edit-nome" name="nome" required minlength="3" maxlength="100"
                       value="<?= h($editando['nome']) ?>">
            </div>

            <div>
                <label for="edit-email">E-mail <span class="obrigatorio">*</span></label>
                <input id="edit-email" type="email" name="email" required maxlength="100"
                       value="<?= h($editando['email']) ?>">
            </div>

            <div>
                <label for="edit-senha">Nova senha</label>
                <input id="edit-senha" type="password" name="senha" minlength="8"
                       autocomplete="new-password" placeholder="Deixe em branco para manter">
                <p class="ajuda">Mínimo de 8 caracteres. Só preencha se quiser trocar.</p>
            </div>

            <div>
                <label for="edit-tipo">Perfil de acesso <span class="obrigatorio">*</span></label>
                <select id="edit-tipo" name="tipo_acesso" required>
                    <?php foreach (perfis_validos() as $perfil): ?>
                        <option value="<?= h($perfil) ?>"
                            <?= normalizar_perfil($editando['tipo_acesso']) === $perfil ? 'selected' : '' ?>>
                            <?= h(rotulo_perfil($perfil)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="edit-setor">Setor</label>
                <input id="edit-setor" name="setor" maxlength="100"
                       value="<?= h($editando['setor'] ?? '') ?>">
            </div>

            <div>
                <label for="edit-telefone">Telefone</label>
                <input id="edit-telefone" name="telefone" maxlength="20"
                       placeholder="(00) 00000-0000"
                       value="<?= h($editando['telefone'] ?? '') ?>">
            </div>

            <div>
                <label for="edit-status">Situação <span class="obrigatorio">*</span></label>
                <select id="edit-status" name="status" required>
                    <option value="Ativo" <?= status_ativo($editando['status']) ? 'selected' : '' ?>>Ativo</option>
                    <option value="Inativo" <?= !status_ativo($editando['status']) ? 'selected' : '' ?>>Inativo</option>
                </select>
            </div>

            <div class="campo-largo form-actions">
                <button class="botao" type="submit"><?= icone('salvar', 17) ?> Salvar alterações</button>
                <a class="botao botao-sec" href="perfil.php?id=<?= (int)$editando['id'] ?>"><?= icone('perfil', 17) ?> Ver perfil</a>
                <a class="botao botao-ghost" href="usuarios.php">Cancelar</a>
            </div>
        </form>
    </div>
<?php else: ?>
    <!-- ===================================================== Novo usuário -->
    <div class="content-card form-card">
        <div class="card-heading">
            <div>
                <h2>Novo usuário</h2>
                <p class="card-sub">A senha é gravada criptografada. O usuário pode alterá-la depois no perfil.</p>
            </div>
        </div>

        <form method="POST" class="form-grid" autocomplete="off">
            <?= csrf_campo() ?>
            <input type="hidden" name="acao" value="criar">

            <div class="campo-largo">
                <label for="nome">Nome completo <span class="obrigatorio">*</span></label>
                <input id="nome" name="nome" required minlength="3" maxlength="100"
                       placeholder="Ex.: Maria Silva Santos">
            </div>

            <div>
                <label for="email">E-mail <span class="obrigatorio">*</span></label>
                <input id="email" type="email" name="email" required maxlength="100"
                       placeholder="maria@empresa.com.br" autocomplete="off">
                <p class="ajuda">Será o login de acesso ao sistema.</p>
            </div>

            <div>
                <label for="senha">Senha inicial <span class="obrigatorio">*</span></label>
                <div class="campo-senha">
                    <input id="senha" type="password" name="senha" required minlength="8"
                           autocomplete="new-password" placeholder="Mínimo 8 caracteres">
                    <button type="button" class="ver-senha" data-toggle-senha="senha"
                            aria-label="Mostrar senha"><?= icone('olho', 17) ?></button>
                </div>
            </div>

            <div>
                <label for="tipo_acesso">Perfil de acesso <span class="obrigatorio">*</span></label>
                <select id="tipo_acesso" name="tipo_acesso" required>
                    <?php foreach (perfis_validos() as $perfil): ?>
                        <option value="<?= h($perfil) ?>" <?= $perfil === 'Tecnico' ? 'selected' : '' ?>>
                            <?= h(rotulo_perfil($perfil)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="ajuda">Administrador gerencia usuários e todo o sistema.</p>
            </div>

            <div>
                <label for="setor">Setor</label>
                <input id="setor" name="setor" maxlength="100" placeholder="Ex.: Produção">
            </div>

            <div>
                <label for="telefone">Telefone</label>
                <input id="telefone" name="telefone" maxlength="20" placeholder="(00) 00000-0000">
            </div>

            <div>
                <label for="status">Situação <span class="obrigatorio">*</span></label>
                <select id="status" name="status" required>
                    <option value="Ativo" selected>Ativo</option>
                    <option value="Inativo">Inativo</option>
                </select>
            </div>

            <div class="campo-largo form-actions">
                <button class="botao" type="submit"><?= icone('mais', 17) ?> Cadastrar usuário</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- ===================================================== Lista -->
<div class="content-card">
    <div class="card-heading">
        <div>
            <h2>Usuários cadastrados</h2>
            <p class="card-sub">Clique nos títulos das colunas para ordenar.</p>
        </div>
        <div class="card-heading-actions">
            <span class="table-count" data-table-count="tabela-usuarios"><?= $total ?> registros</span>
        </div>
    </div>

    <div class="table-tools">
        <label class="search-box">
            <?= icone('busca', 17) ?>
            <input type="search" placeholder="Buscar por nome, e-mail ou setor..."
                   data-table-search="tabela-usuarios" aria-label="Buscar usuários">
        </label>

        <select class="table-filter" data-table-filter="tabela-usuarios"
                data-filter-key="perfil" aria-label="Filtrar por perfil">
            <option value="">Todos os perfis</option>
            <?php foreach (perfis_validos() as $perfil): ?>
                <option value="<?= h($perfil) ?>"><?= h(rotulo_perfil($perfil)) ?></option>
            <?php endforeach; ?>
        </select>

        <select class="table-filter" data-table-filter="tabela-usuarios"
                data-filter-key="status" aria-label="Filtrar por situação">
            <option value="">Todas as situações</option>
            <option value="Ativo">Ativos</option>
            <option value="Inativo">Inativos</option>
        </select>
    </div>

    <div class="table-wrap">
        <table id="tabela-usuarios" data-table>
            <thead>
                <tr>
                    <th data-sort="texto">Usuário</th>
                    <th data-sort="texto">Perfil</th>
                    <th data-sort="texto">Setor</th>
                    <th>Contato</th>
                    <th data-sort="texto">Situação</th>
                    <th data-sort="texto">Último acesso</th>
                    <th class="center">Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!$linhas): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state compact">
                                <span class="empty-icon"><?= icone('usuarios', 24) ?></span>
                                <strong>Nenhum usuário cadastrado</strong>
                                <p>Use o formulário acima para criar o primeiro acesso.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($linhas as $u): ?>
                        <?php
                        $perfil   = normalizar_perfil($u['tipo_acesso']);
                        $ativo    = status_ativo($u['status']);
                        $inicial  = mb_strtoupper(mb_substr((string)$u['nome'], 0, 1));
                        ?>
                        <tr data-row
                            data-perfil="<?= h($perfil) ?>"
                            data-status="<?= h($ativo ? 'Ativo' : 'Inativo') ?>">
                            <td>
                                <div class="cell-user">
                                    <span class="avatar avatar-azul"><?= h($inicial) ?></span>
                                    <span>
                                        <strong>
                                            <?= h($u['nome']) ?>
                                            <?php if ((int)$u['id'] === usuario_id()): ?>
                                                <span class="mini-tag">você</span>
                                            <?php endif; ?>
                                        </strong>
                                        <small><?= h($u['email']) ?></small>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="chip <?= classe_perfil($perfil) ?>"><?= h(rotulo_perfil($perfil)) ?></span>
                            </td>
                            <td><?= h($u['setor'] ?: '—') ?></td>
                            <td class="cell-muted"><?= h($u['telefone'] ?: '—') ?></td>
                            <td>
                                <span class="status <?= $ativo ? 'status-ok' : 'status-alerta' ?>">
                                    <?= h($ativo ? 'Ativo' : 'Inativo') ?>
                                </span>
                            </td>
                            <td class="cell-muted">
                                <?php if (!empty($u['ultimo_acesso'])): ?>
                                    <?= h(data_br($u['ultimo_acesso'], true)) ?>
                                <?php else: ?>
                                    Nunca acessou
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="acoes">
                                    <a class="botao botao-sec" href="perfil.php?id=<?= (int)$u['id'] ?>"
                                       title="Ver perfil de <?= h($u['nome']) ?>" aria-label="Ver perfil de <?= h($u['nome']) ?>">
                                        <?= icone('perfil', 15) ?>
                                    </a>

                                    <a class="botao botao-sec" href="usuarios.php?editar=<?= (int)$u['id'] ?>"
                                       title="Editar usuário"><?= icone('editar', 15) ?> Editar</a>

                                    <form method="POST" data-confirm="Alternar a situação de <?= h($u['nome']) ?>?">
                                        <?= csrf_campo() ?>
                                        <input type="hidden" name="acao" value="alternar">
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <button type="submit"
                                                class="botao <?= $ativo ? 'botao-perigo' : 'botao-sucesso' ?>"
                                                <?= (int)$u['id'] === usuario_id() ? 'disabled title="Você não pode desativar a própria conta"' : '' ?>>
                                            <?= $ativo ? icone('x', 15) . ' Desativar' : icone('check', 15) . ' Ativar' ?>
                                        </button>
                                    </form>

                                    <form method="POST" data-confirm="Gerar uma nova senha temporária para <?= h($u['nome']) ?>?">
                                        <?= csrf_campo() ?>
                                        <input type="hidden" name="acao" value="redefinir">
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <button type="submit" class="botao botao-sec" title="Redefinir senha">
                                            <?= icone('chave', 15) ?>
                                        </button>
                                    </form>

                                    <form method="POST" data-confirm="Excluir definitivamente o usuário <?= h($u['nome']) ?>?">
                                        <?= csrf_campo() ?>
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <button type="submit" class="botao botao-perigo" title="Excluir usuário"
                                                <?= (int)$u['id'] === usuario_id() ? 'disabled title="Você não pode excluir a própria conta"' : '' ?>>
                                            <?= icone('excluir', 15) ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <tr data-empty-filtro hidden>
                        <td colspan="7">
                            <div class="empty-state compact">
                                <strong>Nenhum usuário encontrado</strong>
                                <p>Ajuste a busca ou os filtros selecionados.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php rodape(); ?>
