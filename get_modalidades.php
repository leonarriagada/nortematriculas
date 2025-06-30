<?php
require_once 'config/db.php';

header('Content-Type: application/json');

$anio = isset($_GET['anio']) ? $_GET['anio'] : null;
$trimestre = isset($_GET['trimestre']) ? $_GET['trimestre'] : null;
$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : null;

if (!$anio || !$trimestre || !$categoria) {
    echo json_encode([]);
    exit;
}

try {
    $sql = "SELECT DISTINCT modalidad FROM cursos WHERE anio = ? AND trimestre = ? AND categoria = ? ORDER BY modalidad";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$anio, $trimestre, $categoria]);
    $modalidades = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode($modalidades);
} catch (PDOException $e) {
    error_log("Error en get_modalidades.php: " . $e->getMessage());
    echo json_encode([]);
}