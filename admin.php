<?php
session_start();
require_once "conexion.php";

$admin_user = getenv("ADMIN_USER") ?: "admin";
$admin_pass = getenv("ADMIN_PASS") ?: "summit2026!";

// Procesar cierre de sesión
if (isset($_GET["action"]) && $_GET["action"] === "logout") {
    unset($_SESSION["admin_logged"]);
    session_destroy();
    header("Location: admin.php");
    exit;
}

// Procesar intento de login
$login_error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["login_admin"])) {
    $user_input = trim($_POST["usuario"] ?? "");
    $pass_input = trim($_POST["password"] ?? "");

    if ($user_input === $admin_user && $pass_input === $admin_pass) {
        $_SESSION["admin_logged"] = true;
        header("Location: admin.php");
        exit;
    } else {
        $login_error = "Usuario o contraseña incorrectos.";
    }
}

// Si no está autenticado, mostrar formulario de inicio de sesión seguro
if (empty($_SESSION["admin_logged"])) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acceso Administrativo - Summit Empresarial</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            brandNavy: '#0A1931',
                            brandNavyDark: '#050C1A',
                            brandGold: '#C5A059',
                        }
                    }
                }
            }
        </script>
    </head>
    <body class="bg-gradient-to-br from-brandNavyDark via-brandNavy to-slate-900 min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-8 border border-brandGold/30">
            <div class="text-center mb-8">
                <img src="summit.PNG" alt="Logo" class="h-14 mx-auto mb-3">
                <h1 class="text-xl font-bold text-brandNavy">Panel Administrativo</h1>
                <p class="text-xs text-gray-500 mt-1">Ingresa tus credenciales oficiales para continuar</p>
            </div>

            <?php if (!empty($login_error)): ?>
                <div class="mb-5 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-semibold flex items-center gap-2">
                    <span>⚠️</span>
                    <span><?php echo htmlspecialchars($login_error); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="admin.php" class="space-y-4">
                <input type="hidden" name="login_admin" value="1">

                <div>
                    <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Usuario</label>
                    <input type="text" name="usuario" required autofocus
                        placeholder="admin"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-brandGold outline-none text-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Contraseña</label>
                    <input type="password" name="password" required
                        placeholder="••••••••"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-brandGold outline-none text-sm">
                </div>

                <button type="submit"
                    class="w-full bg-brandNavy hover:bg-brandNavyDark text-white font-bold py-3 rounded-xl shadow-lg transition mt-2 text-sm flex justify-center items-center gap-2">
                    <span>Iniciar Sesión</span>
                    <span>→</span>
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="index.html" class="text-xs text-gray-400 hover:text-brandNavy transition">
                    ← Volver al sitio web principal
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ==========================================
// A PARTIR DE AQUÍ SOLO ACCEDEN USUARIOS AUTENTICADOS
// ==========================================

// Exportación a Excel / CSV
if (isset($_GET["action"]) && $_GET["action"] === "exportar") {
    $filename = "asistentes_summit_empresarial_" . date("Ymd_His") . ".csv";
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '";');

    // Salida directa a PHP output con BOM para que Excel abra acentos y ñ correctamente
    $f = fopen('php://output', 'w');
    fprintf($f, chr(0xEF).chr(0xBB).chr(0xBF));

    // Encabezados de columnas
    fputcsv($f, [
        'ID',
        'Código de Boleto',
        'Nombre(s)',
        'Apellido Paterno',
        'Apellido Materno',
        'Nombre Completo',
        'Teléfono',
        'Correo Electrónico',
        'Precio (MXN)',
        'Estado de Pago',
        '¿Acceso Usado?',
        'Fecha de Compra'
    ]);

    $export_query = $conexion->query("SELECT * FROM boletos ORDER BY id ASC");
    while ($row = $export_query->fetch_assoc()) {
        fputcsv($f, [
            $row['id'],
            $row['codigo_boleto'],
            $row['nombre'],
            $row['apellido_paterno'],
            $row['apellido_materno'],
            trim($row['nombre'] . ' ' . $row['apellido_paterno'] . ' ' . $row['apellido_materno']),
            $row['telefono'],
            $row['correo'],
            number_format($row['precio'], 2, '.', ''),
            $row['estado_pago'],
            $row['usado'] == 1 ? 'SÍ (UTILIZADO)' : 'NO (DISPONIBLE)',
            $row['fecha_compra']
        ]);
    }
    fclose($f);
    exit;
}

