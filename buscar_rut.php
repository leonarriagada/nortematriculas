<?php
require_once 'config/config.php';
require_once 'config/db.php';

ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $rut = $_POST['rut'];

        $sql = "SELECT nombre, apellido_p, apellido_m, telefono, email FROM estudiantes WHERE rut = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$rut]);
        $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($estudiante) {
            echo json_encode(['success' => true, 'nombre' => $estudiante['nombre'], 'apellido_p' => $estudiante['apellido_p'], 'apellido_m' => $estudiante['apellido_m'], 'telefono' => $estudiante['telefono'], 'email' => $estudiante['email']]);
        } else {
            echo json_encode(['success' => false]);
        }
    } catch (Exception $e) {
        error_log("Error en buscar_rut.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
    }
}