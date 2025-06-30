<?php

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/home3/ichnccl/public_html/norteamericanoconcepcion.cl/matriculas/error_log'); // Asegúrate de especificar la ruta correcta a tu archivo de log
error_reporting(E_ALL);

$host = 'localhost';
$db   = 'php_mariculas';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}