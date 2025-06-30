<?php
session_start();
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de estudiante no válido']);
    exit;
}

$estudiante_id = intval($_GET['id']);

try {
    $query = "SELECT c.anio, c.trimestre, c.categoria, c.course, c.profesor, n.final_grade
              FROM cursos c
              INNER JOIN matriculas m ON c.id = m.curso_id
              INNER JOIN notas n ON m.notas_id = n.id
              WHERE m.estudiante_id = :estudiante_id
              ORDER BY c.anio DESC, c.trimestre DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':estudiante_id', $estudiante_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($cursos);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener los cursos']);
}