<?php
/**
 * Layout padrão do EpiSafe (cabeçalho, menu, conteúdo e rodapé).
 */

require_once __DIR__ . '/auth.php';

/**
 * Ícones SVG (traço) usados no sistema.
 */
function icone(string $nome, int $tamanho = 18): string
{
    $caminhos = [
        'inicio'        => 'M3 10.5 12 3l9 7.5V21h-6v-6H9v6H3z',
        'entregas'      => 'M21 8l-9-5-9 5v8l9 5 9-5V8z M3 8l9 5 9-5 M12 13v8',
        'devolucoes'    => 'M9 14 4 9l5-5 M4 9h10a6 6 0 0 1 0 12h-3',
        'movimentacoes' => 'M7 7h13 M16 3l4 4-4 4 M17 17H4 M8 13l-4 4 4 4',
        'epis'          => 'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z M9 12l2 2 4-4',
        'funcionarios'  => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z M22 21v-2a4 4 0 0 0-3-3.9 M16 3.1a4 4 0 0 1 0 7.8',
        'estoque'       => 'M3 7l9-4 9 4-9 4-9-4z M3 12l9 4 9-4 M3 17l9 4 9-4',
        'usuarios'      => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8z M4 21a8 8 0 0 1 16 0',
        'validade'      => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z M12 7v5l3 2',
        'relatorios'    => 'M4 20V11 M10 20V4 M16 20v-6 M2 21h20',
        'sair'          => 'M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4 M16 17l5-5-5-5 M21 12H9',
        'perfil'        => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8z M4 21a8 8 0 0 1 16 0',
        'busca'         => 'M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16z M21 21l-4.3-4.3',
        'editar'        => 'M12 20h9 M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z',
        'excluir'       => 'M3 6h18 M8 6V4h8v2 M19 6l-1 14H6L5 6 M10 11v6 M14 11v6',
        'chave'         => 'M21 2l-2 2 M15.5 8.5 19 5l2 2-3.5 3.5 M10.5 12.5a5 5 0 1 1-7 7 5 5 0 0 1 7-7z M10.5 12.5 15.5 7.5',
        'mais'          => 'M12 5v14 M5 12h14',
        'salvar'        => 'M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z M17 21v-8H7v8 M7 3v5h8',
        'imprimir'      => 'M6 9V2h12v7 M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2 M6 14h12v8H6z',
        'caixa'         => 'M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z M3.3 7 12 12l8.7-5 M12 22V12',
        'alerta'        => 'M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z M12 9v4 M12 17h.01',
        'check'         => 'M20 6 9 17l-5-5',
        'x'             => 'M18 6 6 18 M6 6l12 12',
        'email'         => 'M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z M22 6l-10 7L2 6',
        'telefone'      => 'M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.4 2.1L8.1 9.9a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2z',
        'setor'         => 'M3 21h18 M5 21V7l8-4v18 M19 21V11l-6-4 M9 9v.01 M9 12v.01 M9 15v.01 M9 18v.01',
        'calendario'    => 'M8 2v4 M16 2v4 M3 10h18 M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z',
        'escudo'        => 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z',
        'voltar'        => 'M19 12H5 M12 19l-7-7 7-7',
        'olho'          => 'M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z',
        'olho-off'      => 'M3 3l18 18 M10.6 10.6a2 2 0 0 0 2.8 2.8 M9.9 5.2A10.5 10.5 0 0 1 12 5c6.5 0 10 7 10 7a17.9 17.9 0 0 1-3.2 4.1 M6.6 6.6A17.6 17.6 0 0 0 2 12s3.5 7 10 7a10.6 10.6 0 0 0 4.4-.9',
        'refresh'       => 'M23 4v6h-6 M1 20v-6h6 M3.5 9a9 9 0 0 1 14.9-3.4L23 10 M1 14l4.6 4.4A9 9 0 0 0 20.5 15',
        'sino'          => 'M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9 M13.7 21a2 2 0 0 1-3.4 0',
    ];

    $d = $caminhos[$nome] ?? '';

    return '<svg class="icone" viewBox="0 0 24 24" width="' . $tamanho . '" height="' . $tamanho . '" '
        . 'fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" '
        . 'stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="' . $d . '"/></svg>';
}

/** Itens do menu: arquivo => [rótulo, ícone, perfis permitidos (vazio = todos)] */
function menu_do_sistema(): array
{
    return [
        'Visão geral' => [
            'dashboard_admin.php' => ['Painel', 'inicio', []],
        ],
        'Operação' => [
            'entregas.php'      => ['Entregas', 'entregas', []],
            'devolucoes.php'    => ['Devoluções', 'devolucoes', []],
            'movimentacoes.php' => ['Movimentações', 'movimentacoes', []],
        ],
        'Cadastros' => [
            'epis.php'         => ['EPIs', 'epis', []],
            'funcionarios.php' => ['Funcionários', 'funcionarios', []],
            'estoque.php'      => ['Estoque', 'estoque', []],
            'usuarios.php'     => ['Usuários', 'usuarios', ['Administrador']],
        ],
        'Controle' => [
            'validade.php'   => ['Validade dos CAs', 'validade', []],
            'relatorios.php' => ['Relatórios', 'relatorios', []],
        ],
    ];
}

