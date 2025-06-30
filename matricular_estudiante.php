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

$mensaje = '';
$estudiante = null;

// Verificar si se recibió un ID de estudiante
if (isset($_GET['id'])) {
    $estudiante_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

    // Obtener los datos del estudiante
    try {
        $sql = "SELECT * FROM estudiantes WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$estudiante_id]);
        $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$estudiante) {
            $mensaje = "Estudiante no encontrado.";
        }
    } catch (Exception $e) {
        error_log("Error al obtener datos del estudiante: " . $e->getMessage());
        $mensaje = "Error al obtener datos del estudiante. Por favor, intente nuevamente.";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar y sanitizar entradas
    $rut = htmlspecialchars(trim($_POST['rut'] ?? ''), ENT_QUOTES, 'UTF-8');
    $nombre = htmlspecialchars(trim($_POST['nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
    $apellido_p = htmlspecialchars(trim($_POST['apellido_p'] ?? ''), ENT_QUOTES, 'UTF-8');
    $apellido_m = htmlspecialchars(trim($_POST['apellido_m'] ?? ''), ENT_QUOTES, 'UTF-8');
    $telefono = htmlspecialchars(trim($_POST['telefono'] ?? ''), ENT_QUOTES, 'UTF-8');
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $curso_id = filter_input(INPUT_POST, 'curso', FILTER_SANITIZE_NUMBER_INT);
    $fecha_matricula = date('Y-m-d'); // Fecha actual

    try {
        $pdo->beginTransaction();

        // Verificar si el estudiante existe
        $sql_estudiante = "SELECT id FROM estudiantes WHERE rut = ?";
        $stmt_estudiante = $pdo->prepare($sql_estudiante);
        $stmt_estudiante->execute([$rut]);
        $result_estudiante = $stmt_estudiante->fetch(PDO::FETCH_ASSOC);

        if ($result_estudiante) {
            // Actualizar estudiante existente
            $estudiante_id = $result_estudiante['id'];
            $sql_update = "UPDATE estudiantes SET nombre = ?, apellido_p = ?, apellido_m = ?, telefono = ?, email = ? WHERE id = ?";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([$nombre, $apellido_p, $apellido_m, $telefono, $email, $estudiante_id]);
            error_log("Estudiante actualizado con ID: " . $estudiante_id);
        } else {
            // Insertar nuevo estudiante
            $sql_insert = "INSERT INTO estudiantes (rut, nombre, apellido_p, apellido_m, telefono, email) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_insert = $pdo->prepare($sql_insert);
            $stmt_insert->execute([$rut, $nombre, $apellido_p, $apellido_m, $telefono, $email]);
            $estudiante_id = $pdo->lastInsertId();
            error_log("Nuevo estudiante insertado con ID: " . $estudiante_id);
        }

        // Verificar si ya existe una matrícula para el mismo estudiante y curso
        $sql_check_matricula = "SELECT COUNT(*) FROM matriculas WHERE estudiante_id = ? AND curso_id = ?";
        $stmt_check_matricula = $pdo->prepare($sql_check_matricula);
        $stmt_check_matricula->execute([$estudiante_id, $curso_id]);
        $matricula_existente = $stmt_check_matricula->fetchColumn();

        if ($matricula_existente > 0) {
            throw new Exception("El estudiante ya está matriculado en este curso.");
        }

        // Insertar registro en la tabla notas
        $sql_notas = "INSERT INTO notas () VALUES ()";
        $stmt_notas = $pdo->prepare($sql_notas);
        $stmt_notas->execute([]);
        $notas_id = $pdo->lastInsertId();
        error_log("Registro de notas insertado con ID: " . $notas_id);

        // Insertar matrícula
        $sql_matricula = "INSERT INTO matriculas (estudiante_id, curso_id, fecha_matricula, notas_id) VALUES (?, ?, ?, ?)";
        $stmt_matricula = $pdo->prepare($sql_matricula);
        $estudiante_id = $estudiante_id ?? filter_input(INPUT_POST, 'estudiante_id', FILTER_SANITIZE_NUMBER_INT);
        $stmt_matricula->execute([$estudiante_id, $curso_id, $fecha_matricula, $notas_id]);
        $matricula_id = $pdo->lastInsertId();
        error_log("Nueva matrícula insertada con ID: " . $matricula_id);

        $pdo->commit();
        error_log("Transacción completada con éxito");

        // Redirigir a la página de ficha de matrícula
        ob_end_clean(); // Limpia el buffer de salida si es necesario
        header("Location: ficha_matricula.php?id=" . $matricula_id);
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error en la matriculación: " . $e->getMessage());
        $mensaje = "Error al matricular el curso. Por favor, intente nuevamente. Detalles: " . $e->getMessage();
    }
}

include 'includes/header.php';
?>



<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matricular Estudiante</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        .container-matricula {
            padding: 12px 24px !important;
            font-size: 16px !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 8px rgb(4 0 0 / 34%) !important;
            cursor: pointer !important;
            width: 50%;
            text-align: center;
            transition: background-color 0.3s ease !important;

        }

        @media (max-width: 600px) {
            .container .form-container {
                flex-direction: column !important;
            }
        }
    </style>
</head>

<body>
    <center>
        <div class="container-matricula mt-5">
            <main>
                <h2 class="text-center mb-4">Matricular Estudiante</h2>
                <form method="POST" id="matriculaForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="rut" class="form-label">RUT:</label>
                                <input type="text" class="form-control" id="rut" name="rut"
                                    value="<?php echo htmlspecialchars($estudiante['rut'] ?? ''); ?>"
                                    onblur="validarRut()" required>
                            </div>
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre:</label>
                                <input type="text" class="form-control" id="nombre" name="nombre"
                                    value="<?php echo htmlspecialchars($estudiante['nombre'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="apellido_p" class="form-label">Apellido Paterno:</label>
                                <input type="text" class="form-control" id="apellido_p" name="apellido_p"
                                    value="<?php echo htmlspecialchars($estudiante['apellido_p'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="apellido_m" class="form-label">Apellido Materno:</label>
                                <input type="text" class="form-control" id="apellido_m" name="apellido_m"
                                    value="<?php echo htmlspecialchars($estudiante['apellido_m'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico:</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="<?php echo htmlspecialchars($estudiante['email'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Teléfono:</label>
                                <input type="text" class="form-control" id="telefono" name="telefono"
                                    value="<?php echo htmlspecialchars($estudiante['telefono'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="anio" class="form-label">Año:</label>
                                <select class="form-select" id="anio" name="anio" required
                                    onchange="cargarTrimestres()">
                                    <option value="">Seleccione un año</option>
                                    <?php
                                    $sql_anios = "SELECT DISTINCT anio FROM cursos ORDER BY anio DESC";
                                    $stmt_anios = $pdo->query($sql_anios);
                                    while ($row = $stmt_anios->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<option value='{$row['anio']}'>{$row['anio']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="trimestre" class="form-label">Trimestre:</label>
                                <select class="form-select" id="trimestre" name="trimestre" required
                                    onchange="cargarCategorias()" disabled>
                                    <option value="">Seleccione un trimestre</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="categoria" class="form-label">Categoría:</label>
                                <select class="form-select" id="categoria" name="categoria" required
                                    onchange="cargarModalidades()" disabled>
                                    <option value="">Seleccione una categoría</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="modalidad" class="form-label">Modalidad:</label>
                                <select class="form-select" id="modalidad" name="modalidad" required
                                    onchange="cargarCursos()" disabled>
                                    <option value="">Seleccione una modalidad</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="curso" class="form-label">Curso:</label>
                                <select class="form-select" id="curso" name="curso" required
                                    onchange="mostrarDiasHorarios()" disabled>
                                    <option value="">Seleccione un curso</option>
                                </select>
                            </div>
                            <div id="dias_horarios" style="display:none;">
                                <div class="mb-3">
                                    <label for="dias" class="form-label">Días:</label>
                                    <select class="form-select" id="dias" name="dias">
                                        <option value="">Seleccione los días</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="horario" class="form-label">Horario:</label>
                                    <select class="form-select" id="horario" name="horario">
                                        <option value="">Seleccione el horario</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-success me-2">Matricular</button>
                        <button type="button" class="btn btn-danger"
                            onclick="window.location.href='dashboard.php'">Cancelar</button>
                    </div>
                </form>
            </main>
        </div>
    </center>
    <script>

        // Funcion para cargar trimestre
        function cargarTrimestres() {
            const anio = document.getElementById('anio').value;
            const trimestreSelect = document.getElementById('trimestre');
            trimestreSelect.innerHTML = '<option value="">Seleccione un trimestre</option>';
            trimestreSelect.disabled = true;

            if (anio) {
                fetch(`get_trimestres.php?anio=${anio}`)
                    .then(response => response.json())
                    .then(result => {
                        console.log('Respuesta del servidor:', result);
                        if (result.success) {
                            result.data.forEach(trimestre => {
                                const option = document.createElement('option');
                                option.value = trimestre;
                                option.textContent = trimestre;
                                trimestreSelect.appendChild(option);
                            });
                            trimestreSelect.disabled = false;
                        } else {
                            console.error('Error al cargar trimestres:', result.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error en la petición:', error);
                    });
            }
        }

        // Funcion para cargar categorias
        function cargarCategorias() {
            const anio = document.getElementById('anio').value;
            const trimestre = document.getElementById('trimestre').value;
            const categoriaSelect = document.getElementById('categoria');
            categoriaSelect.innerHTML = '<option value="">Seleccione una categoría</option>';
            categoriaSelect.disabled = true;

            if (anio && trimestre) {
                fetch(`get_categorias.php?anio=${anio}&trimestre=${trimestre}`)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(categoria => {
                            const option = document.createElement('option');
                            option.value = categoria;
                            option.textContent = categoria;
                            categoriaSelect.appendChild(option);
                        });
                        categoriaSelect.disabled = false;
                    });
            }
        }

        // Funcion para cargar modalidades
        function cargarModalidades() {
            const anio = document.getElementById('anio').value;
            const trimestre = document.getElementById('trimestre').value;
            const categoria = document.getElementById('categoria').value;
            const modalidadSelect = document.getElementById('modalidad');
            modalidadSelect.innerHTML = '<option value="">Seleccione una modalidad</option>';
            modalidadSelect.disabled = true;

            if (anio && trimestre && categoria) {
                fetch(`get_modalidades.php?anio=${anio}&trimestre=${trimestre}&categoria=${categoria}`)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(modalidad => {
                            const option = document.createElement('option');
                            option.value = modalidad;
                            option.textContent = modalidad;
                            modalidadSelect.appendChild(option);
                        });
                        modalidadSelect.disabled = false;
                    });
            }
        }

        // Funcion para cargar cursos
        function cargarCursos() {
            const anio = document.getElementById('anio').value;
            const trimestre = document.getElementById('trimestre').value;
            const categoria = document.getElementById('categoria').value;
            const modalidad = document.getElementById('modalidad').value;
            const cursoSelect = document.getElementById('curso');
            cursoSelect.innerHTML = '<option value="">Seleccione un curso</option>';
            cursoSelect.disabled = true;

            if (anio && trimestre && categoria && modalidad) {
                fetch(`get_cursos.php?anio=${anio}&trimestre=${trimestre}&categoria=${categoria}&modalidad=${modalidad}`)
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            result.data.forEach(curso => {
                                const option = document.createElement('option');
                                option.value = curso.id;
                                option.textContent = curso.course;
                                cursoSelect.appendChild(option);
                            });
                            cursoSelect.disabled = false;
                        } else {
                            console.error('Error al cargar cursos:', result.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error en la petición:', error);
                    });
            }
        }

        // Función para cargar días y horarios
        function mostrarDiasHorarios() {
            const cursoId = document.getElementById('curso').value;
            const diasHorariosDiv = document.getElementById('dias_horarios');
            const diasSelect = document.getElementById('dias');
            const horarioSelect = document.getElementById('horario');

            diasHorariosDiv.style.display = 'none';
            diasSelect.innerHTML = '<option value="">Seleccione los días</option>';
            horarioSelect.innerHTML = '<option value="">Seleccione el horario</option>';

            if (cursoId) {
                fetch(`get_dias_horarios.php?curso_id=${cursoId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0) {  // Cambiado de > 1 a > 0
                            data.forEach(opcion => {
                                const diasOption = document.createElement('option');
                                diasOption.value = opcion.dias;
                                diasOption.textContent = opcion.dias;
                                diasSelect.appendChild(diasOption);

                                const horarioOption = document.createElement('option');
                                horarioOption.value = opcion.horario;
                                horarioOption.textContent = opcion.horario;
                                horarioSelect.appendChild(horarioOption);
                            });
                            diasHorariosDiv.style.display = 'block';
                            diasSelect.disabled = false;
                            horarioSelect.disabled = false;
                        } else {
                            // Si no hay opciones, ocultar el div
                            diasHorariosDiv.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error al cargar días y horarios:', error);
                        diasHorariosDiv.style.display = 'none';
                    });
            } else {
                diasHorariosDiv.style.display = 'none';
            }
        }

        // Función para validar RUT 
        function validarRut() {
            let rut = document.getElementById("rut").value;
            rut = rut.replace(/\./g, '').replace(/-/g, '').toUpperCase();
            let cuerpo = rut.slice(0, -1);
            let dv = rut.slice(-1);

            if (cuerpo.length < 7) {
                alert("RUT incompleto.");
                return;
            }

            let suma = 0;
            let multiplo = 2;

            for (let i = cuerpo.length - 1; i >= 0; i--) {
                suma += multiplo * cuerpo.charAt(i);
                multiplo = multiplo < 7 ? multiplo + 1 : 2;
            }

            let dvEsperado = 11 - (suma % 11);
            dvEsperado = dvEsperado === 11 ? 0 : dvEsperado === 10 ? 'K' : dvEsperado;

            if (dv !== dvEsperado.toString()) {
                alert("RUT inválido.");
                return;
            }

            rut = cuerpo + '-' + dv;
            document.getElementById("rut").value = rut;

            buscarRut(rut);
        }

        // Función para buscar RUT
        function buscarRut(rut) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "buscar_rut.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function () {
                if (this.status === 200) {
                    const datos = JSON.parse(this.responseText);
                    if (datos.success) {
                        document.getElementById("nombre").value = datos.nombre;
                        document.getElementById("apellido_p").value = datos.apellido_p;
                        document.getElementById("apellido_m").value = datos.apellido_m;
                        document.getElementById("telefono").value = datos.telefono;
                        document.getElementById("email").value = datos.email;
                    } else {
                        alert("RUT no encontrado.");
                    }
                }
            };
            xhr.send("rut=" + encodeURIComponent(rut));
        }

    </script>
</body>

</html>

<?php
if ($mensaje) {
    echo "<p>$mensaje</p>";
}
?>

<?php include 'includes/footer.php'; ?>