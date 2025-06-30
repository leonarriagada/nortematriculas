<?php
file_put_contents('error_log', print_r($_GET, true) . "\n\n", FILE_APPEND);
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    require_once 'config/config.php';
    require_once 'config/db.php';
    require_once 'includes/functions.php';
    require_once 'includes/auth.php';

    if (!isset($_GET['curso_id'])) {
        throw new Exception('ID de curso no proporcionado');
    }

    $curso_id = intval($_GET['curso_id']);

    $query = "SELECT e.id as estudiante_id, e.nombre, e.apellido_p, e.apellido_m, e.rut, 
              n.id as nota_id, 
              n.cp1, 
              n.cp2, 
              n.average_cp,
              n.cp_average_30,
              n.platform_progress,
              n.platform_progress_15,
              n.platform_score,
              n.platform_score_15,
              n.oral_exam,
              n.oral_exam_40,
              n.final_grade,
              n.content_cp1,
              n.content_cp2,
              n.content_oe,
              n.fluency_cp1,
              n.fluency_cp2,
              n.fluency_oe,
              n.pronunciation_cp1,
              n.pronunciation_cp2,
              n.pronunciation_oe,
              n.grammar_cp1,
              n.grammar_cp2,
              n.grammar_oe,
              n.vocabulary_cp1,
              n.vocabulary_cp2,
              n.vocabulary_oe,
              n.total_cp1,
              n.total_cp2,
              n.total_oe,
              n.grade_cp1,
              n.grade_cp2,
              n.grade_oe
              FROM matriculas m
              JOIN estudiantes e ON m.estudiante_id = e.id
              LEFT JOIN notas n ON m.notas_id = n.id
              WHERE m.curso_id = :curso_id";

    $stmt = $pdo->prepare($query);
    $stmt->execute(['curso_id' => $curso_id]);
    $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear los valores numéricos
    foreach ($notas as &$nota) {
        $camposNumericos = [
            'cp1',
            'cp2',
            'average_cp',
            'cp_average_30',
            'platform_progress',
            'platform_progress_15',
            'platform_score',
            'platform_score_15',
            'oral_exam',
            'oral_exam_40',
            'final_grade',
            'content_cp1',
            'content_cp2',
            'content_oe',
            'fluency_cp1',
            'fluency_cp2',
            'fluency_oe',
            'pronunciation_cp1',
            'pronunciation_cp2',
            'pronunciation_oe',
            'grammar_cp1',
            'grammar_cp2',
            'grammar_oe',
            'vocabulary_cp1',
            'vocabulary_cp2',
            'vocabulary_oe',
            'total_cp1',
            'total_cp2',
            'total_oe',
            'grade_oral_cp1',
            'grade_oral_cp2',
            'grade_oral_oe'
        ];

        foreach ($camposNumericos as $campo) {
            if (isset($nota[$campo]) && $nota[$campo] !== '' && $nota[$campo] !== null) {
                $nota[$campo] = number_format(floatval($nota[$campo]), 1, '.', '');
            } else {
                $nota[$campo] = null;
            }
        }
    }

    echo json_encode(['success' => true, 'notas' => $notas]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}