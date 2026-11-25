<?php
/**
 * Componentes compartilhados de acessibilidade do EpiSafe.
 *
 * Incluído por layout.php (telas internas), index.php (login) e cadastro.php.
 * Não depende de nenhuma outra função do sistema.
 */

/**
 * Aplica as preferências salvas antes da folha de estilo carregar, evitando
 * que a pessoa com baixa visão veja um "flash" de tema claro.
 */
function acessibilidade_script_inicial(): string
{
    return <<<'JS'
(function () {
    var raiz = document.documentElement;
    raiz.classList.add('acess-alto-contraste');
    raiz.classList.add('acess-sem-movimento');
    try {
        var p = JSON.parse(localStorage.getItem('episafe_acessibilidade') || '{}');
        if (p.contraste === 'claro') raiz.classList.remove('acess-alto-contraste');
        if (p.links) raiz.classList.add('acess-links-sublinhados');
        if (p.movimento === false) raiz.classList.remove('acess-sem-movimento');
        if (p.zoom && p.zoom >= 1 && p.zoom <= 2) {
            raiz.style.setProperty('--acess-zoom', String(p.zoom));
        }
    } catch (e) {
        /* Preferências indisponíveis: mantém o alto contraste padrão. */
    }
})();
JS;
}

/**
 * Botão flutuante, painel de ajustes, região de anúncios e atalhos.
 */
function ferramentas_acessibilidade(): string
{
    return <<<'HTML'
<a class="skip-link skip-link-acess" href="#acess-painel">Ir para as ferramentas de acessibilidade</a>

<button type="button" class="acess-btn" id="acess-btn"
        aria-label="Abrir ferramentas de acessibilidade" aria-expanded="false"
        aria-controls="acess-painel" title="Ferramentas de acessibilidade (Alt+A)">
    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor"
         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
        <circle cx="12" cy="4.5" r="1.8"/>
        <path d="M4.5 8.5h15"/>
        <path d="M12 8.5v4.2"/>
        <path d="M8.5 12.7 5.5 21"/>
        <path d="M15.5 12.7 18.5 21"/>
    </svg>
    <span class="acess-btn-texto">Acessibilidade</span>
</button>

<section class="acess-painel" id="acess-painel" aria-labelledby="acess-titulo" hidden>
    <header class="acess-painel-head">
        <div>
            <h2 id="acess-titulo">Acessibilidade</h2>
            <p>Ajuste a leitura e a navegação do sistema.</p>
        </div>
        <button type="button" class="acess-fechar" data-acess="fechar"
                aria-label="Fechar ferramentas de acessibilidade">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false">
                <path d="M18 6 6 18 M6 6l12 12"/>
            </svg>
        </button>
    </header>

    <div class="acess-controle">
        <span class="acess-controle-titulo" id="acess-zoom-label">Tamanho do texto e da tela</span>
        <div class="acess-passos" role="group" aria-labelledby="acess-zoom-label">
            <button type="button" data-acess="zoom-menor" aria-label="Diminuir o tamanho do texto">A-</button>
            <output id="acess-zoom-valor" aria-live="polite">100%</output>
            <button type="button" data-acess="zoom-maior" aria-label="Aumentar o tamanho do texto">A+</button>
        </div>
    </div>

    <div class="acess-controle">
        <span class="acess-controle-titulo">Aparência</span>
        <button type="button" class="acess-opcao" data-acess="contraste" aria-pressed="true">
            <span>Alto contraste</span><strong data-acestado>Ativado</strong>
        </button>
        <button type="button" class="acess-opcao" data-acess="links" aria-pressed="false">
            <span>Sublinhar todos os links</span><strong data-acestado>Desativado</strong>
        </button>
        <button type="button" class="acess-opcao" data-acess="movimento" aria-pressed="true">
            <span>Reduzir animações</span><strong data-acestado>Ativado</strong>
        </button>
    </div>

    <div class="acess-controle">
        <span class="acess-controle-titulo">Leitura em voz alta</span>
        <button type="button" class="acess-opcao" data-acess="ler" aria-pressed="false">
            <span>Ler esta página</span><strong data-acestado>Pronto</strong>
        </button>
        <p class="acess-dica">A leitura usa a voz do navegador. Pause com o botão “Parar leitura”.</p>
        <button type="button" class="acess-parar" data-acess="parar" hidden>Parar leitura</button>
    </div>

    <p class="acess-atalhos">
        Atalhos: <kbd>Alt</kbd>+<kbd>A</kbd> abre este painel ·
        <kbd>Alt</kbd>+<kbd>L</kbd> ler/parar · <kbd>Esc</kbd> fecha
    </p>
</section>

<div class="sr-only" id="acess-anuncio" role="status" aria-live="polite" aria-atomic="true"></div>
HTML;
}
