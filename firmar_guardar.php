<?php
ob_start();
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

$token      = preg_replace('/[^a-fA-F0-9]/', '', $_POST['token'] ?? '');
$codigo     = trim($_POST['codigo'] ?? '');
$firmadoPor = trim($_POST['firmado_por'] ?? '');
$firma      = $_POST['firma'] ?? '';
$acepto     = isset($_POST['acepto']);

function pagina($titulo, $tipo, $mensaje) {
    $color = $tipo === 'ok' ? '#28a745' : '#dc3545';
    $icono = $tipo === 'ok' ? 'check-circle' : 'x-circle';
    echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'>
          <meta name='viewport' content='width=device-width, initial-scale=1'>
          <title>$titulo</title>
          <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
          <link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css' rel='stylesheet'>
          </head><body style='background:#f4f6f9;'>
          <div style='max-width:520px;margin:60px auto;background:#fff;border-radius:10px;padding:40px;text-align:center;box-shadow:0 4px 18px rgba(0,0,0,.08);'>
            <i class='bi bi-$icono' style='font-size:3rem;color:$color;'></i>
            <h4 class='mt-3'>" . htmlspecialchars($titulo) . "</h4>
            <p class='text-muted'>" . htmlspecialchars($mensaje) . "</p>
          </div></body></html>";
    exit;
}

if (!$token || !$codigo) { pagina('Datos incompletos', 'err', 'Faltan datos para registrar la firma.'); }
if (!$acepto)           { pagina('Falta aceptar', 'err', 'Debes aceptar los términos del documento.'); }
if ($firmadoPor === '') { pagina('Falta el nombre', 'err', 'Indica el nombre de quien firma.'); }
if (strpos($firma, 'data:image/png;base64,') !== 0 || strlen($firma) < 100) {
    pagina('Falta la firma', 'err', 'No se recibió una firma válida.');
}

// Buscar el envío
$stmt = $conexion->prepare("SELECT id_envio, codigo, estado FROM documento_envio WHERE token = ? LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$envio = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$envio)                          { pagina('Enlace no válido', 'err', 'El enlace no existe o expiró.'); }
if ((string)$envio['codigo'] !== $codigo) { pagina('Código incorrecto', 'err', 'El código de acceso no coincide.'); }
if ($envio['estado'] === 'Firmado')   { pagina('Ya firmado', 'ok', 'Este documento ya había sido firmado. ¡Gracias!'); }

$ip = $_SERVER['REMOTE_ADDR'] ?? '';

$upd = $conexion->prepare(
    "UPDATE documento_envio
        SET estado = 'Firmado', firmado_por = ?, firma_img = ?, ip_firma = ?, fecha_firma = NOW()
      WHERE token = ? AND estado <> 'Firmado'"
);
$upd->bind_param("ssss", $firmadoPor, $firma, $ip, $token);
$upd->execute();
$ok = $upd->affected_rows > 0;
$upd->close();

if ($ok) {
    pagina('¡Documento firmado!', 'ok', 'Gracias, ' . $firmadoPor . '. Tu firma quedó registrada correctamente.');
} else {
    pagina('No se pudo registrar', 'err', 'Es posible que el documento ya estuviera firmado. Intenta abrir el enlace nuevamente.');
}
