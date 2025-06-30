<?php
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Verificar si se proporcionó un ID de curso
if (!isset($_GET['curso_id']) || !is_numeric($_GET['curso_id'])) {
    echo json_encode(['error' => 'ID de curso no válido']);
    exit;
}

$curso_id = intval($_GET['curso_id']);

try {
    // Preparar la consulta SQL
    $query = "SELECT e.id, e.rut, e.nombre, e.apellido_p, e.apellido_m, e.email, n.final_grade
          FROM estudiantes e
          JOIN matriculas m ON e.id = m.estudiante_id
          JOIN notas n ON m.notas_id = n.id
          WHERE m.curso_id = :curso_id";

    $stmt = $pdo->prepare($query);
    $stmt->execute([':curso_id' => $curso_id]);
    $alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->bindParam(':curso_id', $curso_id, PDO::PARAM_INT);



    // Verificar si se encontraron alumnos
    if (empty($alumnos)) {
        echo json_encode(['message' => 'No se encontraron alumnos matriculados en este curso']);
    } else {
        echo json_encode($alumnos);
    }
} catch (PDOException $e) {
    error_log("Error en obtener_alumnos.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error al obtener los alumnos']);
}
?>