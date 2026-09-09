<?php
/**
 * migrar_documentos_lote5.php
 * Inserta 1 documento legal derivado del PDF 22:
 *   (22a) Full Consent Package (Insurance version — accepts insurance)
 * (PDF 22b es duplicado del 20b y se omite.)
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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Migración docs lote 5</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Migrar documentos legales — Lote 5</h4>
<p class="text-muted">Inserta / actualiza <b>(22a) Full Consent Package (Insurance version)</b>.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar migración</button>
<a class="btn btn-link" href="gestionar_documentos.php">Volver</a></form></div></body></html>
    <?php
    exit;
}

$documentos = [];

// (22a) Full Consent Package (Insurance version)
$documentos[] = [
    'titulo' => '(22a) Full Consent Package (Insurance version)',
    'contenido' => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#2b2b2b;line-height:1.4;">
<h4 style="color:#5a2d82;margin:0 0 10px 0;">Full Consent Package (Insurance version) — SRoss Nutrition PLLC</h4>

<p><b>ID:</b> {{cedula}} &nbsp; <b>Name:</b> {{paciente}} &nbsp; <b>DOB:</b> {{fecha_nacimiento}}</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Agreement to Use Electronic Signatures and Electronic Documents</h5>
<p>You agree that the electronic signatures included in this notice are intended to authenticate this writing and to have the same force and effect as manual signatures.</p>
<p><i>Electronic signature</i> means any electronic sound, symbol or process attached to or logically associated with a record and executed and adopted by a party with the intent to sign such record, including (without limitation) typing a name or clicking a check box.</p>
<p>You agree to use electronic documents, notices and contacts "electronic documents", for all future transactions and communications. Electronic documents contain the same information as paper documents, notices and contracts. Paper documents, notices and contracts are available at your request. If you give your consent to use electronic documents, you can later change your mind and request a paper agreement instead.</p>
<p><b>I agree:</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Appointments</h5>
<p>I agree to keep all scheduled appointments and be on time. If I cannot attend a scheduled session, I will call to cancel and/or reschedule. There will be no fee if phone message or conversation is received before 24 hours of the scheduled appointment time. I understand if I miss or cancel with less than 24 hours of notice, but before the appointment window, clients will be charged a fee of <b>$30.00</b>.<br>
No-shows will be charged the <b>full out-of-pocket fee</b> for their appointment.<br>
If you have any questions about this notice, please contact: <b>Silvia Ross, MS, RDN, CDN</b>, Founder and CEO of SRoss Nutrition PLLC.</p>
<p><b>I agree:</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Financial Policy</h5>
<p>This is a statement of our financial policy. You understand that you are obligated to ensure that our fees are paid in full. We will verify your coverage and bill your insurance carrier on your behalf. However, you are ultimately responsible for payment of your bill.</p>
<p>You agree that you will pay any deductible and co-payment or co-insurance as determined by your insurance plan. Those payments will be due at the time of service. Many insurance companies have additional requirements or stipulations that may affect your coverage. You are responsible for any amounts not covered or payable by your insurance. If your insurance denies any part of your claim, you agree to be responsible to pay the full balance.</p>
<p><b>I agree:</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">HIPAA Privacy Policies</h5>
<p>The HIPAA Privacy Practices Notice is supplied separately. Please review it before signing below.</p>
<p><b>I agree:</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Consent to Treatment</h5>
<p>I have read through all the above information and have been clearly advised of my rights and responsibilities as a client of <b>SRoss Nutrition PLLC</b>, including the HIPAA Notice of Privacy Practices.</p>
<p>I understand these rights and responsibilities and agree to abide by them. I consent to treatment, and I understand I have a right to receive a copy of this form upon request. I also understand that I can withdraw this consent in writing and terminate at any time.</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Credit Card Authorization Form</h5>
<p>For <b>SROSS NUTRITION PLLC</b>. Please complete all fields. You may cancel this authorization at any time by contacting us. This authorization will remain in effect until cancelled.</p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><td><b>Credit Card Information</b></td></tr>
<tr><td><b>Card Type:</b> ☐ MasterCard &nbsp; ☐ VISA &nbsp; ☐ Discover &nbsp; ☐ AMEX &nbsp; ☐ Other</td></tr>
<tr><td><b>Cardholder Name (as shown on card):</b> ______________________</td></tr>
<tr><td><b>Card Number:</b> ______________________</td></tr>
<tr><td><b>Expiration Date (mm/yy):</b> ______________ &nbsp; <b>CVV:</b> ________</td></tr>
<tr><td><b>Cardholder ZIP Code (from credit card billing address):</b> ______________________</td></tr>
</table>
<p>I, above for agreed upon purchases. I understand that my information will be saved to file for future transactions on my account. <b>Customer Signature:</b> ______ &nbsp; <b>Date:</b> {{fecha}}</p>
<p><b>I agree:</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Signature</h5>
<p><i>Please sign below if you agree to all policies described above.</i></p>
<p><b>Name:</b> {{paciente}}<br>
<b>Date of birth:</b> {{fecha_nacimiento}}</p>
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
   . '<body class="p-4"><div class="container" style="max-width:720px;"><h4>Resultado — Documentos lote 5</h4><ul class="list-group">';
foreach ($msgs as $m) echo '<li class="list-group-item">'.htmlspecialchars($m).'</li>';
echo '</ul><a class="btn btn-primary mt-3" href="gestionar_documentos.php">Ir a Documents</a></div></body></html>';
