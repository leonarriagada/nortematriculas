<?php
ob_start();
session_start();
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/utilities.php';
require_once 'includes/auth.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('error_log', 'error_log');
error_reporting(E_ALL);

if (!isset($_SESSION['token']) || !validate_jwt($_SESSION['token'])) {
    header('Location: login.php');
    exit;
}

$resultados = [];
$items_per_page = 10; // Define items per page
$current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$busqueda = '';

// Agregar lógica para el filtrado
$anio_filtro = isset($_GET['anio']) ? (int) $_GET['anio'] : date('Y');
$trimestre_filtro = isset($_GET['trimestre']) ? $_GET['trimestre'] : '';

// Modificar la consulta SQL para incluir los filtros y la ordenación
$sortColumn = isset($_GET['sort']) ? $_GET['sort'] : 'fecha_creacion';
$sortOrder = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'DESC' : 'ASC';

$query = "SELECT * FROM cursos WHERE anio = :anio";
$params = [':anio' => $anio_filtro];

if (!empty($trimestre_filtro)) {
    $query .= " AND trimestre = :trimestre";
    $params[':trimestre'] = $trimestre_filtro;
}

$query .= " ORDER BY $sortColumn $sortOrder";
$url_params = '&busqueda=' . urlencode($busqueda);

// Execute the query to get cursos
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_items = count($cursos);

    // Calculate pagination
    $items_per_page = 10; // Define items per page if not already defined
    $total_pages = ceil($total_items / $items_per_page);
    $offset = ($current_page - 1) * $items_per_page;
    $cursos = array_slice($cursos, $offset, $items_per_page);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $cursos = [];
    $total_items = 0;
    $total_pages = 0;
}

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Cursos y Notas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table-sm th,
        .table-sm td {
            text-align: left;
            vertical-align: middle;
            width: 15px;
            font-size: 15px;
        }

        th a {
            color: inherit;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        th a:hover {
            text-decoration: underline;
        }

        th a::after {
            content: '▲';
            font-size: 0.8em;
            margin-left: 5px;
            opacity: 0.5;
        }

        th a.desc::after {
            content: '▼';
        }



        .form-control-sm {
            padding: 0.1rem 0.3rem;
            font-size: 15px;
        }

        .btn-sm {
            padding: 0.1rem 0.3rem;
            font-size: 15px;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h2>Gestión de Cursos y Notas</h2>

        <!-- Formulario de filtro -->
        <form action="" method="GET" class="mb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="anio" class="form-label">Año</label>
                    <select class="form-select" id="filter_anio" name="anio">
                        <?php
                        $currentYear = date('Y');
                        for ($i = $currentYear - 2; $i <= $currentYear + 2; $i++) {
                            $selected = ($i == $anio_filtro) ? 'selected' : '';
                            echo "<option value=\"$i\" $selected>$i</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="trimestre" class="form-label">Trimestre</label>
                    <select class="form-select" id="filter_trimestre" name="trimestre">
                        <option value="">Todos los trimestres</option>
                        <option value="First Quarter" <?php echo ($trimestre_filtro == 'First Quarter') ? 'selected' : ''; ?>>First
                            Quarter</option>
                        <option value="Second Quarter" <?php echo ($trimestre_filtro == 'Second Quarter') ? 'selected' : ''; ?>>Second
                            Quarter</option>
                        <option value="Third Quarter" <?php echo ($trimestre_filtro == 'Third Quarter') ? 'selected' : ''; ?>>Third
                            Quarter</option>
                        <option value="Summer" <?php echo ($trimestre_filtro == 'Summer') ? 'selected' : ''; ?>>Summer
                        </option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filtrar</button>

                </div>
            </div>
        </form>

        <!-- Listado de Cursos -->
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th><a href="?sort=anio&order=<?php echo $sortColumn === 'anio' && $sortOrder === 'ASC' ? 'desc' : 'asc';
                    echo $url_params; ?>">Año
                            <?php echo $sortColumn === 'anio' ? ($sortOrder === 'ASC' ? '▲' : '▼') : ''; ?></a></th>
                    <th><a href="?sort=trimestre&order=<?php echo $sortColumn === 'trimestre' && $sortOrder === 'ASC' ? 'desc' : 'asc';
                    echo $url_params; ?>">Trimestre
                            <?php echo $sortColumn === 'trimestre' ? ($sortOrder === 'ASC' ? '▲' : '▼') : ''; ?></a>
                    </th>
                    <th><a href="?sort=course&order=<?php echo $sortColumn === 'course' && $sortOrder === 'ASC' ? 'desc' : 'asc';
                    echo $url_params; ?>">Nombre
                            del Curso
                            <?php echo $sortColumn === 'course' ? ($sortOrder === 'ASC' ? '▲' : '▼') : ''; ?></a></th>
                    <th><a href="?sort=horario&order=<?php echo $sortColumn === 'horario' && $sortOrder === 'ASC' ? 'desc' : 'asc';
                    echo $url_params; ?>">Horario
                            <?php echo $sortColumn === 'horario' ? ($sortOrder === 'ASC' ? '▲' : '▼') : ''; ?></a></th>
                    <th><a href="?sort=profesor&order=<?php echo $sortColumn === 'profesor' && $sortOrder === 'ASC' ? 'desc' : 'asc';
                    echo $url_params; ?>">Profesor
                            <?php echo $sortColumn === 'profesor' ? ($sortOrder === 'ASC' ? '▲' : '▼') : ''; ?></a></th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cursos as $curso): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($curso['anio']); ?></td>
                        <td><?php echo htmlspecialchars($curso['trimestre']); ?></td>
                        <td><?php echo htmlspecialchars($curso['course']); ?></td>
                        <td><?php echo htmlspecialchars($curso['dias']); ?>
                            <?php echo htmlspecialchars($curso['horario']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($curso['profesor']); ?></td>
                        <td>
                            <button class="btn btn-primary ver-notas-btn" data-curso-id="<?php echo $curso['id']; ?>">Ver
                                Notas</button>
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
                    <a class="page-link rounded-circle" href="?page=<?php echo $i . $url_params; ?>"><?php echo $i; ?></a>
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

    <!-- Modal para mostrar las notas -->
    <div class="modal fade" id="notasModal" tabindex="-1" aria-labelledby="notasModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notasModalLabel">Notas del Curso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Área para notificaciones -->
                    <div id="notificationArea"></div>

                    <!-- Pestañas y tablas de notas -->
                    <ul class="nav nav-tabs" id="notasTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="academic-tab" data-bs-toggle="tab"
                                data-bs-target="#academic" type="button" role="tab" aria-controls="academic"
                                aria-selected="true">
                                Academic Performance
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="cp-oral-tab" data-bs-toggle="tab" data-bs-target="#cp-oral"
                                type="button" role="tab" aria-controls="cp-oral" aria-selected="false">
                                CP & Oral Exam Score
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="attendance-tab" data-bs-toggle="tab"
                                data-bs-target="#attendance" type="button" role="tab" aria-controls="attendance"
                                aria-selected="false">
                                Online Attendance
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="notasTabContent">
                        <div class="tab-pane fade show active" id="academic" role="tabpanel"
                            aria-labelledby="academic-tab">
                            <!-- Contenido de Academic Performance -->
                        </div>
                        <div class="tab-pane fade" id="cp-oral" role="tabpanel" aria-labelledby="cp-oral-tab">
                            <!-- Contenido de CP & Oral Exam Score -->
                        </div>
                        <div class="tab-pane fade" id="attendance" role="tabpanel" aria-labelledby="attendance-tab">
                            <!-- Contenido de Online Attendance -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>

    <script>
        // Global object to store our functions
        const NotasApp = {};

        (function ($) {
            'use strict';

            const $modal = $('#notasModal');
            const $academicTab = $('#academic');
            const $cpOralTab = $('#cp-oral');
            const $attendanceTab = $('#attendance');

            NotasApp.init = function () {
                $(document).on('click', '.ver-notas-btn', function (e) {
                    e.preventDefault();
                    const cursoId = $(this).data('curso-id');
                    NotasApp.verNotas(cursoId);
                });

                $(document).on('click', '.edit-btn', NotasApp.toggleEdit);

                $(document).on('input', 'input[type="number"], input[type="text"], select', function () {
                    const $this = $(this);
                    const originalValue = $this.attr('data-original-value');
                    const currentValue = $this.val();

                    if (currentValue !== originalValue) {
                        $this.addClass('modified');
                    } else {
                        $this.removeClass('modified');
                    }
                });
            };

            NotasApp.verNotas = async function (cursoId) {
                console.log('Iniciando cargarNotas con cursoId:', cursoId);
                try {
                    const response = await fetch(`get_notas.php?curso_id=${cursoId}`);
                    const data = await response.json();
                    console.log('Datos recibidos:', data);
                    if (data.success) {
                        NotasApp.renderizarTablas(data.notas);
                        $modal.modal('show');
                    } else {
                        throw new Error(data.error || 'Error desconocido');
                    }
                } catch (error) {
                    console.error('Error detallado:', error);
                    NotasApp.showNotification('Hubo un error al cargar las notas.', 'danger');
                }
            };

            NotasApp.toggleEdit = function () {
                const $row = $(this).closest('tr');
                const $editables = $row.find('.editable');

                if ($(this).text() === 'Editar') {
                    $editables.prop('disabled', false).addClass('bg-body-tertiary text-body');
                    $(this).text('Guardar').removeClass('btn-primary').addClass('btn-success');
                } else {
                    $editables.prop('disabled', true).removeClass('bg-body-tertiary text-body');
                    $(this).text('Editar').removeClass('btn-success').addClass('btn-primary');
                    NotasApp.saveChanges($row);
                }
            };

            NotasApp.updateCalculatedFields = function ($row, updatedData) {
                const activeTab = $('#notasTabs .nav-link.active').attr('id');

                const updateField = (selector, value) => {
                    const $element = $row.find(selector);
                    if ($element.length) {
                        if ($element.is('td')) {
                            $element.text(NotasApp.formatearNota(value));
                        } else if ($element.is('input, select')) {
                            $element.val(value);
                            $element.attr('data-original-value', value);
                        }
                    }
                };

                if (activeTab === 'academic-tab') {
                    const fieldsToUpdate = [
                        'average_cp', 'cp_average_30', 'platform_progress_15',
                        'platform_score_15', 'oral_exam_40', 'final_grade'
                    ];

                    fieldsToUpdate.forEach(field => {
                        if (updatedData[field] !== undefined) {
                            updateField(`[data-field="${field}"]`, updatedData[field]);
                        }
                    });

                    // Actualizar campos individuales
                    ['cp1', 'cp2', 'platform_progress', 'platform_score', 'oral_exam'].forEach(field => {
                        if (updatedData[field] !== undefined) {
                            updateField(`[name="${field}"]`, updatedData[field]);
                        }
                    });
                } else if (activeTab === 'cp-oral-tab') {
                    const examTypes = ['cp1', 'cp2', 'oe'];
                    const categories = ['content', 'fluency', 'pronunciation', 'grammar', 'vocabulary'];

                    examTypes.forEach(examType => {
                        categories.forEach(category => {
                            const fieldName = `${category}_${examType}`;
                            if (updatedData[fieldName] !== undefined) {
                                updateField(`[name="${fieldName}"]`, updatedData[fieldName]);
                            }
                        });

                        if (updatedData[`total_${examType}`] !== undefined) {
                            updateField(`[data-field="total_${examType}"]`, updatedData[
                                `total_${examType}`]);
                        }

                        if (updatedData[`grade_${examType}`] !== undefined) {
                            updateField(`[data-field="grade_${examType}"]`, updatedData[
                                `grade_${examType}`]);
                        }
                    });
                }

                // Remover la clase 'modified' de todos los campos editados
                $row.find('.modified').removeClass('modified');
            };

            NotasApp.saveChanges = function ($row) {
                const id = $row.data('id');
                const $modifiedInputs = $row.find('.modified');

                if ($modifiedInputs.length === 0) {
                    NotasApp.showNotification('No se detectaron cambios para actualizar', 'warning');
                    return;
                }

                const data = {
                    id: id
                };
                $modifiedInputs.each(function () {
                    data[$(this).attr('name')] = NotasApp.validateGrade($(this).val().trim());
                });

                $.ajax({
                    url: 'update_notas.php',
                    method: 'POST',
                    data: JSON.stringify(data),
                    contentType: 'application/json',
                    dataType: 'json'
                })
                    .done(function (result) {
                        if (result.success) {
                            NotasApp.updateCalculatedFields($row, result.updatedData);
                            NotasApp.showNotification(
                                'Notas actualizadas correctamente. Para ver los cambios vuelva a abrir esta ventana',
                                'success');
                            $modifiedInputs.each(function () {
                                $(this).attr('data-original-value', $(this).val()).removeClass(
                                    'modified');
                            });
                        } else {
                            throw new Error(result.message || 'Error al actualizar las notas');
                        }
                    })
                    .fail(function (jqXHR, textStatus, errorThrown) {
                        console.error('Error:', errorThrown);
                        NotasApp.showNotification('Error al actualizar las notas: ' + errorThrown, 'danger');
                    });
            };

            NotasApp.validateGrade = function (value) {
                const grade = parseFloat(value);
                return isNaN(grade) ? null : Math.max(1.0, Math.min(7.0, grade)).toFixed(1);
            };

            NotasApp.showNotification = function (message, type) {
                const $alertDiv = $(`
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `);

                $('#notificationArea').append($alertDiv);

                setTimeout(() => {
                    $alertDiv.alert('close');
                }, 5000);
            };

            NotasApp.renderizarTablas = function (notas) {
                console.log('Generando tabla Academic');
                $academicTab.html(NotasApp.generarTablaAcademic(notas));
                console.log('Generando tabla CP & Oral');
                $cpOralTab.html(NotasApp.generarTablaCPOral(notas));
                // Por ahora, no generamos la tabla de Attendance
                // $attendanceTab.html(NotasApp.generarTablaAttendance(notas));
            };

            //Tabla Academic Performance
            NotasApp.generarTablaAcademic = function (notas) {
                if (!Array.isArray(notas) || notas.length === 0) {
                    return '<p>No hay estudiantes matriculados en este curso</p>';
                }

                const headers = [
                    'STUDENT', 'RUT', 'CP 1', 'CP 2', 'Average CP', 'CP Average 30%',
                    'Platform Progress', 'Platform Progress 15%', 'Platform Score',
                    'Platform Score 15%', 'ORAL EXAM', 'ORAL EXAM 40%', 'FINAL GRADE', 'Acciones'
                ];

                const headerRow = headers.map(header =>
                    `<th ${header === 'STUDENT' || header === 'RUT' || header === 'Acciones' ? 'left' : 'center'}; vertical-align: bottom;" scope="col" ${header !== 'STUDENT' && header !== 'RUT' && header !== 'Acciones' ? 'style="writing-mode: vertical-rl; transform: rotate(180deg);"' : ''}>${header}</th>`
                ).join('');

                const tableBody = notas.map(row => `
                 
            <tr class="border border-5" data-id="${row.nota_id}">
                <td>${NotasApp.escapeHtml(row.nombre)} ${NotasApp.escapeHtml(row.apellido_p)}</td>
                <td>${NotasApp.escapeHtml(row.rut)}</td>
                ${NotasApp.generateInputCell('cp1', row.cp1)}
                ${NotasApp.generateInputCell('cp2', row.cp2)}
                <td>${NotasApp.formatearNota(row.average_cp)}</td>
                <td>${NotasApp.formatearNota(row.cp_average_30)}</td>
                ${NotasApp.generateInputCell('platform_progress', row.platform_progress)}
                <td>${NotasApp.formatearNota(row.platform_progress_15)}</td>
                ${NotasApp.generateInputCell('platform_score', row.platform_score)}
                <td>${NotasApp.formatearNota(row.platform_score_15)}</td>
                ${NotasApp.generateInputCell('oral_exam', row.oral_exam)}
                <td>${NotasApp.formatearNota(row.oral_exam_40)}</td>
                <td>${NotasApp.formatearNota(row.final_grade)}</td>
                <td style="text-align: center;"><button class="btn btn-sm btn-primary edit-btn">Editar</button></td>
            </tr>
        `).join('');

                return `
            <table class="table table-striped table-sm">
                <thead><tr>${headerRow}</tr></thead>
                <p>&nbsp;</p>
                <tbody>${tableBody}</tbody>
            </table>
        `;
            };

            //Tabla CP & Oral Exam 
            NotasApp.generarTablaCPOral = function (data) {
                if (!data || !Array.isArray(data) || data.length === 0) {
                    console.log('No hay datos para generar la tabla CP&OralExam');
                    return '<p>No hay estudiantes matriculados en este curso</p>';
                }

                const puntajes = [1.0, 2.0, 3.5, 5.0, 6.5, 8.0];
                const categorias = ['CONTENT', 'FLUENCY', 'PRONUNCIATION', 'GRAMMAR', 'VOCABULARY'];
                const examenes = ['CP1', 'CP2', 'Oral Exam'];

                let html = '<table class="table table-striped table-sm">';
                html += '<thead><tr>';
                html += '<th style="vertical-align: bottom">Estudiante</th>';
                html += '<th style="vertical-align: bottom">RUT</th>';
                html += '<th style="vertical-align: bottom">Exam</th>';
                categorias.forEach(cat => html +=
                    `<th style="writing-mode: vertical-rl; transform: rotate(180deg);">${cat}</th>`);
                html += '<th style="vertical-align: bottom">Total</th>';
                html += '<th style="vertical-align: bottom">Grade</th>';
                html += '<th style="vertical-align: bottom">Acciones</th>';
                html += '</tr></thead><tbody>';

                data.forEach((row) => {
                    examenes.forEach((examen, exIndex) => {
                        const examenLower = examen.toLowerCase() === 'oral exam' ? 'oe' : examen
                            .toLowerCase();
                        html += `<tr class="border border-5" data-id="${row.nota_id}">`;

                        if (exIndex === 0) {
                            html +=
                                `<td rowspan="3">${NotasApp.escapeHtml(row.nombre)} ${NotasApp.escapeHtml(row.apellido_p)}</td>`;
                            html += `<td rowspan="3">${NotasApp.escapeHtml(row.rut)}</td>`;
                        }

                        html += `<td>${examen}</td>`;

                        categorias.forEach(cat => {
                            const fieldName = `${cat.toLowerCase()}_${examenLower}`;
                            html += `<td style="text-align: center;">
                        <select class="form-select form-select-sm editable" name="${fieldName}" disabled>
                        ${puntajes.map(p => `<option value="${p}" ${row[fieldName] == p ? 'selected' : ''}>${p.toFixed(1)}</option>`).join('')}
                        </select>
                         </td>`;
                        });
                        html += `<td>${NotasApp.formatearNota(row[`total_${examenLower}`])}</td>`;
                        html += `<td>${NotasApp.formatearNota(row[`grade_${examenLower}`])}</td>`;
                        html +=
                            `<td><button class="btn btn-sm btn-primary edit-btn">Editar</button></td>`;
                        html += '</tr>';
                    });
                });

                html += '</tbody></table>';
                return html;
            };

            //Tabla Online Attendence
            function generarTablaAttendance(data) {
                let html = '<table class="table table-striped table-sm">';
                html +=
                    '<thead><tr><th>Estudiante</th><th>RUT</th><th>Semana 1</th><th>Semana 2</th><th>Semana 3</th><th>Acciones</th></tr></thead>';
                html += '<tbody>';

                data.forEach((row, index) => {
                    html += `<tr data-id="${row.nota_id}">
                <td>${row.nombre} ${row.apellido_p}</td>
                <td>${row.rut}</td>
                <td><input type="number" step="1" min="0" max="100" class="form-control form-control-sm editable" name="semana1" value="${parseInt(row.semana1) || 0}" disabled></td>
                <td><input type="number" step="1" min="0" max="100" class="form-control form-control-sm editable" name="semana2" value="${parseInt(row.semana2) || 0}" disabled></td>
                <td><input type="number" step="1" min="0" max="100" class="form-control form-control-sm editable" name="semana3" value="${parseInt(row.semana3) || 0}" disabled></td>
                <td><button class="btn btn-sm btn-primary edit-btn" onclick="toggleEdit(this)">Editar</button></td>
            </tr>`;
                });

                html += '</tbody></table>';
                return html;
            }

            NotasApp.generateInputCell = function (name, value) {
                return `
            <td>
                <input type="number" step="0.1" min="1.0" max="7.0" 
                       class="form-control form-control-sm editable" 
                       name="${name}" value="${NotasApp.formatearNota(value)}" 
                       data-original-value="${NotasApp.formatearNota(value)}" disabled>
            </td>
        `;
            };

            NotasApp.formatearNota = function (nota) {
                const parsedNota = parseFloat(nota);
                return isNaN(parsedNota) ? '1.0' : Math.min(Math.max(parsedNota, 1.0), 7.0).toFixed(1);
            };

            NotasApp.escapeHtml = function (unsafe) {
                return unsafe
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            };

        })(jQuery);

        // Initialize the app when the document is ready
        $(document).ready(function () {
            NotasApp.init();
        });
    </script>
</body>

</html>

<?php include 'includes/footer.php'; ?>