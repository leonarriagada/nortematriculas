<?php
session_start();
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/utilities.php';
require_once 'vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');
error_reporting(E_ALL);

if (!isset($_SESSION['token']) || !validate_jwt($_SESSION['token'])) {
    header('Location: login.php');
    exit;
}

$items_per_page = 25;
$current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

$resultados = [];
$total_items = 0;
$busqueda = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' || isset($_GET['busqueda'])) {
    $busqueda = isset($_POST['busqueda']) ? sanitize_input($_POST['busqueda']) : sanitize_input($_GET['busqueda']);
    $rut_formatted = validate_rut_format($busqueda);
    $url_params = '&busqueda=' . urlencode($busqueda);

    try {
        // Contar total de resultados
        $query = "SELECT COUNT(*) FROM estudiantes e 
                  WHERE e.nombre LIKE :busqueda 
                  OR e.apellido_p LIKE :busqueda 
                  OR e.apellido_m LIKE :busqueda 
                  OR e.email LIKE :busqueda";
        if ($rut_formatted) {
            $query .= " OR e.rut = :rut";
        }
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':busqueda', "%$busqueda%", PDO::PARAM_STR);
        if ($rut_formatted) {
            $stmt->bindValue(':rut', $rut_formatted, PDO::PARAM_STR);
        }
        $stmt->execute();
        $total_items = $stmt->fetchColumn();

        // Consulta para obtener los resultados paginados
        $query = "SELECT e.id, e.rut, e.nombre, e.apellido_p, e.apellido_m, e.email, e.telefono
                  FROM estudiantes e
                  WHERE e.nombre LIKE :busqueda 
                  OR e.apellido_p LIKE :busqueda 
                  OR e.apellido_m LIKE :busqueda 
                  OR e.email LIKE :busqueda";
        if ($rut_formatted) {
            $query .= " OR e.rut = :rut";
        }
        $query .= " LIMIT :offset, :limit";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':busqueda', "%$busqueda%", PDO::PARAM_STR);
        if ($rut_formatted) {
            $stmt->bindValue(':rut', $rut_formatted, PDO::PARAM_STR);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log($e->getMessage());
        $resultados = [];
    }
}

