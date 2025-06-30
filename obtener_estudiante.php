<?php
// obtener_estudiante.php
require_once 'config/config.php';
require_once 'config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $query = "SELECT * FROM estudiantes WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($estudiante) {
        echo json_encode($estudiante);
    } else {
        echo json_encode(['error' => 'Estudiante no encontrado']);
    }
} else {
    echo json_encode(['error' => 'ID de estudiante no válido']);
}