// Reenviar boleto por correo electrónico desde el Panel de Administración
$mensaje_reenvio = "";
if (isset($_GET["action"]) && $_GET["action"] === "reenviar" && !empty($_GET["codigo"])) {
    require_once "enviar_correo.php";
    $codigo_reenvio = trim($_GET["codigo"]);

    $stmt_r = $conexion->prepare("SELECT * FROM boletos WHERE codigo_boleto = ?");
    $stmt_r->bind_param("s", $codigo_reenvio);
    $stmt_r->execute();
    $res_r = $stmt_r->get_result();

    if ($b_data = $res_r->fetch_assoc()) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $dir    = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        $url_boleto_reenvio = $scheme . "://" . $host . $dir . "/boleto.php?codigo=" . urlencode($b_data["codigo_boleto"]);
        $nombre_comp = trim($b_data["nombre"] . " " . $b_data["apellido_paterno"] . " " . $b_data["apellido_materno"]);

        $envio = enviarBoletoPorCorreo($b_data["correo"], $nombre_comp, $b_data["codigo_boleto"], $b_data["precio"], $url_boleto_reenvio);
        if ($envio["success"]) {
            $mensaje_reenvio = "Boleto reenviado con éxito a " . htmlspecialchars($b_data["correo"]);
        } else {
            $mensaje_reenvio = "Error al reenviar: " . htmlspecialchars($envio["message"]);
        }
    } else {
        $mensaje_reenvio = "Boleto no encontrado para reenvío.";
    }
    $stmt_r->close();
}

// Búsqueda
$busqueda = trim($_GET["q"] ?? "");

// Estadísticas
$total_query = $conexion->query("SELECT COUNT(*) as total, SUM(precio) as recaudado FROM boletos");
$stats = $total_query->fetch_assoc();
$total_boletos = $stats["total"] ?? 0;
$total_recaudado = $stats["recaudado"] ?? 0.00;

$usados_query = $conexion->query("SELECT COUNT(*) as usados FROM boletos WHERE usado = 1");
$usados = ($usados_query->fetch_assoc())["usados"] ?? 0;
$disponibles = $total_boletos - $usados;

