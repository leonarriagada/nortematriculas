<?php
ob_start();
session_start();
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/utilities.php';
require_once 'vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'error_log');
error_reporting(E_ALL);



function logMessage($message) {
    $logFile = 'error_log';
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

// Si se está accediendo directamente a este archivo (por ejemplo, para descargar el PDF)
if (isset($_GET['alumno_id']) && isset($_GET['curso_id']) && isset($_GET['action']) && $_GET['action'] == 'download') {
    if (!isset($_SESSION['token']) || !validate_jwt($_SESSION['token'])) {
        header('Location: login.php');
        exit;
    }

    $pdfContent = generarCertificadoPDF($_GET['alumno_id'], $_GET['curso_id'], $pdo);
    
    if ($pdfContent) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="certificado.pdf"');
        echo $pdfContent;
    } else {
        echo "No se pudo generar el certificado. Datos insuficientes.";
    }
    exit;
}
ob_end_clean();
