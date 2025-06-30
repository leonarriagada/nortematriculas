<?php
ob_start();
session_start();
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/utilities.php';
require_once 'includes/auth.php';
check_role('admin');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('error_log', 'error_log');
error_reporting(E_ALL);

if (!isset($_SESSION['token']) || !validate_jwt($_SESSION['token'])) {
    header('Location: login.php');
    exit;
}

$items_per_page = 15;
$current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

$resultados = [];
$anio = isset($_GET['anio']) ? sanitize_input($_GET['anio']) : '';
$trimestre = isset($_GET['trimestre']) ? sanitize_input($_GET['trimestre']) : '';

// Manejo de ordenación
$valid_columns = ['anio', 'trimestre', 'course', 'profesor'];
$orderBy = isset($_GET['sort']) && in_array($_GET['sort'], $valid_columns) ? $_GET['sort'] : 'anio';
$orderDirection = isset($_GET['direction']) && strtolower($_GET['direction']) === 'desc' ? 'DESC' : 'ASC';

//Actualizar certificados antes de mostrar los resultados
actualizarCertificados($pdo);

try {
    $query = "SELECT DISTINCT c.id, c.anio, c.trimestre, c.course as curso, c.profesor
              FROM cursos c
              WHERE 1=1";

    $params = [];

    if (!empty($anio)) {
        $query .= " AND c.anio = :anio";
        $params[':anio'] = $anio;
    }
    if (!empty($trimestre)) {
        $query .= " AND c.trimestre = :trimestre";
        $params[':trimestre'] = $trimestre;
    }

    // Añadir la cláusula ORDER BY
    $query .= " ORDER BY $orderBy $orderDirection";

    // Contar el total de resultados
    $countQuery = str_replace("SELECT DISTINCT c.id, c.anio, c.trimestre, c.course as curso, c.profesor", "SELECT COUNT(DISTINCT c.id) as total", $query);
    $countStmt = $pdo->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalItems = $countStmt->fetchColumn();

    // Agregar LIMIT y OFFSET a la consulta principal
    $query .= " LIMIT :offset, :items_per_page";
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':items_per_page', $items_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log($e->getMessage());
    $resultados = [];
}

