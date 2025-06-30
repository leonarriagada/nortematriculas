<?php
session_start();
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/utilities.php';

// Habilitar reporte de todos los errores
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'error_log');
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT id, password, role FROM usuarios WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $token = generate_jwt($user['id']);
            $_SESSION['token'] = $token;
            $_SESSION['user_role'] = $user['role'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = "Credenciales incorrectas";
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        $error = "Ha ocurrido un error. Por favor, intenta de nuevo más tarde.";
    }
}
?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- <link rel="stylesheet" href="css/styles.css"> -->
    <style>
        .custom-card {
            max-width: 350px;
            margin: 0 auto;
        }

        .custom-title {
            font-size: 1.5rem;
        }

        .custom-form .custom-label {
            font-size: 0.9rem;
        }

        .custom-form .custom-input {
            font-size: 0.9rem;
            padding: 0.375rem 0.75rem;
        }

        .custom-form .custom-button {
            font-size: 0.9rem;
            padding: 0.375rem 0.75rem;
        }

        .bg-body-tertiary {
            background-color: #1b263b !important;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="images/logo.png" alt="Logo" height="70">
            </a>
            <button class="btn btn-outline-primary" onclick="toggleTheme()">Cambiar tema</button>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4 custom-title">Iniciar sesión</h2>
                        <form method="POST" class="custom-form">
                            <div class="mb-3">
                                <label for="username" class="form-label custom-label">Usuario</label>
                                <input type="text" class="form-control custom-input" id="username" name="username"
                                    placeholder="Usuario" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label custom-label">Contraseña</label>
                                <input type="password" class="form-control custom-input" id="password" name="password"
                                    placeholder="Contraseña" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary custom-button">Iniciar sesión</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleTheme() {
            const htmlElement = document.documentElement;
            if (htmlElement.getAttribute('data-bs-theme') === 'dark') {
                htmlElement.setAttribute('data-bs-theme', 'light');
                localStorage.setItem('theme', 'light');
            } else {
                htmlElement.setAttribute('data-bs-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            }
        }

        // Aplicar el tema guardado al cargar la página
        document.addEventListener('DOMContentLoaded', (event) => {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme) {
                document.documentElement.setAttribute('data-bs-theme', savedTheme);
            }
        });
    </script>
</body>

</html>