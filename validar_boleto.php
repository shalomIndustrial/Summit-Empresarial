<?php

include "conexion.php";

$codigo = trim($_GET["codigo"] ?? "");

if (empty($codigo)) {
    die("No se proporcionó un código de boleto.");
}

$sql = "SELECT * FROM boletos WHERE codigo_boleto = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $codigo);
$stmt->execute();
$resultado = $stmt->get_result();

$encontrado = ($resultado->num_rows > 0);
$boleto = $encontrado ? $resultado->fetch_assoc() : null;

$stmt->close();
$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validación de Boleto - Summit Empresarial</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brandNavy: '#0A1931',
                        brandGold: '#C5A059',
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-100 min-h-screen py-10 px-4 flex justify-center items-center font-sans">

    <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden border border-gray-100">

        <!-- Encabezado -->
        <div class="bg-brandNavy text-white p-6 text-center border-b-4 border-brandGold">
            <h1 class="text-xl font-bold tracking-wide">SUMMIT EMPRESARIAL MÉXICO</h1>
            <p class="text-brandGold text-xs uppercase font-semibold mt-1">Control de Acceso y Validación</p>
        </div>

        <div class="p-8">

            <?php if (!$encontrado): ?>
                <!-- No encontrado -->
                <div class="text-center py-6">
                    <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto text-3xl mb-4">
                        ✕
                    </div>
                    <h2 class="text-2xl font-bold text-red-600">Boleto No Encontrado</h2>
                    <p class="text-gray-500 mt-2 text-sm">
                        El código <strong class="font-mono"><?php echo htmlspecialchars($codigo); ?></strong> no existe en la base de datos oficial.
                    </p>
                </div>

            <?php elseif ($boleto["usado"] == 1): ?>
                <!-- Ya Utilizado -->
                <div class="text-center py-6">
                    <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto text-3xl mb-4">
                        ⚠️
                    </div>
                    <h2 class="text-2xl font-bold text-amber-600">Boleto Ya Utilizado</h2>
                    <p class="text-gray-600 mt-2 text-sm">
                        Este boleto ya fue registrado y validado para el ingreso con anterioridad.
                    </p>

                    <div class="bg-gray-50 p-4 rounded-2xl border text-left mt-6 text-sm space-y-2">
                        <p><strong>Asistente:</strong> <?php echo htmlspecialchars($boleto["nombre"] . " " . $boleto["apellido_paterno"]); ?></p>
                        <p><strong>Código:</strong> <span class="font-mono font-bold"><?php echo htmlspecialchars($boleto["codigo_boleto"]); ?></span></p>
                        <p><strong>Estado:</strong> <span class="text-red-600 font-bold">ACCESO YA CONSUMIDO</span></p>
                    </div>
                </div>

            <?php else: ?>
                <!-- Válido -->
                <div class="text-center py-4">
                    <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto text-3xl mb-4">
                        ✓
                    </div>
                    <h2 class="text-2xl font-bold text-emerald-600">Boleto Válido</h2>
                    <p class="text-gray-500 text-sm mt-1">Listo para ingresar al evento</p>

                    <div class="bg-gray-50 p-5 rounded-2xl border text-left mt-6 text-sm space-y-3">
                        <div>
                            <span class="text-xs uppercase text-gray-400 font-bold block">Nombre Completo</span>
                            <p class="font-bold text-base text-gray-900">
                                <?php echo htmlspecialchars($boleto["nombre"] . " " . $boleto["apellido_paterno"] . " " . $boleto["apellido_materno"]); ?>
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 pt-2 border-t border-gray-200">
                            <div>
                                <span class="text-xs uppercase text-gray-400 font-bold block">Código</span>
                                <p class="font-mono font-bold text-brandNavy"><?php echo htmlspecialchars($boleto["codigo_boleto"]); ?></p>
                            </div>
                            <div>
                                <span class="text-xs uppercase text-gray-400 font-bold block">Estado de Pago</span>
                                <p class="font-bold text-emerald-600"><?php echo htmlspecialchars($boleto["estado_pago"]); ?></p>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-gray-200">
                            <span class="text-xs uppercase text-gray-400 font-bold block">Contacto</span>
                            <p class="text-gray-700"><?php echo htmlspecialchars($boleto["correo"]); ?> · <?php echo htmlspecialchars($boleto["telefono"]); ?></p>
                        </div>
                    </div>

                    <!-- Botón para Marcar como Usado -->
                    <div class="mt-6">
                        <a href="marcar_usado.php?codigo=<?php echo urlencode($boleto["codigo_boleto"]); ?>"
                           class="w-full inline-block bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3.5 px-6 rounded-xl shadow-lg transition">
                            Dar Acceso y Marcar como Usado
                        </a>
                    </div>
                </div>

            <?php endif; ?>

            <div class="mt-6 pt-4 border-t border-gray-100 text-center">
                <a href="index.html" class="text-xs text-gray-400 hover:text-brandNavy transition">
                    ← Volver a Summit Empresarial México
                </a>
            </div>

        </div>

    </div>

</body>
</html>