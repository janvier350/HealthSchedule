<?php
/**
 * migrar_documentos_lote6.php
 * Inserta 1 documento legal derivado del PDF 27:
 *   (27) Signature on File Authorization (insurance claims)
 *
 * PDF 28 = combinación ya migrada (Consent Insurance en lote 5 +
 * HIPAA Notice English en lote 4). PDF 29 es duplicado byte-idéntico
 * de 28. Ambos se omiten.
 * Idempotente por título. SISTEMA-only, POST-driven.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

if (!isset($_SESSION["rol"]) || strtoupper($_SESSION["rol"]) !== 'SISTEMA') {
    http_response_code(403); exit('Acceso restringido: sólo SISTEMA.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Migración docs lote 6</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Migrar documentos legales — Lote 6 (final)</h4>
<p class="text-muted">Inserta / actualiza <b>(27) Signature on File Authorization</b>.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar migración</button>
<a class="btn btn-link" href="gestionar_documentos.php">Volver</a></form></div></body></html>
    <?php
    exit;
}

$documentos = [];

// (27) Signature on File Authorization
$documentos[] = [
    'titulo' => '(27) Signature on File Authorization',
    'contenido' => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#2b2b2b;line-height:1.4;">
<h4 style="color:#1f4e79;margin:0 0 10px 0;">Signature on File Authorization — SRoss Nutrition PLLC</h4>

<p><b>ID:</b> {{cedula}} &nbsp; <b>Name:</b> {{paciente}} &nbsp; <b>DOB:</b> {{fecha_nacimiento}}</p>

<p>By signing this statement, you are authorizing <b>SRoss Nutrition PLLC</b> to complete any necessary insurance claim forms on your behalf. You are also authorizing the release of any medical or other information which may be needed in order to process your claims.</p>

<p>Your signature will be kept on file and shall be referred to when insurance claim forms are submitted for healthcare services you have received.</p>

<p><i>Note: if you are incapable of signing, or are under the age of 18, a parent or legal guardian must sign in your place.</i></p>

<p><b>I agree:</b> ______</p>

<h5 style="color:#1f4e79;margin:14px 0 6px 0;">Client Information</h5>
<p><b>Name of client:</b> {{paciente}}<br>
<b>Name of legal guardian or parent (if applicable):</b> ______________________</p>

<h5 style="color:#1f4e79;margin:14px 0 6px 0;">Insured's Signature</h5>
<p><b>Type name:</b> ______________________<br>
<b>Date of birth:</b> {{fecha_nacimiento}}<br>
<b>Insurance card ID number:</b> ______________________<br>
<b>Signature:</b> ______ &nbsp; <b>Date:</b> {{fecha}}</p>

<h5 style="color:#1f4e79;margin:14px 0 6px 0;">Provider's Signature</h5>
<p><b>Type name:</b> ______________________<br>
<b>Signature:</b> ______ &nbsp; <b>Date:</b> {{fecha}}</p>
</div>
HTML
];

$msgs = [];
foreach ($documentos as $d) {
    $chk = $conexion->prepare("SELECT id_documento FROM documentos WHERE titulo = ? LIMIT 1");
    $chk->bind_param('s', $d['titulo']);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    $chk->close();
    if ($row) {
        $up = $conexion->prepare("UPDATE documentos SET contenido = ? WHERE id_documento = ?");
        $up->bind_param('si', $d['contenido'], $row['id_documento']);
        $msgs[] = $up->execute()
            ? 'ACTUALIZADO (id '.(int)$row['id_documento'].'): '.$d['titulo']
            : 'ERROR: '.$up->error;
        $up->close();
    } else {
        $ins = $conexion->prepare("INSERT INTO documentos (titulo, contenido, archivo_pdf, estado) VALUES (?, ?, NULL, 1)");
        $ins->bind_param('ss', $d['titulo'], $d['contenido']);
        $msgs[] = $ins->execute()
            ? 'CREADO (id '.(int)$conexion->insert_id.'): '.$d['titulo']
            : 'ERROR: '.$ins->error;
        $ins->close();
    }
}

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Migración</title>'
   . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>'
   . '<body class="p-4"><div class="container" style="max-width:720px;"><h4>Resultado — Documentos lote 6</h4><ul class="list-group">';
foreach ($msgs as $m) echo '<li class="list-group-item">'.htmlspecialchars($m).'</li>';
echo '</ul><a class="btn btn-primary mt-3" href="gestionar_documentos.php">Ir a Documents</a></div></body></html>';
