<?php
require_once 'config/db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('error_log', 'error_log');
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    $anio = isset($_GET['anio']) ? $_GET['anio'] : null;

    if (!$anio) {
        throw new Exception('Año no especificado');
    }

    $sql = "SELECT DISTINCT trimestre FROM cursos WHERE anio = ? ORDER BY trimestre";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$anio]);
    $trimestres = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode(['success' => true, 'data' => $trimestres]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}