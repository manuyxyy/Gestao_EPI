<?php
require_once 'conexao.php';
require_once 'funcoes.php';
require_once __DIR__ . '/layout.php';

/** @var PDO $pdo */

exigir_login();

$idAlvo  = isset($_GET['id']) ? (int)$_GET['id'] : usuario_id();
$proprio = ($idAlvo === usuario_id());

if (!$proprio && !e_admin()) {
    flash('erro', 'Você só pode visualizar o seu próprio perfil.');
    redirecionar('perfil.php');
}

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? LIMIT 1");
$stmt->execute([$idAlvo]);
$usuario = $stmt->fetch();

if (!$usuario) {
    flash('erro', 'Usuário não encontrado.');
    redirecionar($proprio ? 'perfil.php' : 'usuarios.php');
}

/* =========================================================================
 * Ações
 * ====================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_proteger();

    $acao = (string)($_POST['acao'] ?? '');

    try {
        if ($acao === 'atualizar_dados') {
            if (!$proprio) {
                throw new InvalidArgumentException('Você só pode alterar os seus próprios dados.');
            }

            $nome     = trim((string)($_POST['nome'] ?? ''));
            $email    = mb_strtolower(trim((string)($_POST['email'] ?? '')));
            $setor    = trim((string)($_POST['setor'] ?? ''));
            $telefone = trim((string)($_POST['telefone'] ?? ''));

            if (mb_strlen($nome) < 3) {
                throw new InvalidArgumentException('Informe o nome completo (mínimo 3 caracteres).');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Informe um e-mail válido.');
            }

            $existe = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ? AND id <> ?");
            $existe->execute([$email, usuario_id()]);

            if ((int)$existe->fetchColumn() > 0) {
                throw new InvalidArgumentException('Esse e-mail já está em uso por outro usuário.');
            }

            $pdo->prepare("
                UPDATE usuarios
                   SET nome = ?, email = ?, setor = ?, telefone = ?
                 WHERE id = ?
            ")->execute([
                $nome,
                $email,
                $setor !== '' ? $setor : null,
                $telefone !== '' ? $telefone : null,
                usuario_id(),
            ]);

            $_SESSION['usuario_nome']  = $nome;
            $_SESSION['usuario_email'] = $email;
            $_SESSION['usuario_setor'] = $setor;

            flash('sucesso', 'Seus dados foram atualizados.');
            redirecionar('perfil.php');
        }

        if ($acao === 'alterar_senha') {
            if (!$proprio) {
                throw new InvalidArgumentException('Você só pode alterar a sua própria senha.');
            }

            $atual     = (string)($_POST['senha_atual'] ?? '');
            $nova      = (string)($_POST['senha_nova'] ?? '');
            $confirmar = (string)($_POST['senha_confirmar'] ?? '');

            if (!password_verify($atual, (string)$usuario['senha'])) {
                throw new InvalidArgumentException('A senha atual não confere.');
            }
            if (mb_strlen($nova) < 8) {
                throw new InvalidArgumentException('A nova senha deve ter pelo menos 8 caracteres.');
            }
            if ($nova !== $confirmar) {
                throw new InvalidArgumentException('A confirmação não corresponde à nova senha.');
            }
            if ($nova === $atual) {
                throw new InvalidArgumentException('A nova senha deve ser diferente da atual.');
            }

            $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?")
                ->execute([password_hash($nova, PASSWORD_DEFAULT), usuario_id()]);

            session_regenerate_id(true);

            flash('sucesso', 'Senha alterada com sucesso.');
            redirecionar('perfil.php');
        }

        throw new InvalidArgumentException('Ação não reconhecida.');
    } catch (InvalidArgumentException | RuntimeException $e) {
        flash('erro', $e->getMessage());
        redirecionar('perfil.php' . ($proprio ? '' : '?id=' . $idAlvo));
    } catch (PDOException $e) {
        error_log('Erro no perfil: ' . $e->getMessage());
        flash('erro', 'Não foi possível salvar as alterações.');
        redirecionar('perfil.php');
    }
}

/* =========================================================================
 * Dados complementares
 * ====================================================================== */
