<?php

include "conexion.php";

$nombre = $_POST["nombre"];
$apellido_paterno = $_POST["apellido_paterno"];
$apellido_materno = $_POST["apellido_materno"];
$correo = $_POST["correo"];
$telefono = $_POST["telefono"];

$codigo_boleto = "SUMMIT-" . strtoupper(substr(uniqid(), -8));

$sql = "INSERT INTO boletos 
        (codigo_boleto, nombre, apellido_paterno, apellido_materno, telefono, correo, precio, estado_pago)
        VALUES (?, ?, ?, ?, ?, ?, 500.00, 'PENDIENTE')";

$stmt = $conexion->prepare($sql);

$stmt->bind_param(
    "ssssss",
    $codigo_boleto,
    $nombre,
    $apellido_paterno,
    $apellido_materno,
    $telefono,
    $correo
);

if ($stmt->execute()) {

    header("Location: boleto.php?codigo=" . urlencode($codigo_boleto));
    exit;

} else {

    echo "Error al guardar el boleto: " . $stmt->error;

}

$stmt->close();
$conexion->close();

?>