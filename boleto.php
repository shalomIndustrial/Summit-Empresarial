<?php

include "conexion.php";

if (!isset($_GET["codigo"]) || empty(trim($_GET["codigo"]))) {
    header("Location: index.html");
    exit;
}

$codigo = trim($_GET["codigo"]);

$sql = "SELECT * FROM boletos WHERE codigo_boleto = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $codigo);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows == 0) {
    die("Boleto no encontrado. Por favor verifica tu código.");
}

$boleto = $resultado->fetch_assoc();

$stmt->close();
$conexion->close();

// Detección dinámica de la URL para el código QR
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$dir    = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$url_validacion = $scheme . "://" . $host . $dir . "/validar_boleto.php?codigo=" . urlencode($boleto["codigo_boleto"]);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boleto Digital - Summit Empresarial México</title>

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

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Librería QRCode.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <!-- Librería html2pdf.js (Descarga directa de PDF de alta resolución) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .serif-title {
            font-family: 'Playfair Display', serif;
        }
        @media print {
            body {
                background: white !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .ticket-card {
                box-shadow: none !important;
                border: 2px solid #C5A059 !important;
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen py-10 px-4 flex flex-col justify-center items-center">

    <!-- Contenedor del Boleto -->
    <div id="boleto-card" class="ticket-card bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden border border-gray-100">

        <!-- Cabecera del Boleto -->
        <div class="bg-brandNavy text-white p-6 text-center relative border-b-4 border-brandGold">
            <div class="flex justify-center items-center gap-3 mb-2">
                <img src="summit.PNG" alt="Logo" class="h-10 w-auto object-contain brightness-0 invert">
            </div>
            <h1 class="serif-title text-2xl font-bold tracking-wide">SUMMIT EMPRESARIAL</h1>
            <p class="text-brandGold text-xs uppercase tracking-widest font-semibold mt-1">México · Acceso Oficial</p>
        </div>

        <!-- Cuerpo del Boleto -->
        <div class="p-6 space-y-5">

            <?php if (isset($_GET["enviado"]) && $_GET["enviado"] == "1"): ?>
                <div class="no-print bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold px-4 py-3 rounded-2xl flex items-center gap-2">
                    <span class="text-base">✉️</span>
                    <span>Copia digital enviada a: <strong><?php echo htmlspecialchars($boleto["correo"]); ?></strong></span>
                </div>
            <?php endif; ?>

            <!-- Estado y Tipo de Acceso -->
            <div class="flex justify-between items-center bg-gray-50 p-3 rounded-xl border border-gray-100">
                <div>
                    <span class="text-xs uppercase text-gray-400 font-bold block">Pase</span>
                    <span class="font-bold text-brandNavy text-sm">Acceso Completo</span>
                </div>
                <div>
                    <?php if ($boleto["usado"] == 1): ?>
                        <span class="bg-gray-200 text-gray-700 text-xs font-bold px-3 py-1 rounded-full">UTILIZADO</span>
                    <?php else: ?>
                        <span class="bg-emerald-100 text-emerald-700 text-xs font-bold px-3 py-1 rounded-full">VÁLIDO</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Datos del Asistente -->
            <div class="space-y-3 text-sm">
                <div>
                    <span class="text-xs uppercase text-gray-400 font-bold block">Asistente</span>
                    <p class="text-base font-bold text-gray-900">
                        <?php echo htmlspecialchars($boleto["nombre"] . " " . $boleto["apellido_paterno"] . " " . $boleto["apellido_materno"]); ?>
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-xs uppercase text-gray-400 font-bold block">Correo</span>
                        <p class="text-gray-700 truncate" title="<?php echo htmlspecialchars($boleto["correo"]); ?>">
                            <?php echo htmlspecialchars($boleto["correo"]); ?>
                        </p>
                    </div>
                    <div>
                        <span class="text-xs uppercase text-gray-400 font-bold block">Teléfono</span>
                        <p class="text-gray-700">
                            <?php echo htmlspecialchars($boleto["telefono"]); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Código QR y Código Único -->
            <div class="pt-4 border-t border-dashed border-gray-200 text-center">
                <div id="qrcode" class="flex justify-center p-3 bg-white inline-block border-2 border-brandGold/40 rounded-2xl mx-auto shadow-sm"></div>

                <p class="mt-3 font-mono font-bold text-brandNavy text-base tracking-widest">
                    <?php echo htmlspecialchars($boleto["codigo_boleto"]); ?>
                </p>
                <p class="text-xs text-gray-400 mt-1">
                    Presenta este código QR en la entrada del evento.
                </p>
            </div>

        </div>

        <!-- Pie del Boleto -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex justify-between items-center text-xs text-gray-500">
            <span>Total Pagado: <strong class="text-brandNavy">$<?php echo number_format($boleto["precio"], 2); ?> MXN</strong></span>
            <span>Fecha: <strong><?php echo date("d/m/Y", strtotime($boleto["fecha_compra"])); ?></strong></span>
        </div>

    </div>

    <!-- Botones de Acción (no se imprimen) -->
    <div class="no-print mt-6 flex flex-wrap gap-3 justify-center items-center">
        <!-- Descargar PDF Directo -->
        <button id="btn-descargar-pdf" onclick="descargarPDFDirecto()" class="bg-brandGold hover:bg-brandGoldHover text-brandNavy font-bold px-5 py-2.5 rounded-xl shadow-md transition flex items-center gap-2 text-sm cursor-pointer">
            <span>⬇️ Descargar Boleto PDF</span>
        </button>

        <!-- Imprimir -->
        <button onclick="window.print()" class="bg-white hover:bg-gray-50 text-gray-700 font-semibold px-4 py-2.5 rounded-xl shadow-sm border border-gray-200 transition flex items-center gap-2 text-sm cursor-pointer">
            <span>🖨️ Imprimir</span>
        </button>

        <a href="index.html" class="bg-white hover:bg-gray-50 text-brandNavy font-bold px-4 py-2.5 rounded-xl shadow-sm border border-gray-200 transition text-sm">
            ← Regresar al inicio
        </a>
    </div>

    <script>
        new QRCode(document.getElementById("qrcode"), {
            text: "<?php echo htmlspecialchars($url_validacion, ENT_QUOTES); ?>",
            width: 180,
            height: 180,
            colorDark: "#0A1931",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });

        // Función para descargar el boleto directamente en archivo PDF oficial
        function descargarPDFDirecto() {
            const btn = document.getElementById('btn-descargar-pdf');
            const card = document.getElementById('boleto-card');
            const codigo = "<?php echo htmlspecialchars($boleto['codigo_boleto'], ENT_QUOTES); ?>";

            const textoOriginal = btn.innerHTML;
            btn.innerHTML = '<span>⏳ Generando PDF...</span>';
            btn.disabled = true;

            const opciones = {
                margin:       [10, 10, 10, 10],
                filename:     'Boleto_Summit_' + codigo + '.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true, letterRendering: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opciones).from(card).save().then(() => {
                btn.innerHTML = '<span>✓ ¡Descargado!</span>';
                setTimeout(() => {
                    btn.innerHTML = textoOriginal;
                    btn.disabled = false;
                }, 2500);
            }).catch(err => {
                console.error("Error al generar PDF:", err);
                btn.innerHTML = textoOriginal;
                btn.disabled = false;
                // Si falla el renderizado en canvas del navegador, recurre al diálogo de impresión
                window.print();
            });
        }
    </script>
</body>
</html>