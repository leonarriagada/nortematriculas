<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('error_log', 'error_log');
error_reporting(E_ALL);
ob_start();
session_start();
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/utilities.php';
require_once 'includes/auth.php';

if (!isset($_SESSION['token']) || !validate_jwt($_SESSION['token'])) {
    header('Location: login.php');
    exit;
}

// Función para limpiar y codificar correctamente los strings
function cleanAndEncode($string)
{
    $string = trim($string);
    $string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');
    $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
    return $string;
}

// Función para sanitizar y formatear la nota
function sanitizeGrade($grade)
{
    if (empty($grade))
        return null;
    $grade = str_replace(' ', '', $grade);
    $grade = str_replace(',', '.', $grade);
    return number_format((float) $grade, 1, '.', '');
}

// Función para obtener o crear un curso
function getOrCreateCourse($pdo, $courseData)
{
    $stmt = $pdo->prepare("SELECT id FROM cursos WHERE anio = ? AND trimestre = ? AND course = ?");
    $stmt->execute([$courseData['anio'], $courseData['trimestre'], $courseData['course']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        return $result['id'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO cursos (anio, trimestre, categoria, horas, cefr, course, profesor) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $courseData['anio'],
            $courseData['trimestre'],
            $courseData['category'],
            $courseData['hours'],
            $courseData['cefr'],
            $courseData['course'],
            $courseData['profesor']
        ]);
        return $pdo->lastInsertId();
    }
}

// Función para obtener o crear un estudiante
function getOrCreateStudent($pdo, $studentData)
{
    if (empty($studentData['rut']))
        return null;

    $stmt = $pdo->prepare("SELECT id FROM estudiantes WHERE rut = ?");
    $stmt->execute([$studentData['rut']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // Actualizar datos del estudiante si ya existe
        $stmt = $pdo->prepare("UPDATE estudiantes SET nombre = ?, apellido_p = ?, apellido_m = ?, email = ? WHERE id = ?");
        $stmt->execute([
            $studentData['nombre'],
            $studentData['apellido_p'],
            $studentData['apellido_m'],
            $studentData['email'],
            $result['id']
        ]);
        return $result['id'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO estudiantes (rut, nombre, apellido_p, apellido_m, email) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $studentData['rut'],
            $studentData['nombre'],
            $studentData['apellido_p'],
            $studentData['apellido_m'],
            $studentData['email']
        ]);
        return $pdo->lastInsertId();
    }
}

// Función para crear una nota
function createNote($pdo, $finalGrade)
{
    $stmt = $pdo->prepare("INSERT INTO notas (final_grade) VALUES (?)");
    $stmt->execute([$finalGrade]);
    return $pdo->lastInsertId();
}

// Función para matricular a un estudiante
function enrollStudent($pdo, $studentId, $courseId, $notaId)
{
    if ($studentId === null || $courseId === null || $notaId === null)
        return;

    $stmt = $pdo->prepare("INSERT INTO matriculas (estudiante_id, curso_id, notas_id, fecha_matricula) VALUES (?, ?, ?, CURDATE())");
    $stmt->execute([$studentId, $courseId, $notaId]);
}

// Función para crear un certificado
function createCertificate($pdo, $studentId, $courseData, $finalGrade, $serie)
{
    if ($studentId === null)
        return;

    $stmt = $pdo->prepare("INSERT INTO certificados (estudiante_id, nota, curso, cefr, horas, trimestre, categoria, profesor, anio, serie) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $studentId,
        $finalGrade,
        $courseData['course'],
        $courseData['cefr'],
        $courseData['hours'],
        $courseData['trimestre'],
        $courseData['category'],
        $courseData['profesor'],
        $courseData['anio'],
        $serie
    ]);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["csvFile"])) {
    try {
        global $pdo;

        if (!isset($pdo) || !($pdo instanceof PDO)) {
            throw new Exception("La conexión a la base de datos no está disponible.");
        }

        $pdo->beginTransaction();

        $file = $_FILES["csvFile"]["tmp_name"];
        $handle = fopen($file, "r");

        if (!$handle) {
            throw new Exception("No se pudo abrir el archivo CSV.");
        }

        // Configurar la codificación UTF-8 para la lectura del CSV
        setlocale(LC_ALL, 'es_ES.UTF-8');

        // Saltar la primera línea (encabezados)
        fgetcsv($handle, 1000, ",");

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $rut = isset($data[0]) ? cleanAndEncode($data[0]) : null;
            $nombre = isset($data[1]) ? cleanAndEncode($data[1]) : null;
            $apellido_p = isset($data[2]) ? cleanAndEncode($data[2]) : null;
            $apellido_m = isset($data[3]) ? cleanAndEncode($data[3]) : null;
            $email = isset($data[4]) ? cleanAndEncode($data[4]) : null;
            $final_grade = isset($data[5]) ? sanitizeGrade($data[5]) : null;
            $course = isset($data[6]) ? cleanAndEncode($data[6]) : null;
            $cefr = isset($data[7]) ? cleanAndEncode($data[7]) : null;
            $hours = isset($data[8]) ? cleanAndEncode($data[8]) : null;
            $trimestre = isset($data[9]) ? cleanAndEncode($data[9]) : null;
            $category = isset($data[10]) ? cleanAndEncode($data[10]) : null;
            $profesor = isset($data[11]) ? cleanAndEncode($data[11]) : null;
            $anio = isset($data[12]) ? cleanAndEncode($data[12]) : null;
            $serie = isset($data[13]) ? cleanAndEncode($data[13]) : null;

            $studentData = [
                'rut' => $rut,
                'nombre' => $nombre,
                'apellido_p' => $apellido_p,
                'apellido_m' => $apellido_m,
                'email' => $email
            ];

            $courseData = [
                'anio' => $anio,
                'trimestre' => $trimestre,
                'category' => $category,
                'hours' => $hours,
                'cefr' => $cefr,
                'course' => $course,
                'profesor' => $profesor
            ];

            $studentId = getOrCreateStudent($pdo, $studentData);
            $courseId = getOrCreateCourse($pdo, $courseData);
            $notaId = $final_grade !== null ? createNote($pdo, $final_grade) : null;

            enrollStudent($pdo, $studentId, $courseId, $notaId);
            createCertificate($pdo, $studentId, $courseData, $final_grade, $serie);
        }

        fclose($handle);
        $pdo->commit();
        echo "Datos cargados exitosamente.";
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        echo "Error al cargar los datos: " . $e->getMessage();
        error_log("Error en subir_info.php: " . $e->getMessage());
    }
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir CSV</title>
</head>
<body>
    <h2>Subir archivo CSV</h2>
    <form action="subir_info.php" method="post" enctype="multipart/form-data">
        <input type="file" name="csvFile" accept=".csv" required>
        <input type="submit" value="Subir CSV">
    </form>
</body>
</html>