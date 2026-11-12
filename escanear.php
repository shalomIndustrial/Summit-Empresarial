<?php
session_start();
require_once "conexion.php";

$staff_pin_config = getenv("STAFF_PIN") ?: "2026";
$es_staff = !empty($_SESSION["admin_logged"]) || !empty($_SESSION["staff_authorized"]);

// Procesar login con PIN si aún no tiene sesión
$pin_error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["login_pin"])) {
    $pin_ingresado = trim($_POST["pin"] ?? "");
    if ($pin_ingresado === $staff_pin_config) {
        $_SESSION["staff_authorized"] = true;
        $es_staff = true;
    } else {
        $pin_error = "PIN incorrecto. Ingresa el PIN oficial de Staff.";
    }
}

// Endpoint AJAX para validar o canjear boleto en vivo desde la cámara
if (isset($_GET["action"]) && $_GET["action"] === "procesar_qr") {
    header('Content-Type: application/json; charset=UTF-8');

    if (!$es_staff) {
        echo json_encode(["status" => "error", "message" => "Sesión de Staff requerida."]);
        exit;
    }

    $raw = trim($_GET["codigo"] ?? "");
    if (empty($raw)) {
        echo json_encode(["status" => "error", "message" => "Código QR vacío."]);
        exit;
    }

    // Extraer código SUMMIT-XXXXXXXX si es una URL completa
    $codigo = $raw;
    if (preg_match('/codigo=(SUMMIT-[A-Z0-9]+)/i', $raw, $matches)) {
        $codigo = strtoupper($matches[1]);
    } elseif (preg_match('/(SUMMIT-[A-Z0-9]+)/i', $raw, $matches)) {
        $codigo = strtoupper($matches[1]);
    }

    $stmt = $conexion->prepare("SELECT * FROM boletos WHERE codigo_boleto = ?");
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        echo json_encode([
            "status" => "no_encontrado",
            "codigo" => $codigo,
            "message" => "El boleto no existe en la base de datos."
        ]);
        exit;
    }

    $boleto = $res->fetch_assoc();
    $stmt->close();

    $nombre_completo = trim($boleto["nombre"] . " " . $boleto["apellido_paterno"] . " " . $boleto["apellido_materno"]);

    if ($boleto["usado"] == 1) {
        echo json_encode([
            "status" => "ya_usado",
            "codigo" => $boleto["codigo_boleto"],
            "asistente" => $nombre_completo,
            "correo" => $boleto["correo"],
            "message" => "¡ATENCIÓN! Este boleto YA FUE UTILIZADO previamente."
        ]);
        exit;
    }

    // Si viene la orden de canjearlo (marcar como usado)
    if (isset($_GET["canjear"]) && $_GET["canjear"] === "1") {
        $update = $conexion->prepare("UPDATE boletos SET usado = 1, estado_pago = 'PAGADO' WHERE codigo_boleto = ? AND usado = 0");
        $update->bind_param("s", $codigo);
        $update->execute();
        $ok = ($update->affected_rows > 0);
        $update->close();

        if ($ok) {
            echo json_encode([
                "status" => "canjeado",
                "codigo" => $boleto["codigo_boleto"],
                "asistente" => $nombre_completo,
                "correo" => $boleto["correo"],
                "message" => "Acceso registrado y boleto marcado como utilizado con éxito."
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "No se pudo actualizar el estado del boleto."
            ]);
        }
        exit;
    }

    // Si solo lo escanea para revisión previa
    echo json_encode([
        "status" => "valido",
        "codigo" => $boleto["codigo_boleto"],
        "asistente" => $nombre_completo,
        "correo" => $boleto["correo"],
        "telefono" => $boleto["telefono"],
        "precio" => $boleto["precio"],
        "message" => "Boleto válido y listo para ingresar."
    ]);
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escáner QR - Puerta de Entrada · Summit</title>
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
    <!-- Librería html5-qrcode para escanear con la cámara del celular -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        #reader video {
            border-radius: 1rem;
            object-fit: cover;
        }
    </style>
