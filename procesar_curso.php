<?php
session_start();
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['token']) || !validate_jwt($_SESSION['token'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// Función para sanitizar entradas
function sanitize_input($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accion = sanitize_input($_POST['accion']);
    $id = isset($_POST['id']) ? intval($_POST['id']) : null;
    $anio = intval($_POST['anio']);
    $trimestre = sanitize_input($_POST['trimestre']);
    $categoria = sanitize_input($_POST['categoria']);
    $course = sanitize_input($_POST['course']);
    $dias = implode(',', array_map('sanitize_input', $_POST['dias']));
    $modalidad = sanitize_input($_POST['modalidad']);
    $horario = sanitize_input($_POST['hora_inicio']) . ' a ' . sanitize_input($_POST['hora_fin']);
    $profesor = sanitize_input($_POST['profesor']);

    // Determinar horas y CEFR basado en el curso
    $horas = (strpos($course, 'Intensive') !== false) ? 72 : 36;
    $cefr_map = [
        "Beginners 1A" => "A1-", "Beginners 1B" => "A1", "Intensive 1-2" => "A2",
        "Intensive 3-4" => "B1-", "Intensive 5-6" => "B1+", "Intensive 1A-1B" => "A1",
        "Kids 1" => "A1-", "Kids 10" => "B2", "Kids 2" => "A1", "Kids 3" => "A2-",
        "Kids 4" => "A2", "Kids 5" => "A2+", "Kids 6" => "B1-", "Kids 7" => "B1",
        "Kids 8" => "B1+", "Kids 9" => "B2-", "K Special" => "A2", "Regular 1" => "A2-",
        "Regular 2" => "A2", "Regular 3" => "A2+", "Regular 4" => "B1-", "Regular 5" => "B1",
        "Regular 6" => "B1+", "Swep" => "B2-", "Teens 1" => "A1-", "TEP/Teens 10" => "B2",
        "Teens 2" => "A1", "Teens 3" => "A2-", "Teens 4" => "A2", "Teens 5" => "A2+",
        "Teens 6" => "B1-", "Teens 7" => "B1", "Teens 8" => "B1+", "TCG/Teens 9" => "B2-"
    ];
    $cefr = $cefr_map[$course] ?? 'N/A';

    try {
        if ($accion === 'anadir') {
            $stmt = $pdo->prepare("INSERT INTO cursos (anio, trimestre, categoria, horas, cefr, course, dias, modalidad, horario, profesor, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$anio, $trimestre, $categoria, $horas, $cefr, $course, $dias, $modalidad, $horario, $profesor]);
            $mensaje = "Curso añadido con éxito.";
        } elseif ($accion === 'actualizar' && $id) {
            $stmt = $pdo->prepare("UPDATE cursos SET anio = ?, trimestre = ?, categoria = ?, horas = ?, cefr = ?, course = ?, dias = ?, modalidad = ?, horario = ?, profesor = ?, fecha_actualizacion = NOW() WHERE id = ?");
            $stmt->execute([$anio, $trimestre, $categoria, $horas, $cefr, $course, $dias, $modalidad, $horario, $profesor, $id]);
            $mensaje = "Curso actualizado con éxito.";
        } else {
            throw new Exception("Acción no válida");
        }
        
        error_log('Course processed successfully: ' . $mensaje);
        echo json_encode(['success' => true, 'message' => $mensaje]);
    } catch (Exception $e) {
        error_log('Error processing course: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => "Error en la operación: " . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>