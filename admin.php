<?php

require_once "conexion.php";

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
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Railway Online
                </span>
                <a href="index.html" class="text-xs text-gray-300 hover:text-white underline">
                    Ver Sitio Web
                </a>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-6 py-8">

        <!-- Métricas Principales -->
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
