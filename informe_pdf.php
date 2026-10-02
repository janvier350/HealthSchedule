<?php
/**
 * informe_pdf.php — Vista limpia del informe de atención para exportar a PDF.
 * Abre el informe solo (sin el resto de la app) y lanza el diálogo de impresión,
 * donde el usuario elige "Guardar como PDF". Requiere sesión.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/lang/i18n.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

if (!isset($_SESSION["rol"])) { header("Location: break.php"); exit(); }
if (isset($_SESSION['expire']) && time() > $_SESSION['expire']) { session_destroy(); header("Location: expirada.php"); exit(); }

$en = (current_lang() === 'en');
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { exit($en ? 'Invalid report.' : 'Informe inválido.'); }

$st = $conexion->prepare(
    "SELECT H.CONTENIDO_INFORME, P.NOMBRES, P.APELLIDOS, C.FECHA_CITA
       FROM AG_HISTORIAL H
       INNER JOIN AG_CITA C     ON H.IDCITA     = C.IDCITA
       INNER JOIN AG_PACIENTE P ON C.IDPACIENTE = P.IDPACIENTE
      WHERE H.IDHISTORIAL = ? LIMIT 1"
);
$st->bind_param('i', $id); $st->execute();
$row = $st->get_result()->fetch_assoc(); $st->close();
if (!$row) { exit($en ? 'Report not found.' : 'Informe no encontrado.'); }

$paciente = trim($row['NOMBRES'].' '.$row['APELLIDOS']);
$fecha    = $row['FECHA_CITA'] ? date('m/d/Y', strtotime($row['FECHA_CITA'])) : '';
$tituloDoc = ($en?'Report':'Informe').' - '.$paciente.($fecha?' - '.$fecha:'');
$contenido = (string)$row['CONTENIDO_INFORME'];
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($tituloDoc); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background:#f5f5f5; color:#000; }
        .hoja { max-width: 820px; margin: 16px auto; background:#fff; padding: 28px 34px; box-shadow:0 2px 10px rgba(0,0,0,.12); }
        .barra { max-width: 820px; margin: 0 auto 8px; display:flex; gap:8px; justify-content:flex-end; }
        .hoja img { max-width: 100%; height: auto; }
        @media print {
            body { background:#fff; }
            .no-print { display:none !important; }
            .hoja { box-shadow:none; margin:0; max-width:none; padding:0; }
        }
    </style>
</head>
<body>
    <div class="barra no-print">
        <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="bi bi-filetype-pdf"></i> <?php echo $en?'Download / Print PDF':'Descargar / Imprimir PDF'; ?></button>
        <button class="btn btn-outline-secondary btn-sm" onclick="window.close()"><i class="bi bi-x-lg"></i> <?php echo $en?'Close':'Cerrar'; ?></button>
    </div>
    <div class="hoja">
        <?php echo $contenido; ?>
    </div>
    <script>
        // Lanza el diálogo de impresión automáticamente (elige "Guardar como PDF").
        window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 350); });
    </script>
</body>
</html>
