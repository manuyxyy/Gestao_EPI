<?php
/**
 * Códigos de acesso — área exclusiva do administrador.
 *
 * Aqui o administrador gera os códigos usados na etapa de autorização do
 * cadastro. Cada código serve para um perfil, pode expirar e é de uso único.
 */
require_once __DIR__ . '/layout.php';

exigir_perfil(['Administrador']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validar()) {
        flash('erro', 'Sessão expirada. Envie o formulário novamente.');
        redirecionar('convites.php');
    }

    $acao = (string)($_POST['acao'] ?? '');

    try {
        if ($acao === 'gerar') {
            $perfil = perfil_de_cadastro($_POST['perfil'] ?? '');
            $dias   = (int)($_POST['validade'] ?? 7);

            if (!in_array($dias, [0, 7, 30], true)) {
                $dias = 7;
            }

            $codigo = convite_gerar($perfil, $dias);
            $_SESSION['codigo_gerado'] = $codigo;
            flash('sucesso', 'Código gerado para ' . rotulo_perfil($perfil) . '. Envie-o ao usuário.');
        } elseif ($acao === 'revogar') {
            if (convite_revogar((int)($_POST['id'] ?? 0))) {
                flash('sucesso', 'Código revogado. Ele não poderá mais ser usado.');
            } else {
                flash('aviso', 'Este código não pode mais ser revogado.');
            }
        } elseif ($acao === 'excluir') {
            if (convite_excluir((int)($_POST['id'] ?? 0))) {
                flash('sucesso', 'Código excluído.');
            } else {
                flash('aviso', 'Código não encontrado.');
            }
        } else {
            flash('erro', 'Ação desconhecida.');
        }
    } catch (Throwable $e) {
        error_log('Falha ao gerenciar códigos de acesso: ' . $e->getMessage());
        flash('erro', 'Não foi possível concluir a ação. Tente novamente.');
    }

    redirecionar('convites.php');
}

$codigoGerado = (string)($_SESSION['codigo_gerado'] ?? '');
unset($_SESSION['codigo_gerado']);

$convites   = convites_listar();
$disponiveis = 0;
$usados      = 0;
$encerrados  = 0;

foreach ($convites as $convite) {
    $situacao = convite_situacao($convite);
    if ($situacao === 'Disponível') {
        $disponiveis++;
    } elseif ($situacao === 'Usado') {
        $usados++;
    } else {
        $encerrados++;
    }
}

$acoesTopo = '<a class="botao botao-sec" href="usuarios.php">' . icone('usuarios', 17) . ' Usuários</a>';

cabecalho(
    'Códigos de acesso',
    'Gere a autorização que cada pessoa deve informar ao criar a conta',
    $acoesTopo
);
?>

<div class="stats-grid">
    <article class="stat-card green">
        <div class="stat-top">
            <span class="stat-label">Disponíveis</span>
            <span class="stat-icon"><?= icone('chave', 19) ?></span>
        </div>
        <strong><?= $disponiveis ?></strong>
        <small>Podem ser usados agora</small>
    </article>

    <article class="stat-card blue">
        <div class="stat-top">
            <span class="stat-label">Usados</span>
            <span class="stat-icon"><?= icone('check', 19) ?></span>
        </div>
        <strong><?= $usados ?></strong>
        <small>Contas já autorizadas</small>
    </article>

    <article class="stat-card <?= $encerrados > 0 ? 'orange' : 'pale' ?>">
        <div class="stat-top">
            <span class="stat-label">Encerrados</span>
            <span class="stat-icon"><?= icone('x', 19) ?></span>
        </div>
        <strong><?= $encerrados ?></strong>
        <small>Revogados ou expirados</small>
    </article>
</div>

