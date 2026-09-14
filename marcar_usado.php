<?php

include "conexion.php";

if (!isset($_GET["codigo"])) {
    die("Código de boleto no proporcionado.");
}

$codigo = $_GET["codigo"];

$sql = "UPDATE boletos 
        SET usado = 1 
        WHERE codigo_boleto = ? 
        AND usado = 0 
        AND estado_pago = 'PAGADO'";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $codigo);

if ($stmt->execute()) {

    if ($stmt->affected_rows > 0) {

        echo "<h1>✅ Boleto registrado</h1>";
        echo "<p>El boleto ha sido marcado como utilizado.</p>";
        echo "<p>Código: <strong>" . htmlspecialchars($codigo) . "</strong></p>";

    } else {

        echo "<h1>⚠️ No se pudo registrar</h1>";
        echo "<p>El boleto puede estar ya utilizado, no existir o no estar pagado.</p>";

    }

} else {

    echo "Error: " . $stmt->error;

}

$stmt->close();
$conexion->close();

?>