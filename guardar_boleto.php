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

// Función para formatear y validar nombres (primera mayúscula, resto minúsculas, solo letras)
function limpiarYFormatearTexto($texto, $max) {
    // Quitar cualquier carácter que no sea letra o espacio
    $limpio = preg_replace('/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/u', '', $texto);
    if (mb_strlen($limpio) > $max) {
        $limpio = mb_substr($limpio, 0, $max);
    }
    $palabras = preg_split('/\s+/u', trim($limpio));
    $formateadas = array_map(function($p) {
        if (empty($p)) return '';
        $primera = mb_strtoupper(mb_substr($p, 0, 1));
        $resto = mb_strtolower(mb_substr($p, 1));
        return $primera . $resto;
    }, $palabras);
    return implode(' ', $formateadas);
}

$nombre           = limpiarYFormatearTexto($nombre, 27);
$apellido_paterno = limpiarYFormatearTexto($apellido_paterno, 30);
$apellido_materno = limpiarYFormatearTexto($apellido_materno, 30);

if (empty($nombre) || empty($apellido_paterno) || empty($apellido_materno) || empty($telefono)) {
    die("Todos los datos del asistente son obligatorios (Nombre, Apellidos, Teléfono). <a href='menu.html'>Regresar</a>");
}

// Asegurar formato telefónico con lada y espacios 3-3-4
if (preg_match('/^(\+\d{1,4})\s*(\d{3})\s*(\d{3})\s*(\d{4})$/', $telefono, $m)) {
    $telefono = "{$m[1]} {$m[2]} {$m[3]} {$m[4]}";
} elseif (preg_match('/^\+?\d+$/', preg_replace('/\s+/', '', $telefono))) {
    $digitos = preg_replace('/\D/', '', $telefono);
    if (strlen($digitos) == 10) {
        $telefono = "+52 " . substr($digitos, 0, 3) . " " . substr($digitos, 3, 3) . " " . substr($digitos, 6);
    }
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strpos($correo, '@') === false) {
    die("El correo electrónico es obligatorio y debe tener un formato válido (@). <a href='menu.html'>Regresar</a>");
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