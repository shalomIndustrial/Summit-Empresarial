<?php

// Cargar variables de entorno desde .env si existe (entorno local)
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    if ($env !== false) {
        foreach ($env as $key => $value) {
            if (getenv($key) === false) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
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