</head>
<body class="bg-slate-900 text-gray-100 min-h-screen flex flex-col font-sans">

    <!-- Barra superior -->
    <header class="bg-brandNavy border-b border-brandGold/30 px-4 py-3 flex justify-between items-center">
        <div class="flex items-center gap-2">
            <img src="summit.PNG" alt="Logo" class="h-8 w-auto brightness-0 invert">
            <span class="text-sm font-bold tracking-wide">Control de Acceso</span>
        </div>
        <div class="flex items-center gap-2">
            <?php if (!empty($_SESSION["admin_logged"])): ?>
                <a href="admin.php" class="text-xs bg-brandGold text-brandNavy font-bold px-3 py-1.5 rounded-lg hover:bg-brandGoldHover transition">
                    ← Volver al Panel
                </a>
            <?php else: ?>
                <a href="index.html" class="text-xs text-gray-400 hover:text-white transition">
                    ← Inicio
                </a>
            <?php endif; ?>
        </div>
    </header>

    <main class="flex-1 max-w-lg w-full mx-auto p-4 flex flex-col justify-center">

        <?php if (!$es_staff): ?>
            <!-- Pantalla para pedir PIN de Staff -->
            <div class="bg-brandNavy/80 border border-brandGold/40 rounded-3xl p-8 shadow-2xl text-center">
                <div class="w-16 h-16 bg-brandGold/20 text-brandGold rounded-full flex items-center justify-center mx-auto text-3xl mb-4">
                    🔒
                </div>
                <h2 class="text-xl font-bold text-white mb-1">Acceso de Staff</h2>
                <p class="text-xs text-gray-300 mb-6">Ingresa el PIN de recepción para activar la cámara del escáner</p>

                <?php if (!empty($pin_error)): ?>
                    <div class="mb-4 p-3 bg-red-500/20 border border-red-500/40 text-red-200 text-xs rounded-xl">
                        <?php echo htmlspecialchars($pin_error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-4">
                    <input type="password" name="pin" required autofocus maxlength="8" placeholder="••••"
                           class="w-full text-center text-2xl font-mono tracking-widest px-4 py-3 bg-slate-800 border border-gray-600 rounded-2xl text-white focus:ring-2 focus:ring-brandGold outline-none">
                    <button type="submit" name="login_pin"
                            class="w-full bg-brandGold hover:bg-brandGoldHover text-brandNavy font-bold py-3.5 rounded-2xl transition shadow-lg text-sm">
                        Activar Escáner
                    </button>
                </form>
            </div>
        <?php else: ?>
            <!-- Interfaz del Escáner Activo -->
            <div class="space-y-4">

                <!-- Visor de la Cámara -->
                <div class="bg-slate-800 rounded-3xl p-3 border border-gray-700 shadow-2xl overflow-hidden relative">
                    <div id="reader" class="w-full overflow-hidden rounded-2xl bg-black"></div>

                    <!-- Guía visual del marco -->
                    <div id="scanner-placeholder" class="text-center py-10 px-4">
                        <div class="text-4xl mb-2 animate-bounce">📷</div>
                        <p class="text-sm font-semibold text-gray-200">Presiona para encender la cámara</p>
                        <p class="text-xs text-gray-400 mt-1">Usa la cámara trasera de tu celular para mayor rapidez</p>
                        <button id="btn-iniciar-camara" onclick="iniciarEscaner()"
                                class="mt-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-6 py-2.5 rounded-xl text-xs shadow-lg transition">
                            Encender Cámara
                        </button>
                    </div>

                    <div id="scanner-controls" class="hidden mt-3 flex justify-between items-center px-2">
                        <span class="text-[11px] text-emerald-400 font-semibold flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            Cámara Activa
                        </span>
                        <button onclick="detenerEscaner()" class="text-xs bg-red-500/20 hover:bg-red-500 text-red-300 hover:text-white px-3 py-1 rounded-lg transition border border-red-500/30">
                            Pausar Cámara
                        </button>
                    </div>
                </div>

                <!-- Búsqueda manual por código si la cámara no puede enfocar -->
                <div class="bg-slate-800/80 border border-gray-700 p-3 rounded-2xl flex gap-2">
                    <input type="text" id="input-manual" placeholder="O escribe el código (ej. SUMMIT-XXXX)"
                           class="flex-1 bg-slate-900 border border-gray-700 rounded-xl px-3 py-2 text-xs uppercase font-mono text-white focus:ring-2 focus:ring-brandGold outline-none">
                    <button onclick="procesarManual()" class="bg-brandGold text-brandNavy font-bold px-4 py-2 rounded-xl text-xs hover:bg-brandGoldHover transition">
                        Buscar
                    </button>
                </div>

                <!-- Tarjeta de Resultado del Escaneo -->
                <div id="resultado-card" class="hidden bg-white text-gray-900 rounded-3xl p-6 shadow-2xl border-2 transition-all">
                    <div id="resultado-icono" class="w-14 h-14 rounded-full flex items-center justify-center mx-auto text-2xl mb-3"></div>
                    <h3 id="resultado-titulo" class="text-xl font-bold text-center"></h3>
                    <p id="resultado-mensaje" class="text-xs text-center text-gray-600 mt-1"></p>

                    <div id="resultado-detalles" class="hidden mt-4 p-4 bg-gray-50 rounded-2xl text-xs space-y-2 border border-gray-100">
                        <p><strong>Asistente:</strong> <span id="det-asistente" class="font-bold"></span></p>
                        <p><strong>Código:</strong> <span id="det-codigo" class="font-mono font-bold text-brandNavy"></span></p>
                        <p><strong>Correo:</strong> <span id="det-correo"></span></p>
                    </div>

                    <div id="resultado-acciones" class="mt-5 space-y-2">
                        <button id="btn-canjear" onclick="confirmarCanje()" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl text-sm shadow-md transition">
                            ✓ Dar Acceso y Marcar como Usado
                        </button>
                        <button onclick="reiniciarEscaner()" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 rounded-xl text-xs transition">
                            Escanear Siguiente Boleto
                        </button>
                    </div>
                </div>

            </div>
        <?php endif; ?>

    </main>

    <footer class="text-center py-4 text-xs text-gray-500 border-t border-gray-800">
        Summit Empresarial México · Escáner de Puerta en Tiempo Real
    </footer>

    <?php if ($es_staff): ?>
    <script>
        let html5QrCode = null;
        let codigoActual = "";
        let procesando = false;

        function iniciarEscaner() {
            document.getElementById('scanner-placeholder').classList.add('hidden');
            document.getElementById('scanner-controls').classList.remove('hidden');

            html5QrCode = new Html5Qrcode("reader");
            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0
            };

            html5QrCode.start(
                { facingMode: "environment" },
                config,
                onScanSuccess
            ).catch(err => {
                console.error("Error al iniciar cámara:", err);
                alert("No se pudo acceder a la cámara. Asegúrate de dar permisos en tu navegador o ingresa el código manualmente.");
                detenerEscaner();
            });
        }

        function detenerEscaner() {
            if (html5QrCode) {
                html5QrCode.stop().then(() => {
                    html5QrCode.clear();
                    document.getElementById('scanner-placeholder').classList.remove('hidden');
                    document.getElementById('scanner-controls').classList.add('hidden');
                }).catch(err => console.error(err));
            }
        }

        function onScanSuccess(decodedText) {
            if (procesando) return;
            procesando = true;

            // Feedback háptico (vibración en celular si está disponible)
            if (navigator.vibrate) navigator.vibrate(100);

            consultarBoleto(decodedText, false);
        }

        function procesarManual() {
            const input = document.getElementById('input-manual');
            const valor = input.value.trim();
            if (!valor) return;
            consultarBoleto(valor, false);
        }

        function consultarBoleto(codigo, canjear = false) {
            const url = `escanear.php?action=procesar_qr&codigo=${encodeURIComponent(codigo)}` + (canjear ? '&canjear=1' : '');

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    mostrarResultado(data);
                })
                .catch(err => {
                    alert("Error de conexión al consultar el boleto: " + err);
                    procesando = false;
                });
        }

        function mostrarResultado(data) {
            const card = document.getElementById('resultado-card');
            const icono = document.getElementById('resultado-icono');
            const titulo = document.getElementById('resultado-titulo');
            const mensaje = document.getElementById('resultado-mensaje');
            const detalles = document.getElementById('resultado-detalles');
            const btnCanjear = document.getElementById('btn-canjear');

            card.classList.remove('hidden');
            codigoActual = data.codigo || "";

            if (data.status === 'valido') {
                card.className = "bg-white text-gray-900 rounded-3xl p-6 shadow-2xl border-4 border-emerald-500";
                icono.className = "w-14 h-14 rounded-full flex items-center justify-center mx-auto text-2xl mb-3 bg-emerald-100 text-emerald-700";
                icono.innerHTML = "✓";
                titulo.className = "text-xl font-bold text-center text-emerald-700";
                titulo.innerText = "¡Boleto Válido!";
                mensaje.innerText = data.message;

                detalles.classList.remove('hidden');
                document.getElementById('det-asistente').innerText = data.asistente;
                document.getElementById('det-codigo').innerText = data.codigo;
                document.getElementById('det-correo').innerText = data.correo;

                btnCanjear.classList.remove('hidden');
            } else if (data.status === 'canjeado') {
                card.className = "bg-white text-gray-900 rounded-3xl p-6 shadow-2xl border-4 border-emerald-600";
                icono.className = "w-14 h-14 rounded-full flex items-center justify-center mx-auto text-2xl mb-3 bg-emerald-600 text-white";
                icono.innerHTML = "✓";
                titulo.className = "text-xl font-bold text-center text-emerald-700";
                titulo.innerText = "Acceso Concedido";
                mensaje.innerText = data.message;

                detalles.classList.remove('hidden');
                document.getElementById('det-asistente').innerText = data.asistente;
                document.getElementById('det-codigo').innerText = data.codigo;
                document.getElementById('det-correo').innerText = data.correo;

                btnCanjear.classList.add('hidden');
            } else if (data.status === 'ya_usado') {
                card.className = "bg-white text-gray-900 rounded-3xl p-6 shadow-2xl border-4 border-amber-500";
                icono.className = "w-14 h-14 rounded-full flex items-center justify-center mx-auto text-2xl mb-3 bg-amber-100 text-amber-700";
                icono.innerHTML = "⚠️";
                titulo.className = "text-xl font-bold text-center text-amber-600";
                titulo.innerText = "Boleto Ya Utilizado";
                mensaje.innerText = data.message;

                detalles.classList.remove('hidden');
                document.getElementById('det-asistente').innerText = data.asistente;
                document.getElementById('det-codigo').innerText = data.codigo;
                document.getElementById('det-correo').innerText = data.correo;

                btnCanjear.classList.add('hidden');
            } else {
                card.className = "bg-white text-gray-900 rounded-3xl p-6 shadow-2xl border-4 border-red-500";
                icono.className = "w-14 h-14 rounded-full flex items-center justify-center mx-auto text-2xl mb-3 bg-red-100 text-red-700";
                icono.innerHTML = "✕";
                titulo.className = "text-xl font-bold text-center text-red-600";
                titulo.innerText = "Boleto No Encontrado";
                mensaje.innerText = data.message;

                detalles.classList.add('hidden');
                btnCanjear.classList.add('hidden');
            }

            // Scroll suave hacia el resultado en pantallas pequeñas
            card.scrollIntoView({ behavior: 'smooth' });
        }

        function confirmarCanje() {
            if (!codigoActual) return;
            consultarBoleto(codigoActual, true);
        }

        function reiniciarEscaner() {
            document.getElementById('resultado-card').classList.add('hidden');
            document.getElementById('input-manual').value = "";
            codigoActual = "";
            procesando = false;
        }
    </script>
    <?php endif; ?>

</body>
</html>