function cabecalho(string $titulo, string $descricao = '', string $acoes = ''): void
{
    exigir_login();

    $pagina   = basename($_SERVER['PHP_SELF'] ?? '');
    $usuario  = usuario_atual();
    $inicial  = mb_strtoupper(mb_substr($usuario['nome'] !== '' ? $usuario['nome'] : 'U', 0, 1));
    $perfil   = $usuario['perfil'];
    $grupos   = menu_do_sistema();

    $iconePagina = '';
    foreach ($grupos as $links) {
        if (isset($links[$pagina])) {
            $iconePagina = $links[$pagina][1];
        }
    }

    echo '<!DOCTYPE html><html lang="pt-BR"><head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<meta name="theme-color" content="#1d2127">';
    echo '<title>' . h($titulo) . ' | EpiSafe</title>';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700&family=Public+Sans:wght@400;500;600;700&display=swap">';
    echo '<style>' . estilo_base() . '</style>';
    echo '</head><body class="app-body">';

    echo '<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>';
    echo '<div class="app-shell">';

    /* ---------- Barra superior ---------- */
    echo '<header class="topbar"><div class="topbar-inner">';
    echo '<a class="brand" href="dashboard_admin.php" aria-label="EpiSafe — página inicial">';
    echo '<span class="brand-mark">' . icone('epis', 24) . '</span>';
    echo '<span class="brand-text"><span class="brand-name">Epi<span>Safe</span></span>';
    echo '<span class="brand-sub">Gestão de Equipamentos de Proteção</span></span>';
    echo '</a>';

    echo '<div class="topbar-right">';
    echo '<span class="topbar-date">' . icone('calendario', 15) . ' ' . date('d/m/Y') . '</span>';

    echo '<div class="user-menu" data-user-menu>';
    echo '<button type="button" class="user-trigger" data-user-toggle aria-haspopup="true" aria-expanded="false" aria-controls="menu-usuario">';
    echo '<span class="avatar avatar-lg">' . h($inicial) . '</span>';
    echo '<span class="user-info"><strong>' . h($usuario['nome']) . '</strong>';
    echo '<small>' . h(rotulo_perfil($perfil)) . '</small></span>';
    echo '<span class="chevron" aria-hidden="true">▾</span>';
    echo '</button>';

    echo '<div class="user-dropdown" id="menu-usuario" role="menu" hidden>';
    echo '<div class="user-dropdown-head">';
    echo '<strong>' . h($usuario['nome']) . '</strong>';
    echo '<span>' . h($usuario['email'] ?: 'sem e-mail cadastrado') . '</span>';
    echo '<span class="chip ' . classe_perfil($perfil) . '">' . h(rotulo_perfil($perfil)) . '</span>';
    echo '</div>';
    echo '<a role="menuitem" href="perfil.php">' . icone('perfil', 17) . ' Meu perfil</a>';
    if (e_admin()) {
        echo '<a role="menuitem" href="usuarios.php">' . icone('usuarios', 17) . ' Gerenciar usuários</a>';
    }
    echo '<a role="menuitem" href="relatorios.php">' . icone('relatorios', 17) . ' Relatórios</a>';
    echo '<form method="POST" action="logout.php" class="logout-form">' . csrf_campo();
    echo '<button type="submit" role="menuitem" class="logout-link">' . icone('sair', 17) . ' Sair do sistema</button>';
    echo '</form>';
    echo '</div></div>';

    echo '</div></div></header>';

    /* ---------- Menu ---------- */
    echo '<nav class="menu" aria-label="Menu principal"><div class="menu-inner">';
    foreach ($grupos as $nomeGrupo => $links) {
        $visiveis = [];
        foreach ($links as $arquivo => $info) {
            $permitidos = $info[2] ?? [];
            if (!$permitidos || in_array($perfil, $permitidos, true)) {
                $visiveis[$arquivo] = $info;
            }
        }

        if (!$visiveis) {
            continue;
        }

        echo '<div class="nav-group" role="group" aria-label="' . h($nomeGrupo) . '">';
        foreach ($visiveis as $arquivo => $info) {
            $ativo = ($pagina === $arquivo) ? ' ativo' : '';
            $aria  = ($pagina === $arquivo) ? ' aria-current="page"' : '';

            echo '<a class="nav-link' . $ativo . '" href="' . h($arquivo) . '"' . $aria . '>';
            echo '<span class="nav-icon">' . icone($info[1], 17) . '</span>';
            echo '<span>' . h($info[0]) . '</span>';
            echo '</a>';
        }
        echo '</div>';
    }
    echo '</div></nav>';

    /* ---------- Conteúdo ---------- */
    echo '<main class="main-content" id="conteudo">';
    echo '<section class="page-heading">';
    echo '<div class="page-heading-main">';

    if ($iconePagina !== '') {
        echo '<span class="page-badge">' . icone($iconePagina, 26) . '</span>';
    }

    echo '<div class="page-heading-text">';
    echo '<h1>' . h($titulo) . '</h1>';

    if ($descricao !== '') {
        echo '<p>' . h($descricao) . '</p>';
    }

    echo '</div></div>';

    if ($acoes !== '') {
        echo '<div class="page-heading-actions">' . $acoes . '</div>';
    }

    echo '</section>';

    foreach (consumir_flashes() as $msg) {
        $tipo = in_array($msg['tipo'], ['sucesso', 'erro', 'aviso', 'info'], true) ? $msg['tipo'] : 'info';
        $iconeAlerta = match ($tipo) {
            'sucesso' => 'check',
            'erro'    => 'alerta',
            'aviso'   => 'alerta',
            default   => 'sino',
        };
        echo '<div class="alerta ' . $tipo . '" role="status">';
        echo '<span class="alerta-icon">' . icone($iconeAlerta, 18) . '</span>';
        echo '<span>' . h($msg['mensagem']) . '</span>';
        echo '<button type="button" class="alerta-fechar" data-dismiss aria-label="Fechar aviso">' . icone('x', 16) . '</button>';
        echo '</div>';
    }
}

function rodape(): void
{
    echo '</main>';

    echo '<footer class="app-footer"><div class="app-footer-inner">';
    echo '<span><strong>EpiSafe</strong> · Gestão de Equipamentos de Proteção Individual</span>';
    echo '<span>© ' . date('Y') . ' · Ambiente interno</span>';
    echo '</div></footer>';

    echo '</div>';
    echo '<script>' . script_base() . '</script>';
    echo '</body></html>';
}

/**
 * CSS do sistema.
 */
