<?php

include "conexion.php";

if (!isset($_GET["codigo"])) {
    die("No se proporcionó un código de boleto.");
}

$codigo = $_GET["codigo"];

$sql = "SELECT * FROM boletos WHERE codigo_boleto = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $codigo);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows == 0) {

    echo "<h1>❌ Boleto no encontrado</h1>";

} else {

    $boleto = $resultado->fetch_assoc();

    if ($boleto["usado"] == 1) {

        echo "<h1>⚠️ Boleto ya utilizado</h1>";
        echo "<p>Este boleto ya fue registrado anteriormente.</p>";

    } else {

        echo "<h1>✅ Boleto válido</h1>";
        echo "<p><strong>Nombre:</strong> " . htmlspecialchars($boleto["nombre"]) . "</p>";
        echo "<p><strong>Apellidos:</strong> "
            . htmlspecialchars($boleto["apellido_paterno"])
            . " "
            . htmlspecialchars($boleto["apellido_materno"])
            . "</p>";

        echo "<p><strong>Código:</strong> "
            . htmlspecialchars($boleto["codigo_boleto"])
            . "</p>";

        echo "<p><strong>Estado de pago:</strong> "
            . htmlspecialchars($boleto["estado_pago"])
            . "</p>";
    }
}

$stmt->close();
$conexion->close();

?>