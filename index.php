<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

$conexion = new mysqli("localhost", "root", "", "sistema_login");
if ($conexion->connect_error) {
    die("Error en la conexión: " . $conexion->connect_error);
}

$mensaje = "";

// Eliminar archivo
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["eliminar_archivo"])) {
    $rutaEliminar = $_POST["ruta_archivo"];
    $usuario = $_SESSION["usuario"];

    $sql = "SELECT id FROM archivos WHERE usuario = ? AND ruta_archivo = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ss", $usuario, $rutaEliminar);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();

        $sql = "DELETE FROM archivos WHERE usuario = ? AND ruta_archivo = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ss", $usuario, $rutaEliminar);
        if ($stmt->execute()) {
            if (file_exists($rutaEliminar)) {
                unlink($rutaEliminar);
            }
            $mensaje = "Archivo eliminado correctamente.";
        } else {
            $mensaje = "Error al eliminar el archivo de la base de datos.";
        }
    } else {
        $mensaje = "Archivo no encontrado o no tienes permiso para eliminarlo.";
    }
}

// Subida de archivo
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["archivo"])) {
    $usuario = $_SESSION["usuario"];
    $archivo = $_FILES["archivo"];
    $nombreArchivo = basename($archivo["name"]);
    $tipoArchivo = $archivo["type"];
    $tamanoArchivo = $archivo["size"];
    $tempArchivo = $archivo["tmp_name"];
    $errorArchivo = $archivo["error"];

    $carpetaDestino = "uploads/";

    if (!is_dir($carpetaDestino)) {
        mkdir($carpetaDestino, 0755, true);
    }

    $rutaArchivo = $carpetaDestino . time() . "_" . $nombreArchivo;

    if ($errorArchivo === 0) {
        if (move_uploaded_file($tempArchivo, $rutaArchivo)) {
            $sql = "INSERT INTO archivos (usuario, nombre_archivo, tipo_archivo, tamano_archivo, ruta_archivo) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sssis", $usuario, $nombreArchivo, $tipoArchivo, $tamanoArchivo, $rutaArchivo);

            if ($stmt->execute()) {
                $mensaje = "Archivo subido y guardado correctamente.";
            } else {
                $mensaje = "Error al guardar en la base: " . $stmt->error;
                unlink($rutaArchivo);
            }
        } else {
            $mensaje = "Error al mover el archivo.";
        }
    } else {
        $mensaje = "Error en la subida del archivo. Código de error: $errorArchivo";
    }
}

// Obtener archivos del usuario actual
$archivos = [];
$usuario = $_SESSION["usuario"];
$sql = "SELECT nombre_archivo, tipo_archivo, tamano_archivo, ruta_archivo FROM archivos WHERE usuario = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $usuario);
$stmt->execute();
$resultado = $stmt->get_result();
while ($fila = $resultado->fetch_assoc()) {
    $archivos[] = $fila;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bienvenido</title>
    <link rel="stylesheet" href="estilo.css">
    <style>
        .container {
            display: flex;
            justify-content: space-between;
            max-width: 1000px;
            margin: 50px auto;
        }

        .bienvenida {
            max-width: 45%;
            text-align: center;
        }

        .tabla-archivos {
            max-width: 100%;
            background-color:rgb(255, 255, 255);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        th {
            background-color: #f2f2f2;
        }

        a.descargar, a.abrir {
            color: rgb(0, 174, 255);
            text-decoration: none;
            margin-right: 8px;
        }

        a.descargar:hover, a.abrir:hover {
            text-decoration: underline;
        }

        form.eliminar-form {
            display: inline;
        }

        button.eliminar-btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 10px;
        }
    </style>
</head>
<body>

<div class="bienvenida" style="
    max-width: 600px;
    margin: 40px auto;
    padding: 30px;
    text-align: center;
    border: 1px solid #ccc;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    background-color: #f9f9f9;
">
    <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION["usuario"]); ?>!</h1>

    <?php if ($mensaje) echo "<p style='color: green;'>$mensaje</p>"; ?>

    <form method="POST" enctype="multipart/form-data" style="
        margin-top: 20px;
        width: 100%;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
        display: flex;
        flex-direction: column;
        align-items: center;
    ">
        <label for="archivo" style="
            margin-bottom: 10px;
            font-weight: bold;
            width: 100%;
            text-align: center;
        ">
            Selecciona un archivo para subir:
        </label>
        <input type="file" name="archivo" id="archivo" required style="
            margin-bottom: 20px;
            display: block;
        " />
        <button type="submit" style="
            padding: 10px 20px;
            background-color: rgb(0, 153, 255);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        ">
            Subir Archivo
        </button>
    </form>

    <a href="logout.php" style="
        display: inline-block;
        margin-top: 30px;
        color: #333;
        text-decoration: none;
    ">Cerrar sesión</a>
</div>

<div class="tabla-archivos" style="
    max-width: 500px;
    margin: 60px auto;
    padding: 20px;
    border: 1px solid #ccc;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    background-color: #ffffff;
">

    <h2 style="text-align: center; margin-bottom: 20px;">Archivos Subidos</h2>
    
    <?php if (count($archivos) > 0): ?>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Tamaño</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($archivos as $archivo): ?>
                <tr>
                    <td><?php echo htmlspecialchars($archivo["nombre_archivo"]); ?></td>
                    <td><?php echo htmlspecialchars($archivo["tipo_archivo"]); ?></td>
                    <td><?php echo round($archivo["tamano_archivo"] / 1024, 2) . " KB"; ?></td>
                   <td>
    <a class="descargar" href="<?php echo htmlspecialchars($archivo["ruta_archivo"]); ?>" download>Descargar archivo</a>
    <a class="abrir" href="<?php echo htmlspecialchars($archivo["ruta_archivo"]); ?>" target="_blank">Abrir archivo</a>
    <form method="POST" class="eliminar-form" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este archivo?');">
        <input type="hidden" name="ruta_archivo" value="<?php echo htmlspecialchars($archivo["ruta_archivo"]); ?>">
        <input type="hidden" name="eliminar_archivo" value="1">
        <button type="submit" class="eliminar-btn">Eliminar archivo</button>
    </form>
</td>

                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p style="text-align: center;">No has subido archivos aún.</p>
    <?php endif; ?>
</div>

</body>
</html>
