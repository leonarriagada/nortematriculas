<?php
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/ms_graph_helper.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$alumnos = $input['alumnos'] ?? [];

$enviados = 0;
$errores = [];

$msGraph = new MSGraphHelper();

foreach ($alumnos as $alumno) {
    $alumnoId = $alumno['alumnoId'];
    $cursoId = $alumno['cursoId'];
    
    $pdfContent = generarCertificadoPDF($alumnoId, $cursoId, $pdo);
    
    if (!$pdfContent) {
        $errores[] = "Error al generar certificado para alumno ID: $alumnoId";
        continue;
    }
    
    $stmt = $pdo->prepare("SELECT email FROM estudiantes WHERE id = ?");
    $stmt->execute([$alumnoId]);
    $email = $stmt->fetchColumn();
    
    if (!$email) {
        $errores[] = "No se encontró email para alumno ID: $alumnoId";
        continue;
    }
    
    $subject = "Tu certificado de curso";
    $body = "Adjunto encontrarás tu certificado de curso. Felicitaciones por completar el curso exitosamente.";
    
    if ($msGraph->sendMail($email, $subject, $body, $pdfContent, 'certificado.pdf')) {
        $enviados++;
    } else {
        $errores[] = "Error al enviar certificado para alumno ID: $alumnoId";
    }
}

if ($enviados === count($alumnos)) {
    echo json_encode(['success' => true, 'enviados' => $enviados]);
} else {
    echo json_encode(['success' => false, 'error' => implode(", ", $errores), 'enviados' => $enviados]);
}