<?php
/**
 * Compatibilidade: o login agora fica na página inicial (index.php).
 */
$query = (string)($_SERVER['QUERY_STRING'] ?? '');

header('Location: index.php' . ($query !== '' ? '?' . $query : ''));
exit;
