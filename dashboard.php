<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'error.log');
error_reporting(E_ALL);

session_start();
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/utilities.php';

if (!isset($_SESSION['token']) || !validate_jwt($_SESSION['token'])) {
    header('Location: login.php');
    exit;
}

$user_role = $_SESSION['user_role'] ?? 'profesor';

function get_current_trimester()
{
    $month = date('n');
    if ($month >= 1 && $month <= 2) {
        return 'Summer';
    } elseif ($month >= 3 && $month <= 6) {
        return 'Primer Trimestre';
    } elseif ($month >= 7 && $month <= 10) {
            return 'Segundo Trimestre';
    } else { ($month >= 7 && $month <= 10);
        return 'Tercer Trimestre';
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - Sistema de Gestión Académica</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            background-color: #f3f4f6;
            min-height: 100vh;
        }
        .card {
            transition: transform 0.3s;
        }
        .card:hover {
            transform: scale(1.05);
        }
        .card-body i {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }
        .card-body i:hover {
            animation: bounce 0.5s;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        /* Tailwind colors */
        .bg-green-500 { background-color: #22c55e; }
        .bg-red-500 { background-color: #ef4444; }
        .bg-orange-500 { background-color: #f97316; }
        .bg-blue-500 { background-color: #3B82F6; }
        .bg-purple-500 { background-color: #a855f7; }
        .bg-teal-500 { background-color: #14B8A6; }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="text-center mb-4">
            <img src="images/logo.svg" width="250" alt="Logo">
        </div>
        <h1 style="font-weight:bold;" class="display-6 text-center mb-3">Sistema de Gestión Académica</h1>
        <h2 class="h3 text-center mb-5">Panel de Control - <?php echo get_current_trimester(); ?> <?php echo date('Y'); ?></h2>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mb-5">
            <?php if ($user_role === 'admin'): ?>
                <div class="col">
                    <a href="crear_curso.php" class="text-decoration-none">
                        <div class="card bg-green-500 text-white h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-plus mb-2"></i>
                                <p class="card-text fw-bold">Crear Curso</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col">
                    <a href="matricular_estudiante.php" class="text-decoration-none">
                        <div class="card bg-red-500 text-white h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-user-graduate mb-2"></i>
                                <p class="card-text fw-bold">Matricular Estudiante</p>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endif; ?>
            <div class="col">
                <a href="notas.php" class="text-decoration-none">
                    <div class="card bg-orange-500 text-white h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-check mb-2"></i>
                            <p class="card-text fw-bold">Gestionar Notas</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="buscar_certificado.php" class="text-decoration-none">
                    <div class="card bg-blue-500 text-white h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-certificate mb-2"></i>
                            <p class="card-text fw-bold">Certificados</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="resultado_test.php" class="text-decoration-none">
                    <div class="card bg-purple-500 text-white h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-eye mb-2"></i>
                            <p class="card-text fw-bold">Resultados Test</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="buscar_registros.php" class="text-decoration-none">
                    <div class="card bg-teal-500 text-white h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-search mb-2"></i>
                            <p class="card-text fw-bold">Buscar Estudiante</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php include 'includes/footer.php'; ?>



