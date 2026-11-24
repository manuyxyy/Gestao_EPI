<?php
/**
 * Suporte do EpiSafe: canais de contato e perguntas frequentes.
 * Disponível para todos os perfis.
 *
 * Atualize as constantes abaixo com os dados reais de atendimento.
 */
require_once 'conexao.php';
require_once 'funcoes.php';
require_once __DIR__ . '/layout.php';

/** @var PDO $pdo */

exigir_login();

const SUPORTE_EMAIL    = 'suporte@episafe.local';
const SUPORTE_TELEFONE = '(11) 4000-0000';
const SUPORTE_HORARIO  = 'Segunda a sexta-feira, das 8h às 18h';

cabecalho(
    'Suporte',
    'Canais de contato e respostas para as dúvidas mais comuns sobre o sistema'
);
?>

<section class="content-card">
    <div class="card-heading">
        <div>
            <h2>Fale com o suporte</h2>
            <p class="card-sub">Em caso de dúvida, erro ou solicitação de acesso, use um dos canais abaixo.</p>
        </div>
    </div>

    <div class="contato-grid">
        <div class="contato-card">
            <span class="stat-icon"><?= icone('email', 19) ?></span>
            <div>
                <strong>E-mail</strong>
                <p><a href="mailto:<?= h(SUPORTE_EMAIL) ?>"><?= h(SUPORTE_EMAIL) ?></a></p>
            </div>
        </div>

        <div class="contato-card">
            <span class="stat-icon"><?= icone('telefone', 19) ?></span>
            <div>
                <strong>Telefone</strong>
                <p><a href="tel:+551140000000"><?= h(SUPORTE_TELEFONE) ?></a></p>
            </div>
        </div>

        <div class="contato-card">
            <span class="stat-icon"><?= icone('calendario', 19) ?></span>
            <div>
                <strong>Horário de atendimento</strong>
                <p><?= h(SUPORTE_HORARIO) ?></p>
            </div>
        </div>
    </div>

    <p class="ajuda" style="margin-top:16px">
        Ao entrar em contato, informe seu nome, perfil de acesso e, se possível, a página onde o problema aconteceu.
    </p>
</section>

<section class="content-card">
    <div class="card-heading">
        <div>
            <h2>Perguntas frequentes</h2>
            <p class="card-sub">Respostas rápidas antes de abrir um chamado.</p>
        </div>
    </div>

    <div class="faq-lista">
        <details class="faq-item">
            <summary>Esqueci minha senha. Como faço para entrar?</summary>
            <p>
                A redefinição é feita pelo administrador do sistema. Solicite uma nova senha pelo e-mail de suporte
                informando o e-mail da sua conta.
            </p>
        </details>

        <details class="faq-item">
            <summary>Por que não vejo a aba Funcionários no meu menu?</summary>
            <p>
                O cadastro de funcionários é restrito ao perfil Administrador. Os perfis Gestor e Almoxarifado
                enxergam apenas as páginas da sua rotina. Se precisar cadastrar ou corrigir um colaborador,
                solicite ao administrador.
            </p>
        </details>

        <details class="faq-item">
            <summary>Como registro uma entrega ou devolução de EPI?</summary>
            <p>
                Acesse <strong>Entregas</strong> ou <strong>Devoluções</strong> no menu. A baixa e a reposição de
                estoque são feitas automaticamente ao salvar o registro.
            </p>
        </details>

        <details class="faq-item">
            <summary>O que significa estoque crítico?</summary>
            <p>
                É o item cuja quantidade está no limite mínimo ou abaixo dele. Acompanhe em
                <strong>Estoque</strong> e use <strong>Movimentações</strong> para registrar entradas e saídas.
            </p>
        </details>

        <details class="faq-item">
            <summary>Recebi aviso de que meu perfil não tem permissão. O que fazer?</summary>
            <p>
                Volte para a página inicial do seu perfil pelo menu. Se você precisa de acesso a outra rotina,
                entre em contato com o administrador para revisar o seu perfil.
            </p>
        </details>
    </div>
</section>

<?php rodape(); ?>
