<?php
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['curso_id']) || !is_numeric($_GET['curso_id'])) {
    echo json_encode(['error' => 'ID de curso no válido']);
    exit;
}

$curso_id = intval($_GET['curso_id']);

try {
    // Primero, obtenemos el nombre del curso
    $query_curso = "SELECT course FROM cursos WHERE id = :curso_id";
    $stmt_curso = $pdo->prepare($query_curso);
    $stmt_curso->bindParam(':curso_id', $curso_id, PDO::PARAM_INT);
    $stmt_curso->execute();
    $curso = $stmt_curso->fetch(PDO::FETCH_ASSOC);

    if (!$curso) {
        echo json_encode(['error' => 'Curso no encontrado']);
        exit;
    }

    // Luego, obtenemos los estudiantes matriculados
    $query = "SELECT e.id, e.rut, e.nombre, e.apellido_p, e.apellido_m, e.email, n.final_grade
              FROM estudiantes e
              JOIN matriculas m ON e.id = m.estudiante_id
              JOIN notas n ON m.notas_id = n.id
              WHERE m.curso_id = :curso_id";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':curso_id', $curso_id, PDO::PARAM_INT);
    $stmt->execute();
    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Preparamos la respuesta
    $respuesta = [
        'curso' => $curso['course'],
        'estudiantes' => $estudiantes
    ];

    // Verificar si se encontraron estudiantes
    if (empty($estudiantes)) {
        $respuesta['message'] = 'No se encontraron alumnos matriculados en este curso';
    }

    echo json_encode($respuesta);

} catch (PDOException $e) {
    error_log("Error en obtener_matriculados.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error al obtener los alumnos matriculados']);
}