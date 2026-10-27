<?php
require_once __DIR__ . '/auth.php';

/**
 * Ícones SVG (traço) usados no menu.
 */
function icone(string $nome): string {
    $caminhos = [
        'inicio'        => 'M3 10.5 12 3l9 7.5V21h-6v-6H9v6H3z',
        'entregas'      => 'M21 8l-9-5-9 5v8l9 5 9-5V8z M3 8l9 5 9-5 M12 13v8',
        'devolucoes'    => 'M9 14 4 9l5-5 M4 9h10a6 6 0 0 1 0 12h-3',
        'movimentacoes' => 'M7 7h13 M16 3l4 4-4 4 M17 17H4 M8 13l-4 4 4 4',
        'epis'          => 'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z',
        'funcionarios'  => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z M22 21v-2a4 4 0 0 0-3-3.9 M16 3.1a4 4 0 0 1 0 7.8',
        'estoque'       => 'M3 7l9-4 9 4-9 4-9-4z M3 12l9 4 9-4 M3 17l9 4 9-4',
        'usuarios'      => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8z M4 21a8 8 0 0 1 16 0',
        'validade'      => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z M12 7v5l3 2',
        'relatorios'    => 'M4 20V11 M10 20V4 M16 20v-6 M2 21h20',
        'sair'          => 'M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4 M16 17l5-5-5-5 M21 12H9',
    ];

    $d = $caminhos[$nome] ?? '';

    return '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" '
         . 'stroke="currentColor" stroke-width="1.8" stroke-linecap="round" '
         . 'stroke-linejoin="round" aria-hidden="true"><path d="' . $d . '"/></svg>';
}

function cabecalho($titulo, $descricao = '') {
    $pagina = basename($_SERVER['PHP_SELF']);

    // Menu agrupado: arquivo => [rótulo, ícone]
    $grupos = [
        'Visão geral' => [
            'dashboard_admin.php' => ['Início', 'inicio'],
        ],
        'Operação do dia' => [
            'entregas.php'      => ['Entregas', 'entregas'],
            'devolucoes.php'    => ['Devoluções', 'devolucoes'],
            'movimentacoes.php' => ['Movimentações', 'movimentacoes'],
        ],
        'Cadastros' => [
            'epis.php'         => ['EPIs', 'epis'],
            'funcionarios.php' => ['Funcionários', 'funcionarios'],
            'estoque.php'      => ['Estoque', 'estoque'],
            'usuarios.php'     => ['Usuários', 'usuarios'],
        ],
        'Controle' => [
            'validade.php'   => ['Validade dos CAs', 'validade'],
            'relatorios.php' => ['Relatórios', 'relatorios'],
        ],
    ];

    $nome   = $_SESSION['usuario'] ?? $_SESSION['usuario_nome'] ?? 'Usuário';
    $perfil = $_SESSION['perfil'] ?? $_SESSION['usuario_funcao'] ?? '';
    $inicial = mb_strtoupper(mb_substr((string)$nome, 0, 1));

    echo '<!DOCTYPE html><html lang="pt-BR"><head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . htmlspecialchars($titulo) . ' | EpiSafe</title>';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600&family=Barlow+Condensed:wght@500;600;700&display=swap">';
    echo '<style>' . estilo_base() . '</style>';
    echo '</head><body class="app-body">';

    echo '<div class="app-shell">';

    // ---------- Sidebar ----------
    echo '<aside class="sidebar">';
    echo '<div class="sidebar-stripe" aria-hidden="true"></div>';
    echo '<div class="sidebar-brand">';
    echo '<span class="brand-mark">' . icone('epis') . '</span>';
    echo '<span class="brand-name">EpiSafe</span>';
    echo '</div>';

    echo '<nav class="sidebar-nav">';
    foreach ($grupos as $titulo_grupo => $links) {
        echo '<div class="nav-group">';
        echo '<p class="nav-group-title">' . htmlspecialchars($titulo_grupo) . '</p>';

        foreach ($links as $arquivo => $info) {
            $ativo = ($pagina === $arquivo) ? ' ativo' : '';
            $aria  = ($pagina === $arquivo) ? ' aria-current="page"' : '';

            echo '<a class="nav-link' . $ativo . '" href="' . $arquivo . '"' . $aria . '>';
            echo '<span class="nav-icon">' . icone($info[1]) . '</span>';
            echo '<span>' . htmlspecialchars($info[0]) . '</span>';
            echo '</a>';
        }

        echo '</div>';
    }
    echo '</nav>';

    echo '<div class="sidebar-user">';
    echo '<span class="avatar">' . htmlspecialchars($inicial) . '</span>';
    echo '<span class="user-info"><strong>' . htmlspecialchars((string)$nome) . '</strong>';
    echo '<small>' . htmlspecialchars(ucfirst((string)$perfil)) . '</small></span>';
    echo '<a class="logout" href="logout.php" title="Sair" aria-label="Sair">' . icone('sair') . '</a>';
    echo '</div>';
    echo '</aside>';

    // ---------- Conteúdo ----------
    echo '<main class="main-content">';
    echo '<section class="page-heading"><div>';
    echo '<h1>' . htmlspecialchars($titulo) . '</h1>';

    if ($descricao !== '') {
        echo '<p>' . htmlspecialchars($descricao) . '</p>';
    }

    echo '</div></section>';
}

