<?php
session_start();
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/utilities.php';

if (!isset($_SESSION['token']) || !validate_jwt($_SESSION['token'])) {
    header('Location: login.php');
    exit;
}

// Inicialización de variables
$anio_filtro = isset($_GET['anio']) ? (int) $_GET['anio'] : date('Y');
$trimestre_filtro = isset($_GET['trimestre']) ? $_GET['trimestre'] : '';
$current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 20; // Número de resultados por página
$offset = ($current_page - 1) * $limit;
$sortColumn = isset($_GET['sort']) ? $_GET['sort'] : 'anio';
$sortOrder = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'DESC' : 'ASC';

// Consulta SQL principal
$query = "SELECT * FROM cursos WHERE anio = :anio";
$params = [':anio' => $anio_filtro];

if (!empty($trimestre_filtro)) {
    $query .= " AND trimestre = :trimestre";
    $params[':trimestre'] = $trimestre_filtro;
}

$query .= " ORDER BY $sortColumn $sortOrder LIMIT :limit OFFSET :offset";
$params[':limit'] = $limit;
$params[':offset'] = $offset;

// Obtener los cursos filtrados y calcular el total de páginas
try {
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, (is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR));
    }

    $stmt->execute();
    $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener el total de registros para la paginación
    $countQuery = "SELECT COUNT(*) FROM cursos WHERE anio = :anio";
    $countParams = [':anio' => $anio_filtro];

    if (!empty($trimestre_filtro)) {
        $countQuery .= " AND trimestre = :trimestre";
        $countParams[':trimestre'] = $trimestre_filtro;
    }

    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($countParams);
    $total_items = $countStmt->fetchColumn();

    $total_pages = ceil($total_items / $limit);
} catch (Exception $e) {
    error_log($e->getMessage());
    $cursos = [];
    $message = "Error al cargar los cursos: " . $e->getMessage();
}

// Procesar formulario de añadir/editar curso
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accion = $_POST['accion'] ?? '';
    $datos = [
        'anio' => (int) ($_POST['anio'] ?? 0),
        'trimestre' => sanitize_input($_POST['trimestre'] ?? ''),
        'categoria' => sanitize_input($_POST['categoria'] ?? ''),
        'course' => sanitize_input($_POST['course'] ?? ''),
        'dias' => sanitize_input($_POST['dias'] ?? ''),
        'horario' => sanitize_input($_POST['horario'] ?? ''),
        'profesor' => sanitize_input($_POST['profesor'] ?? ''),
        'modalidad' => sanitize_input($_POST['modalidad'] ?? '')
    ];

    try {
        if ($accion === 'anadir') {
            $result = anadirCurso($pdo, $datos, $cefr_map);
            $message = $result ? "Curso añadido con éxito." : "Error al añadir el curso.";
        } elseif ($accion === 'actualizar') {
            $id = (int) ($_POST['id'] ?? 0);
            $result = actualizarCurso($pdo, $id, $datos, $cefr_map);
            $message = $result ? "Curso actualizado con éxito." : "Error al actualizar el curso.";
        }

        if ($result) {
            $_SESSION['message'] = $message;
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        $message = "Error en la operación del curso: " . $e->getMessage();
    }
}

