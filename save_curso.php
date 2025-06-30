<?php
session_start();
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/utilities.php';

if (!isset($_SESSION['token']) || !validate_jwt($_SESSION['token'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : null;
$anio = isset($_POST['anio']) ? intval($_POST['anio']) : null;
$trimestre = isset($_POST['trimestre']) ? $_POST['trimestre'] : null;
$categoria = isset($_POST['categoria']) ? $_POST['categoria'] : null;
$horas = isset($_POST['horas']) ? intval($_POST['horas']) : null;
$cefr = isset($_POST['cefr']) ? $_POST['cefr'] : null;
$course = isset($_POST['course']) ? $_POST['course'] : null;
$dias = isset($_POST['dias']) ? $_POST['dias'] : null;
$modalidad = isset($_POST['modalidad']) ? $_POST['modalidad'] : null;
$horario = isset($_POST['horario']) ? $_POST['horario'] : null;
$profesor = isset($_POST['profesor']) ? $_POST['profesor'] : null;

try {
    if ($id) {
        // Actualizar curso existente
        $sql = "UPDATE cursos SET anio = :anio, trimestre = :trimestre, categoria = :categoria, horas = :horas, 
                cefr = :cefr, course = :course, dias = :dias, modalidad = :modalidad, horario = :horario, 
                profesor = :profesor, fecha_actualizacion = NOW() WHERE id = :id";
    } else {
        // Insertar nuevo curso
        $sql = "INSERT INTO cursos (anio, trimestre, categoria, horas, cefr, course, dias, modalidad, horario, 
                profesor, fecha_creacion, fecha_actualizacion) VALUES (:anio, :trimestre, :categoria, :horas, 
                :cefr, :course, :dias, :modalidad, :horario, :profesor, NOW(), NOW())";
    }

    $stmt = $pdo->prepare($sql);
    $params = [
        ':anio' => $anio,
        ':trimestre' => $trimestre,
        ':categoria' => $categoria,
        ':horas' => $horas,
        ':cefr' => $cefr,
        ':course' => $course,
        ':dias' => $dias,
        ':modalidad' => $modalidad,
        ':horario' => $horario,
        ':profesor' => $profesor
    ];

    if ($id) {
        $params[':id'] = $id;
    }

    $stmt->execute($params);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}