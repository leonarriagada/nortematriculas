<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

try {
    // Crear la tabla Usuarios
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    echo "Tabla Usuarios creada con éxito.<br>";

    // Verificar si el usuario admin ya existe
    $stmt = $pdo->prepare("SELECT id FROM Usuarios WHERE username = :username");
    $stmt->execute(['username' => 'admin']);
    $user = $stmt->fetch();

    if (!$user) {
        // Insertar el usuario admin con la contraseña hasheada
        $username = 'admin';
        $password = 'admin';
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO Usuarios (username, password) VALUES (:username, :password)");
        $stmt->execute(['username' => $username, 'password' => $hashed_password]);

        echo "Usuario admin creado con éxito.<br>";
    } else {
        echo "El usuario admin ya existe.<br>";
    }

    echo "Configuración de la base de datos completada.";

} catch (PDOException $e) {
    die("Error en la configuración de la base de datos: " . $e->getMessage());
}