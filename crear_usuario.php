<?php
session_start();
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Verificar si el usuario actual es un administrador
check_role('admin');

$message = '';
$messageClass = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    $role = sanitize_input($_POST['role']);

    try {
        // Verificar si el usuario ya existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if (!$user) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO usuarios (username, password, role) VALUES (:username, :password, :role)");
            $stmt->execute([
                'username' => $username,
                'password' => $hashed_password,
                'role' => $role
            ]);

            $message = "Usuario creado con éxito.";
            $messageClass = "success";
        } else {
            $message = "El usuario ya existe.";
            $messageClass = "error";
        }
    } catch (PDOException $e) {
        $message = "Error al crear el usuario: " . $e->getMessage();
        $messageClass = "error";
    }
}

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nuevo Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .password-input-group {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--bs-body-color);
            cursor: pointer;
        }

        .container-crear-usuario {
            width: 60%;
            margin: 2rem auto;
            padding: 2rem;

            border-radius: 12px;
            box-shadow: var(--box-shadow);

        }
    </style>
</head>

<body>
    <div class="container-crear-usuario">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Crear Nuevo Usuario</h2>
                        <p></p>
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageClass === 'success' ? 'success' : 'danger'; ?>"
                                role="alert">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Nombre de usuario</label>
                                <input type="text" id="username" name="username" required class="form-control">
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <div class="password-input-group">
                                    <input type="password" id="password" name="password" required class="form-control">
                                    <button type="button" class="password-toggle" onclick="togglePassword()">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="role" class="form-label">Rol</label>
                                <select name="role" id="role" required class="form-select">
                                    <option value="admin">Administrador</option>
                                    <option value="profesor">Profesor</option>
                                </select>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Crear Usuario</button>
                            </div>
                        </form>

                        <div class="text-center mt-3">
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver al panel principal
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.querySelector('.password-toggle i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleButton.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleButton.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>

</html>