// Manejar la descarga de CSV
if (isset($_GET['descargar_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="cursos_filtrados.csv"');
    if (ob_get_length())
        ob_end_clean();
    generarCSV($cursos);
    exit;
}

// Función para generar CSV
function generarCSV($cursos)
{
    $output = fopen('php://output', 'w');
    fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

    fputcsv($output, ['Año', 'Trimestre', 'Curso', 'Días', 'Horario', 'Modalidad', 'Profesor']);

    foreach ($cursos as $curso) {
        // Convertir cada valor a UTF-8 si no lo está ya
        $row = array_map(function ($value) {
            return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }, [
            $curso['anio'],
            $curso['trimestre'],
            $curso['course'],
            $curso['dias'],
            $curso['horario'],
            $curso['modalidad'],
            $curso['profesor']
        ]);
        fputcsv($output, $row);
    }

    fclose($output);
}

function getHoras($course)
{
    if (strpos($course, 'Intensive') !== false) {
        return 72;
    } else {
        return 36;
    }
}

function anadirCurso($pdo, $datos, $cefr_map): bool
{
    $query = "INSERT INTO cursos (anio, trimestre, categoria, horas, cefr, course, dias, horario, profesor, modalidad) 
              VALUES (:anio, :trimestre, :categoria, :horas, :cefr, :course, :dias, :horario, :profesor, :modalidad)";
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute([
        ':anio' => $datos['anio'],
        ':trimestre' => $datos['trimestre'],
        ':categoria' => $datos['categoria'],
        ':horas' => getHoras($datos['course']),
        ':cefr' => $cefr_map[$datos['course']] ?? 'N/A',
        ':course' => $datos['course'],
        ':dias' => $datos['dias'],
        ':horario' => $datos['horario'],
        ':profesor' => $datos['profesor'],
        ':modalidad' => $datos['modalidad']
    ]);
    return $result;
}

function actualizarCurso($pdo, $id, $datos, $cefr_map): bool
{
    $query = "UPDATE cursos SET anio = :anio, trimestre = :trimestre, categoria = :categoria, 
              horas = :horas, cefr = :cefr, course = :course, dias = :dias, horario = :horario, 
              profesor = :profesor, modalidad = :modalidad, fecha_actualizacion = CURRENT_TIMESTAMP WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute([
        ':id' => $id,
        ':anio' => $datos['anio'],
        ':trimestre' => $datos['trimestre'],
        ':categoria' => $datos['categoria'],
        ':horas' => getHoras($datos['course']),
        ':cefr' => $cefr_map[$datos['course']] ?? 'N/A',
        ':course' => $datos['course'],
        ':dias' => $datos['dias'],
        ':horario' => $datos['horario'],
        ':profesor' => $datos['profesor'],
        ':modalidad' => $datos['modalidad']
    ]);
    return $result;
}

include 'includes/header.php';

?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Cursos</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.1.4/css/dataTables.dataTables.min.css">

    <!-- Iconos de fontawesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">


    <style>
        .alert {
            margin-top: 2rem;
        }

        .modal-content {
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
        }

        .modal-header {
            border-bottom-color: var(--bs-border-color);
        }

        .modal-footer {
            border-top-color: var(--bs-border-color);
        }

        .close {
            color: var(--bs-body-color);
        }


        .table {
            color: var(--bs-body-color);
        }

        .table-responsive {
            overflow-x: visible;
        }

        #cursosTable {
            width: 95% !important;
        }

        .form-control {
            margin: 0px 50px 0px;
            width: 200px;

        }

        .pagination .page-link {
            width: 40px;
            height: 40px;
            line-height: 28px;
            text-align: center;
            margin: 0 5px;
        }

        .pagination .page-item.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
        }
    </style>

</head>

