<?php
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('error_log', 'error_log');
error_reporting(E_ALL);

// Recibir datos JSON
$datos = json_decode(file_get_contents('php://input'), true);

if (!isset($datos['email']) || !isset($datos['estudianteId']) || !isset($datos['cursoId'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$email = filter_var($datos['email'], FILTER_VALIDATE_EMAIL);
$estudiante_id = intval($datos['estudianteId']);
$curso_id = intval($datos['cursoId']);

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Email no válido']);
    exit;
}

try {
    // Generar el PDF del certificado
    $pdf_path = generarCertificadoPDF($estudiante_id, $curso_id, $pdo);

    if (!$pdf_path) {
        echo json_encode(['success' => false, 'message' => 'Error al generar el certificado']);
        exit;
    }

    // Obtener información del estudiante y del curso
    $stmt = $pdo->prepare("
        SELECT e.nombre, e.apellido_p, c.course as curso
        FROM estudiantes e
        JOIN matriculas m ON e.id = m.estudiante_id
        JOIN cursos c ON m.curso_id = c.id
        WHERE e.id = :estudiante_id AND c.id = :curso_id
    ");
    $stmt->execute([':estudiante_id' => $estudiante_id, ':curso_id' => $curso_id]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);

    // Enviar email con el certificado adjunto
    $mail = new PHPMailer(true);
    
    // Configuración del servidor de correo
    $mail->isSMTP();
    $mail->Host = 'smtp.office365.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'leonarriagada@norteamericanoconcepcion.cl';
    $mail->Password = 'LARmac0804@';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Configuración del email
    $mail->setFrom('leonarriagada@norteamericanoconcepcion.cl', 'Instituto Chileno Norteamericano de Cultura de Concepción');
    $mail->addAddress($email);
    $mail->Subject = 'Tu Certificado de Curso';
    $mail->Body = "Estimado/a {$info['nombre']} {$info['apellido_p']},\n\nAdjunto encontrarás tu certificado del curso {$info['curso']}.\n\nSaludos,\nInstituto Chileno Norteamericano de Cultura de Concepción";
    $mail->addAttachment($pdf_path);

    $mail->send();
    
    // Eliminar el archivo temporal
    unlink($pdf_path);

    echo json_encode(['success' => true, 'message' => 'Certificado enviado exitosamente']);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al enviar el certificado: ' . $e->getMessage()]);
}
