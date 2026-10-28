<?php
// ARQUIVO TEMPORÁRIO DE PRÉ-VISUALIZAÇÃO — removido após os testes.
require_once __DIR__ . '/auth.php';

$permitidos = [
    'dashboard_admin.php', 'usuarios.php', 'perfil.php', 'entregas.php',
    'devolucoes.php', 'epis.php', 'funcionarios.php', 'estoque.php',
    'movimentacoes.php', 'validade.php', 'relatorios.php',
];

$p = basename((string)($_GET['p'] ?? 'dashboard_admin.php'));

if (!in_array($p, $permitidos, true)) {
    http_response_code(400);
    exit('Página inválida');
}

$_SESSION['usuario_id']     = 4;
$_SESSION['usuario_nome']   = 'Administrador';
$_SESSION['usuario_email']  = 'admin@episafe.local';
$_SESSION['usuario_perfil'] = 'Administrador';
$_SESSION['usuario_funcao'] = 'Administrador';
$_SESSION['perfil']         = 'Administrador';

$query = (string)($_GET['q'] ?? '');

redirecionar($p . ($query !== '' ? '?' . $query : ''));
