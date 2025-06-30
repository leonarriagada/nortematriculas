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

$resultados = [];
$busqueda = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' || isset($_GET['busqueda'])) {
    $busqueda = isset($_POST['busqueda']) ? sanitize_input($_POST['busqueda']) : sanitize_input($_GET['busqueda']);
    $rut_formatted = validate_rut_format($busqueda);

    try {
        $query = "SELECT rt.id, rt.curso_test, rt.categ_test, rt.fecha_test, 
                         e.id AS estudiante_id, e.rut, e.nombre, e.apellido_p, e.apellido_m
                  FROM resul_test rt
                  JOIN estudiantes e ON rt.estudiante_id = e.id
                  WHERE e.nombre LIKE :busqueda
                  OR e.apellido_p LIKE :busqueda
                  OR e.apellido_m LIKE :busqueda";

        if ($rut_formatted) {
            $query .= " OR e.rut = :rut";
        }

        $query .= " ORDER BY rt.fecha_test DESC LIMIT 15";

        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':busqueda', "%$busqueda%", PDO::PARAM_STR);

        if ($rut_formatted) {
            $stmt->bindValue(':rut', $rut_formatted, PDO::PARAM_STR);
        }

        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log($e->getMessage());
        $resultados = [];
    }
} else {
    // Si no hay búsqueda, mostrar los últimos 15 registros
    try {
        $query = "SELECT rt.id, rt.curso_test, rt.categ_test, rt.fecha_test, 
                         e.id AS estudiante_id, e.rut, e.nombre, e.apellido_p, e.apellido_m
                  FROM resul_test rt
                  JOIN estudiantes e ON rt.estudiante_id = e.id
                  ORDER BY rt.fecha_test DESC LIMIT 15";

        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log($e->getMessage());
        $resultados = [];
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados de Test</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
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

        .container-buscar-registro {
            width: 450px;
            ;
        }

        .form-control {
            margin: 0px 20px 0px;
            width: 75%;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <!-- Buscar Resultados -->
    <center>
        <div class="container-buscar-registro mt-4">
            <h1 class="text-center mb-1">Resultados Test</h1>
            <p></p>
            <form method="POST" class="mb-4">
                <p>&nbsp;</p>
                <div class="input-group">
                    <input type="text" name="busqueda" class="form-control" placeholder="Buscar por nombre, RUT o email"
                        value="<?php echo htmlspecialchars(isset($busqueda) ? $busqueda : ''); ?>">
                    <button type="submit" class="btn btn-success">Buscar</button>
                </div>
            </form>
        </div>
    </center>

    <!-- Resultados de Busqueda -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>RUT</th>
                        <th>Nombre</th>
                        <th>Curso Test</th>
                        <th>Categoría Test</th>
                        <th>Fecha Test</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultados as $resultado): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($resultado['rut']); ?></td>
                            <td><?php echo htmlspecialchars($resultado['nombre'] . ' ' . $resultado['apellido_p'] . ' ' . $resultado['apellido_m']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($resultado['curso_test']); ?></td>
                            <td><?php echo htmlspecialchars($resultado['categ_test']); ?></td>
                            <td><?php echo htmlspecialchars($resultado['fecha_test']); ?></td>
                            <td>
                                <a href="matricular_estudiante.php?id=<?php echo $resultado['estudiante_id']; ?>"
                                    class="btn btn-sm btn-primary">Matricular Estudiante</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>