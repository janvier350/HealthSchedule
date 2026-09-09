<?php
/**
 * migrar_plantillas_bulk_lote6.php
 * LOTE 6 (final) de plantillas convertidas desde PDFs 26 y 30:
 *   (26) Pediatric Nutrition Assessment (school-age)   — Pediatric Assessment
 *   (30) Pre-LEAP Assessment & Education (Checklist)   — LEAP / MRT
 *
 * PDF 27 es documento legal (Signature on File Authorization) → va en
 * migrar_documentos_lote6.php.
 * PDF 28 = combinación ya migrada (Consent Insurance en lote 5 + HIPAA
 * en lote 4). PDF 29 es duplicado byte-idéntico de 28. Ambos se omiten.
 *
 * Idempotente: si una plantilla con el mismo nombre ya existe, actualiza
 * su cuerpo y categoría en vez de duplicar. SISTEMA-only, POST-driven.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

if (!isset($_SESSION["rol"]) || strtoupper($_SESSION["rol"]) !== 'SISTEMA') {
    http_response_code(403);
    exit('Acceso restringido: sólo SISTEMA.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Migración lote 6</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Migrar plantillas — Lote 6 (final)</h4>
<p class="text-muted">Inserta / actualiza <b>(26) Pediatric Nutrition Assessment (school-age)</b>
y <b>(30) Pre-LEAP Assessment &amp; Education (Checklist)</b>.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar migración</button>
<a class="btn btn-link" href="plantillas_admin.php">Volver</a></form></div></body></html>
    <?php
    exit;
}

$plantillas = [];

// (26) Pediatric Nutrition Assessment (school-age)
$plantillas[] = [
    'nombre'    => '(26) Pediatric Nutrition Assessment (school-age)',
    'categoria' => 'Pediatric Assessment',
    'cuerpo'    => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#2b2b2b;">
<p><b>Name:</b> {{paciente_nombre}}<br>
<b>DOB:</b> {{paciente_dob}}<br>
<b>Referred by:</b> ______________________<br>
<b>Start time:</b> {{hora_inicio}}</p>

<h5 style="color:#1f4e79;margin:14px 0 6px 0;border-bottom:1px solid #cfd8e3;padding-bottom:3px;">Subjective</h5>
<p><b>Reason for visit:</b> ______________________<br>
<b>History of presenting condition:</b> ______________________<br>
<b>Questions/concerns for today:</b> ______________________</p>

<h5 style="color:#1f4e79;margin:14px 0 6px 0;border-bottom:1px solid #cfd8e3;padding-bottom:3px;">Health &amp; Medical History</h5>
<p><b>Medical history:</b> ______________________<br>
<b>Immunizations up-to-date:</b> ☐ Yes &nbsp; ☐ No &nbsp; <b>Details:</b> ______<br>
<b>Past surgeries / hospitalizations:</b> ______<br>
<b>Food allergies:</b> ______<br>
<b>Food intolerances and sensitivities:</b> ______<br>
<b>Other allergies:</b> ______<br>
<b>Other:</b> ______</p>

<h5 style="color:#1f4e79;margin:14px 0 6px 0;border-bottom:1px solid #cfd8e3;padding-bottom:3px;">Family Medical History</h5>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><td>☐ Cancer</td><td>☐ High blood cholesterol</td><td>☐ Liver disease</td></tr>
<tr><td>☐ Diabetes</td><td>☐ High blood pressure</td><td>☐ Thyroid disease</td></tr>
<tr><td>☐ Heart disease</td><td>☐ Kidney disease</td><td>☐ Obesity</td></tr>
</table>
<p><b>Other family medical history:</b> ______________________</p>

<h5 style="color:#1f4e79;margin:14px 0 6px 0;border-bottom:1px solid #cfd8e3;padding-bottom:3px;">Social History</h5>
<p><b>School:</b> ______<br>
<b>Year/grade level:</b> ______<br>
<b>School contact:</b> ______ &nbsp; <b>Phone:</b> ______<br>
<b>Living arrangements:</b> ______<br>
<b>Siblings:</b> ______<br>
<b>Other:</b> ______</p>

<h5 style="color:#1f4e79;margin:14px 0 6px 0;border-bottom:1px solid #cfd8e3;padding-bottom:3px;">Diet History</h5>
<p><b>Date:</b> ______</p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Meal</th><th>Place Prepared</th><th>Food &amp; Beverages</th></tr>
<tr><td>Breakfast</td><td></td><td></td></tr>
<tr><td>Snack</td><td></td><td></td></tr>
<tr><td>Lunch</td><td></td><td></td></tr>
<tr><td>Snack</td><td></td><td></td></tr>
<tr><td>Dinner</td><td></td><td></td></tr>
<tr><td>Snack / Other</td><td></td><td></td></tr>
</table>

<p><b>Date:</b> ______</p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Meal</th><th>Place Prepared</th><th>Food &amp; Beverages</th></tr>
<tr><td>Breakfast</td><td></td><td></td></tr>
<tr><td>Snack</td><td></td><td></td></tr>
<tr><td>Lunch</td><td></td><td></td></tr>
<tr><td>Snack</td><td></td><td></td></tr>
<tr><td>Dinner</td><td></td><td></td></tr>
<tr><td>Snack / Other</td><td></td><td></td></tr>
</table>

<p><b>Summary of diet:</b> ______________________</p>

<p><b>Eating behavior:</b></p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><td>☐ Fast eater</td><td>☐ High milk intake</td><td>☐ Limited variety of foods (&lt;5 a day)</td></tr>
<tr><td>☐ Erratic eating pattern</td><td>☐ Low fruit/vegetable intake</td><td>☐ Eats fewer than 3 times a day</td></tr>
<tr><td>☐ Eats too much</td><td>☐ High sugar/sweet intake</td><td>☐ Refuses meals</td></tr>
<tr><td>☐ Dislikes healthy food</td><td>☐ &gt;50% of meals eaten away from home</td><td>☐ Eats too little</td></tr>
<tr><td>☐ Poor snack choices</td><td>☐ Refuses solid foods</td><td>☐ Has a poor appetite</td></tr>
<tr><td>☐ Uses food as a bribe or reward</td><td>☐ Refuses many foods</td><td>☐ Sensory issues with food</td></tr>
<tr><td>☐ Erratic mealtimes</td><td>☐ Picky eater</td><td>☐ Meals are eaten away from the table</td></tr>
<tr><td>☐ Snacks too much</td><td>☐ Prefers cold food</td><td>☐ Family members eat meals separately</td></tr>
<tr><td>☐ High juice intake</td><td>☐ Prefers hot food</td><td><b>Other:</b> ______</td></tr>
</table>

<p><b>Food likes:</b> ______<br>
<b>Food dislikes:</b> ______<br>
<b>Dietary restrictions / limitations:</b> ______<br>
<b>Eating out frequency:</b> ______ &nbsp; <b>Details:</b> ______<br>
<b>Grocery shopping:</b> ______<br>
<b>Meal preparation and cooking:</b> ______<br>
<b>Eating environment:</b> ______<br>
<b>Other:</b> ______</p>

<h5 style="color:#1f4e79;margin:14px 0 6px 0;border-bottom:1px solid #cfd8e3;padding-bottom:3px;">Physical Activity</h5>
<p><b>Regular physical activity / exercise:</b> ☐ Yes &nbsp; ☐ No &nbsp; <b>Type:</b> ______<br>
<b>Session duration:</b> ______ &nbsp; <b>Frequency:</b> ______<br>
<b>Barriers to exercising:</b> ______ &nbsp; <b>Details:</b> ______<br>
<b>Favorite activities:</b> ______<br>
<b>Screen time:</b> ______<br>
<b>Other:</b> ______</p>

<h5 style="color:#1f4e79;margin:14px 0 6px 0;border-bottom:1px solid #cfd8e3;padding-bottom:3px;">Objective — Weight History</h5>
<p><b>Weight:</b> {{peso}} lbs (measured weight)<br>
<b>Height:</b> {{talla}} in<br>
<b>Body mass index:</b> {{imc}}<br>
<b>Other:</b> ______</p>

<h5 style="color:#1f4e79;margin:14px 0 6px 0;border-bottom:1px solid #cfd8e3;padding-bottom:3px;">Physical Findings — Gastrointestinal</h5>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><td>☐ Abdominal bloating</td><td>☐ Diarrhea</td><td>☐ Nausea</td></tr>
<tr><td>☐ Abdominal cramping</td><td>☐ Early satiety</td><td>☐ Pain on swallowing</td></tr>
<tr><td>☐ Abdominal distension</td><td>☐ Excessive appetite</td><td>☐ Poor appetite</td></tr>
<tr><td>☐ Abdominal pain</td><td>☐ Excessive belching</td><td>☐ Retching</td></tr>
<tr><td>☐ Acid reflux</td><td>☐ Excessive wind</td><td>☐ Vomiting</td></tr>
<tr><td>☐ Bulky stools</td><td>☐ Heartburn</td><td><b>Other:</b> ______</td></tr>
<tr><td>☐ Constipation</td><td>☐ Liquid stools</td><td><b>Other:</b> ______</td></tr>
</table>
<p><b>Other:</b> ______</p>

<h5 style="color:#1f4e79;margin:14px 0 6px 0;border-bottom:1px solid #cfd8e3;padding-bottom:3px;">Medication &amp; Supplements</h5>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Name of Medication / Supplement</th><th>Reason</th><th>Dose &amp; Frequency</th></tr>
<tr><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td></tr>
</table>

<h5 style="color:#1f4e79;margin:14px 0 6px 0;border-bottom:1px solid #cfd8e3;padding-bottom:3px;">Lab Results</h5>
<p><b>Lab results:</b> ______<br>
<b>Diagnostic studies:</b> ______<br>
<b>Other:</b> ______</p>

<h5 style="color:#1f4e79;margin:14px 0 6px 0;border-bottom:1px solid #cfd8e3;padding-bottom:3px;">Assessment — Estimated Energy &amp; Nutrition Needs</h5>
<p><b>Total energy estimated needs:</b> ______ kcal/day — REE = ______ kcal/day using EER for Healthy Children &amp; Infants (0–18 yrs); Activity Factor = ______; Stress/Injury Factor = None (1.0–1.0); weight = {{peso}} lbs (actual body weight).<br>
<b>Total protein estimated needs:</b> ______ g/day for normal nutrition; weight = {{peso}} lbs (actual body weight).<br>
<b>Total fiber estimated needs:</b> ______<br>
<b>Total fat estimated needs:</b> ______ (25–35 % total energy).<br>
<b>Total fluid estimated needs:</b> ______ (normal nutrition using weight = {{peso}} lbs actual body weight).</p>

<h5 style="color:#1f4e79;margin:14px 0 6px 0;border-bottom:1px solid #cfd8e3;padding-bottom:3px;">Comprehension &amp; Motivation</h5>
<p><b>Readiness to change (0 = low, 10 = very high):</b> ______<br>
<b>Receptiveness to education:</b> ______<br>
<b>Understanding of education:</b> ______<br>
<b>Expected level of compliance:</b> ______</p>

<h5 style="color:#1f4e79;margin:14px 0 6px 0;border-bottom:1px solid #cfd8e3;padding-bottom:3px;">Nutrition Diagnosis</h5>
<p><b>1.</b> ______ is related to ______, as evidenced by ______. <b>Status:</b> New diagnosis<br>
<b>2.</b> ______ is related to ______, as evidenced by ______. <b>Status:</b> New diagnosis<br>
<b>3.</b> ______ is related to ______, as evidenced by ______. <b>Status:</b> New diagnosis<br>
<b>Other:</b> ______</p>

<h5 style="color:#1f4e79;margin:14px 0 6px 0;border-bottom:1px solid #cfd8e3;padding-bottom:3px;">Plan</h5>
<p><b>Interventions:</b> ______<br>
<b>Nutrition counseling utilized:</b> ______<br>
<b>Education materials provided:</b> ______<br>
<b>Food log:</b> ______ for ______<br>
<b>Meal plan:</b> ______</p>

<p><b>Short-Term Dietary Goals</b><br>
<b>1.</b> ______ &nbsp; <b>Status:</b> New goal<br>
<b>2.</b> ______ &nbsp; <b>Status:</b> New goal<br>
<b>3.</b> ______ &nbsp; <b>Status:</b> New goal</p>

<p><b>Other goals:</b> ______<br>
<b>Action plan:</b> ______<br>
<b>Stop time:</b> ______ &nbsp; <b># 15-min units:</b> ______<br>
<b>Summary notes / goals on review:</b> ______</p>

<div style="margin-top:30px;border-top:1px solid #ccc;padding-top:10px;">
Electronically Signed By: <b>{{firma_nombre}}</b><br>
{{firma_credenciales}}
</div>
</div>
HTML
];

// (30) Pre-LEAP Assessment & Education — Checklist
$plantillas[] = [
    'nombre'    => '(30) Pre-LEAP Assessment & Education (Checklist)',
    'categoria' => 'LEAP / MRT',
    'cuerpo'    => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#2b2b2b;">
<h4 style="color:#5a9d3a;margin:0 0 10px 0;">Pre-LEAP Assessment &amp; Education (20–60 min)</h4>
<p><i>CLIENT seen in office setting. Telephone / Telehealth Consult comments <u>underlined</u>.</i></p>

<p><b>Name:</b> {{paciente_nombre}} &nbsp; <b>DOB:</b> {{paciente_dob}} &nbsp; <b>Date:</b> {{fecha_actual}}<br>
<b>Start time:</b> {{hora_inicio}}</p>

<h5 style="color:#5a9d3a;margin:14px 0 6px 0;">Statement of Purpose for this Encounter</h5>
<p>RD to obtain data from client &amp; assess for appropriateness of LEAP-MRT.</p>

<h5 style="color:#5a9d3a;margin:14px 0 6px 0;">Forms Needed: Assessment</h5>
<ul>
<li>☐ HIPAA Release Form</li>
<li>☐ Patient Initial Consultation Form / Health History (HH) + any personal, extended Histories / Records</li>
<li>☐ Symptom Survey (SS) (Initial)</li>
<li>☐ Progress Note</li>
</ul>

<h5 style="color:#5a9d3a;margin:14px 0 6px 0;">Supplies / Forms Needed When Client is Ready to Move Forward With MRT Testing</h5>
<ul>
<li>☐ Specimen Mailer — <u>Distance Client: Prepaid-Supply Order form for Oxford to ship to client</u></li>
<li>☐ MRT Requisition Form (Blood Work Order Form — must be within professional scope of practice to order)</li>
<li>☐ Food Avoidance Form (submit with blood work)</li>
<li>☐ Your Practice Pricing List (optional — may use for package pricing and extensive additional services)</li>
<li>☐ Enrollment Agreement (optional — simple "Contract of Agreement" between practitioner and client)</li>
<li>☐ Insurance Verification Worksheet (optional — client's physician referral or prescription needed)</li>
<li>☐ Specimen Collection and Shipping Instructions</li>
<li>☐ Laboratory List or Local Lab Information for blood draw (to provide client)</li>
<li>☐ Sample LEAP Report and Results (to show client)</li>
<li>☐ "How Food Sensitivities Cause Symptoms" (foamboard, handouts or PowerPoint / slides)</li>
</ul>

<h5 style="color:#5a9d3a;margin:14px 0 6px 0;">Procedure / Checklist: Pre-LEAP Consult</h5>

<p><b>1. Review Health History with Client</b></p>
<ul>
<li>☐ Open up. Maintain control of the interview. Qualify them to be your client. Ask probing questions.</li>
<li>☐ Develop "partnership" with prospective LEAP client.</li>
<li>☐ Referring physician(s) info gathered?</li>
<li>☐ General Health / Medical History (additional medical forms as RD practice dictates). Interference with life? Likelihood that food sensitivity is playing a role.</li>
<li>☐ Current eating and exercise; lifestyle habits; willing to try new foods. Establish motives for going forward with LEAP.</li>
<li>☐ Establish how actively they are seeking a solution.</li>
<li>☐ Assess potential difficulties: eating habits, ability to cook, willingness to learn. Overall goals.</li>
<li>☐ Focus prospect's needs.</li>
<li>☐ Assess "readiness to change" — is motivation adequate?</li>
</ul>

<p><b>2. Determine: Is this an appropriate LEAP candidate?</b></p>
<ul>
<li>☐ <b>YES</b> — continue to Step 3 (determine and document Nutrition Diagnosis, plan for Nutrition Intervention, Monitoring and Evaluation, or other Progress Note per your practice).</li>
<li>☐ <b>NO</b> — determine alternate / additional Plan of Care; client may reconsider MRT at a later date.</li>
</ul>

<p><b>3. Present LEAP Education</b><br>
<i>(PRE-LEAP slides / PDF; review Ethan DeMitchell's webinar from May 2011 or his second webinar. Note: "LEAP Protocol" is not just avoiding MRT reactive foods, but an oligoantigenic / anti-inflammatory diet based on client history + MRT results.)</i></p>

<p><b>4. Obtain Commitment from Client.</b> "Does this make sense to you so far?" ☐</p>

<p><b>5. Explain / Share Your Program and Costs.</b> ☐</p>

<p><b>6. Obtain Payment.</b> ☐<br>
<i>(Private pay / package pricing vs. insurance — see below regarding insurance.)</i></p>

<p><b>7. Complete Remaining Paperwork</b></p>
<ul>
<li>☐ Enrollment Agreement (optional)</li>
<li>☐ Initial Symptom Survey</li>
<li>☐ Food Avoidance Form (note known IgE allergies, suspected foods — consider having client "sign")</li>
<li>☐ MRT Requisition Form and payment information (page 2). Determine ordering physician.</li>
</ul>

<p><b>8. Arrange for MRT Blood Draw</b></p>
<ul>
<li>☐ Insurance to be used? Approved? (See: Insurance Verification Worksheet — complete, submit to Oxford PRIOR to blood draw.)</li>
<li>☐ Obtain signature of client's ordering physician.</li>
<li>☐ Review Specimen Collection and Shipping Instructions (also in specimen mailer box).</li>
<li>☐ Determine / locate laboratory / location for blood draw ("Finding Labs Nationwide" — LEAP mentor assist). FedEx tracking # noted. (No need to call in as of 2014.)</li>
</ul>

<p><b>9. Schedule Next Appointment</b><br>
<i>(Approx. 10–14 business days AFTER blood is drawn. If by telephone, set next phone appt approx. 7–10 days out to start "avoidance" of red / yellow — via electronic version.)</i></p>

<p><b>10. Complete PRE-LEAP Progress Note.</b> ☐</p>

<p><b>11. Optional: Assign Food / Symptom Diary (Timeline) for client to complete for 1 week.</b> ☐<br>
<i>(If a client is keeping an incomplete Food / Symptom Diary, you can review before they start their LEAP diet and instruct on the need for complete details: times of foods / water consumed, time of symptoms, etc.)</i></p>

<p><b>12. Other:</b> Before meeting with client, review results with your LEAP Mentor, if needed. ☐</p>

<p><b>13. Other:</b> ______</p>

<h5 style="color:#5a9d3a;margin:14px 0 6px 0;">Reminders</h5>
<p><b>Insurance Verifications:</b> If a client has an "Out-of-Network" deductible that has not been met yet, then they will have to pay for testing, since Oxford only bills "Out-of-Network".</p>
<p><b>Client familiar with / requesting MRT testing / LEAP Support:</b> When a potential client is very familiar with LEAP / MRT and contacts you for testing / LEAP counseling, procedures may be shortened significantly; however the overall process remains the same.</p>

<h5 style="color:#5a9d3a;margin:14px 0 6px 0;">Encounter Summary</h5>
<p><b>Stop time:</b> ______ &nbsp; <b># 15-min units:</b> ______<br>
<b>Notes:</b> ______________________</p>

<div style="margin-top:30px;border-top:1px solid #ccc;padding-top:10px;">
Electronically Signed By: <b>{{firma_nombre}}</b><br>
{{firma_credenciales}}
</div>
</div>
HTML
];

// ── Ejecutar upsert por nombre ───────────────────────────────────────
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
   . '<body class="p-4"><div class="container" style="max-width:720px;"><h4>Resultado — Lote 6</h4><ul class="list-group">';
foreach ($msgs as $m) echo '<li class="list-group-item">'.htmlspecialchars($m).'</li>';
echo '</ul><a class="btn btn-primary mt-3" href="plantillas_admin.php">Ir a Templates</a></div></body></html>';
