<?php
/**
 * Conexão com o banco de dados.
 *
 * Mantém as duas variáveis ($pdo e $conn) por compatibilidade com as páginas
 * antigas do sistema. A partir de agora, use sempre $pdo.
 */

$host    = 'localhost';
$db      = 'gestao_epis';
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // Datas iguais às do servidor MySQL (evita divergência de fuso horário).
    $pdo->exec("SET time_zone = '-03:00'");
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Falha na conexão com o banco: ' . $e->getMessage());

    exit(
        '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">'
        . '<title>Erro de conexão</title></head>'
        . '<body style="font-family:Segoe UI,Arial,sans-serif;background:#eceff3;'
        . 'display:grid;place-items:center;min-height:100vh;margin:0">'
        . '<div style="background:#fff;border:1px solid #d8dfe6;border-left:5px solid #c4372e;'
        . 'border-radius:6px;padding:28px 32px;max-width:520px">'
        . '<h1 style="margin:0 0 8px;font-size:20px;color:#1d2127">Banco de dados indisponível</h1>'
        . '<p style="margin:0;color:#586677">Não foi possível conectar ao MySQL. '
        . 'Verifique se o serviço está iniciado e se o banco <strong>gestao_epis</strong> existe.</p>'
        . '</div></body></html>'
    );
}

// Compatibilidade com o código antigo que usava $conn.
$conn = $pdo;