function rodape() {
    echo '</main></div></body></html>';
}

function h($valor) {
    return htmlspecialchars(
        (string)($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

/**
 * CSS completo do sistema (cobre todas as classes usadas nas páginas).
 */
function estilo_base(): string {
    return <<<'CSS'
:root{
    --grafite:#1d2127;
    --grafite-2:#2a3038;
    --texto:#20262d;
    --suave:#667180;
    --linha:#e2e6ea;
    --fundo:#f1f3f5;
    --branco:#ffffff;
    --ambar:#f5a800;
    --ambar-escuro:#d98f00;
    --ambar-claro:#fff3cc;
    --verde:#1f9d63;
    --vermelho:#d64545;
    --azul:#2f6fdf;
    --fonte:"Barlow","Segoe UI",system-ui,-apple-system,Roboto,Arial,sans-serif;
    --fonte-titulo:"Barlow Condensed","Arial Narrow","Segoe UI",system-ui,sans-serif;
}

*,*::before,*::after{box-sizing:border-box}

body.app-body{
    margin:0;
    font-family:var(--fonte);
    font-size:15px;
    line-height:1.5;
    color:var(--texto);
    background:var(--fundo);
}

/* ---------- Estrutura ---------- */
.app-shell{display:grid;grid-template-columns:260px 1fr;min-height:100vh}

.sidebar{
    position:sticky;top:0;height:100vh;
    display:flex;flex-direction:column;
    background:var(--grafite);
    color:#b4bcc7;
}

/* faixa de sinalização: o único elemento "ousado" do sistema */
.sidebar-stripe{
    height:10px;flex:none;
    background:repeating-linear-gradient(-45deg,var(--ambar) 0 10px,var(--grafite) 10px 20px);
}

.sidebar-brand{display:flex;align-items:center;gap:12px;padding:22px 22px 18px}
.brand-mark{
    display:grid;place-items:center;width:38px;height:38px;border-radius:9px;
    background:var(--ambar);color:var(--grafite);
}
.brand-mark svg{width:22px;height:22px;stroke-width:2.2}
.brand-name{
    font-family:var(--fonte-titulo);font-weight:700;font-size:28px;
    color:#fff;letter-spacing:.3px;
}

.sidebar-nav{flex:1;overflow-y:auto;padding:4px 14px 12px}
.nav-group{margin-bottom:14px}
.nav-group-title{
    margin:0 0 4px;padding:0 10px;font-size:12.5px;color:#7d8896;font-weight:500;
}
.nav-link{
    display:flex;align-items:center;gap:12px;
    padding:9px 12px;margin-bottom:2px;border-radius:8px;
    color:#c4cbd5;text-decoration:none;font-weight:500;
    transition:background-color .15s,color .15s;
}
.nav-link:hover{background:rgba(255,255,255,.07);color:#fff}
.nav-link.ativo{background:var(--ambar);color:var(--grafite);font-weight:600}
.nav-icon{display:inline-flex;flex:none}

.sidebar-user{
    display:flex;align-items:center;gap:12px;
    padding:16px 18px;border-top:1px solid rgba(255,255,255,.09);
    background:var(--grafite-2);
}
.avatar{
    display:grid;place-items:center;flex:none;width:38px;height:38px;border-radius:50%;
    background:var(--ambar);color:var(--grafite);font-weight:700;font-size:16px;
}
.user-info{display:flex;flex-direction:column;min-width:0;flex:1}
.user-info strong{color:#fff;font-weight:600;font-size:14px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.user-info small{color:#97a1ae;font-size:12.5px}
.logout{
    display:grid;place-items:center;width:36px;height:36px;border-radius:8px;
    color:#c4cbd5;transition:background-color .15s,color .15s;
}
.logout:hover{background:rgba(255,255,255,.1);color:#fff}

.main-content{padding:34px 42px 48px;min-width:0;max-width:1240px;width:100%}

/* ---------- Título da página ---------- */
.page-heading{margin-bottom:26px}
.page-heading h1{
    margin:0;font-family:var(--fonte-titulo);font-weight:600;
    font-size:40px;line-height:1.1;color:var(--grafite);
}
.page-heading p{margin:6px 0 0;color:var(--suave);font-size:16px}

/* ---------- Indicadores ---------- */
.stats-grid{
    display:grid;gap:16px;margin-bottom:22px;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
}
.stat-card{
    --c:var(--suave);
    display:flex;flex-direction:column;gap:2px;
    padding:18px 22px 20px;
    background:var(--branco);border:1px solid var(--linha);
    border-left:6px solid var(--c);border-radius:6px 12px 12px 6px;
}
.stat-card span{color:var(--suave);font-size:14.5px;font-weight:500}
.stat-card strong{
    font-family:var(--fonte-titulo);font-weight:700;
    font-size:46px;line-height:1.1;color:var(--grafite);
}
.stat-card small{color:var(--suave);font-size:13.5px}
.stat-card.orange{--c:#f07c1f}
.stat-card.blue{--c:var(--azul)}
.stat-card.green{--c:var(--verde)}
.stat-card.red{--c:var(--vermelho)}
.stat-card.yellow{--c:var(--ambar)}
.stat-card.pale{--c:#98a2b0}
.stat-card.warning{--c:var(--vermelho);background:#fff8f8}

/* ---------- Cartões de conteúdo ---------- */
.content-card{
    background:var(--branco);border:1px solid var(--linha);
    border-radius:12px;padding:24px 26px;margin-bottom:22px;
}
.content-card h2{
    margin:0 0 18px;font-family:var(--fonte-titulo);font-weight:600;
    font-size:26px;color:var(--grafite);
}
.card-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap}
.card-heading h2{margin-bottom:18px}

/* ---------- Formulários ---------- */
.form-grid{
    display:grid;gap:18px 20px;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    align-items:end;
}
.form-grid label{display:block;margin-bottom:6px;font-size:14px;font-weight:600;color:var(--texto)}
.form-grid input,.form-grid select,.form-grid textarea{
    width:100%;padding:10px 12px;
    font:inherit;color:var(--texto);
    background:#fff;border:1px solid #c9d0d8;border-radius:6px;
    transition:border-color .15s,box-shadow .15s;
}
.form-grid input::placeholder{color:#9aa4b0}
.form-grid input:focus,.form-grid select:focus,.form-grid textarea:focus{
    outline:none;border-color:var(--ambar-escuro);
    box-shadow:0 0 0 3px rgba(245,168,0,.28);
}
.form-action{display:flex;align-items:flex-end}

.botao{
    display:inline-flex;align-items:center;justify-content:center;gap:8px;
    padding:10px 20px;font:inherit;font-weight:600;
    color:var(--grafite);background:var(--ambar);
    border:0;border-radius:6px;cursor:pointer;text-decoration:none;
    transition:background-color .15s,transform .05s;
}
.botao:hover{background:var(--ambar-escuro)}
.botao:active{transform:translateY(1px)}
.botao:focus-visible{outline:3px solid rgba(29,33,39,.45);outline-offset:2px}

/* ---------- Alertas ---------- */
.alerta{
    margin:0 0 22px;padding:13px 18px;border-radius:8px;
    border-left:5px solid;font-weight:500;
}
.alerta.sucesso{background:#e8f6ee;color:#14663f;border-color:var(--verde)}
.alerta.erro{background:#fcebeb;color:#9b2929;border-color:var(--vermelho)}

/* ---------- Tabelas ---------- */
.table-wrap{overflow-x:auto;border:1px solid var(--linha);border-radius:8px}
.table-wrap table{width:100%;border-collapse:collapse;background:#fff}
.table-wrap th{
    padding:11px 16px;text-align:left;white-space:nowrap;
    font-size:13.5px;font-weight:600;color:var(--suave);
    background:#f6f7f9;border-bottom:1px solid var(--linha);
}
.table-wrap td{padding:13px 16px;border-bottom:1px solid #eceff2;vertical-align:middle}
.table-wrap tbody tr:last-child td{border-bottom:0}
.table-wrap tbody tr:hover td{background:#fbfaf5}

.status{
    display:inline-block;padding:3px 11px;border-radius:999px;
    font-size:13px;font-weight:600;white-space:nowrap;
}
.status-ok{background:#e1f4ea;color:#14663f}
.status-aviso{background:var(--ambar-claro);color:#7a5600}
.status-alerta{background:#fbe3e3;color:#9b2929}

/* ---------- Telas menores ---------- */
@media (max-width:900px){
    .app-shell{grid-template-columns:1fr}
    .sidebar{position:static;height:auto}
    .sidebar-brand{padding:16px 18px 8px}
    .sidebar-nav{display:flex;gap:6px;overflow-x:auto;padding:6px 14px 12px}
    .nav-group{display:flex;gap:6px;margin:0}
    .nav-group-title{display:none}
    .nav-link{white-space:nowrap;margin:0}
    .sidebar-user{padding:12px 18px}
    .main-content{padding:24px 18px 40px}
    .page-heading h1{font-size:34px}
}

/* ---------- Impressão (botão "Imprimir relatório") ---------- */
@media print{
    .sidebar{display:none}
    .app-shell{display:block}
    body.app-body{background:#fff}
    .main-content{padding:0;max-width:none}
    .botao{display:none}
    .content-card,.stat-card{break-inside:avoid}
}

@media (prefers-reduced-motion:reduce){
    *{transition:none !important}
}
CSS;
}