$resumo = $pdo->prepare("
    SELECT
        COUNT(*) AS total_registros,
        COALESCE(SUM(quantidade), 0) AS total_unidades,
        MAX(data_entrega) AS ultima_entrega
    FROM entregas
    WHERE id_usuario = ?
");
$resumo->execute([$idAlvo]);
$resumo = $resumo->fetch();

$ultimasEntregas = [];
try {
    $stmt = $pdo->prepare("
        SELECT en.data_entrega, en.quantidade, f.nome AS funcionario, p.nome AS epi
        FROM entregas en
        LEFT JOIN funcionarios f ON f.id = en.id_funcionario
        LEFT JOIN epis p ON p.id = en.id_epi
        WHERE en.id_usuario = ?
        ORDER BY en.data_entrega DESC, en.id DESC
        LIMIT 6
    ");
    $stmt->execute([$idAlvo]);
    $ultimasEntregas = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('Erro ao listar entregas do usuário: ' . $e->getMessage());
}

$perfil   = normalizar_perfil($usuario['tipo_acesso']);
$ativo    = status_ativo($usuario['status']);
$inicial  = mb_strtoupper(mb_substr((string)$usuario['nome'], 0, 1));

$acoes = '';
if (e_admin() && !$proprio) {
    $acoes .= '<a class="botao botao-sec" href="usuarios.php">' . icone('voltar', 17) . ' Voltar aos usuários</a>';
    $acoes .= '<a class="botao" href="usuarios.php?editar=' . (int)$usuario['id'] . '">' . icone('editar', 17) . ' Editar usuário</a>';
}

cabecalho(
    $proprio ? 'Meu perfil' : 'Perfil do usuário',
    $proprio
        ? 'Seus dados de acesso e o histórico de entregas registradas por você'
        : 'Informações da conta e histórico de entregas registradas',
    $acoes
);
?>

<!-- ===================================================== Cabeçalho do perfil -->
<section class="profile-hero">
    <span class="profile-avatar" aria-hidden="true"><?= h($inicial) ?></span>

    <div class="profile-hero-info">
        <h2><?= h($usuario['nome']) ?></h2>
        <p><?= h($usuario['email']) ?></p>
        <div class="profile-hero-meta">
            <span class="chip"><?= h(rotulo_perfil($perfil)) ?></span>
            <span class="status <?= $ativo ? 'status-ok' : 'status-alerta' ?>">
                <?= h($ativo ? 'Ativo' : 'Inativo') ?>
            </span>
            <?php if (!empty($usuario['setor'])): ?>
                <span class="chip"><?= h($usuario['setor']) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="profile-hero-actions">
        <a class="botao botao-sec" href="dashboard_admin.php"><?= icone('inicio', 17) ?> Painel</a>
        <?php if ($proprio): ?>
            <a class="botao botao-sec" href="perfil.php#dados"><?= icone('editar', 17) ?> Editar dados</a>
            <a class="botao botao-sec" href="perfil.php#senha"><?= icone('chave', 17) ?> Alterar senha</a>
        <?php endif; ?>
    </div>
</section>

<!-- ===================================================== Indicadores -->
<div class="stats-grid">
    <article class="stat-card blue">
        <div class="stat-top">
            <span class="stat-label">Entregas registradas</span>
            <span class="stat-icon"><?= icone('entregas', 19) ?></span>
        </div>
        <strong><?= (int)$resumo['total_registros'] ?></strong>
        <small>Operações feitas por este usuário</small>
    </article>

    <article class="stat-card green">
        <div class="stat-top">
            <span class="stat-label">Unidades entregues</span>
            <span class="stat-icon"><?= icone('caixa', 19) ?></span>
        </div>
        <strong><?= (int)$resumo['total_unidades'] ?></strong>
        <small>Total de EPIs movimentados</small>
    </article>

    <article class="stat-card orange">
        <div class="stat-top">
            <span class="stat-label">Última operação</span>
            <span class="stat-icon"><?= icone('calendario', 19) ?></span>
        </div>
        <strong class="stat-data"><?= h(data_br($resumo['ultima_entrega'])) ?></strong>
        <small>Data da entrega mais recente</small>
    </article>

    <article class="stat-card pale">
        <div class="stat-top">
            <span class="stat-label">Último acesso</span>
            <span class="stat-icon"><?= icone('sino', 19) ?></span>
        </div>
        <strong class="stat-data"><?= h(data_br($usuario['ultimo_acesso'] ?? null, true)) ?></strong>
        <small><?= h($usuario['ultimo_ip'] ?? 'IP não registrado') ?></small>
    </article>
</div>

<!-- ===================================================== Informações -->
<div class="content-card" id="dados">
    <div class="card-heading">
        <div>
            <h2>Informações da conta</h2>
            <p class="card-sub">Dados cadastrais e de auditoria do acesso.</p>
        </div>
    </div>

    <dl class="info-grid">
        <div class="info-item">
            <span class="stat-icon"><?= icone('usuarios', 17) ?></span>
            <div>
                <dt>Nome completo</dt>
                <dd><?= h($usuario['nome']) ?></dd>
            </div>
        </div>

        <div class="info-item">
            <span class="stat-icon"><?= icone('email', 17) ?></span>
            <div>
                <dt>E-mail de acesso</dt>
                <dd><?= h($usuario['email']) ?></dd>
            </div>
        </div>

        <div class="info-item">
            <span class="stat-icon"><?= icone('telefone', 17) ?></span>
            <div>
                <dt>Telefone</dt>
                <dd><?= h($usuario['telefone'] ?: 'Não informado') ?></dd>
            </div>
        </div>

        <div class="info-item">
            <span class="stat-icon"><?= icone('setor', 17) ?></span>
            <div>
                <dt>Setor</dt>
                <dd><?= h($usuario['setor'] ?: 'Não informado') ?></dd>
            </div>
        </div>

        <div class="info-item">
            <span class="stat-icon"><?= icone('escudo', 17) ?></span>
            <div>
                <dt>Perfil de acesso</dt>
                <dd><?= h(rotulo_perfil($perfil)) ?></dd>
            </div>
        </div>

        <div class="info-item">
            <span class="stat-icon"><?= icone('check', 17) ?></span>
            <div>
                <dt>Situação</dt>
                <dd><?= h($ativo ? 'Ativo' : 'Inativo') ?></dd>
            </div>
        </div>

        <div class="info-item">
            <span class="stat-icon"><?= icone('calendario', 17) ?></span>
            <div>
                <dt>Cadastrado em</dt>
                <dd><?= h(data_br($usuario['criado_em'] ?? null, true)) ?></dd>
            </div>
        </div>

        <div class="info-item">
            <span class="stat-icon"><?= icone('sino', 17) ?></span>
            <div>
                <dt>Último acesso</dt>
                <dd><?= h(data_br($usuario['ultimo_acesso'] ?? null, true)) ?></dd>
            </div>
        </div>

        <div class="info-item">
            <span class="stat-icon"><?= icone('refresh', 17) ?></span>
            <div>
                <dt>Registro (ID)</dt>
                <dd>#<?= (int)$usuario['id'] ?></dd>
            </div>
        </div>
    </dl>
</div>

<div class="dashboard-grid">
    <!-- ===================================================== Histórico -->
    <div class="content-card">
        <div class="card-heading">
            <div>
                <h2>Entregas registradas por <?= $proprio ? 'você' : h($usuario['nome']) ?></h2>
                <p class="card-sub">As 6 operações mais recentes.</p>
            </div>
            <a class="botao botao-sec botao-sm" href="entregas.php">Ver entregas</a>
        </div>

        <?php if (!$ultimasEntregas): ?>
            <div class="empty-state compact">
                <span class="empty-icon"><?= icone('entregas', 24) ?></span>
                <strong>Nenhuma entrega registrada</strong>
                <p>As entregas registradas por este usuário aparecerão aqui.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Funcionário</th>
                            <th>EPI</th>
                            <th class="num">Qtd.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimasEntregas as $entrega): ?>
                            <tr>
                                <td class="cell-mono"><?= h(data_br($entrega['data_entrega'])) ?></td>
                                <td><?= h($entrega['funcionario'] ?? '—') ?></td>
                                <td><?= h($entrega['epi'] ?? '—') ?></td>
                                <td class="num"><?= h($entrega['quantidade']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($proprio): ?>
        <!-- ===================================================== Alterar senha -->
        <div class="content-card" id="senha">
            <div class="card-heading">
                <div>
                    <h2>Alterar senha</h2>
                    <p class="card-sub">Use pelo menos 8 caracteres.</p>
                </div>
            </div>

            <form method="POST" class="form-stack" autocomplete="off">
                <?= csrf_campo() ?>
                <input type="hidden" name="acao" value="alterar_senha">

                <div>
                    <label for="senha_atual">Senha atual <span class="obrigatorio">*</span></label>
                    <div class="campo-senha">
                        <input id="senha_atual" type="password" name="senha_atual" required
                               autocomplete="current-password">
                        <button type="button" class="ver-senha" data-toggle-senha="senha_atual"
                                aria-label="Mostrar senha"><?= icone('olho', 17) ?></button>
                    </div>
                </div>

                <div>
                    <label for="senha_nova">Nova senha <span class="obrigatorio">*</span></label>
                    <div class="campo-senha">
                        <input id="senha_nova" type="password" name="senha_nova" required minlength="8"
                               autocomplete="new-password">
                        <button type="button" class="ver-senha" data-toggle-senha="senha_nova"
                                aria-label="Mostrar senha"><?= icone('olho', 17) ?></button>
                    </div>
                </div>

                <div>
                    <label for="senha_confirmar">Confirmar nova senha <span class="obrigatorio">*</span></label>
                    <div class="campo-senha">
                        <input id="senha_confirmar" type="password" name="senha_confirmar" required minlength="8"
                               autocomplete="new-password">
                        <button type="button" class="ver-senha" data-toggle-senha="senha_confirmar"
                                aria-label="Mostrar senha"><?= icone('olho', 17) ?></button>
                    </div>
                </div>

                <button class="botao" type="submit"><?= icone('chave', 17) ?> Alterar senha</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php if ($proprio): ?>
    <!-- ===================================================== Editar dados -->
    <div class="content-card">
        <div class="card-heading">
            <div>
                <h2>Editar meus dados</h2>
                <p class="card-sub">
                    O perfil de acesso e a situação só podem ser alterados por um administrador.
                </p>
            </div>
        </div>

        <form method="POST" class="form-grid" autocomplete="off">
            <?= csrf_campo() ?>
            <input type="hidden" name="acao" value="atualizar_dados">

            <div class="campo-largo">
                <label for="perfil-nome">Nome completo <span class="obrigatorio">*</span></label>
                <input id="perfil-nome" name="nome" required minlength="3" maxlength="100"
                       value="<?= h($usuario['nome']) ?>">
            </div>

            <div>
                <label for="perfil-email">E-mail <span class="obrigatorio">*</span></label>
                <input id="perfil-email" type="email" name="email" required maxlength="100"
                       value="<?= h($usuario['email']) ?>">
            </div>

            <div>
                <label for="perfil-setor">Setor</label>
                <input id="perfil-setor" name="setor" maxlength="100"
                       value="<?= h($usuario['setor'] ?? '') ?>">
            </div>

            <div>
                <label for="perfil-telefone">Telefone</label>
                <input id="perfil-telefone" name="telefone" maxlength="20"
                       placeholder="(00) 00000-0000"
                       value="<?= h($usuario['telefone'] ?? '') ?>">
            </div>

            <div class="campo-largo form-actions">
                <button class="botao" type="submit"><?= icone('salvar', 17) ?> Salvar meus dados</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php rodape(); ?>
