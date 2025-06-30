<?php
require_once 'config/db.php';

header('Content-Type: application/json');

$anio = isset($_GET['anio']) ? $_GET['anio'] : null;
$trimestre = isset($_GET['trimestre']) ? $_GET['trimestre'] : null;

if (!$anio || !$trimestre) {
    echo json_encode([]);
    exit;
}

try {
    $sql = "SELECT DISTINCT categoria FROM cursos WHERE anio = ? AND trimestre = ? ORDER BY categoria";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$anio, $trimestre]);
    $categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode($categorias);
} catch (PDOException $e) {
    error_log("Error en get_categorias.php: " . $e->getMessage());
    echo json_encode([]);
}