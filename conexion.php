<?php

// Cargar variables de entorno desde .env de forma robusta
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || $line[0] === ';') {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val, " \t\n\r\0\x0B\"'");
            if (getenv($key) === false) {
                putenv("$key=$val");
                $_ENV[$key] = $val;
            }
        }
    }
}

// Configuración de conexión compatible con Railway y entorno local
$servidor   = getenv("MYSQLHOST") ?: "centerbeam.proxy.rlwy.net";
$usuario    = getenv("MYSQLUSER") ?: "root";
$password   = getenv("MYSQLPASSWORD") ?: "";
$base_datos = getenv("MYSQLDATABASE") ?: "railway";
$puerto     = getenv("MYSQLPORT") ? (int)getenv("MYSQLPORT") : 32063;

$conexion = new mysqli($servidor, $usuario, $password, $base_datos, $puerto);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");

?>