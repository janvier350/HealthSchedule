<?php
/**
 * migrar_plantillas_bulk_lote3.php
 * Inserta el LOTE 3 de plantillas Kalix:
 *   (14) Pediatric/Infant Nutrition Assessment — Pediatric Assessment
 *   (15) Pediatric Assessment (School-age)      — Pediatric Assessment
 *
 * (PDF #11 se omitió: es duplicado exacto de #9 LEAP Health History
 * Intake. PDFs #12 y #13 son documentos legales y se registran en
 * migrar_documentos_lote3.php.)
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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Migración lote 3</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Migrar plantillas — Lote 3</h4>
<p class="text-muted">Inserta / actualiza <b>(14) Pediatric/Infant Nutrition Assessment</b> y
<b>(15) Pediatric Assessment (School-age)</b>.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar migración</button>
<a class="btn btn-link" href="plantillas_admin.php">Volver</a></form></div></body></html>
    <?php
    exit;
}

$plantillas = [];

// (14) Pediatric/Infant Nutrition Assessment
$plantillas[] = [
    'nombre'    => '(14) Pediatric/Infant Nutrition Assessment',
    'categoria' => 'Pediatric Assessment',
    'cuerpo'    => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#2b2b2b;">
<p><b>Name:</b> {{paciente_nombre}}<br>
<b>DOB:</b> {{paciente_dob}}</p>

<p><b>Referred by:</b> ______________________<br>
<b>Start time:</b> {{hora_inicio}}<br>
<b>Reason for visit:</b> ______________________<br>
<b>Medical diagnosis:</b> ______________________<br>
<b>Questions/concerns for today:</b> ______________________</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Nutrition Assessment</h5>
<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Food/Nutrition - Related History</h6>
<p><b>Food &amp; Nutrient Intake — Date:</b> ______</p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Meal</th><th>Place Prepared</th><th>Food &amp; Beverages</th></tr>
<tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr>
</table>
<p><b>Other:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Food &amp; Nutrient Administration</h6>
<p><b>Feeding schedule:</b> ______________________<br>
<b>Feeding method:</b> ______ <b>Other:</b> ______<br>
<b>Food allergies:</b> ______<br>
<b>Food intolerances and sensitivities:</b> ______<br>
<b>Food likes:</b> ______ &nbsp; <b>Food dislikes:</b> ______<br>
<b>Dietary restrictions / limitations:</b> ______<br>
<b>Eating out frequency — Details:</b> ______<br>
<b>Grocery shopping:</b> ______<br>
<b>Meal preparation and cooking:</b> ______<br>
<b>Eating environment:</b> ______<br>
<b>Summary of diet:</b> ______<br>
<b>Other:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Medication &amp; Complementary/Alternative Medicine Use</h6>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Name of Medication / Supplement</th><th>Reason</th><th>Dose &amp; Frequency</th></tr>
<tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td></tr>
</table>
<p><b>Other:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Knowledge/Beliefs/Attitudes (Parents)</h6>
<p><b>Readiness to change (0 = low &amp; 10 = very high):</b> ____<br>
<b>Receptiveness to education:</b> ______<br>
<b>Understanding of education:</b> ______<br>
<b>Expected level of compliance:</b> ______<br>
<b>Other:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Physical Activity</h6>
<p><b>Favorite activities:</b> ______<br>
<b>Screen time:</b> ______<br>
<b>Other:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Anthropometric Measures</h6>
<p><b>Height:</b> {{talla}} &nbsp; <b>Weight:</b> {{peso}}<br>
<b>Head circumference:</b> ____ in<br>
<b>Weight history:</b> ______<br>
<b>Other:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Biochemical Data, Medical Test &amp; Procedures</h6>
<p><b>Lab results:</b> ______<br>
<b>Diagnostic studies:</b> ______<br>
<b>Other:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Nutrition Focused Physical Findings — Gastrointestinal</h6>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><td>Abdominal bloating</td><td>Diarrhea</td><td>Nausea</td></tr>
<tr><td>Abdominal cramping</td><td>Early satiety</td><td>Pain on swallowing</td></tr>
<tr><td>Abdominal distension</td><td>Excessive appetite</td><td>Poor appetite</td></tr>
<tr><td>Abdominal pain</td><td>Excessive belching</td><td>Retching</td></tr>
<tr><td>Acid reflux</td><td>Excessive wind</td><td>Vomiting</td></tr>
<tr><td>Bulky stools</td><td>Heartburn</td><td>Other:</td></tr>
<tr><td>Constipation</td><td>Liquid stools</td><td>Other:</td></tr>
</table>
<p><b>Bowel movements:</b> Diarrhea — No &nbsp; Constipation — No<br>
<b>Other:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Client History</h6>
<p><i><b>Personal History</b></i><br>
<b>Members of household:</b> ______<br>
<b>Other:</b> ______</p>
<p><i><b>Client Medical/Health History</b></i><br>
<b>Medical history:</b> ______<br>
<b>Immunizations up-to-date — Details:</b> ______<br>
<b>Past surgeries / hospitalizations:</b> ______<br>
<b>Other:</b> ______</p>
<p><i><b>Family Medical/Health History</b></i></p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><td>Cancer</td><td>High blood cholesterol</td><td>Liver disease</td></tr>
<tr><td>Diabetes</td><td>High blood pressure</td><td>Thyroid disease</td></tr>
<tr><td>Heart disease</td><td>Kidney disease</td><td>Obesity</td></tr>
</table>
<p><b>Other family medical history:</b> ______</p>
<p><i><b>Social History</b></i><br>
<b>Living arrangements:</b> ______<br>
<b>Siblings:</b> ______<br>
<b>Other:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Comparative Standards</h6>
<p><b>Total energy estimated needs:</b> ____ kcal/day &nbsp; REE = ____ kcal/day using EER for Healthy Children &amp; Infants (0–18 yrs), Activity Factor = Normal ( - ), Stress/Injury Factor = None (1 - 1), weight = ____ lbs (actual body weight)<br>
<b>Total protein estimated needs:</b> ____ g/day, for normal nutrition ( - ), weight = ____ lbs (actual body weight)<br>
<b>Total carbohydrate estimated needs:</b> - ____ (45 – 65 % total energy)<br>
<b>Total fiber estimated needs:</b> ______<br>
<b>Total fat estimated needs:</b> - ____ (30 – 40 % total energy)<br>
<b>Total fluid estimated needs:</b> ____ (normal nutrition using weight = ____ lbs actual body weight)<br>
<b>Other:</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Nutrition Diagnosis</h5>
<p><b>1.</b> ____________ is related to ____________ , as evidenced by ____________ .<br><b>Status:</b> New diagnosis<br>
<b>2.</b> ____________ is related to ____________ , as evidenced by ____________ .<br><b>Status:</b> New diagnosis<br>
<b>3.</b> ____________ is related to ____________ , as evidenced by ____________ .<br><b>Status:</b> New diagnosis</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Nutrition Prescription</h5>
<p><b>Foods/ideas to emphasize:</b> ______<br>
<b>Foods to limit:</b> ______<br>
<b>Foods to avoid:</b> ______<br>
<b>Other notes:</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Nutrition Intervention</h5>
<p><b>Interventions:</b> ______<br>
<b>Nutrition counseling utilized:</b> ______<br>
<b>Education materials provided:</b> ______</p>
<p><i><b>Short Term Dietary Goals</b></i><br>
<b>1.</b> ______ · <b>Status:</b><br>
<b>2.</b> ______ · <b>Status:</b><br>
<b>3.</b> ______ · <b>Status:</b></p>
<p><b>Other goals:</b> ______<br>
<b>Action plan:</b> ______<br>
<b>Food log for:</b> ______<br>
<b>Meal plan:</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Monitoring and Evaluation</h5>
<p><b>Follow up:</b> ______<br>
<b>Food intake — Criteria:</b> ______<br>
<b>Weight — Criteria:</b> ______<br>
<b>Biochemical data — Criteria:</b> ______<br>
<b>Stop time:</b> ______ · <b># 15 min units:</b> ______<br>
<b>Summary notes/goals on review:</b> ______</p>

<div style="margin-top:30px;border-top:1px solid #ccc;padding-top:10px;">
Electronically Signed By: <b>{{firma_nombre}}</b><br>
{{firma_credenciales}}
</div>
</div>
HTML
];

// (15) Pediatric Assessment (School-age)
$plantillas[] = [
    'nombre'    => '(15) Pediatric Assessment (School-age)',
    'categoria' => 'Pediatric Assessment',
    'cuerpo'    => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#2b2b2b;">
<p><b>Name:</b> {{paciente_nombre}}<br>
<b>DOB:</b> {{paciente_dob}}</p>

<p><b>Referred by:</b> ______________________<br>
<b>Start time:</b> {{hora_inicio}}<br>
<b>Reason for visit:</b> ______________________<br>
<b>Medical diagnosis:</b> ______________________<br>
<b>Questions/concerns for today:</b> ______________________</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Nutrition Assessment</h5>
<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Food/Nutrition - Related History</h6>
<p><b>Food &amp; Nutrient Intake — Date:</b> ______</p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Meal</th><th>Place Prepared</th><th>Food &amp; Beverages</th></tr>
<tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr>
</table>
<p><b>Other:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Food &amp; Nutrient Administration</h6>
<p><b>Food allergies:</b> ______<br>
<b>Other allergies:</b> ______<br>
<b>Food intolerances and sensitivities:</b> ______<br>
<b>Food likes:</b> ______ &nbsp; <b>Food dislikes:</b> ______<br>
<b>Dietary restrictions / limitations:</b> ______<br>
<b>Eating out frequency — Details:</b> ______<br>
<b>Grocery shopping:</b> ______<br>
<b>Meal preparation and cooking:</b> ______<br>
<b>Eating environment:</b> ______<br>
<b>Summary of diet:</b> ______<br>
<b>Other:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Medication &amp; Complementary/Alternative Medicine Use</h6>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Name of Medication / Supplement</th><th>Reason</th><th>Dose &amp; Frequency</th></tr>
<tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td></tr>
</table>
<p><b>Other:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Knowledge/Beliefs/Attitudes</h6>
<p><b>Readiness to change (0 = low &amp; 10 = very high):</b> ____<br>
<b>Receptiveness to education:</b> ______<br>
<b>Understanding of education:</b> ______<br>
<b>Expected level of compliance:</b> ______<br>
<b>Other:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Physical Activity</h6>
<p><b>Regular physical activity / exercise — Type:</b> ______<br>
<b>Session duration:</b> ______ · <b>Frequency:</b> ______<br>
<b>Barriers to exercising — Details:</b> ______<br>
<b>Favorite activities:</b> ______<br>
<b>Screen time:</b> ______<br>
<b>Other:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Anthropometric Measures</h6>
<p><b>Height:</b> {{talla}} &nbsp; <b>Weight:</b> {{peso}} &nbsp; <b>BMI:</b> {{imc}}<br>
<b>Weight history:</b> ______<br>
<b>Other:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Biochemical Data, Medical Test &amp; Procedures</h6>
<p><b>Lab results:</b> ______<br>
<b>Diagnostic studies:</b> ______<br>
<b>Other:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Nutrition Focused Physical Findings — Gastrointestinal</h6>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><td>Abdominal bloating</td><td>Diarrhea</td><td>Nausea</td></tr>
<tr><td>Abdominal cramping</td><td>Early satiety</td><td>Pain on swallowing</td></tr>
<tr><td>Abdominal distension</td><td>Excessive appetite</td><td>Poor appetite</td></tr>
<tr><td>Abdominal pain</td><td>Excessive belching</td><td>Retching</td></tr>
<tr><td>Acid reflux</td><td>Excessive wind</td><td>Vomiting</td></tr>
<tr><td>Bulky stools</td><td>Heartburn</td><td>Other:</td></tr>
<tr><td>Constipation</td><td>Liquid stools</td><td>Other:</td></tr>
</table>
<p><b>Bowel movements:</b> Diarrhea — No &nbsp; Constipation — No<br>
<b>Other:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Client History</h6>
<p><i><b>Personal History</b></i><br>
<b>Members of household:</b> ______<br>
<b>Other:</b> ______</p>
<p><i><b>Client Medical/Health History</b></i><br>
<b>Medical history:</b> ______<br>
<b>Immunizations up-to-date — Details:</b> ______<br>
<b>Past surgeries / hospitalizations:</b> ______<br>
<b>Other:</b> ______</p>
<p><i><b>Family Medical/Health History</b></i></p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><td>Cancer</td><td>High blood cholesterol</td><td>Liver disease</td></tr>
<tr><td>Diabetes</td><td>High blood pressure</td><td>Thyroid disease</td></tr>
<tr><td>Heart disease</td><td>Kidney disease</td><td>Obesity</td></tr>
</table>
<p><b>Other family medical history:</b> ______</p>
<p><i><b>Social History</b></i><br>
<b>School:</b> ______ · <b>Year/grade level:</b> ______<br>
<b>School contact — Phone:</b> ______<br>
<b>Living arrangements:</b> ______<br>
<b>Siblings:</b> ______<br>
<b>Other:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Comparative Standards</h6>
<p><b>Total energy estimated needs:</b> ____ kcal/day &nbsp; REE = ____ kcal/day using EER for Healthy Children &amp; Infants (0–18 yrs), Activity Factor = Normal ( - ), Stress/Injury Factor = None (1 - 1), weight = ____ lbs (actual body weight)<br>
<b>Total protein estimated needs:</b> ____ g/day, for normal nutrition ( - ), weight = ____ lbs (actual body weight)<br>
<b>Total carbohydrate estimated needs:</b> - ____ (45 – 65 % total energy)<br>
<b>Total fiber estimated needs:</b> ______<br>
<b>Total fat estimated needs:</b> - ____ (25 – 35 % total energy)<br>
<b>Total fluid estimated needs:</b> ____ (normal nutrition using weight = ____ lbs actual body weight)<br>
<b>Other:</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Nutrition Diagnosis</h5>
<p><b>1.</b> ____________ is related to ____________ , as evidenced by ____________ .<br><b>Status:</b> New diagnosis<br>
<b>2.</b> ____________ is related to ____________ , as evidenced by ____________ .<br><b>Status:</b> New diagnosis<br>
<b>3.</b> ____________ is related to ____________ , as evidenced by ____________ .<br><b>Status:</b> New diagnosis</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Nutrition Prescription</h5>
<p><b>Foods/ideas to emphasize:</b> ______<br>
<b>Foods to limit:</b> ______<br>
<b>Foods to avoid:</b> ______<br>
<b>Other notes:</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Nutrition Intervention</h5>
<p><b>Interventions:</b> ______<br>
<b>Nutrition counseling utilized:</b> ______<br>
<b>Education materials provided:</b> ______</p>
<p><i><b>Short Term Dietary Goals</b></i><br>
<b>1.</b> ______ · <b>Status:</b><br>
<b>2.</b> ______ · <b>Status:</b><br>
<b>3.</b> ______ · <b>Status:</b></p>
<p><b>Other goals:</b> ______<br>
<b>Action plan:</b> ______<br>
<b>Food log for:</b> ______<br>
<b>Meal plan:</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Monitoring and Evaluation</h5>
<p><b>Follow up:</b> ______<br>
<b>Food intake — Criteria:</b> ______<br>
<b>Weight — Criteria:</b> ______<br>
<b>Biochemical data — Criteria:</b> ______<br>
<b>Stop time:</b> ______ · <b># 15 min units:</b> ______<br>
<b>Summary notes/goals on review:</b> ______</p>

<div style="margin-top:30px;border-top:1px solid #ccc;padding-top:10px;">
Electronically Signed By: <b>{{firma_nombre}}</b><br>
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
   . '<body class="p-4"><div class="container" style="max-width:720px;"><h4>Resultado — Lote 3</h4><ul class="list-group">';
foreach ($msgs as $m) echo '<li class="list-group-item">'.htmlspecialchars($m).'</li>';
echo '</ul><a class="btn btn-primary mt-3" href="plantillas_admin.php">Ir a Templates</a></div></body></html>';
