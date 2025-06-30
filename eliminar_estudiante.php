<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

check_role('admin');

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['id'])) {
    $id = intval($data['id']);

    try {
        // Iniciar transacción
        $pdo->beginTransaction();

        // Verificar si el estudiante existe y no está ya desactivado
        $query = "SELECT activo FROM estudiantes WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$estudiante) {
            throw new Exception("Estudiante no encontrado");
        }

        if (!$estudiante['activo']) {
            throw new Exception("El estudiante ya está desactivado");
        }

        // Desactivar el estudiante en lugar de eliminarlo (soft delete)
        $query = "UPDATE estudiantes SET activo = 0 WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        // Commit la transacción
        $pdo->commit();

        // Devolver una respuesta exitosa
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        // Rollback en caso de error
        $pdo->rollBack();
        // Devolver una respuesta de error
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    // Devolver una respuesta de error si no se proporciona el ID
    echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
}
