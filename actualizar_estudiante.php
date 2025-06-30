<?php
// actualizar_estudiante.php
require_once 'config/config.php';
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nombre = isset($_POST['nombre']) ? $_POST['nombre'] : '';
    $apellido_p = isset($_POST['apellido_p']) ? $_POST['apellido_p'] : '';
    $apellido_m = isset($_POST['apellido_m']) ? $_POST['apellido_m'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $telefono = isset($_POST['telefono']) ? $_POST['telefono'] : '';

    if ($id > 0) {
        $query = "UPDATE estudiantes SET nombre = :nombre, apellido_p = :apellido_p, 
                  apellido_m = :apellido_m, email = :email, telefono = :telefono 
                  WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
        $stmt->bindParam(':apellido_p', $apellido_p, PDO::PARAM_STR);
        $stmt->bindParam(':apellido_m', $apellido_m, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':telefono', $telefono, PDO::PARAM_STR);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al actualizar el estudiante']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'ID de estudiante no válido']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método de solicitud no válido']);
}