function estilo_base(): string
{
    return <<<'CSS'
:root{
    --marca-900:#1d2127;
    --marca-800:#2a3038;
    --marca-700:#8a5200;
    --marca-600:#d98f00;
    --marca-500:#f5a800;
    --marca-100:#ffe7b3;
    --marca-50:#fff7e6;

    --papel:#f4f1ea;
    --branco:#ffffff;
    --tinta:#1d2127;
    --tinta-2:#343a42;
    --suave:#5c636d;
    --suave-2:#7d848e;
    --linha:#e0dbd1;
    --linha-forte:#cbc4b6;

    --verde:#1f7a52;
    --verde-bg:#e2f3ea;
    --amarelo:#9a6a00;
    --amarelo-bg:#fdf1cc;
    --laranja:#b5590a;
    --laranja-bg:#fde3c8;
    --vermelho:#b32b23;
    --vermelho-bg:#fbe4e2;

    --raio:8px;
    --raio-sm:5px;
    --sombra:0 1px 2px rgba(29,33,39,.06),0 8px 24px -16px rgba(29,33,39,.3);
    --largura:1320px;
    --fonte:"Public Sans","Segoe UI",system-ui,-apple-system,Roboto,Arial,sans-serif;
    --fonte-titulo:"Archivo","Segoe UI",system-ui,-apple-system,Arial,sans-serif;
}

*,*::before,*::after{box-sizing:border-box}

html{scroll-behavior:smooth}

body.app-body{
    margin:0;
    font-family:var(--fonte);
    font-size:15px;
    line-height:1.55;
    color:var(--tinta);
    background:var(--papel);
    -webkit-font-smoothing:antialiased;
}

a{color:var(--marca-600)}

.skip-link{
    position:absolute;left:12px;top:-60px;z-index:100;
    padding:10px 16px;border-radius:var(--raio-sm);
    background:var(--branco);color:var(--marca-900);font-weight:600;text-decoration:none;
}
.skip-link:focus{top:12px}

.icone{display:block;flex:none}

.app-shell{min-height:100vh;display:flex;flex-direction:column}

/* ---------- Barra superior ---------- */
.topbar{background:linear-gradient(180deg,var(--marca-900),var(--marca-800));color:#fff}
.topbar-inner{
    max-width:var(--largura);margin:0 auto;padding:12px 28px;
    display:flex;align-items:center;justify-content:space-between;gap:20px;
}

.brand{display:flex;align-items:center;gap:13px;color:inherit;text-decoration:none;min-width:0}
.brand-mark{
    display:grid;place-items:center;flex:none;
    width:46px;height:46px;border-radius:50%;
    background:var(--marca-500);color:var(--marca-900);
    border:2px solid rgba(255,255,255,.9);
    box-shadow:0 0 0 3px rgba(245,168,0,.35);
}
.brand-text{display:flex;flex-direction:column;line-height:1.15;min-width:0}
.brand-name{
    font-family:var(--fonte-titulo);font-weight:700;font-size:24px;
    letter-spacing:-.02em;white-space:nowrap;
}
.brand-name span{color:#ffc247}
.brand-sub{font-size:12.5px;color:#a9b1bb;white-space:nowrap}

.topbar-right{display:flex;align-items:center;gap:18px}
.topbar-date{display:flex;align-items:center;gap:7px;font-size:13px;color:#a9b1bb;white-space:nowrap}

.user-menu{position:relative}
.user-trigger{
    display:flex;align-items:center;gap:11px;
    padding:5px 8px 5px 5px;border-radius:40px;
    background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.16);
    color:inherit;font:inherit;cursor:pointer;text-align:left;
    transition:background-color .15s,border-color .15s;
}
.user-trigger:hover{background:rgba(255,255,255,.13);border-color:rgba(255,255,255,.3)}
.user-trigger:focus-visible{outline:3px solid #ffc247;outline-offset:2px}

.avatar{
    display:grid;place-items:center;flex:none;
    width:36px;height:36px;border-radius:50%;
    background:rgba(255,255,255,.16);color:#fff;
    font-weight:700;font-size:14px;letter-spacing:.02em;
}
.avatar-lg{width:36px;height:36px}
.avatar-azul{background:var(--marca-100);color:var(--marca-700)}

.user-info{display:flex;flex-direction:column;min-width:0;line-height:1.2}
.user-info strong{font-weight:600;font-size:14px;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.user-info small{color:#a9b1bb;font-size:12px}
.chevron{font-size:10px;color:#a9b1bb;padding-right:5px}

.user-dropdown{
    position:absolute;right:0;top:calc(100% + 10px);z-index:60;
    width:260px;padding:6px;
    background:#fff;color:var(--tinta);
    border:1px solid var(--linha);border-radius:var(--raio);
    box-shadow:0 18px 40px -12px rgba(29,33,39,.4);
    animation:drop .15s ease-out;
}
@keyframes drop{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}}

.user-dropdown-head{display:flex;flex-direction:column;gap:3px;padding:12px 12px 13px;border-bottom:1px solid var(--linha);margin-bottom:6px}
.user-dropdown-head strong{font-size:14.5px;line-height:1.3}
.user-dropdown-head span{font-size:12.5px;color:var(--suave);overflow:hidden;text-overflow:ellipsis}
.user-dropdown-head .chip{margin-top:6px;align-self:flex-start}

.user-dropdown a,.logout-link{
    display:flex;align-items:center;gap:10px;
    width:100%;padding:9px 12px;border-radius:var(--raio-sm);
    color:var(--tinta-2);font-size:14px;font-weight:500;
    text-decoration:none;background:none;border:0;cursor:pointer;font-family:inherit;text-align:left;
}
.user-dropdown a:hover,.logout-link:hover{background:var(--marca-50);color:var(--marca-700)}
.logout-form{margin:0;border-top:1px solid var(--linha);padding-top:6px;margin-top:6px}
.logout-link{color:var(--vermelho)}
.logout-link:hover{background:var(--vermelho-bg);color:var(--vermelho)}

/* ---------- Menu ---------- */
.menu{
    position:sticky;top:0;z-index:30;
    background:var(--branco);border-bottom:1px solid var(--linha-forte);
    box-shadow:0 6px 14px -12px rgba(29,33,39,.4);
}
.menu-inner{
    max-width:var(--largura);margin:0 auto;padding:0 22px;
    display:flex;overflow-x:auto;scrollbar-width:thin;
}
.nav-group{display:flex;flex:none}
.nav-group + .nav-group{margin-left:8px;padding-left:8px;border-left:1px solid var(--linha)}

.nav-link{
    display:flex;align-items:center;gap:8px;white-space:nowrap;
    padding:14px 12px 11px;
    border-bottom:3px solid transparent;
    color:var(--suave);text-decoration:none;font-weight:500;font-size:14.5px;
    transition:background-color .15s,color .15s,border-color .15s;
}
.nav-link:hover{color:var(--marca-900);background:var(--marca-50)}
.nav-link.ativo{color:var(--marca-700);border-bottom-color:var(--marca-500);font-weight:700}
.nav-link:focus-visible{outline:3px solid var(--marca-600);outline-offset:-3px}
.nav-icon{display:inline-flex;flex:none}

/* ---------- Conteúdo ---------- */
.main-content{
    flex:1;width:100%;max-width:var(--largura);
    margin:0 auto;padding:28px 28px 52px;min-width:0;
}

.page-heading{
    display:flex;align-items:center;justify-content:space-between;gap:20px;
    flex-wrap:wrap;margin-bottom:24px;
}
.page-heading-main{display:flex;align-items:center;gap:16px;min-width:0}
.page-badge{
    display:grid;place-items:center;flex:none;
    width:52px;height:52px;border-radius:14px;
    background:var(--marca-500);color:var(--marca-900);
    box-shadow:0 0 0 4px var(--marca-100);
}
.page-heading h1{
    margin:0;font-family:var(--fonte-titulo);font-weight:700;
    font-size:29px;line-height:1.15;letter-spacing:-.02em;color:var(--marca-900);
}
.page-heading p{margin:3px 0 0;color:var(--suave);font-size:15px;max-width:75ch}
.page-heading-actions{display:flex;gap:10px;flex-wrap:wrap}

/* ---------- Indicadores ---------- */
.stats-grid{
    display:grid;gap:16px;margin-bottom:24px;
    grid-template-columns:repeat(auto-fit,minmax(215px,1fr));
}
.stat-card{
    --c:var(--marca-700);
    --cbg:var(--marca-50);
    position:relative;display:flex;flex-direction:column;gap:2px;
    padding:18px 20px 18px;
    background:var(--branco);border:1px solid var(--linha);border-radius:var(--raio);
    box-shadow:var(--sombra);overflow:hidden;
}
.stat-card::before{
    content:"";position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--c);
}
.stat-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
.stat-card span.stat-label{
    display:flex;align-items:center;
    color:var(--suave);font-size:13.5px;font-weight:600;
    text-transform:uppercase;letter-spacing:.04em;
}
.stat-icon{
    display:grid;place-items:center;flex:none;
    width:38px;height:38px;border-radius:10px;
    background:var(--cbg);color:var(--c);
}
.stat-card strong{
    font-family:var(--fonte-titulo);font-weight:700;font-variant-numeric:tabular-nums;
    font-size:36px;line-height:1.15;letter-spacing:-.02em;color:var(--tinta);
    margin-top:8px;
}
.stat-card small{color:var(--suave);font-size:13px}
.stat-card small b{color:var(--c)}
.stat-card.orange{--c:var(--laranja);--cbg:var(--laranja-bg)}
.stat-card.blue{--c:var(--marca-700);--cbg:var(--marca-50)}
.stat-card.green{--c:var(--verde);--cbg:var(--verde-bg)}
.stat-card.red{--c:var(--vermelho);--cbg:var(--vermelho-bg)}
.stat-card.yellow{--c:var(--amarelo);--cbg:var(--amarelo-bg)}
.stat-card.pale{--c:#64748b;--cbg:#eef1f5}

/* Ações rápidas */
.quick-grid{display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(190px,1fr))}
.quick-card{
    display:flex;align-items:center;gap:13px;
    padding:15px 16px;background:#fff;border:1px solid var(--linha);border-radius:var(--raio);
    color:var(--tinta);text-decoration:none;
    transition:border-color .15s,box-shadow .15s,transform .15s;
}
.quick-card:hover{border-color:var(--marca-500);box-shadow:var(--sombra);transform:translateY(-1px)}
.quick-card .stat-icon{width:40px;height:40px}
.quick-card strong{display:block;font-size:14.5px;line-height:1.3}
.quick-card small{color:var(--suave);font-size:12.5px}

/* ---------- Painéis ---------- */
.content-card{
    background:var(--branco);border:1px solid var(--linha);
    border-radius:var(--raio);padding:22px 24px 24px;margin-bottom:22px;
    box-shadow:var(--sombra);
}
.content-card h2{
    margin:0;font-family:var(--fonte-titulo);font-weight:700;
    font-size:19px;letter-spacing:-.01em;color:var(--marca-900);
}
.card-heading{
    display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;
    margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--linha);
}
.card-heading h2{margin:0}
.card-sub{margin:3px 0 0;color:var(--suave);font-size:13.5px}
.card-heading-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}

/* ---------- Formulários ---------- */
.form-grid{
    display:grid;gap:16px 18px;
    grid-template-columns:repeat(auto-fit,minmax(210px,1fr));
    align-items:end;
}
.form-grid .campo-largo{grid-column:1/-1}
.form-grid label{display:block;margin-bottom:6px;font-size:13.5px;font-weight:600;color:var(--tinta)}
.form-grid label .obrigatorio{color:var(--vermelho);margin-left:2px}
.form-grid input,.form-grid select,.form-grid textarea{
    width:100%;padding:10px 12px;
    font:inherit;color:var(--tinta);
    background:#fff;border:1px solid #b9c4d0;border-radius:var(--raio-sm);
    transition:border-color .15s,box-shadow .15s;
}
.form-grid input[readonly]{background:#f5f7f9;color:var(--suave)}
.form-grid textarea{min-height:92px;resize:vertical}
.form-grid input::placeholder{color:#8a97a6}
.form-grid input:focus,.form-grid select:focus,.form-grid textarea:focus{
    outline:none;border-color:var(--marca-600);
    box-shadow:0 0 0 3px rgba(245,168,0,.28);
}
.form-action{display:flex;align-items:flex-end;gap:10px}
.form-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px;padding-top:16px;border-top:1px solid var(--linha)}
.ajuda{margin:5px 0 0;font-size:12.5px;color:var(--suave-2)}
.campo-senha{position:relative}
.campo-senha input{padding-right:44px}
.ver-senha{
    position:absolute;right:6px;top:50%;transform:translateY(-50%);
    display:grid;place-items:center;width:32px;height:32px;
    background:none;border:0;border-radius:var(--raio-sm);color:var(--suave);cursor:pointer;
}
.ver-senha:hover{background:var(--marca-50);color:var(--marca-700)}

/* ---------- Botões ---------- */
.botao{
    display:inline-flex;align-items:center;justify-content:center;gap:8px;
    padding:10px 18px;font:inherit;font-weight:600;font-size:14.5px;line-height:1.2;
    color:var(--marca-900);background:var(--marca-500);
    border:1px solid var(--marca-500);border-radius:var(--raio-sm);cursor:pointer;text-decoration:none;
    transition:background-color .15s,border-color .15s,color .15s,transform .05s;
    white-space:nowrap;
}
.botao:hover{background:var(--marca-600);border-color:var(--marca-600)}
.botao:active{transform:translateY(1px)}
.botao:focus-visible{outline:3px solid var(--marca-100);outline-offset:2px;box-shadow:0 0 0 2px var(--marca-900)}
.botao-sm{padding:6px 11px;font-size:13px;gap:6px}
.botao-sec{background:#fff;color:var(--marca-700);border-color:var(--linha-forte)}
.botao-sec:hover{background:var(--marca-50);border-color:var(--marca-500);color:var(--marca-900)}
.botao-perigo{background:#fff;color:var(--vermelho);border-color:#e3aaa5}
.botao-perigo:hover{background:var(--vermelho-bg);border-color:var(--vermelho);color:var(--vermelho)}
.botao-sucesso{background:var(--verde);border-color:var(--verde)}
.botao-sucesso:hover{background:#17653f;border-color:#17653f}
.botao-ghost{background:transparent;border-color:transparent;color:var(--marca-700);padding:6px 8px}
.botao-ghost:hover{background:var(--marca-50)}
.botao[disabled]{opacity:.55;cursor:not-allowed}

/* ---------- Alertas ---------- */
.alerta{
    display:flex;align-items:flex-start;gap:11px;
    margin:0 0 20px;padding:13px 15px;border-radius:var(--raio-sm);
    border:1px solid;border-left-width:5px;font-weight:500;font-size:14.5px;
    animation:fadein .2s ease-out;
}
@keyframes fadein{from{opacity:0;transform:translateY(-3px)}to{opacity:1;transform:none}}
.alerta-icon{margin-top:1px}
.alerta > span:nth-child(2){flex:1;min-width:0}
.alerta-fechar{
    display:grid;place-items:center;flex:none;width:26px;height:26px;
    background:none;border:0;border-radius:4px;color:inherit;cursor:pointer;opacity:.75;
}
.alerta-fechar:hover{opacity:1;background:rgba(0,0,0,.07)}
.alerta.sucesso{background:var(--verde-bg);color:#17653f;border-color:#a9d6bf;border-left-color:var(--verde)}
.alerta.erro{background:var(--vermelho-bg);color:#8f241d;border-color:#ebb9b4;border-left-color:var(--vermelho)}
.alerta.aviso{background:var(--amarelo-bg);color:#6b4a00;border-color:#e8d28c;border-left-color:#d6a500}
.alerta.info{background:var(--marca-50);color:#6b4a00;border-color:#f0d79a;border-left-color:var(--marca-500)}

/* ---------- Ferramentas da tabela ---------- */
.table-tools{
    display:flex;align-items:center;gap:10px;flex-wrap:wrap;
    margin-bottom:14px;
}
.search-box{
    position:relative;display:flex;align-items:center;flex:1 1 260px;max-width:380px;
}
.search-box svg{position:absolute;left:12px;color:var(--suave-2);pointer-events:none}
.search-box input{
    width:100%;padding:9px 12px 9px 38px;
    font:inherit;font-size:14px;color:var(--tinta);
    background:#fff;border:1px solid #b9c4d0;border-radius:var(--raio-sm);
}
.search-box input:focus{outline:none;border-color:var(--marca-600);box-shadow:0 0 0 3px rgba(245,168,0,.28)}
.table-filter{
    padding:9px 12px;font:inherit;font-size:14px;color:var(--tinta);
    background:#fff;border:1px solid #b9c4d0;border-radius:var(--raio-sm);cursor:pointer;
}
.table-filter:focus{outline:none;border-color:var(--marca-600);box-shadow:0 0 0 3px rgba(245,168,0,.28)}
.table-count{
    margin-left:auto;padding:5px 11px;border-radius:20px;
    background:var(--marca-50);border:1px solid var(--marca-100);
    color:var(--marca-700);font-size:12.5px;font-weight:600;white-space:nowrap;
}

/* ---------- Tabelas ---------- */
.table-wrap{
    position:relative;overflow:auto;max-height:70vh;
    background:var(--branco);border:1px solid var(--linha-forte);border-radius:var(--raio);
}
.table-wrap table{
    width:100%;border-collapse:separate;border-spacing:0;
    font-size:14px;font-variant-numeric:tabular-nums;
}
.table-wrap th{
    position:sticky;top:0;z-index:2;
    padding:12px 16px;text-align:left;white-space:nowrap;
    font-size:12.5px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;
    color:#f7f3ea;background:var(--marca-900);
    border-bottom:1px solid var(--marca-900);
}
.table-wrap th[data-sort]{cursor:pointer;user-select:none;padding-right:28px;position:sticky}
.table-wrap th[data-sort]::after{
    content:"↕";position:absolute;right:12px;opacity:.45;font-size:11px;
}
.table-wrap th[data-sort][aria-sort="ascending"]::after{content:"↑";opacity:1}
.table-wrap th[data-sort][aria-sort="descending"]::after{content:"↓";opacity:1}
.table-wrap th[data-sort]:hover{background:var(--marca-800)}
.table-wrap th:first-child,.table-wrap td:first-child{padding-left:20px}
.table-wrap th:last-child,.table-wrap td:last-child{padding-right:20px}

.table-wrap td{
    padding:11px 16px;vertical-align:middle;color:var(--tinta);
    border-bottom:1px solid #e6ebf0;
}
.table-wrap tbody tr:nth-child(even) td{background:#fbf9f4}
.table-wrap tbody tr:hover td{background:var(--marca-50)}
.table-wrap tbody tr:last-child td{border-bottom:0}
.table-wrap tbody tr[hidden]{display:none}

.table-wrap th.num,.table-wrap td.num{text-align:right}
.table-wrap th.center,.table-wrap td.center{text-align:center}

.table-wrap td[colspan]{padding:38px 16px;text-align:center;color:var(--suave);font-weight:400;white-space:normal}
.table-wrap td a:not(.botao){color:var(--marca-600);font-weight:600;text-decoration:none}
.table-wrap td a:not(.botao):hover{text-decoration:underline}

.table-wrap tfoot td{
    position:sticky;bottom:0;
    font-weight:700;background:var(--marca-50);border-top:2px solid var(--linha-forte);
}

/* Células especiais */
.cell-user{display:flex;align-items:center;gap:11px;min-width:180px}
.cell-user .avatar{width:34px;height:34px;font-size:13px;background:var(--marca-100);color:var(--marca-700)}
.cell-user strong{display:block;font-size:14px;font-weight:600;line-height:1.3}
.cell-user small{display:block;color:var(--suave);font-size:12.5px;line-height:1.3}
.cell-mono{font-family:ui-monospace,SFMono-Regular,Consolas,monospace;font-size:12.5px;color:var(--tinta-2)}
.cell-muted{color:var(--suave-2);font-size:13px}

.acoes{display:flex;align-items:center;gap:5px;white-space:nowrap}
.acoes form{margin:0}
.acoes .botao{padding:5px 10px;font-size:12.5px}
.acoes .botao-ghost{padding:6px}

/* ---------- Chips / status ---------- */
.chip{
    display:inline-flex;align-items:center;gap:6px;
    padding:3px 9px;border-radius:3px;
    font-size:12px;font-weight:700;white-space:nowrap;letter-spacing:.01em;
}
.chip::before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor}

.status{display:inline-flex;align-items:center;gap:6px;padding:3px 9px;border-radius:3px;font-size:12.5px;font-weight:700;white-space:nowrap}
.status::before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor}
.status-ok{background:var(--verde-bg);color:#17653f}
.status-aviso{background:var(--amarelo-bg);color:#6b4a00}
.status-alerta{background:var(--vermelho-bg);color:#8f241d}
.status-neutro{background:#e9edf1;color:#445162}
.status-info{background:var(--marca-50);color:var(--marca-700)}

.perfil-administrador{background:#e7e4fb;color:#4a3a9e}
.perfil-gestor{background:var(--marca-50);color:var(--marca-700)}
.perfil-almoxarifado{background:var(--laranja-bg);color:#8a4707}
.perfil-tecnico{background:#e9edf1;color:#445162}

/* ---------- Barra de progresso ---------- */
.barra{display:flex;align-items:center;gap:9px;min-width:120px}
.barra-trilho{flex:1;height:7px;border-radius:20px;background:#e7ecf1;overflow:hidden}
.barra-trilho > span{display:block;height:100%;border-radius:20px;background:var(--verde)}
.barra-trilho > span.baixo{background:var(--amarelo)}
.barra-trilho > span.zerado{background:var(--vermelho)}
.barra-valor{font-size:12.5px;font-weight:700;font-variant-numeric:tabular-nums;min-width:52px;text-align:right}

/* ---------- Perfil ---------- */
.profile-hero{
    display:flex;align-items:center;gap:22px;flex-wrap:wrap;
    padding:26px 28px;margin-bottom:22px;
    background:
        radial-gradient(120% 160% at 100% 0%,rgba(245,168,0,.22),transparent 55%),
        linear-gradient(120deg,var(--marca-900),var(--marca-800));
    border-radius:var(--raio);color:#fff;box-shadow:var(--sombra);
}
.profile-avatar{
    display:grid;place-items:center;flex:none;
    width:88px;height:88px;border-radius:50%;
    background:rgba(255,255,255,.14);border:2px solid rgba(255,255,255,.65);
    font-family:var(--fonte-titulo);font-size:34px;font-weight:700;
}
.profile-hero-info{min-width:0;flex:1}
.profile-hero-info h2{font-family:var(--fonte-titulo);font-size:26px;line-height:1.2;margin:0 0 4px;letter-spacing:-.015em}
.profile-hero-info p{margin:0 0 10px;color:#c9cfd7;font-size:14.5px}
.profile-hero-meta{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.profile-hero-meta .chip{background:rgba(255,255,255,.14);color:#fff}
.profile-hero-meta .chip::before{background:#ffc247}
.profile-hero-meta .status{background:rgba(255,255,255,.14);color:#c9f0dc}
.profile-hero-actions{margin-left:auto;display:flex;gap:10px;flex-wrap:wrap}
.profile-hero .botao-sec{background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.4);color:#fff}
.profile-hero .botao-sec:hover{background:rgba(255,255,255,.2);border-color:#fff;color:#fff}

.info-grid{display:grid;gap:14px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}
.info-item{
    display:flex;align-items:flex-start;gap:12px;
    padding:14px 15px;border:1px solid var(--linha);border-radius:var(--raio-sm);background:#fbfcfd;
}
.info-item .stat-icon{width:34px;height:34px;border-radius:8px}
.info-item dt{font-size:11.5px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:var(--suave-2)}
.info-item dd{margin:2px 0 0;font-size:14.5px;font-weight:600;color:var(--tinta);word-break:break-word}

/* ---------- Rodapé ---------- */
.app-footer{border-top:1px solid var(--linha);background:#fff}
.app-footer-inner{
    max-width:var(--largura);margin:0 auto;padding:16px 28px;
    display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;
    color:var(--suave-2);font-size:12.5px;
}
.app-footer-inner strong{color:var(--marca-800)}

/* ---------- Painéis em duas colunas (dashboard, perfil, relatórios) ---------- */
.dashboard-grid{
    display:grid;gap:22px;margin-bottom:22px;
    grid-template-columns:minmax(0,1.65fr) minmax(0,1fr);
    align-items:start;
}
.dashboard-grid > .content-card{margin-bottom:0}
.dashboard-grid.igual{grid-template-columns:minmax(0,1fr) minmax(0,1fr)}

/* ---------- Listas de atenção ---------- */
.attention-group + .attention-group{margin-top:20px;padding-top:18px;border-top:1px solid var(--linha)}
.section-title{
    display:flex;align-items:center;gap:7px;
    margin:0 0 10px;font-size:12px;font-weight:700;
    letter-spacing:.06em;text-transform:uppercase;color:var(--suave-2);
}
.attention-list{list-style:none;margin:0;padding:0;display:grid;gap:8px}
.attention-item{
    display:flex;align-items:center;gap:10px;
    padding:9px 10px;background:#fbfcfd;
    border:1px solid var(--linha);border-radius:6px;
}
.attention-dot{flex:none;width:8px;height:8px;border-radius:50%}
.attention-dot.red{background:var(--vermelho)}
.attention-dot.yellow{background:#d6a500}
.attention-text{flex:1;min-width:0}
.attention-text strong{display:block;font-size:13.5px;line-height:1.3;color:var(--tinta)}
.attention-text small{display:block;color:var(--suave);font-size:12px;line-height:1.4}

.inline-note{
    margin:0;padding:10px 12px;border-radius:5px;
    background:#f5f7f9;border:1px solid var(--linha);
    color:var(--suave);font-size:13px;
}
.inline-note.success{background:var(--verde-bg);border-color:#a9d6bf;color:#17653f}

/* ---------- Estado vazio ---------- */
.empty-state{
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    gap:5px;padding:34px 18px;text-align:center;color:var(--suave);
}
.empty-icon{
    display:grid;place-items:center;flex:none;
    width:54px;height:54px;margin-bottom:4px;border-radius:50%;
    background:var(--marca-50);color:var(--marca-600);border:1px solid var(--marca-100);
}
.empty-state strong{color:var(--tinta);font-size:15px;font-family:var(--fonte-titulo)}
.empty-state p{margin:0 0 10px;font-size:13.5px;max-width:46ch;line-height:1.5}
.empty-state .botao{margin-top:2px}
.empty-state.compact{padding:20px 14px}
.empty-state.compact .empty-icon{width:42px;height:42px}

/* ---------- Detalhes ---------- */
.mini-tag{
    display:inline-block;margin-left:4px;padding:1px 5px;
    font-size:10px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;
    color:var(--marca-700);background:var(--marca-50);border:1px solid var(--marca-100);
    border-radius:3px;vertical-align:middle;
}
.stat-card strong.stat-data{font-size:21px;letter-spacing:-.01em;line-height:1.35;margin-top:9px}
.cell-muted small,
.table-wrap small.cell-muted{
    display:block;font-size:11.5px;color:var(--suave-2);
    line-height:1.35;font-weight:400;
}

/* ---------- Formulário vertical (perfil) ---------- */
.form-stack{display:grid;gap:14px;max-width:400px}
.form-stack label{display:block;margin-bottom:6px;font-size:13.5px;font-weight:600;color:var(--tinta)}
.form-stack label .obrigatorio{color:var(--vermelho);margin-left:2px}
.form-stack input{
    width:100%;padding:10px 12px;font:inherit;color:var(--tinta);
    background:#fff;border:1px solid #b9c4d0;border-radius:var(--raio-sm);
}
.form-stack input:focus{outline:none;border-color:var(--marca-600);box-shadow:0 0 0 3px rgba(245,168,0,.28)}
.form-stack .botao{justify-self:start;margin-top:2px}

.report-foot{margin:0 0 8px;color:var(--suave-2);font-size:12.5px;text-align:right}

/* ---------- Telas menores ---------- */
@media (max-width:1150px){
    .dashboard-grid,.dashboard-grid.igual{grid-template-columns:minmax(0,1fr)}
}
@media (max-width:900px){
    .topbar-inner{padding:10px 16px}
    .menu-inner{padding:0 8px}
    .main-content{padding:22px 16px 42px}
    .page-heading h1{font-size:25px}
    .page-badge{width:46px;height:46px}
    .topbar-date{display:none}
    .content-card{padding:18px 16px 20px}
    .brand-sub{display:none}
}
@media (max-width:680px){
    .user-info,.chevron{display:none}
    .user-trigger{padding:4px;background:none;border-color:transparent}
    .stats-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
    .stat-card{padding:14px}
    .stat-card strong{font-size:28px}
    .stat-icon{width:32px;height:32px}
    .table-count{margin-left:0}
    .search-box{max-width:none}
    .profile-hero{padding:20px}
    .profile-hero-actions{margin-left:0;width:100%}
    .form-grid{grid-template-columns:1fr}
}
@media (max-width:420px){
    .stats-grid{grid-template-columns:1fr}
}

/* ---------- Impressão ---------- */
@media print{
    .topbar,.menu,.skip-link,.app-footer,.botao,.table-tools,.page-heading-actions,.alerta-fechar{display:none !important}
    body.app-body{background:#fff}
    .main-content{padding:0;max-width:none}
    .content-card,.stat-card{box-shadow:none;break-inside:avoid}
    .table-wrap{overflow:visible;max-height:none;border-color:#bbb}
    .table-wrap th{position:static;color:var(--marca-900);background:var(--marca-50);border-bottom:2px solid var(--linha-forte)}
    .table-wrap tr{break-inside:avoid}
    .profile-hero{background:#fff;color:#000;border:1px solid #bbb}
}

@media (prefers-reduced-motion:reduce){
    *{transition:none !important;animation:none !important;scroll-behavior:auto !important}
}
CSS;
}

/**
 * JavaScript do sistema (menu do usuário, busca/ordenação de tabelas, etc.).
 */
function script_base(): string
{
    return <<<'JS'
(function () {
    'use strict';

    /* ---------- Menu do usuário ---------- */
    document.querySelectorAll('[data-user-menu]').forEach(function (menu) {
        var botao = menu.querySelector('[data-user-toggle]');
        var painel = menu.querySelector('.user-dropdown');
        if (!botao || !painel) return;

        function fechar() {
            painel.hidden = true;
            botao.setAttribute('aria-expanded', 'false');
        }

        botao.addEventListener('click', function (evento) {
            evento.stopPropagation();
            var abrir = painel.hidden;
            painel.hidden = !abrir;
            botao.setAttribute('aria-expanded', abrir ? 'true' : 'false');
        });

        document.addEventListener('click', function (evento) {
            if (!menu.contains(evento.target)) fechar();
        });

        document.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape') fechar();
        });
    });

    /* ---------- Fechar avisos ---------- */
    document.querySelectorAll('[data-dismiss]').forEach(function (botao) {
        botao.addEventListener('click', function () {
            var alerta = botao.closest('.alerta');
            if (alerta) alerta.remove();
        });
    });

    /* ---------- Mostrar/ocultar senha ---------- */
    document.querySelectorAll('[data-toggle-senha]').forEach(function (botao) {
        botao.addEventListener('click', function () {
            var campo = document.getElementById(botao.getAttribute('data-toggle-senha'));
            if (!campo) return;
            var mostrar = campo.type === 'password';
            campo.type = mostrar ? 'text' : 'password';
            botao.setAttribute('aria-pressed', mostrar ? 'true' : 'false');
            botao.setAttribute('aria-label', mostrar ? 'Ocultar senha' : 'Mostrar senha');
        });
    });

    /* ---------- Confirmação de ações destrutivas ---------- */
    document.querySelectorAll('[data-confirm]').forEach(function (elemento) {
        var eventoAlvo = elemento.tagName === 'FORM' ? 'submit' : 'click';
        elemento.addEventListener(eventoAlvo, function (evento) {
            var texto = elemento.getAttribute('data-confirm') || 'Confirmar esta ação?';
            if (!window.confirm(texto)) {
                evento.preventDefault();
                evento.stopPropagation();
            }
        });
    });

    /* ---------- Tabelas: busca, filtro, ordenação e contagem ---------- */
    document.querySelectorAll('table[data-table]').forEach(function (tabela) {
        var id = tabela.id;
        if (!id) return;

        var busca = document.querySelector('[data-table-search="' + id + '"]');
        var contador = document.querySelector('[data-table-count="' + id + '"]');
        var filtros = Array.prototype.slice.call(document.querySelectorAll('[data-table-filter="' + id + '"]'));
        var corpo = tabela.tBodies[0];
        if (!corpo) return;

        function linhas() {
            return Array.prototype.slice.call(corpo.querySelectorAll('tr[data-row]'));
        }

        function aplicar() {
            var termo = busca ? busca.value.toLowerCase().trim() : '';
            var visiveis = 0;

            linhas().forEach(function (linha) {
                var combina = !termo || linha.textContent.toLowerCase().indexOf(termo) !== -1;

                if (combina) {
                    filtros.forEach(function (filtro) {
                        var chave = filtro.getAttribute('data-filter-key');
                        var valor = filtro.value;
                        if (!chave || !valor) return;
                        if ((linha.getAttribute('data-' + chave) || '') !== valor) combina = false;
                    });
                }

                linha.hidden = !combina;
                if (combina) visiveis++;
            });

            var vazio = tabela.querySelector('[data-empty-filtro]');
            if (vazio) vazio.hidden = !(visiveis === 0 && linhas().length > 0);

            if (contador) {
                contador.textContent = visiveis + (visiveis === 1 ? ' registro' : ' registros');
            }
        }

        if (busca) busca.addEventListener('input', aplicar);
        filtros.forEach(function (filtro) { filtro.addEventListener('change', aplicar); });

        tabela.querySelectorAll('th[data-sort]').forEach(function (th, indice) {
            th.addEventListener('click', function () {
                var coluna = parseInt(th.getAttribute('data-coluna') || th.cellIndex, 10);
                var tipo = th.getAttribute('data-sort');
                var direcao = th.getAttribute('aria-sort') === 'ascending' ? 'descending' : 'ascending';

                tabela.querySelectorAll('th[data-sort]').forEach(function (outra) {
                    outra.removeAttribute('aria-sort');
                });
                th.setAttribute('aria-sort', direcao);

                linhas().sort(function (a, b) {
                    var ca = a.cells[coluna] ? a.cells[coluna].textContent.trim() : '';
                    var cb = b.cells[coluna] ? b.cells[coluna].textContent.trim() : '';
                    var valor;

                    if (tipo === 'num') {
                        valor = (parseFloat(ca.replace(/[^\d,.-]/g, '').replace(',', '.')) || 0)
                              - (parseFloat(cb.replace(/[^\d,.-]/g, '').replace(',', '.')) || 0);
                    } else {
                        valor = ca.localeCompare(cb, 'pt-BR', { numeric: true, sensitivity: 'base' });
                    }

                    return direcao === 'ascending' ? valor : -valor;
                }).forEach(function (linha) {
                    corpo.appendChild(linha);
                });

                aplicar();
            });
        });

        aplicar();
    });

    /* ---------- Select de entrega: define o máximo a devolver ---------- */
    document.querySelectorAll('[data-max-select]').forEach(function (select) {
        var alvo = document.getElementById(select.getAttribute('data-max-select'));
        if (!alvo) return;

        function atualizar() {
            var opcao = select.options[select.selectedIndex];
            var max = opcao ? opcao.getAttribute('data-max') : null;
            if (max) {
                alvo.max = max;
                if (parseInt(alvo.value, 10) > parseInt(max, 10)) alvo.value = max;
            } else {
                alvo.removeAttribute('max');
            }
        }

        select.addEventListener('change', atualizar);
        atualizar();
    });

    /* ---------- Aviso de Caps Lock no login ---------- */
    var campoLogin = document.querySelector('[data-caps-warning]');
    var avisoCaps = document.querySelector('[data-caps-message]');
    if (campoLogin && avisoCaps) {
        campoLogin.addEventListener('keyup', function (evento) {
            if (typeof evento.getModifierState !== 'function') return;
            avisoCaps.hidden = !evento.getModifierState('CapsLock');
        });
    }
})();
JS;
}
