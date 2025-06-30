<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/home3/ichnccl/public_html/norteamericanoconcepcion.cl/matriculas/error_log');
error_reporting(E_ALL);

session_start();
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/utilities.php';
require 'vendor/autoload.php'; // Asegúrate de que PhpSpreadsheet esté instalado y autoload.php esté disponible

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (!isset($_SESSION['token']) || !validate_jwt($_SESSION['token'])) {
    header('Location: login.php');
    exit;
}

$years = isset($_GET['years']) ? $_GET['years'] : date('Y');
$trimestre = isset($_GET['trimestre']) ? $_GET['trimestre'] : '01 First Quarter';
$curso = isset($_GET['curso']) ? $_GET['curso'] : '';
$profesor = isset($_GET['profesor']) ? $_GET['profesor'] : '';
$modalidad = isset($_GET['modalidad']) ? $_GET['modalidad'] : '';
$dias = isset($_GET['dias']) ? $_GET['dias'] : '';
$horario = isset($_GET['horario']) ? $_GET['horario'] : '';

$conditions = ['years = :years', 'trimestre = :trimestre'];
$params = ['years' => $years, 'trimestre' => $trimestre];

if ($curso) {
    $conditions[] = 'curso_matri LIKE :curso';
    $params['curso'] = "%$curso%";
}
if ($profesor) {
    $conditions[] = 'profesor LIKE :profesor';
    $params['profesor'] = "%$profesor%";
}
if ($modalidad) {
    $conditions[] = 'modalidad LIKE :modalidad';
    $params['modalidad'] = "%$modalidad%";
}
if ($dias) {
    $conditions[] = 'dias LIKE :dias';
    $params['dias'] = "%$dias%";
}
if ($horario) {
    $conditions[] = 'horario LIKE :horario';
    $params['horario'] = "%$horario%";
}

try {
    $sql = "SELECT * FROM matriculas WHERE " . implode(' AND ', $conditions);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error al ejecutar la consulta: " . $e->getMessage());
    $cursos = [];
}

if (isset($_GET['download'])) {
    if ($_GET['download'] == 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="informe_cursos.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM para UTF-8

        if (!empty($cursos)) {
            fputcsv($output, array_keys($cursos[0]));
            foreach ($cursos as $curso) {
                fputcsv($output, $curso);
            }
        }

        fclose($output);
        exit;
    } elseif ($_GET['download'] == 'xlsx') {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            if (!empty($cursos)) {
                $sheet->fromArray(array_keys($cursos[0]), NULL, 'A1');
                $sheet->fromArray($cursos, NULL, 'A2');
            }

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="informe_cursos.xlsx"');
            header('Cache-Control: max-age=0');
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        } catch (Exception $e) {
            error_log("Error al generar el archivo XLSX: " . $e->getMessage());
        }
    }
}

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bootstrap demo</title>
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"> -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
</head>

<body>
    <div class="container-lg">
        <h2 class="section-title">Revisar Cursos</h2>

        <form method="GET" class="needs-validation" novalidate>
            <div class="form-row">
                <div class="col-md-4 mb-3">
                    <label for="years">Año:</label>
                    <input type="number" class="form-control" id="years" name="years"
                        value="<?php echo htmlspecialchars($years, ENT_QUOTES, 'UTF-8'); ?>" min="2000" max="2100"
                        required>
                    <div class="invalid-feedback">
                        Por favor, ingrese un año válido.
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="trimestre">Trimestre:</label>
                    <select class="form-control" id="trimestre" name="trimestre" required>
                        <option value="01 First Quarter" <?php echo $trimestre == '01 First Quarter' ? 'selected' : ''; ?>>Primer Trimestre</option>
                        <option value="02 Second Quarter" <?php echo $trimestre == '02 Second Quarter' ? 'selected' : ''; ?>>Segundo Trimestre</option>
                        <option value="03 Third Quarter" <?php echo $trimestre == '03 Third Quarter' ? 'selected' : ''; ?>>Tercer Trimestre</option>
                        <option value="00 Summer" <?php echo $trimestre == '00 Summer' ? 'selected' : ''; ?>>Verano
                        </option>
                    </select>
                    <div class="invalid-feedback">
                        Por favor, seleccione un trimestre.
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="curso">Curso:</label>
                    <input type="text" class="form-control" id="curso" name="curso"
                        value="<?php echo htmlspecialchars($curso, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="col-md-4 mb-3">
                    <label for="profesor">Profesor:</label>
                    <input type="text" class="form-control" id="profesor" name="profesor"
                        value="<?php echo htmlspecialchars($profesor, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="modalidad">Modalidad:</label>
                    <input type="text" class="form-control" id="modalidad" name="modalidad"
                        value="<?php echo htmlspecialchars($modalidad, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="dias">Días:</label>
                    <input type="text" class="form-control" id="dias" name="dias"
                        value="<?php echo htmlspecialchars($dias, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="col-md-4 mb-3">
                    <label for="horario">Horario:</label>
                    <input type="text" class="form-control" id="horario" name="horario"
                        value="<?php echo htmlspecialchars($horario, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Buscar</button>
                <button type="submit" name="download" value="csv" class="btn btn-secondary">Descargar CSV</button>
                <button type="submit" name="download" value="xlsx" class="btn btn-secondary">Descargar XLSX</button>
            </div>
        </form>

        <?php if (!empty($cursos)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Curso</th>
                            <th>Profesor</th>
                            <th>Horario</th>
                            <th>Modalidad</th>
                            <th>Días</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cursos as $curso): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($curso['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($curso['curso_matri'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($curso['profesor'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($curso['horario'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($curso['modalidad'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($curso['dias'], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="no-results">No se encontraron cursos para los filtros seleccionados.</p>
        <?php endif; ?>
    </div>
</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
    // Example starter JavaScript for disabling form submissions if there are invalid fields
    (function () {
        'use strict';
        window.addEventListener('load', function () {
            // Fetch all the forms we want to apply custom Bootstrap validation styles to
            var forms = document.getElementsByClassName('needs-validation');
            // Loop over them and prevent submission
            var validation = Array.prototype.filter.call(forms, function (form) {
                form.addEventListener('submit', function (event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();
</script>
<?php include 'includes/footer.php'; ?>