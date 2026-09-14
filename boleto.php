<?php

include "conexion.php";

if (!isset($_GET["codigo"])) {
    die("Código de boleto no proporcionado.");
}

$codigo = $_GET["codigo"];

$sql = "SELECT * FROM boletos WHERE codigo_boleto = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $codigo);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows == 0) {
    die("Boleto no encontrado.");
}

$boleto = $resultado->fetch_assoc();

$stmt->close();
$conexion->close();

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Boleto - Summit Empresarial México</title>

    <!-- Librería para generar QR -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .boleto {
            background: white;
            width: 420px;
            padding: 35px;
            border-radius: 20px;
            box-shadow: 0 10px 35px rgba(0,0,0,0.15);
            text-align: center;
        }

        .titulo {
            color: #0A1931;
            font-size: 28px;
            font-weight: bold;
        }

        .subtitulo {
            color: #C5A059;
            font-weight: bold;
            margin-bottom: 25px;
        }

        .datos {
            text-align: left;
            margin-top: 20px;
            line-height: 1.7;
        }

        .codigo {
            margin-top: 20px;
            font-weight: bold;
            color: #0A1931;
        }

        #qrcode {
            display: flex;
            justify-content: center;
            margin-top: 25px;
        }

    </style>

</head>

<body>

    <div class="boleto">

        <div class="titulo">
            SUMMIT EMPRESARIAL
        </div>

        <div class="subtitulo">
            MÉXICO
        </div>

        <h2>Acceso Completo</h2>

        <div class="datos">

            <p>
                <strong>Nombre:</strong>
                <?php echo htmlspecialchars($boleto["nombre"]); ?>
            </p>

            <p>
                <strong>Apellidos:</strong>
                <?php echo htmlspecialchars($boleto["apellido_paterno"]); ?>
                <?php echo htmlspecialchars($boleto["apellido_materno"]); ?>
            </p>

            <p>
                <strong>Correo:</strong>
                <?php echo htmlspecialchars($boleto["correo"]); ?>
            </p>

            <p>
                <strong>Teléfono:</strong>
                <?php echo htmlspecialchars($boleto["telefono"]); ?>
            </p>

        </div>

        <div class="codigo">
            <?php echo htmlspecialchars($boleto["codigo_boleto"]); ?>
        </div>

        <div id="qrcode"></div>

        <p>
            Presenta este código QR para ingresar al evento.
        </p>

    </div>

    <?php
    $url_validacion = "http://localhost/summit/validar_boleto.php?codigo="
        . urlencode($boleto["codigo_boleto"]);
    ?>

    <script>

        new QRCode(document.getElementById("qrcode"), {
            text: "<?php echo htmlspecialchars($url_validacion, ENT_QUOTES); ?>",
            width: 200,
            height: 200
        });

    </script>

</body>

</html>