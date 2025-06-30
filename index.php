<?php
session_start();
require_once 'config/config.php';
require_once 'includes/utilities.php';

ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');
error_reporting(E_ALL);

// Verificar si el usuario está autenticado
if (isset($_SESSION['token']) && validate_jwt($_SESSION['token'])) {
    // Usuario autenticado, redirigir al panel de control
    header('Location: dashboard.php');
    exit;
} else {
    // Usuario no autenticado, redirigir a la página de inicio de sesión
    header('Location: login.php');
    exit;
}

