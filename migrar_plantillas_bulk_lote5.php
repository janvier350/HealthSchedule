<?php
/**
 * migrar_plantillas_bulk_lote5.php
 * Inserta el LOTE 5 de plantillas Kalix:
 *   (21) Food Diary - 3 days                          — Handouts / Food Log
 *   (23) Pre-LEAP Assessment & Education Checklist    — Adult Assessment
 *   (25) Physician Referral Form                       — Referral Forms
 *
 * PDF #22 partido en dos (22a como documento) y PDF #24 omitido
 * (duplicado exacto de #18).
 *
 * Idempotente. SISTEMA-only, POST-driven.
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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Migración lote 5</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Migrar plantillas — Lote 5</h4>
<p class="text-muted">Inserta / actualiza <b>(21) Food Diary - 3 days</b>,
<b>(23) Pre-LEAP Assessment &amp; Education Checklist</b>,
<b>(25) Physician Referral Form</b>.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar migración</button>
<a class="btn btn-link" href="plantillas_admin.php">Volver</a></form></div></body></html>
    <?php
    exit;
}

$plantillas = [];

// (21) Food Diary - 3 days
$plantillas[] = [
    'nombre'    => '(21) Food Diary — 3 days',
    'categoria' => 'Handouts / Food Log',
    'cuerpo'    => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#2b2b2b;">
<p><b>ID:</b> {{paciente_cedula}}<br>
<b>Name:</b> {{paciente_nombre}}<br>
<b>DOB:</b> {{paciente_dob}}</p>

<h5 style="color:#5a2d82;margin:12px 0 6px 0;">Instructions</h5>
<ol>
<li>Please eat as you would normally.</li>
<li>Record everything you eat and drink, including all snacks.</li>
<li>Record the time you consume all meals, snacks and drinks.</li>
<li>Select the meal type (breakfast, lunch, evening meal or snack) the food or drink is consumed at.</li>
<li>Select the location the food or drink is prepared (home, restaurant or other). If restaurant, enter the name. If other, describe (e.g., my mother's place, work Christmas party).</li>
<li>Use a new table each day.</li>
</ol>

<h5 style="color:#5a2d82;margin:12px 0 6px 0;">How to record each food or drink</h5>
<p>Describe each food and drink in detail, as best you can. Include:</p>
<ol>
<li>How it is cooked (e.g., fried, grilled, baked).</li>
<li>How it is prepared (e.g., battered, raw, minced).</li>
<li>Added fats, sugar, salt, oils, sauces, dressings and ketchup.</li>
<li>The brand name.</li>
<li>Describe each ingredient in a mixed dish (e.g., sandwich = multigrain bread, butter, ham, lettuce, tomato and swiss cheese).</li>
</ol>

<h5 style="color:#5a2d82;margin:12px 0 6px 0;">How to describe amount of food or drink consumed</h5>
<p>Only record the amount actually eaten (e.g., 2 tbsp of peas; ½ slice of bread).</p>
<p><i>For foods amount can be described using:</i></p>
<ol>
<li>Household measures (e.g., one teaspoon of sugar, two thick slices of bread, 4 tablespoons of peas, ½ cup of gravy).</li>
<li>Weights from labels (e.g., 4 oz steak, 420 g tin of baked beans, 125 g tub of yogurt).</li>
<li>Number of items (e.g., 4 fish fingers, 2 chicken nuggets, 1 slice of cheese).</li>
</ol>
<p><i>For drinks, amount can be described using:</i></p>
<ol>
<li>The size of glass or cup (e.g., large glass).</li>
<li>The volume (e.g., 300 ml).</li>
</ol>

<p><b>Date:</b> ______</p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Meal</th><th>Time</th><th>Place Prepared</th><th>Food &amp; Beverages</th><th>Amount Consumed</th></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
</table>

<p><b>Date:</b> ______</p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Meal</th><th>Time</th><th>Place Prepared</th><th>Food &amp; Beverages</th><th>Amount Consumed</th></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
</table>

<p><b>Date:</b> ______</p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Meal</th><th>Time</th><th>Place Prepared</th><th>Food &amp; Beverages</th><th>Amount Consumed</th></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
</table>

<div style="margin-top:30px;border-top:1px solid #ccc;padding-top:10px;">
Signed on: {{fecha_actual}}<br>
Signed by: <b>{{firma_nombre}}</b><br>
{{firma_credenciales}}
</div>
</div>
HTML
];

// (23) Pre-LEAP Assessment & Education Checklist
$plantillas[] = [
    'nombre'    => '(23) Pre-LEAP Assessment & Education Checklist',
    'categoria' => 'Adult Assessment',
    'cuerpo'    => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#2b2b2b;">
<h4 style="color:#5a9d3a;margin:0 0 8px 0;">Pre-LEAP Assessment &amp; Education (20 – 60 min)</h4>
<p><b>Name:</b> {{paciente_nombre}} &nbsp; <b>DOB:</b> {{paciente_dob}} &nbsp; <b>Date:</b> {{fecha_actual}}</p>
<p>CLIENT seen in office setting. <u>Telephone/Telehealth Consult comments Underlined</u>.</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Statement of Purpose for this Encounter</h5>
<p>RD to obtain data from client &amp; assess for appropriateness of LEAP-MRT.</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Forms Needed: Assessment</h5>
<ul>
<li>HIPAA Release Form</li>
<li>Patient Initial Consultation Form / Health History (HH) + any personal, extended Histories / Records</li>
<li>Symptom Survey (SS) (Initial)</li>
<li>Progress Note</li>
</ul>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Supplies / Forms Needed When Client is Ready to Move Forward With MRT Testing</h5>
<ul>
<li>Specimen Mailer <u>Distance Client: Prepaid-Supply Order form for Oxford to ship to client</u></li>
<li>MRT Requisition Form (Blood Work Order Form — Must be within Prof. Scope of Practice to order)</li>
<li>Food Avoidance Form (Submit with blood work)</li>
<li>Your Practice Pricing List (Optional — may use for package pricing and extensive additional services)</li>
<li>Enrollment Agreement (Optional — simple "Contract of Agreement" between Practitioner and Client)</li>
<li>Insurance Verification Worksheet — Optional — Client's physician referral or prescription needed</li>
<li>Specimen Collection and Shipping Instructions</li>
<li>Laboratory List or Local Lab Information for blood draw (to provide client)</li>
<li>Sample LEAP Report and Results (TO SHOW CLIENT)</li>
<li>How Food Sensitivities Cause Symptoms (May be on Foamboard, Handouts or PowerPoint / Slides)</li>
</ul>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Procedure / Checklist: Pre-LEAP Consult</h5>

<p><b>1. Review Health History with Client</b></p>
<ul>
<li>Open up. Maintain control of the interview. Qualify them to be your client. Ask probing questions.</li>
<li>Develop "partnership" with prospective LEAP client.</li>
<li>Referring physician/s info gathered?</li>
<li>General Health / Medical History (Additional medical forms as RD Practice dictates.) Interference with life? Likelihood that food sensitivity is playing a role.</li>
<li>Current eating and exercise; Lifestyle habits; Willing to try new foods. Establish motives for going forward with LEAP.</li>
<li>Establish how actively they are seeking a solution.</li>
<li>Assess potential difficulties: eating habits, ability to cook, willing to learn? Overall goals —</li>
<li>Focus prospects needs.</li>
<li>Assess "readiness to change" — Is motivation adequate?</li>
</ul>

<p><b>2. Determine: Is this an appropriate LEAP candidate?</b><br>
<b>YES — CONTINUE TO STEP 3</b> (Determine and Document Nutrition Diagnosis, Plan for Nutrition Intervention, Monitoring and Evaluation or other Progress Note per your practice)<br>
<b>NO —</b> Determine alternate / additional Plan of Care; Client may reconsider MRT at a later date</p>

<p><b>3. Present LEAP Education</b><br>
(PRELEAP Slides / PDF; Review Ethan DeMitchell's Webinar from May 2011 or his second webinar note: "LEAP Protocol" is not just avoiding MRT reactive foods, but an Oligoantigenic / Anti-inflammatory diet based on client history + MRT results.)</p>

<p><b>4. Obtain Commitment from Client.</b> "Does this make sense to you so far?"</p>

<p><b>5. Explain / Share Your Program and Costs</b></p>

<p><b>6. Obtain Payment</b> (Private pay / Package Pricing vs Insurance: more below regarding insurance.)</p>

<p><b>7. Complete Remaining Paperwork</b></p>
<ul>
<li>Enrollment Agreement (Optional)</li>
<li>Initial Symptom Survey</li>
<li>Food Avoidance Form (Note known IgE allergies, suspected foods — consider having client "sign")</li>
<li>MRT Requisition Form and Payment Information (page 2) (Determine ordering physician)</li>
</ul>

<p><b>8. Arrange for MRT Blood Draw</b></p>
<ul>
<li>Insurance to be used? Approved? (SEE: Insurance Verification Worksheet — Complete, submit to Oxford PRIOR to blood draw.)</li>
<li>Obtain Signature of Client's ordering physician.</li>
<li>Review Specimen Collection and Shipping Instructions (Also in Specimen Mailer Box).</li>
<li>Determine / Locate Laboratory / Location for Blood Draw ("Finding Labs Nationwide" — LEAP mentor assist) FedEx Tracking # noted. (No need to call in as of 2014).</li>
</ul>

<p><b>9. Schedule Next Appointment</b><br>
(approx 10 – 14 business days AFTER blood is drawn) <u>If by telephone, set next phone appt approx 7 – 10 days out to start "avoidance" of red / yellow — via electronic version.</u></p>

<p><b>10. Complete PRE-LEAP Progress Note</b></p>

<p><b>11. Optional: Assign Food / Symptom Diary (Timeline) for client to complete for 1 week.</b><br>
(If a client is keeping an incomplete Food / Symptom Diary, you can review before they start their LEAP diet, and instruct on need for complete details, times of foods / water consumed, time of symptoms, etc.)</p>

<p><b>12. Other:</b> Before meeting with client, review results with your LEAP Mentor, if needed.</p>

<p><b>13. Other:</b></p>

<p><b>Reminder:</b> Insurance Verifications: If a client has an "Out-of-Network" deductible that has not been met yet, then they will have to pay for testing, since Oxford only bills "Out-of-Network".</p>

<p><b>Client familiar with / requesting MRT testing / LEAP Support:</b><br>
When a potential client is very familiar with LEAP / MRT and contacts you for testing / LEAP Counseling — Procedures may be shortened significantly, however the overall process remains the same.</p>

<div style="margin-top:30px;border-top:1px solid #ccc;padding-top:10px;">
Signed on: {{fecha_actual}}<br>
Signed by: <b>{{firma_nombre}}</b><br>
{{firma_credenciales}}
</div>
</div>
HTML
];

// (25) Physician Referral Form
$plantillas[] = [
    'nombre'    => '(25) Physician Referral Form',
    'categoria' => 'Referral Forms',
    'cuerpo'    => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#2b2b2b;">
<h4 style="color:#5a2d82;margin:0 0 8px 0;">Physician Referral for Medical Nutrition Therapy</h4>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Referral Needs</h5>
<p>This referral is for:<br>
☐ New Diagnosis &nbsp; ☐ New Complication &nbsp; ☐ New Treatment Plan<br>
☐ Other: ______________________</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Patient Details</h5>
<p>The patient below is referred for medical nutrition therapy as a necessary part of medical treatment and prevention of complications for diagnoses listed.</p>
<p><b>Date:</b> {{fecha_actual}}<br>
<b>Patient Name:</b> {{paciente_nombre}} &nbsp; <b>DOB:</b> {{paciente_dob}}<br>
<b>Home Address:</b> ______________________<br>
<b>Phone Number:</b> {{paciente_telefono}}</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Special Needs</h5>
<p>This patient has the following special needs:<br>
☐ Language &nbsp; ☐ Hearing / Speech / Vision &nbsp; ☐ Learning / Processing<br>
☐ Other: ______________________</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Referral Reason/s</h5>
<p>Please select for and select all diagnoses that apply to this referral.<br>
<b>Medical diagnosis:</b> ______________________</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Medications</h5>
<p>Could you please upload or list this patient's current medications.</p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Name of Medication / Supplement</th><th>Reason</th><th>Dose &amp; Frequency</th></tr>
<tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td></tr>
</table>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Lab Work</h5>
<p>Could you please upload the patient's latest lab work below: ______________________</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Physician Signature</h5>
<p><b>Physician Name:</b> ______________________<br>
<b>NPI:</b> ______________________<br>
<b>Practice / Clinic:</b> ______________________<br>
<b>Signature:</b> ______________________ &nbsp; <b>Date:</b> ______________</p>

<div style="margin-top:30px;border-top:1px solid #ccc;padding-top:10px;font-size:11px;color:#666;">
Received by: <b>{{firma_nombre}}</b><br>
{{firma_credenciales}}
</div>
</div>
HTML
];

$msgs = [];
foreach ($plantillas as $p) {
    $chk = $conexion->prepare("SELECT id FROM cat_plantillas_nutricion WHERE nombre_plantilla = ? LIMIT 1");
    $chk->bind_param('s', $p['nombre']);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    $chk->close();
    if ($row) {
        $up = $conexion->prepare("UPDATE cat_plantillas_nutricion SET categoria = ?, cuerpo_html = ? WHERE id = ?");
        $up->bind_param('ssi', $p['categoria'], $p['cuerpo'], $row['id']);
        $msgs[] = $up->execute()
            ? 'ACTUALIZADA (id '.(int)$row['id'].'): '.$p['nombre']
            : 'ERROR (id '.(int)$row['id'].'): '.$up->error;
        $up->close();
    } else {
        $ins = $conexion->prepare("INSERT INTO cat_plantillas_nutricion (nombre_plantilla, categoria, cuerpo_html) VALUES (?, ?, ?)");
        $ins->bind_param('sss', $p['nombre'], $p['categoria'], $p['cuerpo']);
        $msgs[] = $ins->execute()
            ? 'CREADA (id '.(int)$conexion->insert_id.'): '.$p['nombre']
            : 'ERROR: '.$ins->error;
        $ins->close();
    }
}

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Migración</title>'
   . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>'
   . '<body class="p-4"><div class="container" style="max-width:720px;"><h4>Resultado — Lote 5</h4><ul class="list-group">';
foreach ($msgs as $m) echo '<li class="list-group-item">'.htmlspecialchars($m).'</li>';
echo '</ul><a class="btn btn-primary mt-3" href="plantillas_admin.php">Ir a Templates</a></div></body></html>';
