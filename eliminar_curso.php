<?php
session_start();
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

check_role('admin');
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'error_log'); // Asegúrate de especificar la ruta correcta a tu archivo de log
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        $query = "DELETE FROM cursos WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute([':id' => $id]);
        
        if ($result === false) {
            $errorInfo = $stmt->errorInfo();
            throw new PDOException("Error en la consulta: " . $errorInfo[2]);
        }
        
        if ($stmt->rowCount() > 0) {
            header('Location: crear_curso.php?mensaje=Curso eliminado con éxito');
        } else {
            throw new Exception("No se encontró el curso para eliminar");
        }
    } catch (PDOException $e) {
        error_log("Error de PDO: " . $e->getMessage());
        header('Location: crear_curso.php?error=' . urlencode($e->getMessage()));
    } catch (Exception $e) {
        error_log("Error general: " . $e->getMessage());
        header('Location: crear_curso.php?error=' . urlencode($e->getMessage()));
    }
} else {
    header('Location: crear_curso.php?error=Solicitud inválida');
}
exit;