<?php
require_once 'config/db.php';

header('Content-Type: application/json');

$anio = isset($_GET['anio']) ? $_GET['anio'] : null;
$trimestre = isset($_GET['trimestre']) ? $_GET['trimestre'] : null;
$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : null;
$modalidad = isset($_GET['modalidad']) ? $_GET['modalidad'] : null;

if (!$anio || !$trimestre || !$categoria || !$modalidad) {
    echo json_encode(['success' => false, 'error' => 'Faltan parámetros']);
    exit;
}

try {
    $sql = "SELECT id, course FROM cursos WHERE anio = ? AND trimestre = ? AND categoria = ? AND modalidad = ? ORDER BY course";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$anio, $trimestre, $categoria, $modalidad]);
    $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $cursos]);
} catch (PDOException $e) {
    error_log("Error en get_cursos.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error al obtener los cursos']);
}