<body>

    <div class="container-fluid py-4">
        <h1 class="text-center mb-4">Gestión de Cursos</h1>

        <!-- Formulario de filtro -->
        <form action="" method="GET" class="mb-4">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="d-flex">
                            <div class="flex-grow-1 me-3">
                                <div class="mb-3">
                                    <select class="form-select" id="filter_anio" name="anio">
                                        <option value="">Seleccione año</option>
                                        <?php
                                        $currentYear = date('Y');
                                        for ($i = $currentYear - 2; $i <= $currentYear + 2; $i++) {
                                            $selected = ($i == $anio_filtro) ? 'selected' : '';
                                            echo "<option value=\"$i\" $selected>$i</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="mb-3">

                                    <select class="form-select" id="filter_trimestre" name="trimestre">
                                        <option value="">Todos los trimestres</option>
                                        <option value="First Quarter" <?php echo ($trimestre_filtro == 'First Quarter') ? 'selected' : ''; ?>>First Quarter</option>
                                        <option value="Second Quarter" <?php echo ($trimestre_filtro == 'Second Quarter') ? 'selected' : ''; ?>>Second Quarter</option>
                                        <option value="Third Quarter" <?php echo ($trimestre_filtro == 'Third Quarter') ? 'selected' : ''; ?>>Third Quarter</option>
                                        <option value="Summer" <?php echo ($trimestre_filtro == 'Summer') ? 'selected' : ''; ?>>Summer</option>
                                    </select>
                                </div>
                            </div>
                            <div class="d-flex flex-column justify-content-start">
                                <button type="submit" class="btn btn-primary mb-2">Filtrar</button>
                                <button type="button" class="btn btn-success" onclick="descargarCSV()">Descargar
                                    CSV</button>

                            </div>
                            <div class="d-flex align-items-center">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#modalCurso">
                                    Añadir Curso
                                </button>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>


        <div class="d-flex justify-content-between mb-3">
            <a>Total de cursos: <?php echo $total_items; ?></a>
        </div>

        <?php if (empty($cursos)): ?>
            <div class="alert alert-info" role="alert">
                No hay cursos disponibles para los filtros seleccionados.
            </div>
        <?php else: ?>

            <!-- HTML y tabla -->
            <div class="table-responsive">
                <table id="cursosTable" class="table table-striped table-hover table-bordered">
                    <thead>
                        <tr>
                            <th>Año</th>
                            <th>Trimestre</th>
                            <th>Curso</th>
                            <th>Días</th>
                            <th>Horario</th>
                            <th>Modalidad</th>
                            <th>Profesor</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="table-group-divider">
                        <?php foreach ($cursos as $curso): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($curso['anio']); ?></td>
                                <td><?php echo htmlspecialchars($curso['trimestre']); ?></td>
                                <td><?php echo htmlspecialchars($curso['course']); ?></td>
                                <td><?php echo htmlspecialchars($curso['dias']); ?></td>
                                <td><?php echo htmlspecialchars($curso['horario']); ?></td>
                                <td><?php echo htmlspecialchars($curso['modalidad']); ?></td>
                                <td><?php echo htmlspecialchars($curso['profesor']); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-warning editar-curso" data-bs-toggle="modal"
                                        data-bs-target="#modalCurso"
                                        data-curso='<?php echo htmlspecialchars(json_encode($curso), ENT_QUOTES, 'UTF-8'); ?>'>
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <button class="btn btn-sm btn-danger eliminar-curso"
                                        data-id="<?php echo htmlspecialchars($curso['id']); ?>">
                                        <i class="fas fa-trash-alt"></i> Eliminar
                                    </button>
                                    <button class="btn btn-sm btn-success ver-matriculas" data-bs-toggle="modal"
                                        data-bs-target="#modalMatriculados"
                                        data-id="<?php echo htmlspecialchars($curso['id']); ?>">
                                        <i class="fas fa-users"></i> Matriculados
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $current_page == 1 ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-circle"
                            href="?page=<?php echo $current_page - 1; ?><?php echo $url_params; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php
                    $visible_pages = 5;
                    $start_page = max(1, $current_page - floor($visible_pages / 2));
                    $end_page = min($total_pages, $start_page + $visible_pages - 1);
                    $start_page = max(1, $end_page - $visible_pages + 1);

                    for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                        <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                            <a class="page-link rounded-circle"
                                href="?page=<?php echo $i . $url_params; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $current_page == $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-circle"
                            href="?page=<?php echo $current_page + 1; ?><?php echo $url_params; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>

        <?php endif; ?>
        <p>&nbsp;</p>
    </div>

    <!-- Modal para Añadir/Editar Curso -->
    <div class="modal fade" id="modalCurso" tabindex="-1" aria-labelledby="modalCursoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCursoLabel">Añadir/Editar Curso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formCurso" method="POST">
                        <input type="hidden" name="id" id="cursoId">
                        <input type="hidden" name="accion" id="accion" value="anadir">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="anio" class="form-label">Año</label>
                                <select class="form-select" id="anio" name="anio" required>
                                    <?php
                                    $currentYear = date('Y');
                                    for ($i = $currentYear - 2; $i <= $currentYear + 2; $i++) {
                                        echo "<option value=\"$i\">$i</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="trimestre" class="form-label">Trimestre</label>
                                <select class="form-select" id="trimestre" name="trimestre" required>
                                    <option value="">Seleccione un Trimestre</option>
                                    <option value="First Quarter">First Quarter</option>
                                    <option value="Second Quarter">Second Quarter</option>
                                    <option value="Third Quarter">Third Quarter</option>
                                    <option value="Summer">Summer</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="modalidad" class="form-label">Modalidad</label>
                                <select class="form-select" id="modalidad" name="modalidad" required>
                                    <option value="">Seleccione Modalidad</option>
                                    <option value="Presencial">Presencial</option>
                                    <option value="Online">Online</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="categoria" class="form-label">Categoría</label>
                                <select class="form-select" id="categoria" name="categoria" required>
                                    <option value="">Seleccione una Categoría</option>
                                    <option value="Adults">Adults</option>
                                    <option value="Teens">Teens</option>
                                    <option value="Kids">Kids</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="course" class="form-label">Curso</label>
                            <select class="form-select" id="course" name="course" required>
                                <option value="">Seleccione un Curso</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="profesor" class="form-label">Profesor</label>
                            <select class="form-select" id="profesor" name="profesor" required>
                                <option value="">Seleccione un Profesor</option>
                                <option value="Areli Collao">Areli Collao</option>
                                <option value="Brenda Perez">Brenda Perez</option>
                                <option value="Carola Almendra">Carola Almendra</option>
                                <option value="Carolina Rojas">Carolina Rojas</option>
                                <option value="Cinthia Bustos">Cinthia Bustos</option>
                                <option value="Cristopher Gatica">Cristopher Gatica</option>
                                <option value="Daniela Melipillan">Daniela Melipillan</option>
                                <option value="Helen Mendoza">Helen Mendoza</option>
                                <option value="Karina Ormeño">Karina Ormeño</option>
                                <option value="Maite Morales">Maite Morales</option>
                                <option value="Marcelo Sandoval">Marcelo Sandoval</option>
                                <option value="Nadia Fernandez">Nadia Fernandez</option>
                                <option value="Nancy Valenzuela">Nancy Valenzuela</option>
                                <option value="Olaya Melo">Olaya Melo</option>
                                <option value="Paula Torres">Paula Torres</option>
                                <option value="Pilar Escobar">Pilar Escobar</option>
                                <option value="Rodrigo Hormazabal">Rodrigo Hormazabal</option>
                                <option value="Sandra Araya">Sandra Araya</option>
                                <option value="Tomas Romero">Tomas Romero</option>
                                <option value="Ximena Landeros">Ximena Landeros</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Días</label>
                            <div id="dias-container">
                                <div class="input-group mb-2">
                                    <select class="form-select" name="dias[]" required>
                                        <option value="">Seleccione un día</option>
                                        <option value="Lunes">Lunes</option>
                                        <option value="Martes">Martes</option>
                                        <option value="Miércoles">Miércoles</option>
                                        <option value="Jueves">Jueves</option>
                                        <option value="Viernes">Viernes</option>
                                        <option value="Sábado">Sábado</option>
                                        <option value="Domingo">Domingo</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary agregar-dia">+</button>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Horario</label>
                            <div class="row g-3 align-items-center">
                                <div class="col-auto">
                                    <input type="time" class="form-control" id="hora_inicio" name="hora_inicio"
                                        required>
                                </div>
                                <div class="col-auto">
                                    <span class="form-text">a</span>
                                </div>
                                <div class="col-auto">
                                    <input type="time" class="form-control" id="hora_fin" name="hora_fin" required>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">Guardar</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para mostrar estudiantes matriculados -->
    <div class="modal fade" id="modalMatriculados" tabindex="-1" aria-labelledby="modalMatriculadosLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalMatriculadosLabel">Estudiantes Matriculados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>RUT</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Nota Final</th>
                                <th style="text-align:center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="matriculadosBody">
                            <!-- Los datos de los estudiantes se cargarán aquí dinámicamente -->
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="descargarCSV">Descargar a Excel</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>

    <!-- DataTables JavaScript -->
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/2.1.4/js/dataTables.min.js"></script>
    <script type="text/javascript" charset="utf8"
        src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.min.js"></script>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>

        $(document).ready(function () {
            $('#cursosTable').DataTable({
                "responsive": true,
                "searching": false,
                "paging": false,
                "info": false,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
                }
            });
        });


        document.addEventListener('DOMContentLoaded', function () {
            const categoriaSelect = document.getElementById('categoria');
            const cursoSelect = document.getElementById('course');
            const horaInicio = document.getElementById('hora_inicio');
            const horaFin = document.getElementById('hora_fin');
            const diasContainer = document.getElementById('dias-container');
            const formCurso = document.getElementById('formCurso');
            const modalCurso = document.getElementById('modalCurso');

            const cursosPorCategoria = {
                'Adults': ['Beginners 1A', 'Beginners 1B', 'Intensive 1-2', 'Intensive 3-4', 'Intensive 5-6', 'Intensive 1A-1B', 'Regular 1', 'Regular 2', 'Regular 3', 'Regular 4', 'Regular 5', 'Regular 6', 'Swep'],
                'Kids': ['Kids 1', 'Kids 2', 'Kids 3', 'Kids 4', 'Kids 5', 'Kids 6', 'Kids 7', 'Kids 8', 'Kids 9', 'Kids 10', 'K Special'],
                'Teens': ['Teens 1', 'Teens 2', 'Teens 3', 'Teens 4', 'Teens 5', 'Teens 6', 'Teens 7', 'Teens 8', 'TCG/Teens 9', 'TEP/Teens 10']
            };

            if (categoriaSelect) {
                categoriaSelect.addEventListener('change', function () {
                    const categoria = this.value;
                    if (cursoSelect) {
                        cursoSelect.innerHTML = '<option value="">Seleccione un Curso</option>';
                        if (cursosPorCategoria[categoria]) {
                            cursosPorCategoria[categoria].forEach(curso => {
                                const option = document.createElement('option');
                                option.value = curso;
                                option.textContent = curso;
                                cursoSelect.appendChild(option);
                            });
                        }
                    }
                });
            }

            if (modalCurso) {
                modalCurso.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    if (button && button.hasAttribute('data-curso')) {
                        const cursoData = JSON.parse(button.getAttribute('data-curso'));
                        if (cursoData) {
                            const cursoIdInput = document.getElementById('cursoId');
                            const accionInput = document.getElementById('accion');
                            const anioSelect = document.getElementById('anio');
                            const trimestreSelect = document.getElementById('trimestre');
                            const categoriaSelect = document.getElementById('categoria');
                            const courseSelect = document.getElementById('course');
                            const profesorSelect = document.getElementById('profesor');
                            const modalidadSelect = document.getElementById('modalidad');

                            if (cursoIdInput) cursoIdInput.value = cursoData.id;
                            if (accionInput) accionInput.value = 'actualizar';
                            if (anioSelect) anioSelect.value = cursoData.anio;
                            if (trimestreSelect) trimestreSelect.value = cursoData.trimestre;
                            if (categoriaSelect) {
                                categoriaSelect.value = cursoData.categoria;
                                categoriaSelect.dispatchEvent(new Event('change'));
                            }

                            setTimeout(() => {
                                if (courseSelect) courseSelect.value = cursoData.course;
                            }, 100);

                            if (profesorSelect) profesorSelect.value = cursoData.profesor;

                            if (modalidadSelect) modalidadSelect.value = cursoData.modalidad;

                            if (diasContainer) {
                                const diasArray = cursoData.dias.split(', ');
                                diasContainer.innerHTML = '';
                                diasArray.forEach((dia, index) => {
                                    if (index === 0) {
                                        const firstSelect = diasContainer.querySelector('select');
                                        if (firstSelect) {
                                            firstSelect.value = dia;
                                        } else {
                                            agregarDia();
                                            diasContainer.querySelector('select').value = dia;
                                        }
                                    } else {
                                        agregarDia();
                                        diasContainer.lastElementChild.querySelector('select').value = dia;
                                    }
                                });
                            }

                            if (horaInicio && horaFin) {
                                const [horaInicioValue, horaFinValue] = cursoData.horario.split(' a ');
                                horaInicio.value = horaInicioValue;
                                horaFin.value = horaFinValue;
                            }
                        }
                    }
                });
            }

            const botonesEliminar = document.querySelectorAll('.eliminar-curso');

            botonesEliminar.forEach(boton => {
                boton.addEventListener('click', function (e) {
                    e.preventDefault();
                    const cursoId = this.getAttribute('data-id');
                    if (cursoId && confirm('¿Estás seguro de que quieres eliminar este curso?')) {
                        fetch('eliminar_curso.php?id=' + cursoId)
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Error en la respuesta del servidor');
                                }
                                return response.text();
                            })
                            .then(() => {
                                window.location.reload();
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('Hubo un error al eliminar el curso. Por favor, inténtalo de nuevo.');
                            });
                    }
                });
            });

            if (horaInicio) {
                horaInicio.addEventListener('change', function () {
                    if (this.value && horaFin) {
                        const inicio = new Date(`2000-01-01T${this.value}`);
                        inicio.setMinutes(inicio.getMinutes() + 90);
                        const fin = inicio.toTimeString().slice(0, 5);
                        horaFin.value = fin;
                    } else if (horaFin) {
                        horaFin.value = '';
                    }
                });
            }

            function agregarDia() {
                if (diasContainer) {
                    const nuevoDia = document.createElement('div');
                    nuevoDia.className = 'input-group mb-2';
                    nuevoDia.innerHTML = `
                <select class="form-select" name="dias[]" required>
                    <option value="">Seleccione un día</option>
                    <option value="Lunes">Lunes</option>
                    <option value="Martes">Martes</option>
                    <option value="Miércoles">Miércoles</option>
                    <option value="Jueves">Jueves</option>
                    <option value="Viernes">Viernes</option>
                    <option value="Sábado">Sábado</option>
                    <option value="Domingo">Domingo</option>
                </select>
                <button type="button" class="btn btn-outline-secondary quitar-dia">-</button>
                <button type="button" class="btn btn-outline-secondary agregar-dia">+</button>
            `;
                    diasContainer.appendChild(nuevoDia);
                }
            }

            if (diasContainer) {
                diasContainer.addEventListener('click', function (e) {
                    if (e.target.classList.contains('agregar-dia')) {
                        agregarDia();
                    } else if (e.target.classList.contains('quitar-dia')) {
                        e.target.closest('.input-group').remove();
                    }
                });
            }

            if (formCurso) {
                formCurso.addEventListener('submit', function (e) {
                    e.preventDefault();

                    if (horaInicio && horaFin) {
                        const horaInicioValue = horaInicio.value;
                        const horaFinValue = horaFin.value;
                        const horario = `${horaInicioValue} a ${horaFinValue}`;

                        const diasSeleccionados = Array.from(document.querySelectorAll('select[name="dias[]"]'))
                            .map(select => select.value)
                            .filter(dia => dia !== "");

                        const dias = diasSeleccionados.join(', ');

                        const horarioInput = document.createElement('input');
                        horarioInput.type = 'hidden';
                        horarioInput.name = 'horario';
                        horarioInput.value = horario;

                        const diasInput = document.createElement('input');
                        diasInput.type = 'hidden';
                        diasInput.name = 'dias';
                        diasInput.value = dias;

                        formCurso.appendChild(horarioInput);
                        formCurso.appendChild(diasInput);
                    }

                    formCurso.submit();
                });
            }

            // Función para actualizar la URL con los parámetros de ordenación
            function updateSortingUrl(column) {
                const urlParams = new URLSearchParams(window.location.search);
                const currentSort = urlParams.get('sort');
                const currentOrder = urlParams.get('order');

                if (currentSort === column) {
                    urlParams.set('order', currentOrder === 'asc' ? 'desc' : 'asc');
                } else {
                    urlParams.set('sort', column);
                    urlParams.set('order', 'asc');
                }

                window.location.search = urlParams.toString();
            }

            // Agregar event listeners a los encabezados de columna para ordenación
            document.querySelectorAll('th a').forEach(header => {
                header.addEventListener('click', function (e) {
                    e.preventDefault();
                    const column = this.getAttribute('href').split('sort=')[1].split('&')[0];
                    updateSortingUrl(column);
                });
            });

        });

        document.getElementById('modalMatriculados').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const cursoId = button.getAttribute('data-id');
            cargarEstudiantesMatriculados(cursoId);
        });

        function descargarCSV(datos, nombreArchivo) {
            let csvContent = "data:text/csv;charset=utf-8,";

            // Agregar encabezados
            csvContent += "RUT,Nombre,Apellidos,Email\n";

            // Agregar datos
            datos.forEach(function (estudiante) {
                let row = `${estudiante.rut},${estudiante.nombre},${estudiante.apellido_p} ${estudiante.apellido_m},${estudiante.email}`;
                csvContent += row + "\n";
            });

            var encodedUri = encodeURI(csvContent);
            var link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", nombreArchivo);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function descargarCSVEstudiantes(estudiantes, nombreCurso) {
            let csvContent = "data:text/csv;charset=utf-8,\uFEFF";  // BOM para Excel

            // Agregar encabezados
            csvContent += "RUT,Nombre,Email,Nota Final\n";

            // Agregar datos de estudiantes
            estudiantes.forEach(function (estudiante) {
                let row = `${estudiante.rut},${estudiante.nombre} ${estudiante.apellido_p} ${estudiante.apellido_m},${estudiante.email},${estudiante.final_grade}`;
                csvContent += row + "\n";
            });

            var encodedUri = encodeURI(csvContent);
            var link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `estudiantes_matriculados_${nombreCurso}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        document.addEventListener('DOMContentLoaded', function () {
            let estudiantesMatriculados = [];
            let nombreCurso = '';

            const modalMatriculados = document.getElementById('modalMatriculados');
            if (modalMatriculados) {
                modalMatriculados.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    if (button && button.hasAttribute('data-id')) {
                        const cursoId = button.getAttribute('data-id');
                        cargarEstudiantesMatriculados(cursoId);
                    } else {
                        console.error('No se pudo obtener el ID del curso');
                        // Opcionalmente, puedes mostrar un mensaje de error al usuario
                        // alert('Hubo un error al cargar los estudiantes matriculados.');
                    }
                });
            } else {
                console.error('El elemento modalMatriculados no fue encontrado');
            }

            const btnDescargarCSV = document.getElementById('descargarCSV');
            if (btnDescargarCSV) {
                btnDescargarCSV.addEventListener('click', function () {
                    if (estudiantesMatriculados.length > 0) {
                        descargarCSVEstudiantes(estudiantesMatriculados, nombreCursoActual);
                    } else {
                        alert('No hay datos de estudiantes para descargar.');
                    }
                });
            } else {
                console.error('El botón descargarCSV no fue encontrado');
            }

            // Modifica la función cargarEstudiantesMatriculados para actualizar las variables
            window.cargarEstudiantesMatriculados = function (cursoId) {
                console.log('Iniciando cargarEstudiantes con cursoId:', cursoId);

                fetch('obtener_matriculados.php?curso_id=' + cursoId)
                    .then(response => response.json())
                    .then(data => {
                        console.log('Datos recibidos:', data);

                        estudiantesMatriculados = Array.isArray(data.estudiantes) ? data.estudiantes : [];
                        nombreCursoActual = data.curso || '';

                        const matriculadosBody = document.getElementById('matriculadosBody');
                        if (matriculadosBody) {
                            matriculadosBody.innerHTML = '';

                            if (estudiantesMatriculados.length === 0) {
                                matriculadosBody.innerHTML = '<tr><td colspan="5">No hay estudiantes matriculados en este curso.</td></tr>';
                            } else {
                                estudiantesMatriculados.forEach(estudiante => {
                                    const row = document.createElement('tr');
                                    // Convertir final_grade a número y manejar casos donde pueda ser undefined o null
                                    const notaFinal = parseFloat(estudiante.final_grade);
                                    const aprobado = !isNaN(notaFinal) && notaFinal >= 4.0 && notaFinal <= 7.0;

                                    row.innerHTML = `
                                <td>${estudiante.rut || ''}</td>
                                <td>${(estudiante.nombre || '') + ' ' + (estudiante.apellido_p || '') + ' ' + (estudiante.apellido_m || '')}</td>
                                <td>${estudiante.email || ''}</td>
                                <td style="text-align:center;">${estudiante.final_grade || ''}</td>
                                <td>
                                    ${aprobado ? `
                                        <button class="btn btn-success btn-sm" onclick="descargarCertificado(${estudiante.id}, ${cursoId})">
                                            <i class="fas fa-download"></i> Descargar
                                        </button>
                                        <button class="btn btn-info btn-sm" onclick="mostrarModalEmail('${estudiante.email || ''}', ${estudiante.id}, ${cursoId})">
                                            <i class="fas fa-envelope"></i> Enviar
                                        </button>
                                    ` : `
                                        <span class="text-danger">Alumno Reprobado</span>
                                    `}
                                </td>
                            `;
                                    matriculadosBody.appendChild(row);
                                });
                            }
                        } else {
                            console.error('El elemento matriculadosBody no fue encontrado');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Hubo un error al cargar los estudiantes matriculados.');
                    });
            };
        });

        function descargarCSV() {
            window.location.href = '<?php echo $_SERVER["PHP_SELF"]; ?>?anio=<?php echo $anio_filtro; ?>&trimestre=<?php echo $trimestre_filtro; ?>&descargar_csv=1';
        }

        function descargarCertificado(alumnoId, cursoId) {
            window.location.href = `certificado.php?alumno_id=${alumnoId}&curso_id=${cursoId}&action=download`;
        }

        function mostrarModalEmail(email, alumnoId, cursoId) {
            document.getElementById('emailRegistrado').value = email;
            document.getElementById('emailAlternativo').value = '';

            // Guardamos los IDs en el formulario para usarlos al enviar
            document.getElementById('emailForm').dataset.alumnoId = alumnoId;
            document.getElementById('emailForm').dataset.cursoId = cursoId;

            new bootstrap.Modal(document.getElementById('emailModal')).show();
        }

        function enviarEmail() {
            const emailForm = document.getElementById('emailForm');
            const alumnoId = emailForm.dataset.alumnoId;
            const cursoId = emailForm.dataset.cursoId;
            const emailAlternativo = document.getElementById('emailAlternativo').value;

            fetch(`enviar_certificado_email.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    alumnoId,
                    cursoId,
                    emailAlternativo
                }),
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Certificado enviado por email exitosamente');
                        bootstrap.Modal.getInstance(document.getElementById('emailModal')).hide();
                    } else {
                        alert('Error al enviar el certificado por email: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Hubo un error al enviar el email. Por favor, inténtalo nuevamente.');
                });
        }

    </script>

    <script></script>
</body>

</html>
<?php include 'includes/footer.php'; ?>