$total_pages = ceil($total_items / $items_per_page);

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Registros</title>
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
        /* .input-group .form-control {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .input-group .btn {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        } */

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

        input {
            width: 300px !important;

        }

        .table {
            color: var(--bs-body-color);
        }

        .table-responsive {
            overflow-x: visible;
        }

        #tablaResultados {
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
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h1 class="text-center mb-4">Buscar Registros</h1>
                <form method="POST" class="mb-3">
                    <center>
                        <div>
                            <input type="text" name="busqueda" class="form-control" placeholder="Buscar por nombre, RUT o email" value="<?php echo htmlspecialchars($busqueda); ?>">
                            <p></p>
                            <button type="submit" class="btn btn-success">Buscar</button>
                        </div>
                    </center>
                </form>
                <?php if (isset($busqueda) && empty($resultados)): ?>
                    <div class="alert alert-info text-center" role="alert">
                        ¿Has realizado una búsqueda? Porque no encuentro nada.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>



    <?php if (!empty($resultados)): ?>
        <h3 class="text-center mb-4">Resultados de la búsqueda</h3>
        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered" id="tablaResultados" data-toggle="tablaResultados">
                <thead>
                    <tr>
                        <th>Rut</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th style="text-align: center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="table-group-divider">
                    <?php foreach ($resultados as $estudiante): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($estudiante['rut'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars(($estudiante['nombre'] ?? '') . ' ' . ($estudiante['apellido_p'] ?? '') . ' ' . ($estudiante['apellido_m'] ?? '')); ?>
                            </td>
                            <td><?php echo htmlspecialchars($estudiante['email'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($estudiante['telefono'] ?? ''); ?></td>
                            <td style="text-align: center">
                                <button class="btn btn-danger btn-sm"
                                    onclick="editarEstudiante(<?php echo $estudiante['id']; ?>)">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="btn btn-warning btn-sm"
                                    onclick="mostrarCertificados(<?php echo $estudiante['id']; ?>)">
                                    <i class="fas fa-certificate"></i> Certificado
                                </button>
                                <button class="btn btn-success btn-sm"
                                    onclick="mostrarCursos(<?php echo $estudiante['id']; ?>)">
                                    <i class="fas fa-book"></i> Cursos
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
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
        <?php endif; ?>
    <?php else: ?>

    <?php endif; ?>
    <p>&nbsp;</p>



    <!-- Modal para editar estudiante -->
    <div class="modal fade" id="editarEstudianteModal" tabindex="-1" aria-labelledby="editarEstudianteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarEstudianteModalLabel">Editar Estudiante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editarEstudianteForm">
                        <input type="hidden" id="editEstudianteId" name="id">
                        <div class="mb-3">
                            <label for="editNombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="editNombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="editApellidoP" class="form-label">Apellido Paterno</label>
                            <input type="text" class="form-control" id="editApellidoP" name="apellido_p" required>
                        </div>
                        <div class="mb-3">
                            <label for="editApellidoM" class="form-label">Apellido Materno</label>
                            <input type="text" class="form-control" id="editApellidoM" name="apellido_m">
                        </div>
                        <div class="mb-3">
                            <label for="editEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="editTelefono" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="editTelefono" name="telefono">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarEdicionEstudiante()">Guardar
                        cambios</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para mostrar certificados -->
    <div class="modal fade" id="certificadosModal" tabindex="-1" aria-labelledby="certificadosModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="certificadosModalLabel">Certificados del estudiante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Curso</th>
                                <th>Año</th>
                                <th>Trimestre</th>
                                <th>Level</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="certificadosBody">
                            <!-- Los datos de los certificados se cargarán aquí dinámicamente -->
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="descargarCertificadosCSV">Descargar a
                        Excel</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para mostrar cursos -->
    <div class="modal fade" id="cursosModal" tabindex="-1" aria-labelledby="cursosModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cursosModalLabel">Cursos del estudiante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Año</th>
                                <th>Trimestre</th>
                                <th>Categoría</th>
                                <th>Curso</th>
                                <th>Profesor</th>
                                <th>Nota</th>
                            </tr>
                        </thead>
                        <tbody id="cursosBody">
                            <!-- Los datos de los cursos se cargarán aquí dinámicamente -->
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
    <script>

        $(document).ready(function () {
            $('#tablaResultados').DataTable({
                "responsive": true,
                "searching": false,
                "paging": false,
                "info": false,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
                }
            });
        });

        function editarEstudiante(id) {
            fetch('obtener_estudiante.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    console.log('Datos recibidos:', data); // Esta es la línea que debes agregar
                    document.getElementById('editEstudianteId').value = data.id;
                    document.getElementById('editNombre').value = data.nombre;
                    document.getElementById('editApellidoP').value = data.apellido_p;
                    document.getElementById('editApellidoM').value = data.apellido_m;
                    document.getElementById('editEmail').value = data.email;
                    document.getElementById('editTelefono').value = data.telefono;

                    new bootstrap.Modal(document.getElementById('editarEstudianteModal')).show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Hubo un error al obtener los datos del estudiante. Por favor, inténtalo nuevamente.');
                });
        }

        function guardarEdicionEstudiante() {
            const form = document.getElementById('editarEstudianteForm');
            const formData = new FormData(form);

            fetch('actualizar_estudiante.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Estudiante actualizado con éxito');
                        location.reload(); // Recargar la página para mostrar los cambios
                    } else {
                        alert('Hubo un error al actualizar el estudiante. Por favor, inténtalo nuevamente.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Hubo un error al actualizar el estudiante. Por favor, inténtalo nuevamente.');
                });
        }

        function mostrarCertificados(id) {
            fetch('obtener_certificados.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    console.log('Datos recibidos:', data);
                    const certificadosBody = document.getElementById('certificadosBody');
                    certificadosBody.innerHTML = '';

                    if (Array.isArray(data)) {
                        if (data.length === 0) {
                            // No se encontraron certificados
                            mostrarMensajeNoCertificados(certificadosBody);
                        } else {
                            // Se encontraron certificados
                            data.forEach(certificado => {
                                const row = document.createElement('tr');
                                row.innerHTML = `
                    <td>${certificado.curso}</td>
                    <td>${certificado.anio}</td>
                    <td>${certificado.trimestre}</td>
                    <td>${certificado.cefr}</td>
                    <td><button class="btn btn-primary btn-sm" onclick="descargarCertificado(${id}, ${certificado.curso_id})">Descargar</button></td>
                `;
                                certificadosBody.appendChild(row);
                            });
                        }
                    } else if (data.message) {
                        // Se recibió un mensaje de error
                        mostrarMensajeNoCertificados(certificadosBody, data.message);
                    } else {
                        // Respuesta inesperada
                        throw new Error('Respuesta inesperada del servidor');
                    }

                    new bootstrap.Modal(document.getElementById('certificadosModal')).show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Hubo un error al obtener los certificados. Por favor, inténtalo nuevamente.');
                });
        }

        function mostrarMensajeNoCertificados(certificadosBody, mensaje = 'No se encontraron certificados para este estudiante.') {
            const row = document.createElement('tr');
            row.innerHTML = `
<td colspan="5" class="text-center">${mensaje}</td>
`;
            certificadosBody.appendChild(row);
        }

        function descargarCertificado(estudianteId, cursoId) {
            window.location.href = `certificado.php?alumno_id=${estudianteId}&curso_id=${cursoId}&action=download`;
        }

        function mostrarCursos(id) {
            fetch('obtener_cursos.php?id=' + id)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    const cursosBody = document.getElementById('cursosBody');
                    cursosBody.innerHTML = '';

                    if (data.error) {
                        throw new Error(data.error);
                    }

                    if (data.length === 0) {
                        cursosBody.innerHTML = '<tr><td colspan="6">No se encontraron cursos para este estudiante.</td></tr>';
                    } else {
                        data.forEach(curso => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                <td>${curso.anio || ''}</td>
                <td>${curso.trimestre || ''}</td>
                <td>${curso.categoria || ''}</td>
                <td>${curso.course || ''}</td>
                <td>${curso.profesor || ''}</td>
                <td>${curso.final_grade || ''}</td>
            `;
                            cursosBody.appendChild(row);
                        });
                    }

                    new bootstrap.Modal(document.getElementById('cursosModal')).show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Hubo un error al obtener los cursos: ' + error.message);
                });
        }

        document.getElementById('descargarCSV').addEventListener('click', function () {
            const table = document.querySelector("#cursosModal table");
            let csv = [];
            for (let i = 0; i < table.rows.length; i++) {
                let row = [], cols = table.rows[i].querySelectorAll("td, th");
                for (let j = 0; j < cols.length; j++)
                    row.push(cols[j].innerText);
                csv.push(row.join(","));
            }
            let csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
            let downloadLink = document.createElement("a");
            downloadLink.download = "cursos_estudiante.csv";
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = "none";
            document.body.appendChild(downloadLink);
            downloadLink.click();
        });

        document.getElementById('descargarCertificadosCSV').addEventListener('click', function () {
            const table = document.querySelector("#certificadosModal table");
            let csv = [];
            for (let i = 0; i < table.rows.length; i++) {
                let row = [], cols = table.rows[i].querySelectorAll("td, th");
                for (let j = 0; j < cols.length; j++)
                    row.push(cols[j].innerText);
                csv.push(row.join(","));
            }
            let csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
            let downloadLink = document.createElement("a");
            downloadLink.download = "certificados_estudiante.csv";
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = "none";
            document.body.appendChild(downloadLink);
            downloadLink.click();
        });

    </script>
</body>

</html>
<?php include 'includes/footer.php'; ?>