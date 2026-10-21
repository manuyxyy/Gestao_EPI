
<?php
require_once 'auth.php';

function cabecalho($titulo, $descricao = '') {
    $pagina = basename($_SERVER['PHP_SELF']);

    $links = [
        'dashboard.php' => ['Início', '⌂'],
        'entregas.php' => ['Entregas', '▤'],
        'devolucoes.php' => ['Devoluções', '⟲'],
        'epis.php' => ['EPIs', '♧'],
        'funcionarios.php' => ['Funcionários', '♙'],
        'estoque.php' => ['Estoque', '▦'],
        'validade.php' => ['Validade', '◷'],
        'relatorios.php' => ['Relatórios', '▧'],
        'usuarios.php' => ['Usuários', '⚙'],
        'movimentacoes.php' => ['Movimentações', '↔']
    ];

    echo '<!DOCTYPE html><html lang="pt-BR"><head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . htmlspecialchars($titulo) . ' | EpiSafe</title>';
    echo '<link rel="stylesheet" href="css/style.css">';
    echo '</head><body class="app-body">';

    echo '<div class="app-shell"><aside class="sidebar">';
    echo '<div class="sidebar-brand">◈ EPISAFE</div><nav>';

    foreach ($links as $arquivo => $info) {
        $ativo = ($pagina === $arquivo) ? 'ativo' : '';

        echo '<a class="' . $ativo . '" href="' . $arquivo . '">';
        echo '<span class="nav-icon">' . $info[1] . '</span>';
        echo $info[0] . '</a>';
    }

    echo '</nav><div class="sidebar-user">';
    echo '<span class="avatar">👤</span><span>';
    echo htmlspecialchars($_SESSION['usuario'] ?? 'Usuário');
    echo '<small>';
    echo htmlspecialchars(ucfirst($_SESSION['perfil'] ?? ''));
    echo '</small></span></div></aside>';

    echo '<main class="main-content"><header class="topbar">';
    echo '<div class="search-box">⌕ <span>Buscar no sistema...</span></div>';
    echo '<div class="top-user">';
    echo htmlspecialchars($_SESSION['usuario'] ?? 'Usuário');
    echo ' <a href="logout.php">Sair</a>';
    echo '</div></header>';

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

function buscar_todos($conn, $sql) {
    try {
        return $conn->query($sql)->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}
?>
