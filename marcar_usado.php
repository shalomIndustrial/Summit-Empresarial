<?php
session_start();
include "conexion.php";

$es_staff = !empty($_SESSION["admin_logged"]) || !empty($_SESSION["staff_authorized"]);

$codigo = trim($_GET["codigo"] ?? "");

if (empty($codigo)) {
    die("Código de boleto no proporcionado.");
}

// Si un usuario no autorizado intenta marcar el boleto, redirigirlo a validar_boleto para pedir PIN
if (!$es_staff) {
    header("Location: validar_boleto.php?codigo=" . urlencode($codigo));
    exit;
}

$sql = "UPDATE boletos 
        SET usado = 1, estado_pago = 'PAGADO' 
        WHERE codigo_boleto = ? 
        AND usado = 0";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $codigo);
$stmt->execute();

$registrado = ($stmt->affected_rows > 0);
$error = $stmt->error;

$stmt->close();
$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Registrado - Summit Empresarial</title>

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

    <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden border border-gray-100 text-center">

        <!-- Encabezado -->
        <div class="bg-brandNavy text-white p-6 border-b-4 border-brandGold">
            <h1 class="text-xl font-bold tracking-wide">SUMMIT EMPRESARIAL MÉXICO</h1>
            <p class="text-brandGold text-xs uppercase font-semibold mt-1">Puerta de Acceso</p>
        </div>

        <div class="p-8">

            <?php if ($registrado): ?>
                <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto text-3xl mb-4">
                    ✓
                </div>
                <h2 class="text-2xl font-bold text-emerald-600">¡Acceso Concedido!</h2>
                <p class="text-gray-600 mt-2 text-sm">
                    El boleto ha sido marcado como utilizado y el asistente puede ingresar.
                </p>

                <div class="bg-gray-50 p-4 rounded-xl border mt-5 text-sm">
                    <span class="text-xs text-gray-400 block uppercase font-bold">Código Validado</span>
                    <span class="font-mono font-bold text-brandNavy text-base"><?php echo htmlspecialchars($codigo); ?></span>
                </div>

            <?php else: ?>
                <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto text-3xl mb-4">
                    ⚠️
                </div>
                <h2 class="text-2xl font-bold text-amber-600">No se pudo registrar</h2>
                <p class="text-gray-600 mt-2 text-sm">
                    Este boleto no pudo ser marcado. Puede que ya haya sido utilizado anteriormente o el código no exista.
                </p>

                <div class="bg-gray-50 p-4 rounded-xl border mt-5 text-sm">
                    <span class="text-xs text-gray-400 block uppercase font-bold">Código Intentado</span>
                    <span class="font-mono font-bold text-gray-700"><?php echo htmlspecialchars($codigo); ?></span>
                </div>
            <?php endif; ?>

            <div class="mt-8 space-y-3">
                <a href="validar_boleto.php?codigo=<?php echo urlencode($codigo); ?>"
                   class="block w-full bg-brandNavy hover:bg-brandNavyDark text-white font-bold py-3 rounded-xl transition text-sm">
                    Ver estado del boleto
                </a>
                <a href="index.html" class="block text-xs text-gray-400 hover:text-brandNavy transition">
                    ← Volver al sitio principal
                </a>
            </div>

        </div>

    </div>

</body>
</html>