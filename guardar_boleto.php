<?php

include "conexion.php";
require_once "enviar_correo.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: menu.html");
    exit;
}

$nombre           = trim($_POST["nombre"] ?? "");
$apellido_paterno = trim($_POST["apellido_paterno"] ?? "");
$apellido_materno = trim($_POST["apellido_materno"] ?? "");
$correo           = trim($_POST["correo"] ?? "");
$telefono         = trim($_POST["telefono"] ?? "");

if (empty($nombre) || empty($apellido_paterno) || empty($correo) || empty($telefono)) {
    die("Por favor completa todos los campos requeridos. <a href='menu.html'>Regresar</a>");
}

// Generación de código único de boleto
$codigo_boleto = "SUMMIT-" . strtoupper(substr(uniqid(), -8));
$precio = 350.00; // Precio oficial según menu.html ($350 MXN)
$estado_pago = "PAGADO"; // Marcado como pagado

$sql = "INSERT INTO boletos 
        (codigo_boleto, nombre, apellido_paterno, apellido_materno, telefono, correo, precio, estado_pago, usado)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("Error al preparar la consulta: " . $conexion->error);
}

$stmt->bind_param(
    "ssssssds",
    $codigo_boleto,
    $nombre,
    $apellido_paterno,
    $apellido_materno,
    $telefono,
    $correo,
    $precio,
    $estado_pago
);

if ($stmt->execute()) {
    $stmt->close();
    $conexion->close();

    // Detección dinámica de la URL del boleto
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    $url_boleto = $scheme . "://" . $host . $dir . "/boleto.php?codigo=" . urlencode($codigo_boleto);

    // Enviar el boleto automáticamente al correo de Gmail del asistente
    $nombre_completo = trim($nombre . " " . $apellido_paterno . " " . $apellido_materno);
    enviarBoletoPorCorreo($correo, $nombre_completo, $codigo_boleto, $precio, $url_boleto);

    // Redirigir a la vista del boleto
    header("Location: boleto.php?codigo=" . urlencode($codigo_boleto) . "&enviado=1");
    exit;
} else {
    echo "Error al guardar el boleto: " . htmlspecialchars($stmt->error);
    $stmt->close();
    $conexion->close();
}

?>