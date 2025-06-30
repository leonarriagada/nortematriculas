<?php
require_once 'config/db.php';
require_once 'includes/functions.php'; // Asegúrate de que este archivo exista y contenga funciones útiles

// Asegúrate de que no se envíe nada antes del JSON
ob_start();

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Verificar que el ID sea válido
    if ($id <= 0) {
        echo json_encode(['error' => 'ID inválido']);
        exit;
    }

    try {
        // Obtener los certificados del estudiante desde la base de datos
        $query = "SELECT c.id, c.estudiante_id, c.nota, c.curso, c.cefr, c.horas, c.trimestre, 
                         c.categoria, c.anio, c.serie, cu.id AS curso_id
                  FROM certificados c
                  JOIN cursos cu ON c.curso = cu.course AND c.anio = cu.anio AND c.trimestre = cu.trimestre
                  WHERE c.estudiante_id = :id
                  ORDER BY c.anio DESC, c.trimestre DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($certificados)) {
            echo json_encode(['message' => 'No se encontraron certificados para este estudiante']);
        } else {
            // Procesar los certificados si es necesario
            foreach ($certificados as &$certificado) {
                // Aquí puedes realizar cualquier procesamiento adicional si es necesario
                // Por ejemplo, formatear fechas, convertir valores, etc.
                $certificado['trimestre'] = ($certificado['trimestre']);
            }

            // Devolver los certificados como respuesta JSON
            echo json_encode($certificados);
        }
    } catch (Exception $e) {
        // Registrar el error para debugging
        error_log("Error en obtener_certificados.php: " . $e->getMessage());
        
        // Devolver una respuesta de error genérica
        echo json_encode(['error' => 'Ocurrió un error al obtener los certificados']);
    }
} else {
    // Devolver una respuesta de error si no se proporciona el ID
    echo json_encode(['error' => 'ID no proporcionado']);
}

ob_end_flush();

?>