<section class="content-card">
    <h2>Gerar novo código</h2>
    <p>O código é de uso único e vale somente para o perfil escolhido. Entregue-o à pessoa autorizada.</p>

    <form method="POST" action="convites.php" class="form-grid">
        <?= csrf_campo() ?>
        <input type="hidden" name="acao" value="gerar">

        <div>
            <label for="perfil">Perfil <span class="obrigatorio">*</span></label>
            <select id="perfil" name="perfil" required>
                <option value="almoxarifado">Almoxarifado — estoque e entregas</option>
                <option value="gestor">Gestor — relatórios e indicadores</option>
                <option value="administrador">Administrador — acesso completo</option>
            </select>
        </div>

        <div>
            <label for="validade">Validade <span class="obrigatorio">*</span></label>
            <select id="validade" name="validade" required>
                <option value="7">7 dias</option>
                <option value="30">30 dias</option>
                <option value="0">Sem expiração</option>
            </select>
        </div>

        <div class="campo-largo">
            <button type="submit" class="botao"><?= icone('chave', 17) ?> Gerar código</button>
        </div>
    </form>

    <?php if ($codigoGerado !== ''): ?>
        <div class="codigo-destaque" role="status">
            <div>
                <small>Código gerado — envie ao usuário</small>
                <strong id="codigo-gerado"><?= h($codigoGerado) ?></strong>
            </div>
            <button type="button" class="botao botao-sec botao-sm" data-copiar="codigo-gerado">
                <?= icone('copiar', 16) ?> Copiar
            </button>
        </div>
    <?php endif; ?>
</section>

<section class="content-card">
    <h2>Códigos gerados</h2>
    <p>O usuário cria a conta com e-mail e senha e informa o código na tela de autorização.</p>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Perfil</th>
                    <th>Validade</th>
                    <th>Situação</th>
                    <th>Criado por</th>
                    <th>Usado por</th>
                    <th class="center">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$convites): ?>
                    <tr><td colspan="7">Nenhum código gerado até agora.</td></tr>
                <?php else: ?>
                    <?php foreach ($convites as $convite): ?>
                        <?php
                        $situacao = convite_situacao($convite);
                        $classe = match ($situacao) {
                            'Disponível' => 'status-ok',
                            'Usado'      => 'status-info',
                            default      => 'status-neutro',
                        };
                        ?>
                        <tr>
                            <td class="cell-mono"><strong><?= h($convite['codigo']) ?></strong></td>
                            <td><span class="chip <?= h(classe_perfil($convite['perfil'])) ?>"><?= h(rotulo_perfil($convite['perfil'])) ?></span></td>
                            <td>
                                <?= $convite['expira_em'] ? h(data_br($convite['expira_em'], true)) : 'Sem expiração' ?>
                                <br><small class="cell-muted">Criado em <?= h(data_br($convite['criado_em'], true)) ?></small>
                            </td>
                            <td><span class="status <?= $classe ?>"><?= h($situacao) ?></span></td>
                            <td><?= h($convite['criado_por_nome'] ?? '—') ?></td>
                            <td>
                                <?php if (!empty($convite['usado_por_nome'])): ?>
                                    <?= h($convite['usado_por_nome']) ?>
                                    <br><small class="cell-muted"><?= h(data_br($convite['usado_em'], true)) ?></small>
                                <?php else: ?>
                                    <span class="cell-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="acoes">
                                    <?php if ($situacao === 'Disponível'): ?>
                                        <form method="POST" data-confirm="Revogar o código <?= h($convite['codigo']) ?>?">
                                            <?= csrf_campo() ?>
                                            <input type="hidden" name="acao" value="revogar">
                                            <input type="hidden" name="id" value="<?= (int)$convite['id'] ?>">
                                            <button type="submit" class="botao botao-sm botao-sec">Revogar</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" data-confirm="Excluir o código <?= h($convite['codigo']) ?>?">
                                        <?= csrf_campo() ?>
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="id" value="<?= (int)$convite['id'] ?>">
                                        <button type="submit" class="botao botao-sm botao-perigo">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
(function () {
    'use strict';
    document.querySelectorAll('[data-copiar]').forEach(function (botao) {
        botao.addEventListener('click', function () {
            var alvo = document.getElementById(botao.getAttribute('data-copiar'));
            if (!alvo) return;
            var texto = alvo.textContent.trim();
            var aviso = document.createElement('span');
            aviso.textContent = 'Copiado!';
            aviso.style.marginLeft = '8px';
            botao.appendChild(aviso);

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(texto).catch(function () {});
            } else {
                var campo = document.createElement('textarea');
                campo.value = texto;
                document.body.appendChild(campo);
                campo.select();
                document.execCommand('copy');
                campo.remove();
            }

            setTimeout(function () { aviso.remove(); }, 1800);
        });
    });
})();
</script>

<?php rodape(); ?>