// Consulta de Boletos
if (!empty($busqueda)) {
    $stmt = $conexion->prepare("SELECT * FROM boletos WHERE codigo_boleto LIKE ? OR nombre LIKE ? OR apellido_paterno LIKE ? OR correo LIKE ? ORDER BY id DESC");
    $term = "%" . $busqueda . "%";
    $stmt->bind_param("ssss", $term, $term, $term, $term);
    $stmt->execute();
    $boletos = $stmt->get_result();
} else {
    $boletos = $conexion->query("SELECT * FROM boletos ORDER BY id DESC");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Summit Empresarial</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brandNavy: '#0A1931',
                        brandNavyDark: '#050C1A',
                        brandGold: '#C5A059',
                        brandGoldHover: '#B28E46',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen text-gray-800 font-sans">

    <!-- Barra de Navegación -->
    <nav class="bg-brandNavy text-white px-6 py-4 border-b-4 border-brandGold shadow-lg">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-3">
                <img src="summit.PNG" alt="Logo" class="h-10 w-auto brightness-0 invert">
                <div>
                    <h1 class="font-bold text-lg tracking-wide">SUMMIT EMPRESARIAL MÉXICO</h1>
                    <p class="text-xs text-brandGold font-medium">Panel de Control de Boletos (Railway MySQL)</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="escanear.php" class="inline-flex items-center gap-1.5 bg-brandGold hover:bg-brandGoldHover text-brandNavy font-bold px-3.5 py-1.5 rounded-xl text-xs shadow-md transition">
                    <span>📷 Escáner QR Puerta</span>
                </a>
                <span class="hidden md:inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Railway Online
                </span>
                <a href="index.html" class="text-xs text-gray-300 hover:text-white underline">
                    Ver Sitio Web
                </a>
                <a href="admin.php?action=logout" class="bg-red-500/20 hover:bg-red-500 text-red-200 hover:text-white px-3 py-1 rounded-lg text-xs font-bold transition border border-red-500/30">
                    Cerrar Sesión ✕
                </a>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-6 py-8">

        <?php if (!empty($mensaje_reenvio)): ?>
            <div class="mb-6 p-4 rounded-2xl bg-brandNavy text-white border-2 border-brandGold flex items-center justify-between shadow-lg">
                <div class="flex items-center gap-3">
                    <span class="text-xl">✉️</span>
                    <span class="text-sm font-semibold"><?php echo $mensaje_reenvio; ?></span>
                </div>
                <a href="admin.php" class="text-xs bg-brandGold text-brandNavy font-bold px-3 py-1 rounded-lg hover:bg-brandGoldHover transition">
                    Cerrar aviso ✕
                </a>
            </div>
        <?php endif; ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <span class="text-xs uppercase font-bold text-gray-400 block mb-1">Total Registrados</span>
                <div class="text-3xl font-extrabold text-brandNavy"><?php echo number_format($total_boletos); ?></div>
                <p class="text-xs text-gray-500 mt-2">Asistentes en base de datos</p>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <span class="text-xs uppercase font-bold text-gray-400 block mb-1">Total Recaudado</span>
                <div class="text-3xl font-extrabold text-brandGold">$<?php echo number_format($total_recaudado, 2); ?> <span class="text-xs font-medium text-gray-500">MXN</span></div>
                <p class="text-xs text-gray-500 mt-2">Ventas acumuladas</p>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <span class="text-xs uppercase font-bold text-gray-400 block mb-1">Por Ingresar</span>
                <div class="text-3xl font-extrabold text-blue-600"><?php echo number_format($disponibles); ?></div>
                <p class="text-xs text-gray-500 mt-2">Boletos sin utilizar</p>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <span class="text-xs uppercase font-bold text-gray-400 block mb-1">Ingresados / Usados</span>
                <div class="text-3xl font-extrabold text-emerald-600"><?php echo number_format($usados); ?></div>
                <p class="text-xs text-gray-500 mt-2">Accesos validados en puerta</p>
            </div>
        </div>

        <!-- Buscador y Tabla -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4">
                <h2 class="text-lg font-bold text-brandNavy">Listado de Asistentes y Boletos</h2>

                <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                    <a href="admin.php?action=exportar" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2 rounded-xl text-sm shadow-sm transition">
                        <span>📊 Descargar Excel</span>
                    </a>

                    <form method="GET" class="flex gap-2 w-full sm:w-auto">
                        <input type="text" name="q" value="<?php echo htmlspecialchars($busqueda); ?>"
                               placeholder="Buscar por código, nombre o correo..."
                               class="w-full sm:w-72 px-4 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-brandGold outline-none">
                        <button type="submit" class="bg-brandNavy hover:bg-brandNavyDark text-white px-4 py-2 rounded-xl text-sm font-bold transition">
                            Buscar
                        </button>
                        <?php if (!empty($busqueda)): ?>
                            <a href="admin.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-xl text-sm transition flex items-center">
                                ✕
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs border-b">
                        <tr>
                            <th class="py-3.5 px-6">Código</th>
                            <th class="py-3.5 px-6">Asistente</th>
                            <th class="py-3.5 px-6">Contacto</th>
                            <th class="py-3.5 px-6">Precio</th>
                            <th class="py-3.5 px-6">Estado</th>
                            <th class="py-3.5 px-6">Fecha</th>
                            <th class="py-3.5 px-6 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if ($boletos->num_rows == 0): ?>
                            <tr>
                                <td colspan="7" class="py-12 text-center text-gray-400">
                                    <div class="text-3xl mb-2">🎟️</div>
                                    <p class="font-bold">No hay boletos registrados aún</p>
                                    <p class="text-xs text-gray-400 mt-1">Los boletos registrados desde el formulario aparecerán aquí en vivo.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php while ($row = $boletos->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="py-4 px-6 font-mono font-bold text-brandNavy whitespace-nowrap">
                                        <?php echo htmlspecialchars($row["codigo_boleto"]); ?>
                                    </td>
                                    <td class="py-4 px-6 whitespace-nowrap font-medium text-gray-900">
                                        <?php echo htmlspecialchars($row["nombre"] . " " . $row["apellido_paterno"] . " " . $row["apellido_materno"]); ?>
                                    </td>
                                    <td class="py-4 px-6 whitespace-nowrap text-xs text-gray-600">
                                        <div><?php echo htmlspecialchars($row["correo"]); ?></div>
                                        <div class="text-gray-400"><?php echo htmlspecialchars($row["telefono"]); ?></div>
                                    </td>
                                    <td class="py-4 px-6 whitespace-nowrap font-bold text-gray-900">
                                        $<?php echo number_format($row["precio"], 2); ?>
                                    </td>
                                    <td class="py-4 px-6 whitespace-nowrap">
                                        <?php if ($row["usado"] == 1): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-700">
                                                UTILIZADO
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                                                VÁLIDO
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6 whitespace-nowrap text-xs text-gray-500">
                                        <?php echo date("d/m/Y H:i", strtotime($row["fecha_compra"])); ?>
                                    </td>
                                    <td class="py-4 px-6 whitespace-nowrap text-center space-x-2">
                                        <a href="boleto.php?codigo=<?php echo urlencode($row["codigo_boleto"]); ?>" target="_blank"
                                           class="inline-block bg-brandGold/10 hover:bg-brandGold text-brandGold hover:text-brandNavy px-3 py-1.5 rounded-lg text-xs font-bold transition">
                                            Ver Boleto
                                        </a>
                                        <a href="admin.php?action=reenviar&codigo=<?php echo urlencode($row["codigo_boleto"]); ?>"
                                           onclick="return confirm('¿Deseas reenviar el boleto a <?php echo htmlspecialchars($row['correo']); ?>?');"
                                           class="inline-block bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition">
                                            ✉️ Reenviar
                                        </a>
                                        <a href="validar_boleto.php?codigo=<?php echo urlencode($row["codigo_boleto"]); ?>" target="_blank"
                                           class="inline-block bg-gray-100 hover:bg-brandNavy text-gray-700 hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition">
                                            Validar
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

</body>
</html>
