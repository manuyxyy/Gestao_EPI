<?php
function buscar_todos(PDO $pdo, string $sql): array {
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}
?>
