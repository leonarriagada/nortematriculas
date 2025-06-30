<?php
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$id = $input['id'] ?? null;
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit;
}

$campos_permitidos = [
    'cp1',
    'cp2',
    'platform_progress',
    'platform_score',
    'oral_exam',
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
    'grade_cp1',
    'grade_cp2',
    'grade_oe'
];

$updates = [];
$params = ['id' => $id];

foreach ($campos_permitidos as $campo) {
    if (isset($input[$campo])) {
        $updates[] = "$campo = :$campo";
        $params[$campo] = formatearNota($input[$campo]);
    }
}

if (!empty($updates)) {
    try {
        $pdo->beginTransaction();

        $query = "UPDATE notas SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        $query = "SELECT * FROM notas WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $id]);
        $nota = $stmt->fetch(PDO::FETCH_ASSOC);

        // Cálculos para Academic Performance
        $average_cp = (isset($nota['cp1'], $nota['cp2'])) ? ($nota['cp1'] + $nota['cp2']) / 2 : null;
        $cp_average_30 = $average_cp !== null ? $average_cp * 0.3 : null;
        $platform_progress_15 = isset($nota['platform_progress']) ? $nota['platform_progress'] * 0.15 : null;
        $platform_score_15 = isset($nota['platform_score']) ? $nota['platform_score'] * 0.15 : null;
        $oral_exam_40 = isset($nota['oral_exam']) ? $nota['oral_exam'] * 0.4 : null;

        $final_grade = 0;
        if ($cp_average_30 !== null)
            $final_grade += $cp_average_30;
        if ($platform_progress_15 !== null)
            $final_grade += $platform_progress_15;
        if ($platform_score_15 !== null)
            $final_grade += $platform_score_15;
        if ($oral_exam_40 !== null)
            $final_grade += $oral_exam_40;

        // Cálculos para CP & Oral Exam Score
        foreach (['cp1', 'cp2', 'oe'] as $exam) {
            $total = 0;
            foreach (['content', 'fluency', 'pronunciation', 'grammar', 'vocabulary'] as $category) {
                $field = "{$category}_{$exam}";
                if (isset($nota[$field])) {
                    $total += $nota[$field];
                }
            }
            $nota["total_{$exam}"] = $total;
            $nota["grade_{$exam}"] = $total > 0 ? min(max(1 + (($total / 40) * 6), 1.0), 7.0) : null;
        }

        $calculated_fields = [
            'average_cp' => $average_cp,
            'cp_average_30' => $cp_average_30,
            'platform_progress_15' => $platform_progress_15,
            'platform_score_15' => $platform_score_15,
            'oral_exam_40' => $oral_exam_40,
            'final_grade' => $final_grade,
            'total_cp1' => $nota['total_cp1'],
            'total_cp2' => $nota['total_cp2'],
            'total_oe' => $nota['total_oe'],
            'grade_cp1' => $nota['grade_cp1'],
            'grade_cp2' => $nota['grade_cp2'],
            'grade_oe' => $nota['grade_oe']
        ];

        $update_calculated = [];
        $params_calculated = ['id' => $id];

        foreach ($calculated_fields as $field => $value) {
            if ($value !== null) {
                $update_calculated[] = "$field = :$field";
                $params_calculated[$field] = formatearNota($value);
            }
        }

        if (!empty($update_calculated)) {
            $query = "UPDATE notas SET " . implode(', ', $update_calculated) . " WHERE id = :id";
            $stmt = $pdo->prepare($query);
            $stmt->execute($params_calculated);
        }

        $pdo->commit();

        // Formatear todos los valores para la respuesta JSON
        $response_data = array_merge($nota, $calculated_fields);
        foreach ($response_data as $key => $value) {
            if (is_numeric($value)) {
                $response_data[$key] = formatearNota($value);
            }
        }

        echo json_encode([
            'success' => true,
            'updatedData' => $response_data
        ]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error en update_notas.php: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Error al actualizar las notas: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
} else {
    echo json_encode(['success' => true, 'message' => 'No se proporcionaron datos para actualizar']);
}