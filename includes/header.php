<?php
// Asegúrate de que la sesión esté iniciada y que tengas acceso a la información del usuario

if (!isset($_SESSION['user_role'])) {
    // Si no hay sesión iniciada, redirige al login
    header('Location: login.php');
    exit;
}

$userRole = $_SESSION['user_role'];

// Define los permisos para cada enlace y sus nombres personalizados
$menuItems = [
    'index.php' => ['roles' => ['admin', 'profesor'], 'name' => 'Inicio'],
    'matricular_estudiante.php' => ['roles' => ['admin', 'profesor'], 'name' => 'Matricular'],
    'buscar_registros.php' => ['roles' => ['admin', 'profesor'], 'name' => 'Buscar'],
    'revisar_cursos.php' => ['roles' => ['admin', 'profesor'], 'name' => 'Cursos'],
    'crear_usuario.php' => ['roles' => ['admin'], 'name' => 'Usuarios'],
    'crear_curso.php' => ['roles' => ['admin'], 'name' => 'Nuevo Curso'],
    'notas.php' => ['roles' => ['admin'], 'name' => 'Notas'],
];
?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Matrículas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        [data-bs-theme="light"] .navbar {
            background-color: #ffffff !important;
        }

        [data-bs-theme="dark"] .navbar {
            background-color: #343a40 !important;
        }

        [data-bs-theme="light"] .navbar-nav .nav-link {
            color: #000000;
        }

        [data-bs-theme="dark"] .navbar-nav .nav-link {
            color: #ffffff;
        }

        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link:focus {
            color: #ff0000;
        }

        .navbar-brand img {
            width: 200px;
        }

        .logo-light,
        .logo-dark {
            display: none;
        }

        [data-bs-theme="light"] .logo-light {
            display: block;
        }

        [data-bs-theme="dark"] .logo-dark {
            display: block;
        }

        .dropdown:hover .dropdown-menu {
            display: block;
        }
    </style>
</head>

<body>
    <header>
        <nav class="navbar navbar-expand-lg bg-body-tertiary">
            <div class="container">

                <!-- Logos -->
                <a class="navbar-brand" href="#">
                    <img src="images/logo-light.png" alt="Logo" class="img-fluid logo-light">
                    <img src="images/logo-dark.png" alt="Logo" class="img-fluid logo-dark">
                </a>

                <!-- Boton Menu Responsive -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Menu -->
                <nav class="navbar navbar-expand-lg bg-body-tertiary">
                    <div class="container-fluid">

                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                            data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false"
                            aria-label="Toggle navigation">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="collapse navbar-collapse" id="navbarNavDropdown">
                            <ul class="navbar-nav">

                                <!-- Inicio -->
                                <li class="nav-item">
                                    <a class="nav-link active" aria-current="page" href="index.php">Inicio</a>
                                </li>

                                <!-- Estudiantes Dropdown -->
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                        Estudiantes
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="matricular_estudiante.php">Matricular
                                                Estudiantes</a></li>
                                        <li><a class="dropdown-item" href="buscar_registros.php">Buscar Estudiante</a>
                                        </li>
                                        <li><a class="dropdown-item" href="buscar_certificado.php">Descargar Certificado</a></li>
                                        <li><a class="dropdown-item" href="notas.php">Notas</a></li>
                                    </ul>
                                </li>

                                <!-- Cursos Dropdown -->
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                        Cursos
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="crear_curso.php">Crear Curso</a>
                                        </li>
                                        <li><a class="dropdown-item" href="crear_curso.php">Buscar Curso</a>
                                        </li>
                                        <li><a class="dropdown-item" href="#">Descargar Informes</a></li>
                                    </ul>
                                </li>

                                <!-- Usuarios Dropdown -->
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                        Usuarios
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="crear_usuario.php">Crear Usuario</a>
                                        </li>
                                        <li><a class="dropdown-item" href="buscar_registro.php">Buscar Usuario</a>
                                        </li>
                                    </ul>
                                </li>

                            </ul>
                        </div>
                    </div>
                </nav>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Cerrar Sesión</a>
                    </li>
                </ul>
                <button class="btn btn-outline-secondary ms-2" id="themeToggle">
                    <i class="fas fa-adjust"></i>
                </button>
            </div>
        </nav>
    </header>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>

    <script>
        const themeToggle = document.getElementById('themeToggle');
        const htmlElement = document.documentElement;

        function toggleTheme() {
            if (htmlElement.getAttribute('data-bs-theme') === 'dark') {
                htmlElement.setAttribute('data-bs-theme', 'light');
                localStorage.setItem('theme', 'light');
            } else {
                htmlElement.setAttribute('data-bs-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            }
        }

        themeToggle.addEventListener('click', toggleTheme);

        document.addEventListener('DOMContentLoaded', (event) => {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme) {
                htmlElement.setAttribute('data-bs-theme', savedTheme);
            }
        });
    </script>
</body>

</html>