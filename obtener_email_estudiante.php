<?php
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// Asegúrate de que se proporcione un ID de estudiante
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'ID de estudiante no válido']);
    exit;
}

$estudiante_id = intval($_GET['id']);

try {
    $stmt = $pdo->prepare("SELECT email FROM estudiantes WHERE id = :id");
    $stmt->execute([':id' => $estudiante_id]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resultado) {
        echo json_encode(['email' => $resultado['email']]);
    } else {
        echo json_encode(['error' => 'Estudiante no encontrado']);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['error' => 'Error al obtener el email del estudiante']);
}