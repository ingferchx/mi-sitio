<?php
session_start();
$conexion = new mysqli("localhost", "root", "", "sistema_login");


if ($conexion->connect_error) {
    die("Error en la conexión: " . $conexion->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST["nombre"]);
    $apellidos = trim($_POST["apellidos"]);
    $usuario = trim($_POST["usuario"]);
    $contrasena = $_POST["contrasena"];


    $sql_check = "SELECT * FROM usuarios WHERE usuario = ?";
    $stmt_check = $conexion->prepare($sql_check);
    $stmt_check->bind_param("s", $usuario);
    $stmt_check->execute();
    $resultado_check = $stmt_check->get_result();

    if ($resultado_check->num_rows > 0) {
        $error = "El usuario ya existe, por favor elige otro.";
    } else {
        
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuarios (nombre, apellidos, usuario, contrasena) VALUES (?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssss", $nombre, $apellidos, $usuario, $hash);

        if ($stmt->execute()) {
            $_SESSION["usuario"] = $usuario;
            header("Location: index.php");
            exit();
        } else {
            $error = "Error al registrar: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>

<form method="POST">
    <h2>Registro</h2>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <input type="text" name="nombre" placeholder="Nombre" required><br>
    <input type="text" name="apellidos" placeholder="Apellidos" required><br>
    <input type="text" name="usuario" placeholder="Usuario" required><br>
    <input type="password" name="contrasena" placeholder="Contraseña" required><br>
    <button type="submit">OK Registrarte</button>
    <a href="login.php">¿Ya tienes cuenta? Inicia sesión</a>
</form>

</body>
</html>
