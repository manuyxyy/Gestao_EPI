/**
 * EpiSafe — ferramentas de acessibilidade.
 *
 * Funciona nas telas internas (layout.php), no login (index.php) e no
 * cadastro (cadastro.php). As preferências ficam no navegador (localStorage).
 */
(function () {
    'use strict';

    var CHAVE = 'episafe_acessibilidade';
    var ZOOMS = [1, 1.1, 1.25, 1.4, 1.6, 1.8, 2];

    var raiz = document.documentElement;
    var botao = document.getElementById('acess-btn');
    var painel = document.getElementById('acess-painel');
    var anuncio = document.getElementById('acess-anuncio');
    var sintese = window.speechSynthesis || null;
    var falando = false;

    var prefs = carregar();
    aplicar();

    if (!botao || !painel) {
        melhorarPagina();
        return;
    }

    /* -------------------------------------------------- Preferências */

    function carregar() {
        var padrao = { contraste: 'alto', zoom: 1, links: false, movimento: true };
        try {
            var salvo = JSON.parse(localStorage.getItem(CHAVE) || '{}');
            return {
                contraste: salvo.contraste === 'claro' ? 'claro' : 'alto',
                zoom: ZOOMS.indexOf(salvo.zoom) >= 0 ? salvo.zoom : 1,
                links: !!salvo.links,
                movimento: salvo.movimento !== false
            };
        } catch (e) {
            return padrao;
        }
    }

    function salvar() {
        try {
            localStorage.setItem(CHAVE, JSON.stringify(prefs));
        } catch (e) {
            /* Navegação privada pode bloquear o armazenamento. */
        }
    }

    function aplicar() {
        raiz.classList.toggle('acess-alto-contraste', prefs.contraste === 'alto');
        raiz.classList.toggle('acess-links-sublinhados', prefs.links);
        raiz.classList.toggle('acess-sem-movimento', prefs.movimento);
        raiz.style.setProperty('--acess-zoom', String(prefs.zoom));
        sincronizarControles();
    }

    function sincronizarControles() {
        definirEstado('contraste', prefs.contraste === 'alto', prefs.contraste === 'alto' ? 'Ativado' : 'Desativado');
        definirEstado('links', prefs.links, prefs.links ? 'Ativado' : 'Desativado');
        definirEstado('movimento', prefs.movimento, prefs.movimento ? 'Ativado' : 'Desativado');

        var valor = document.getElementById('acess-zoom-valor');
        if (valor) {
            valor.textContent = Math.round(prefs.zoom * 100) + '%';
        }
        sincronizarLeitura();
    }

    function definirEstado(nome, ligado, texto) {
        var alvo = painel ? painel.querySelector('[data-acess="' + nome + '"]') : null;
        if (!alvo) return;
        alvo.setAttribute('aria-pressed', ligado ? 'true' : 'false');
        var estado = alvo.querySelector('[data-acestado]');
        if (estado) estado.textContent = texto;
    }

    function sincronizarLeitura() {
        var botaoLer = painel ? painel.querySelector('[data-acess="ler"]') : null;
        var parar = painel ? painel.querySelector('[data-acess="parar"]') : null;
        if (!botaoLer) return;
        botaoLer.setAttribute('aria-pressed', falando ? 'true' : 'false');
        var rotulo = botaoLer.querySelector('span');
        var estado = botaoLer.querySelector('[data-acestado]');
        if (rotulo) rotulo.textContent = falando ? 'Parar leitura' : 'Ler esta página';
        if (estado) estado.textContent = falando ? 'Lendo…' : 'Pronto';
        if (parar) parar.hidden = !falando;
    }

    function anunciar(texto) {
        if (!anuncio) return;
        anuncio.textContent = '';
        window.setTimeout(function () {
            anuncio.textContent = texto;
        }, 60);
    }

    /* -------------------------------------------------- Painel */

    function abrir() {
        painel.hidden = false;
        botao.setAttribute('aria-expanded', 'true');
        botao.setAttribute('aria-label', 'Fechar ferramentas de acessibilidade');
        var primeiro = painel.querySelector('button');
        if (primeiro) primeiro.focus();
    }

    function fechar(devolverFoco) {
        painel.hidden = true;
        botao.setAttribute('aria-expanded', 'false');
        botao.setAttribute('aria-label', 'Abrir ferramentas de acessibilidade');
        if (devolverFoco) botao.focus();
    }

    botao.addEventListener('click', function () {
        if (painel.hidden) abrir();
        else fechar(true);
    });

    document.addEventListener('click', function (evento) {
        if (!painel.hidden && !painel.contains(evento.target) && !botao.contains(evento.target)) {
            fechar(false);
        }
    });

    painel.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            evento.stopPropagation();
            fechar(true);
        }
    });

    painel.addEventListener('click', function (evento) {
        var alvo = evento.target.closest('[data-acess]');
        if (!alvo) return;
        var acao = alvo.getAttribute('data-acess');

        if (acao === 'fechar') {
            fechar(true);
        } else if (acao === 'zoom-maior' || acao === 'zoom-menor') {
            var indice = ZOOMS.indexOf(prefs.zoom);
            if (acao === 'zoom-maior' && indice < ZOOMS.length - 1) indice++;
            if (acao === 'zoom-menor' && indice > 0) indice--;
            prefs.zoom = ZOOMS[indice];
            aplicar();
            salvar();
            anunciar('Tamanho da tela: ' + Math.round(prefs.zoom * 100) + '%.');
        } else if (acao === 'contraste') {
            prefs.contraste = prefs.contraste === 'alto' ? 'claro' : 'alto';
            aplicar();
            salvar();
            anunciar('Alto contraste ' + (prefs.contraste === 'alto' ? 'ativado.' : 'desativado.'));
        } else if (acao === 'links') {
            prefs.links = !prefs.links;
            aplicar();
            salvar();
            anunciar('Links sublinhados ' + (prefs.links ? 'ativados.' : 'desativados.'));
        } else if (acao === 'movimento') {
            prefs.movimento = !prefs.movimento;
            aplicar();
            salvar();
            anunciar('Animações ' + (prefs.movimento ? 'reduzidas.' : 'ativadas.'));
        } else if (acao === 'ler') {
            if (falando) pararLeitura();
            else iniciarLeitura();
        } else if (acao === 'parar') {
            pararLeitura();
        }
    });

    /* -------------------------------------------------- Síntese de voz */

    function textoDaPagina() {
        var alvo = document.getElementById('conteudo')
            || document.querySelector('main.home-login')
            || document.querySelector('main')
            || document.body;
        var copia = alvo.cloneNode(true);
        var ignorados = copia.querySelectorAll(
            'script, style, svg, nav, button, [aria-hidden="true"], .acess-painel, .skip-link, .table-tools'
        );
        Array.prototype.forEach.call(ignorados, function (no) {
            if (no.parentNode) no.parentNode.removeChild(no);
        });
        return ((copia.innerText || copia.textContent || '') + '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function dividirTexto(texto) {
        var frases = texto.match(/[^.!?]+[.!?]+|[^.!?]+$/g) || [texto];
        var partes = [];
        var atual = '';

        frases.forEach(function (frase) {
            frase = frase.trim();
            if (!frase) return;
            if ((atual + ' ' + frase).length > 200 && atual) {
                partes.push(atual);
                atual = frase;
            } else {
                atual = atual ? atual + ' ' + frase : frase;
            }
        });
        if (atual) partes.push(atual);
        return partes;
    }

    function iniciarLeitura() {
        if (!sintese) {
            anunciar('Este navegador não oferece leitura em voz alta.');
            return;
        }

        var texto = textoDaPagina();
        if (!texto) {
            anunciar('Não há texto para ler nesta página.');
            return;
        }

        var partes = dividirTexto(texto);
        sintese.cancel();
        falando = true;
        sincronizarLeitura();
        anunciar('Iniciando leitura da página.');

        function falarParte(indice) {
            if (!falando || indice >= partes.length) {
                if (falando) {
                    falando = false;
                    sincronizarLeitura();
                    anunciar('Leitura concluída.');
                }
                return;
            }

            var fala = new SpeechSynthesisUtterance(partes[indice]);
            fala.lang = 'pt-BR';
            fala.rate = 0.95;
            fala.pitch = 1;
            fala.onend = function () { falarParte(indice + 1); };
            fala.onerror = function () {
                falando = false;
                sincronizarLeitura();
                anunciar('A leitura foi interrompida.');
            };
            sintese.speak(fala);
        }

        falarParte(0);
    }

    function pararLeitura() {
        if (sintese) sintese.cancel();
        falando = false;
        sincronizarLeitura();
        anunciar('Leitura interrompida.');
    }

    /* -------------------------------------------------- Atalhos */

    document.addEventListener('keydown', function (evento) {
        if (!evento.altKey || evento.ctrlKey || evento.metaKey) return;
        var tecla = evento.key.toLowerCase();

        if (tecla === 'a') {
            evento.preventDefault();
            if (painel.hidden) abrir();
            else fechar(true);
        } else if (tecla === 'l') {
            evento.preventDefault();
            if (falando) pararLeitura();
            else iniciarLeitura();
        }
    });

    melhorarPagina();

    /* -------------------------------------------------- Ajustes gerais da página */

    function melhorarPagina() {
        /* Ícones decorativos não devem ser anunciados por leitores de tela. */
        document.querySelectorAll('svg:not([aria-hidden]):not([aria-label]):not([role])')
            .forEach(function (svg) {
                svg.setAttribute('aria-hidden', 'true');
                svg.setAttribute('focusable', 'false');
            });

        /* Erros e avisos interrompem a leitura; sucesso apenas informa. */
        document.querySelectorAll('.alerta.erro, .alerta.aviso, .login-alert.error')
            .forEach(function (alerta) { alerta.setAttribute('role', 'alert'); });

        melhorarTabelas();
        melhorarFormularios();
        melhorarMenu();
        melhorarDicas();
    }

    function melhorarTabelas() {
        document.querySelectorAll('table').forEach(function (tabela) {
            if (!tabela.hasAttribute('aria-label') && !tabela.querySelector('caption')) {
                var cartao = tabela.closest('.content-card') || tabela.closest('section');
                var titulo = cartao ? cartao.querySelector('h2, h3') : null;
                tabela.setAttribute('aria-label', titulo
                    ? 'Tabela: ' + titulo.textContent.trim()
                    : 'Tabela de dados');
            }

            tabela.querySelectorAll('thead th').forEach(function (th) {
                th.setAttribute('scope', 'col');
                if (th.hasAttribute('data-sort') && !th.hasAttribute('tabindex')) {
                    th.setAttribute('tabindex', '0');
                    th.setAttribute('role', 'button');
                    th.setAttribute('aria-label', 'Ordenar por ' + th.textContent.trim());
                    th.addEventListener('keydown', function (evento) {
                        if (evento.key === 'Enter' || evento.key === ' ') {
                            evento.preventDefault();
                            th.click();
                        }
                    });
                }
            });

            tabela.querySelectorAll('tbody tr th:first-child').forEach(function (th) {
                th.setAttribute('scope', 'row');
            });
        });

        document.querySelectorAll('[data-table-count]').forEach(function (contador) {
            contador.setAttribute('aria-live', 'polite');
        });
    }

    function melhorarFormularios() {
        document.querySelectorAll('input, select, textarea').forEach(function (campo) {
            if (campo.type === 'hidden') return;
            if (campo.required) campo.setAttribute('aria-required', 'true');

            if (!campo.id) return;
            var grupo = campo.closest('.form-group, .campo, .form-grid > div') || campo.parentElement;
            var ajuda = grupo ? grupo.querySelector('.field-help, .ajuda') : null;
            if (!ajuda) return;

            if (!ajuda.id) ajuda.id = 'ajuda-' + campo.id;
            var descritos = (campo.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
            if (descritos.indexOf(ajuda.id) < 0) descritos.push(ajuda.id);
            campo.setAttribute('aria-describedby', descritos.join(' '));
        });
    }

    function melhorarMenu() {
        document.querySelectorAll('[data-user-menu]').forEach(function (menu) {
            var gatilho = menu.querySelector('[data-user-toggle]');
            var itens = menu.querySelectorAll('.user-dropdown a, .user-dropdown button');
            if (!gatilho || !itens.length) return;

            gatilho.addEventListener('keydown', function (evento) {
                if (evento.key === 'ArrowDown') {
                    evento.preventDefault();
                    if (painel && painel.hidden === false) return;
                    if (gatilho.getAttribute('aria-expanded') !== 'true') gatilho.click();
                    itens[0].focus();
                }
            });

            Array.prototype.forEach.call(itens, function (item, indice) {
                item.addEventListener('keydown', function (evento) {
                    if (evento.key === 'ArrowDown' || evento.key === 'ArrowUp') {
                        evento.preventDefault();
                        var proximo = indice + (evento.key === 'ArrowDown' ? 1 : -1);
                        if (proximo < 0) proximo = itens.length - 1;
                        if (proximo >= itens.length) proximo = 0;
                        itens[proximo].focus();
                    } else if (evento.key === 'Escape') {
                        gatilho.focus();
                    }
                });
            });
        });
    }

    function melhorarDicas() {
        document.querySelectorAll('.login-help').forEach(function (dica) {
            if (dica.hasAttribute('tabindex')) return;
            dica.setAttribute('tabindex', '0');
            dica.setAttribute('role', 'button');
            dica.setAttribute('aria-label', 'Esqueceu a senha? Solicite a redefinição ao administrador do sistema.');
            var mostrar = function () {
                anunciar('Solicite a redefinição da senha ao administrador do sistema.');
            };
            dica.addEventListener('click', mostrar);
            dica.addEventListener('keydown', function (evento) {
                if (evento.key === 'Enter' || evento.key === ' ') {
                    evento.preventDefault();
                    mostrar();
                }
            });
        });
    }
})();
