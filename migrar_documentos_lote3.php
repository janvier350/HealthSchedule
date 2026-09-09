<?php
/**
 * migrar_documentos_lote3.php
 * Inserta 2 documentos legales del lote 3 en la tabla `documentos`
 * (módulo Documents — enviar y firmar por el paciente).
 *   (12) Consent Package (E-Signature + Waiver + CC Auth + Cancellation)
 *   (13) LEAP/MRT Client Agreement
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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Migración docs lote 3</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Migrar documentos legales — Lote 3</h4>
<p class="text-muted">Inserta / actualiza <b>(12) Consent Package</b> y <b>(13) LEAP/MRT Client Agreement</b>.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar migración</button>
<a class="btn btn-link" href="gestionar_documentos.php">Volver</a></form></div></body></html>
    <?php
    exit;
}

$documentos = [];

// (12) Consent Package
$documentos[] = [
    'titulo' => '(12) Consent Package — Electronic Signature, Waiver, CC Authorization, Cancellation',
    'contenido' => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#2b2b2b;line-height:1.4;">
<h4 style="color:#5a2d82;margin:0 0 10px 0;">Consent Package — SRoss Nutrition PLLC</h4>

<p><b>ID:</b> {{cedula}} &nbsp; <b>Name:</b> {{paciente}} &nbsp; <b>DOB:</b> {{fecha_nacimiento}}</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Agreement to Use Electronic Signatures and Electronic Documents</h5>
<p>You agree that the electronic signatures included in this notice are intended to authenticate this writing and to have the same force and effect as manual signatures.</p>
<p><i>Electronic signature</i> means any electronic sound, symbol or process attached to or logically associated with a record and executed and adopted by a party with the intent to sign such record, including (without limitation) typing a name or clicking a check box.</p>
<p>You agree to use electronic documents, notices and contacts "electronic documents", for all future transactions and communications. Electronic documents contain the same information as paper documents, notices and contracts. Paper documents, notices and contracts are available at your request. If you give your consent to use electronic documents, you can later change your mind and request a paper agreement instead.</p>
<p><b>I agree:</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Waiver Consent</h5>
<p><i>For services or items rendered to you.</i></p>
<p>You are taken on your own risk for any contra-indications/allergic reactions/injury or any health disease related issues since SRoss Nutrition PLLC and employees has been providing you with nutrition services or any other services. This waiver is also entitled to recommendations on supplements or holistic approach and/or other treatments to provide services. You agree for any circumstances that you will never for any reason blame or sue against SRoss Nutrition PLLC services or employees for services rendered. You agree that only YOU are responsible to follow instructions as indicated and follow appropriate recommendations as advised. By signing this agreement, SRoss Nutrition PLLC and employees including Silvia Ross, MS, RDN, CDN to be exempt from any initiation or pursue legal proceedings against another in the civil court of law.</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Credit Card Authorization</h5>
<p>Credit Card Authorization Form For SROSS NUTRITION PLLC. Please complete all fields. You may cancel this authorization at any time by contacting us. This authorization will remain in effect until cancelled.</p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><td><b>Credit Card Information</b></td></tr>
<tr><td><b>Card Type:</b> ☐ MasterCard &nbsp; ☐ VISA &nbsp; ☐ Discover &nbsp; ☐ AMEX &nbsp; ☐ Other</td></tr>
<tr><td><b>Cardholder Name (as shown on card):</b> ______________________</td></tr>
<tr><td><b>Card Number:</b> ______________________</td></tr>
<tr><td><b>Expiration Date (mm/yy):</b> ______________ &nbsp; <b>CVV:</b> ________</td></tr>
<tr><td><b>Cardholder ZIP Code (from credit card billing address):</b> ______________________</td></tr>
</table>
<p>I, above for agreed upon purchases. I understand that my information will be saved to file for future transactions on my account. <b>Customer Signature:</b> ______ &nbsp; <b>Date:</b> {{fecha}}</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Appointments — Cancellation Policy</h5>
<p>I agree to keep all scheduled appointments and be on time. If I cannot attend a scheduled session, I will call to cancel and/or reschedule. There will be no fee if phone message or conversation is received before 24 hours of the scheduled appointment time. I understand if I miss or cancel with less than 24 hours of notice, then I will be charged for the $50, and if I don't show up or call I will be liable to <b>full price</b> of the appointment.</p>
<p><b>I agree:</b> No</p>

<p style="margin-top:20px;"><b>Name:</b> {{paciente}}<br>
<b>Date of birth:</b> {{fecha_nacimiento}}</p>
</div>
HTML
];

// (13) LEAP/MRT Client Agreement
$documentos[] = [
    'titulo' => '(13) LEAP / MRT Client Agreement',
    'contenido' => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#2b2b2b;line-height:1.4;">
<h4 style="color:#5a9d3a;margin:0 0 10px 0;">LEAP — Lifestyle Eating and Performance</h4>

<p>I, <b>{{paciente}}</b>, understand that as a LEAP/MRT client with SRoss Nutrition PLLC, that for the Package Pricing fee, I am entitled to the following consultations:</p>

<ul>
<li>Pre-LEAP Assessment, Overview and Set-up for laboratory/blood draw for MRT testing.</li>
</ul>

<p><b>Once MRT results are Available:</b></p>
<ul>
<li>Consultation 1: Results &amp; LEAP Phase 1</li>
<li>Consultation 2: 10-14 days after starting LEAP Elimination Diet (Start/continue Phase 2-5)</li>
<li>Consultation 3: 21 days after testing; by phone</li>
<li>Consultation 4: 30 days after testing</li>
<li>Consultation 5: 60 days after testing</li>
<li>Consultation 6: 90 days after testing</li>
</ul>

<p>These sessions may vary in length from 10 minutes to 1 hour and some of them may be completed by phone, video conferencing or email if agreed upon by both parties and total time will not exceed a total of 5 hours. The price of this package is $650 and I may choose to use a payment plan.</p>

<p>I understand that the LEAP dietary protocol only works if I follow the LEAP Elimination diet, as directed, and that the LEAP Therapist will be available to assist me with any questions I may have in a timely manner. I understand that with my package I am entitled to one (1) email or phone call per week not to exceed 10 minutes should I have questions between appointments.</p>

<p>I also understand that should I choose not to complete my sessions, I am not entitled to a refund. There is also no guarantee that this program will cure any symptom or disease, and if I don't follow recommended protocol my results may be compromised. If desired results are not obtained, I am not entitled to any refund.</p>

<p>I also understand that if I fail to cancel an appointment at least 12 hours prior to the session, my appointment will be lost. This allows the dietitian to schedule her day to assist as many clients as possible. I can re-schedule my session at the rate of <b>$xxx/hour</b>.</p>

<p>I also understand that most patients see results in two weeks; however, occasionally, patients take longer than 90 days to heal and that if I would like more sessions to continue my treatment, I can receive those at the rate of <b>$XXX/hour</b>.</p>

<p><b>Please Sign Below:</b> ______________________</p>

<p><b>To schedule or cancel appointments please contact:</b></p>

<p>LEAP Therapist, MS, RD, LD, CLT<br>
SRoss Nutrition PLLC<br>
Phone: ______<br>
Email: ______</p>
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
   . '<body class="p-4"><div class="container" style="max-width:720px;"><h4>Resultado — Documentos lote 3</h4><ul class="list-group">';
foreach ($msgs as $m) echo '<li class="list-group-item">'.htmlspecialchars($m).'</li>';
echo '</ul><a class="btn btn-primary mt-3" href="gestionar_documentos.php">Ir a Documents</a></div></body></html>';
