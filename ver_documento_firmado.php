<?php
ob_start();
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

if (!isset($_SESSION["rol"])) { header("Location: break.php"); exit(); }

$id = (int)($_GET['id'] ?? 0);
$e = null;
if ($id) {
    $stmt = $conexion->prepare(
        "SELECT e.*, d.titulo, d.contenido, d.archivo_pdf,
                CONCAT(p.NOMBRES,' ',p.APELLIDOS) AS paciente, p.CEDULA, p.FECHANACIMIENTO
           FROM documento_envio e
           INNER JOIN documentos  d ON d.id_documento = e.id_documento
           INNER JOIN AG_PACIENTE p ON p.IDPACIENTE   = e.IDPACIENTE
          WHERE e.id_envio = ? LIMIT 1"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $e = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
$firmado = $e && $e['estado'] === 'Firmado';

// Reemplaza los campos {{...}} del documento con los datos reales
function aplicarCampos($html, $d) {
    $fnac = (!empty($d['FECHANACIMIENTO']) && $d['FECHANACIMIENTO'] !== '0000-00-00')
        ? date('d/m/Y', strtotime($d['FECHANACIMIENTO'])) : '';
    $fecha  = !empty($d['fecha_envio']) ? date('d/m/Y', strtotime($d['fecha_envio'])) : date('d/m/Y');
    $ffirma = !empty($d['fecha_firma']) ? date('d/m/Y', strtotime($d['fecha_firma'])) : '';
    return strtr($html, [
        '{{paciente}}'         => htmlspecialchars($d['paciente'] ?? ''),
        '{{nombre}}'           => htmlspecialchars($d['paciente'] ?? ''),
        '{{cedula}}'           => htmlspecialchars($d['CEDULA'] ?? ''),
        '{{fecha_nacimiento}}' => htmlspecialchars($fnac),
        '{{fecha}}'            => htmlspecialchars($fecha),
        '{{fecha_firma}}'      => htmlspecialchars($ffirma),
    ]);
}

// Sustituye los campos de texto/casilla que el paciente llenó al firmar
function aplicarCamposLlenados($html, $camposJsonStr) {
    $campos = json_decode($camposJsonStr ?: '', true);
    if (!is_array($campos)) return $html;
    $byIdx = [];
    foreach ($campos as $c) { if (isset($c['idx'])) $byIdx[(int)$c['idx']] = $c; }

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>');
    libxml_clear_errors();

    $xpath  = new DOMXPath($dom);
    $inputs = $xpath->query("//input[contains(concat(' ', normalize-space(@class), ' '), ' campo-llenar ')]");

    $i = 0;
    foreach ($inputs as $inp) {
        $data = $byIdx[$i] ?? null;
        $type = strtolower($inp->getAttribute('type'));
        $span = $dom->createElement('span');
        if ($type === 'checkbox') {
            $checked = $data && !empty($data['value']);
            $span->appendChild($dom->createTextNode($checked ? "\u{2611}" : "\u{2610}"));
        } else {
            $val = $data['value'] ?? '';
            $span->setAttribute('style', 'display:inline-block;border-bottom:1px solid #333;min-width:60px;padding:0 4px;font-weight:600;');
            $span->appendChild($dom->createTextNode($val !== '' ? $val : ' '));
        }
        $inp->parentNode->replaceChild($span, $inp);
        $i++;
    }

    $wrap = $dom->getElementsByTagName('div')->item(0);
    $out  = '';
    foreach ($wrap->childNodes as $child) { $out .= $dom->saveHTML($child); }
    return $out;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Documento firmado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background:#f4f6f9; }
        .doc-wrap { max-width:780px; margin:24px auto; }
        .doc-content { border:1px solid #eee; border-radius:6px; padding:16px; background:#fff; }
        .firma-box { border:1px solid #ddd; border-radius:6px; padding:10px; background:#fff; display:inline-block; }
        @media print { .no-print { display:none !important; } body { background:#fff; } }
    </style>
</head>
<body>
<div class="doc-wrap">
    <?php if (!$e): ?>
        <div class="alert alert-danger">Documento no encontrado.</div>
        <a href="documentos_enviados.php" class="btn btn-secondary">Volver</a>
    <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mb-3 no-print">
            <a href="documentos_enviados.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
            <button onclick="window.print()" class="btn btn-outline-primary btn-sm"><i class="bi bi-printer"></i> Imprimir</button>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h4><?php echo htmlspecialchars($e['titulo']); ?></h4>
                <p class="text-muted mb-1">
                    <strong>Paciente:</strong> <?php echo htmlspecialchars($e['paciente']); ?>
                    <?php if (!empty($e['CEDULA'])): ?> · CI: <?php echo htmlspecialchars($e['CEDULA']); ?><?php endif; ?>
                </p>
                <p class="mb-3">
                    <strong>Estado:</strong>
                    <?php if ($firmado): ?>
                        <span class="badge bg-success">Firmado</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">Pendiente</span>
                    <?php endif; ?>
                    · Enviado: <?php echo $e['fecha_envio'] ? date('d/m/Y H:i', strtotime($e['fecha_envio'])) : '—'; ?>
                </p>

                <h6 class="text-muted">Contenido del documento</h6>
                <div class="doc-content mb-4"><?php
                    echo $firmado
                        ? aplicarCamposLlenados(aplicarCampos($e['contenido'], $e), $e['campos_json'] ?? '')
                        : aplicarCampos($e['contenido'], $e);
                ?></div>
                <?php if (!empty($e['archivo_pdf'])): ?>
                <p class="mb-4">
                    <a href="<?php echo htmlspecialchars($e['archivo_pdf']); ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-file-earmark-pdf"></i> PDF adjunto
                    </a>
                </p>
                <?php endif; ?>

                <h6 class="text-muted">Firma</h6>
                <?php if ($firmado && !empty($e['firma_img'])): ?>
                    <div class="firma-box mb-2">
                        <img src="<?php echo htmlspecialchars($e['firma_img']); ?>" alt="Firma" style="max-width:100%;height:auto;max-height:200px;">
                    </div>
                    <div class="small text-muted">
                        Firmado por <strong><?php echo htmlspecialchars($e['firmado_por'] ?: $e['paciente']); ?></strong>
                        el <?php echo $e['fecha_firma'] ? date('d/m/Y H:i', strtotime($e['fecha_firma'])) : '—'; ?>
                        <?php if (!empty($e['ip_firma'])): ?> · IP: <?php echo htmlspecialchars($e['ip_firma']); ?><?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        Aún no firmado. Código de acceso: <strong><?php echo htmlspecialchars($e['codigo']); ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<script>
document.querySelectorAll('.doc-content input.campo-llenar').forEach(function (i) { i.disabled = true; });
</script>
</body>
</html>
