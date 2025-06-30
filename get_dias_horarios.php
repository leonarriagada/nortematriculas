<?php
require_once 'config/db.php';

header('Content-Type: application/json');

$curso_id = isset($_GET['curso_id']) ? $_GET['curso_id'] : null;

if (!$curso_id) {
    echo json_encode([]);
    exit;
}

try {
    $sql = "SELECT DISTINCT dias, horario FROM cursos WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$curso_id]);
    $dias_horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($dias_horarios);
} catch (PDOException $e) {
    error_log("Error en get_dias_horarios.php: " . $e->getMessage());
    echo json_encode([]);
}