$totalPages = ceil($totalItems / $items_per_page);

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Certificados</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.1.4/css/dataTables.dataTables.min.css">

    <!-- DataTables JavaScript -->
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/2.1.4/js/dataTables.min.js"></script>
    <script type="text/javascript" charset="utf8"
        src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.min.js"></script>

    <!-- Iconos de fontawesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <style>
        #resulTable {
            display: ruby-base-container !important;
            align-self: center !important;
            margin: auto;
            width: 100% !important;
        }

        .dt-search {
            margin-bottom: 1rem;
            margin-right: 600px;
            display: ;

        }

        .table-responsive {
            overflow-x: visible;
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

        .sortable {
            cursor: pointer;
        }

        .sortable::after {
            content: '\f0dc';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            margin-left: 5px;
            color: #ccc;
            display: none;
            /* Ocultar por defecto */
        }

        .sortable.asc::after {
            content: '\f0de';
            color: #333;
            display: inline-block;
        }

        .sortable.desc::after {
            content: '\f0dd';
            color: #333;
            display: inline-block;
        }

        table {
            !important;
        }
    </style>

    <script type="text/javascript" src="js/script.js"></script>

</head>

<body>
    <div class="container mt-4">
        <h1 class="text-center mb-4">Buscar Certificados</h1>

        <p></p>


        <form method="GET" class="mb-4">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="d-flex">
                            <div class="flex-grow-1 me-3">
                                <div class="mb-3">
                                    <select name="anio" class="form-select">
                                        <option value="">Seleccione año</option>
                                        <?php
                                        $current_year = date('Y');
                                        for ($i = $current_year; $i >= $current_year - 5; $i--) {
                                            echo "<option value=\"$i\"" . ($anio == $i ? " selected" : "") . ">$i</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <select name="trimestre" class="form-select">
                                        <option value="">Seleccione trimestre</option>
                                        <option value="First Quarter" <?php echo $trimestre == 'First Quarter' ? 'selected' : ''; ?>>First Quarter</option>
                                        <option value="Second Quarter" <?php echo $trimestre == 'Second Quarter' ? 'selected' : ''; ?>>Second Quarter</option>
                                        <option value="Third Quarter" <?php echo $trimestre == 'Third Quarter' ? 'selected' : ''; ?>>Third Quarter</option>
                                        <option value="Summer" <?php echo $trimestre == 'Summer' ? 'selected' : ''; ?>>
                                            Summer</option>
                                    </select>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <button type="submit" class="btn btn-primary">Filtrar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-3">
                <h3>Total de cursos: <?php echo $totalItems; ?></h3>
            </div>
        </form>

        <!-- Resultados de Búsqueda -->
        <?php if (!empty($resultados)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered" id="resulTable">
                    <thead>
                        <tr>
                            <th class="text-center">Año</th>
                            <th>Trimestre</th>
                            <th>Curso</th>
                            <th>Profesor</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="table-group-divider">
                        <?php foreach ($resultados as $curso): ?>
                            <tr>
                                <td class="text-center"><?php echo htmlspecialchars($curso['anio']); ?></td>
                                <td><?php echo htmlspecialchars($curso['trimestre']); ?></td>
                                <td><?php echo htmlspecialchars($curso['curso']); ?></td>
                                <td><?php echo htmlspecialchars($curso['profesor']); ?></td>
                                <td style="text-align:center;">
                                    <button class="btn btn-primary btn-sm"
                                        onclick="mostrarAlumnos(<?php echo $curso['id']; ?>)">
                                        Certificados
                                    </button>
                                    <button class="btn btn-success btn-sm"
                                        onclick="mostrarEnvioMasivo(<?php echo $curso['id']; ?>)">
                                        Enviar Certificados
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info" role="alert">
                No hay cursos disponibles para los filtros seleccionados.
            </div>
        <?php endif; ?>

        <!-- Paginación -->
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $current_page == 1 ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-circle"
                            href="?page=<?php echo $current_page - 1; ?>&anio=<?php echo $anio; ?>&trimestre=<?php echo $trimestre; ?>&sort=<?php echo $orderBy; ?>&direction=<?php echo $orderDirection; ?>"
                            aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                            <a class="page-link rounded-circle"
                                href="?page=<?php echo $i; ?>&anio=<?php echo $anio; ?>&trimestre=<?php echo $trimestre; ?>&sort=<?php echo $orderBy; ?>&direction=<?php echo $orderDirection; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $current_page == $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-circle"
                            href="?page=<?php echo $current_page + 1; ?>&anio=<?php echo $anio; ?>&trimestre=<?php echo $trimestre; ?>&sort=<?php echo $orderBy; ?>&direction=<?php echo $orderDirection; ?>"
                            aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
        <p>&nbsp;</p>

    </div>

    <!-- Modal para mostrar alumnos -->
    <div class="modal fade" id="alumnosModal" tabindex="-1" aria-labelledby="alumnosModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="alumnosModalLabel">Alumnos matriculados</h5>
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
                        <tbody id="alumnosBody">
                            <!-- Los datos de los alumnos se cargarán aquí dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para enviar certificado por email -->
    <div class="modal fade" id="emailModal" tabindex="-1" aria-labelledby="emailModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="emailModalLabel">Enviar Certificado por Email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="emailForm">
                        <div class="mb-3">
                            <label for="emailRegistrado" class="form-label">Email Registrado</label>
                            <input type="email" class="form-control" id="emailRegistrado" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="emailAlternativo" class="form-label">Email Alternativo (opcional)</label>
                            <input type="email" class="form-control" id="emailAlternativo">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="enviarEmail()">Enviar Certificado</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Añade este nuevo modal al final del archivo -->
    <div class="modal fade" id="envioMasivoModal" tabindex="-1" aria-labelledby="envioMasivoModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="envioMasivoModalLabel">Envío Masivo de Certificados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <button class="btn btn-primary" id="selectAllBtn">Seleccionar Todos</button>
                        <button class="btn btn-secondary" id="deselectAllBtn">Deseleccionar Todos</button>
                    </div>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Seleccionar</th>
                                <th>RUT</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Nota Final</th>
                            </tr>
                        </thead>
                        <tbody id="alumnosMasivoBody">
                            <!-- Los datos de los alumnos se cargarán aquí dinámicamente -->
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="enviarCertificadosMasivo()">Enviar
                        Certificados Seleccionados</button>
                </div>
            </div>
        </div>
    </div>

    <script>

        $(document).ready(function () {
            $('#resulTable').DataTable({
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
            const sortableHeaders = document.querySelectorAll('.sortable');
            sortableHeaders.forEach(header => {
                header.addEventListener('click', function () {
                    const sort = this.getAttribute('data-sort');
                    let direction = 'asc';
                    if (this.classList.contains('asc')) {
                        direction = 'desc';
                    } else if (this.classList.contains('desc')) {
                        direction = 'asc';
                    }

                    let url = new URL(window.location.href);
                    url.searchParams.set('sort', sort);
                    url.searchParams.set('direction', direction);
                    window.location.href = url.toString();
                });
            });
        });

        function updateQueryStringParameter(uri, key, value) {
            var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
            var separator = uri.indexOf('?') !== -1 ? "&" : "?";
            if (uri.match(re)) {
                return uri.replace(re, '$1' + key + "=" + value + '$2');
            }
            else {
                return uri + separator + key + "=" + value;
            }
        }


        function mostrarAlumnos(cursoId) {
            fetch('obtener_alumnos.php?curso_id=' + cursoId)
                .then(response => response.json())
                .then(data => {
                    console.log('Alumnos:', data);
                    const alumnosBody = document.getElementById('alumnosBody');
                    alumnosBody.innerHTML = '';

                    if (Array.isArray(data) && data.length === 0) {
                        // Array vacío: no hay estudiantes matriculados
                        mostrarMensajeNoEstudiantes(alumnosBody);
                    } else if (data.message) {
                        // Objeto con mensaje: no se encontraron alumnos
                        mostrarMensajeNoEstudiantes(alumnosBody, data.message);
                    } else if (Array.isArray(data)) {
                        // Array con estudiantes
                        data.forEach(alumno => {
                            const row = document.createElement('tr');
                            const notaFinal = parseFloat(alumno.final_grade);
                            const aprobado = !isNaN(notaFinal) && notaFinal >= 4.0 && notaFinal <= 7.0;

                            row.innerHTML = `
                        <td>${alumno.rut || ''}</td>
                        <td>${(alumno.nombre || '') + ' ' + (alumno.apellido_p || '') + ' ' + (alumno.apellido_m || '')}</td>
                        <td>${alumno.email || ''}</td>
                        <td style="text-align:center;">${alumno.final_grade || ''}</td>
                        <td>
                            ${aprobado ? `
                                <button class="btn btn-success btn-sm" onclick="descargarCertificado(${alumno.id}, ${cursoId})">
                                    <i class="fas fa-download"></i> Descargar
                                </button>
                                <button class="btn btn-info btn-sm" onclick="mostrarModalEmail('${alumno.email || ''}', ${alumno.id}, ${cursoId})">
                                    <i class="fas fa-envelope"></i> Enviar
                                </button>
                            ` : `
                                <span class="text-danger">Alumno Reprobado</span>
                            `}
                        </td>
                    `;
                            alumnosBody.appendChild(row);
                        });
                    } else {
                        // Datos inesperados
                        mostrarMensajeNoEstudiantes(alumnosBody, "Formato de datos inesperado");
                    }

                    new bootstrap.Modal(document.getElementById('alumnosModal')).show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Hubo un error al obtener los alumnos. Por favor, inténtalo nuevamente.');
                });
        }

        function mostrarMensajeNoEstudiantes(alumnosBody, mensaje = "Este curso no tiene estudiantes matriculados.") {
            const row = document.createElement('tr');
            row.innerHTML = `
            <td colspan="5" class="text-center">
                ${mensaje}
            </td>
            `;
            alumnosBody.appendChild(row);
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


        function mostrarEnvioMasivo(cursoId) {
            fetch('obtener_alumnos.php?curso_id=' + cursoId)
                .then(response => response.json())
                .then(data => {
                    console.log('Alumnos:', data);
                    const alumnosMasivoBody = document.getElementById('alumnosMasivoBody');
                    alumnosMasivoBody.innerHTML = '';

                    if (Array.isArray(data) && data.length === 0) {
                        // Array vacío: no hay estudiantes matriculados
                        mostrarMensajeNoAlumnos(alumnosMasivoBody);
                    } else if (data.message) {
                        // Objeto con mensaje: no se encontraron alumnos
                        mostrarMensajeNoAlumnos(alumnosMasivoBody, data.message);
                    } else if (Array.isArray(data)) {
                        // Array con estudiantes
                        let alumnosAprobados = 0;
                        data.forEach(alumno => {
                            const notaFinal = parseFloat(alumno.final_grade);
                            const aprobado = !isNaN(notaFinal) && notaFinal >= 4.0 && notaFinal <= 7.0;

                            if (aprobado) {
                                alumnosAprobados++;
                                const row = document.createElement('tr');
                                row.innerHTML = `
                            <td><input type="checkbox" class="alumno-checkbox" data-alumno-id="${alumno.id}" data-curso-id="${cursoId}"></td>
                            <td>${alumno.rut || ''}</td>
                            <td>${(alumno.nombre || '') + ' ' + (alumno.apellido_p || '') + ' ' + (alumno.apellido_m || '')}</td>
                            <td>${alumno.email || ''}</td>
                            <td>${alumno.final_grade || ''}</td>
                        `;
                                alumnosMasivoBody.appendChild(row);
                            }
                        });

                        if (alumnosAprobados === 0) {
                            mostrarMensajeNoAlumnos(alumnosMasivoBody, "No hay alumnos aprobados para envío masivo de certificados.");
                        }
                    } else {
                        // Datos inesperados
                        mostrarMensajeNoAlumnos(alumnosMasivoBody, "Formato de datos inesperado");
                    }

                    new bootstrap.Modal(document.getElementById('envioMasivoModal')).show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Hubo un error al obtener los alumnos. Por favor, inténtalo nuevamente.');
                });
        }

        function mostrarMensajeNoAlumnos(alumnosMasivoBody, mensaje = "Este curso no tiene estudiantes matriculados.") {
            const row = document.createElement('tr');
            row.innerHTML = `
            <td colspan="5" class="text-center">
                ${mensaje}
            </td>
            `;
            alumnosMasivoBody.appendChild(row);
        }

        document.getElementById('selectAllBtn').addEventListener('click', () => {
            document.querySelectorAll('.alumno-checkbox').forEach(checkbox => checkbox.checked = true);
        });

        document.getElementById('deselectAllBtn').addEventListener('click', () => {
            document.querySelectorAll('.alumno-checkbox').forEach(checkbox => checkbox.checked = false);
        });

        function enviarCertificadosMasivo() {
            const alumnosSeleccionados = Array.from(document.querySelectorAll('.alumno-checkbox:checked')).map(checkbox => ({
                alumnoId: checkbox.dataset.alumnoId,
                cursoId: checkbox.dataset.cursoId
            }));

            if (alumnosSeleccionados.length === 0) {
                alert('Por favor, selecciona al menos un alumno para enviar los certificados.');
                return;
            }

            fetch('enviar_certificados_masivo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ alumnos: alumnosSeleccionados }),
            })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error(`HTTP error! status: ${response.status}, body: ${text}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert(`Certificados enviados exitosamente a ${data.enviados} alumnos.`);
                    } else {
                        alert('Error al enviar los certificados: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Hubo un error al enviar los certificados. Por favor, inténtalo nuevamente.');
                });
        }

    </script>

</body>

</html>
<?php include 'includes/footer.php'; ?>