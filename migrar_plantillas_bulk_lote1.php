<?php
/**
 * migrar_plantillas_bulk_lote1.php
 * Inserta el LOTE 1 de plantillas convertidas desde PDFs:
 *   (2) Adult Nutrition Assessment (short) — Adult Assessment
 *   (3) IFNA Integrative Intake             — Adult Assessment
 *   (5) CKD Adult Assessment                — Adult Assessment – Renal
 * (Se saltó el PDF 4 = HIPAA Notice porque es un documento legal;
 *  se registra por separado en migrar_documento_hipaa.php.)
 *
 * Idempotente: si una plantilla con el mismo nombre ya existe, actualiza
 * su cuerpo y categoría en vez de duplicar. SISTEMA-only, POST-driven.
 *
 * Nombres numerados + descripción para que la Dra. renombre desde el CRUD
 * (Templates) si quiere otro nombre.
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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Migración lote 1</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Migrar plantillas — Lote 1</h4>
<p class="text-muted">Inserta / actualiza las plantillas <b>(2) Adult Nutrition Assessment (short)</b>,
<b>(3) IFNA Integrative Intake</b> y <b>(5) CKD Adult Assessment</b>.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar migración</button>
<a class="btn btn-link" href="plantillas_admin.php">Volver</a></form></div></body></html>
    <?php
    exit;
}

// ── Plantillas del lote ───────────────────────────────────────────────
$plantillas = [];

// (2) Adult Nutrition Assessment (short) — convertida del PDF #1 (a6590107)
$plantillas[] = [
    'nombre'    => '(2) Adult Nutrition Assessment (short)',
    'categoria' => 'Adult Assessment',
    'cuerpo'    => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#2b2b2b;">
<p><b>Name:</b> {{paciente_nombre}}<br>
<b>DOB:</b> {{paciente_dob}}<br>
<b>Referred by:</b> ______________________<br>
<b>Start time:</b> {{hora_inicio}}<br>
<b>Reason for visit:</b> ______________________<br>
<b>Medical diagnosis:</b> ______________________<br>
<b>Questions/concerns for today:</b> ______________________</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Nutrition Assessment</h5>
<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Food/Nutrition - Related History</h6>
<p><b>Food &amp; Nutrient Intake — Date:</b></p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Meal</th><th>Time</th><th>Food/Beverages Consumed</th></tr>
<tr><td>Breakfast</td><td></td><td></td></tr>
<tr><td>Snack</td><td></td><td></td></tr>
<tr><td>Lunch</td><td></td><td></td></tr>
<tr><td>Snack</td><td></td><td></td></tr>
<tr><td>Evening meal</td><td></td><td></td></tr>
<tr><td>Evening snack</td><td></td><td></td></tr>
<tr><td>Other snacks</td><td></td><td></td></tr>
<tr><td>Other beverages</td><td></td><td></td></tr>
</table>
<p><b>Summary of diet:</b> ______________________<br><b>Other:</b> ______________________</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Medication &amp; Complementary/Alternative Medicine Use</h6>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Name of Medication / Supplement</th><th>Reason</th><th>Dose &amp; Frequency</th></tr>
<tr><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td></tr>
</table>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Knowledge/Beliefs/Attitudes</h6>
<p><b>Readiness to change (0 = low &amp; 10 = very high):</b> ____<br>
<b>Receptiveness to education:</b> ______________________<br>
<b>Understanding of education:</b> ______________________<br>
<b>Expected level of compliance:</b> ______________________<br>
<b>Other:</b> ______________________</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Physical Activity</h6>
<p><b>Regular physical activity / exercise — Type:</b> ______<br>
<b>Session duration:</b> ______ &nbsp; <b>Frequency:</b> ______<br>
<b>Barriers to exercising — Details:</b> ______________________<br>
<b>Other:</b> ______________________</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Anthropometric Measures</h6>
<p><b>Height:</b> {{talla}} &nbsp; <b>Weight:</b> {{peso}} &nbsp; <b>BMI:</b> {{imc}}<br>
<b>Waist circumference:</b> ____ in &nbsp; <b>Hip circumference:</b> ____ in &nbsp; <b>Waist-hip ratio:</b> ____<br>
<b>Weight change:</b> ____ lbs weight change since last session and ____ lbs total weight change since first session, equaling ____ .<br>
<b>Other:</b> ______________________</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Biochemical Data, Medical Test &amp; Procedures</h6>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Test</th><th>Result</th><th>Date</th><th>Test</th><th>Result</th><th>Date</th></tr>
<tr><td>Creatinine</td><td></td><td></td><td>BUN</td><td></td><td></td></tr>
<tr><td>Albumin</td><td></td><td></td><td>eGFR</td><td></td><td></td></tr>
<tr><td>Sodium</td><td></td><td></td><td>Potassium</td><td></td><td></td></tr>
<tr><td>Phosphorus</td><td></td><td></td><td>Calcium</td><td></td><td></td></tr>
<tr><td>Serum bicarbonate</td><td></td><td></td><td>A1c</td><td></td><td></td></tr>
<tr><td>Fasting glucose</td><td></td><td></td><td>Random glucose</td><td></td><td></td></tr>
<tr><td>Total cholesterol</td><td></td><td></td><td>HDL</td><td></td><td></td></tr>
<tr><td>LDL</td><td></td><td></td><td>Triglycerides</td><td></td><td></td></tr>
<tr><td>Vitamin D</td><td></td><td></td><td>BP</td><td>/</td><td></td></tr>
<tr><td>Hemoglobin</td><td></td><td></td><td>Hematocrit</td><td></td><td></td></tr>
<tr><td>Transferrin saturation</td><td></td><td></td><td>Ferritin</td><td></td><td></td></tr>
</table>
<p><b>Other lab results:</b> ______________________<br><b>Diagnostic studies:</b> ______________________<br><b>Other:</b> ______________________</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Nutrition Focused Physical Findings — Gastrointestinal Related</h6>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><td>Abdominal bloating</td><td>Diarrhea</td><td>Nausea</td></tr>
<tr><td>Abdominal cramping</td><td>Early satiety</td><td>Pain on swallowing</td></tr>
<tr><td>Abdominal distension</td><td>Excessive appetite</td><td>Poor appetite</td></tr>
<tr><td>Abdominal pain</td><td>Excessive belching</td><td>Retching</td></tr>
<tr><td>Acid reflux</td><td>Excessive wind</td><td>Vomiting</td></tr>
<tr><td>Bulky stools</td><td>Heartburn</td><td>Other:</td></tr>
<tr><td>Constipation</td><td>Liquid stools</td><td>Other:</td></tr>
</table>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Client History</h6>
<p><b>Current health (rating):</b> ____<br>
<b>Stress rating (0 = no stress, 10 = extreme stress) — Details:</b> ______________________<br>
<b>Amount of sleep on week nights:</b> ______<br>
<b>Amount of sleep on weekend nights:</b> ______<br>
<b>Other:</b> ______________________</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Comparative Standards</h5>
<p><b>Ideal body weight (IBW):</b> ____ lbs (Hamwi equation) &nbsp; <b>%IBW:</b> ____<br>
<b>Total protein estimated needs:</b> ____ g/day, for normal nutrition ( - ), weight = ____ lbs (actual body weight)<br>
<b>Total energy estimated needs:</b> ____ kcal/day (-500 kcal (-2092 kJ) for weight loss) &nbsp; REE = ____ kcal/day using Mifflin–St Jeor Equation (Adults), Activity Factor = Normal ( - ), Stress/Injury Factor = None (1 - 1), weight = ____ lbs (actual body weight).<br>
<b>Total carbohydrate estimated needs:</b> - ____ (45 - 65 % total energy)<br>
<b>Total fiber estimated needs:</b> ____<br>
<b>Total fat estimated needs:</b> - ____ (25 - 35 % total energy)<br>
<b>Total fluid estimated needs:</b> ____ (normal nutrition using weight = ____ lbs adjusted body weight)<br>
<b>Other:</b> ______________________</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Nutrition Diagnosis</h5>
<p><b>1.</b> ____________ is related to ____________ , as evidenced by ____________ .<br><b>Status:</b><br>
<b>2.</b> ____________ is related to ____________ , as evidenced by ____________ .<br><b>Status:</b><br>
<b>3.</b> ____________ is related to ____________ , as evidenced by ____________ .<br><b>Status:</b></p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Nutrition Prescription</h5>
<p><b>Foods/ideas to emphasize:</b> ______________________<br>
<b>Foods to limit:</b> ______________________<br>
<b>Foods to avoid:</b> ______________________<br>
<b>Other notes:</b> ______________________</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Nutrition Intervention</h5>
<p><b>Interventions:</b> ______________________<br>
<b>Nutrition counseling utilized:</b> ______________________<br>
<b>Education materials provided:</b> ______________________</p>
<p><i><b>Short Term Dietary Goals</b></i><br>
<b>1.</b> ______________________ &nbsp; <b>Status:</b><br>
<b>2.</b> ______________________ &nbsp; <b>Status:</b><br>
<b>3.</b> ______________________ &nbsp; <b>Status:</b></p>
<p><i><b>Short Term Exercise Goals</b></i><br>
<b>1.</b> ______________________ &nbsp; <b>Status:</b><br>
<b>2.</b> ______________________ &nbsp; <b>Status:</b><br>
<b>3.</b> ______________________ &nbsp; <b>Status:</b></p>
<p><b>Other goals:</b> ______________________<br>
<b>Action plan:</b> ______________________<br>
<b>Food log for:</b> ______________________<br>
<b>Meal plan:</b> ______________________<br>
<b>Progress towards goals:</b> ______________________<br>
<b>Areas for improvement:</b> ______________________</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Monitoring and Evaluation</h5>
<p><b>Follow up:</b> ______________________<br>
<b>Food intake — Criteria:</b> ______________________<br>
<b>Weight — Criteria:</b> ______________________<br>
<b>Physical Activity/Function — Criteria:</b> ______________________<br>
<b>Blood Glucose — Criteria:</b> FBG 80–130 mg/dl, A1c &lt;7 %, pre-meal 80–130 mg/dl, post-prandial &lt;180 mg/dl.<br>
<b>Blood Pressure — Criteria:</b> &lt;130/80 mmHg.<br>
<b>Lipid Profile — Criteria:</b> Total Cholesterol &lt;200 mg/dL, LDL &lt;100 mg/dL, Triglycerides &lt;150 mg/dL.<br>
<b>Stop time:</b> ______ &nbsp; <b># 15 min units:</b> ______<br>
<b>Summary notes/goals on review:</b> ______________________</p>

<div style="margin-top:30px;border-top:1px solid #ccc;padding-top:10px;">
Electronically Signed By: <b>{{firma_nombre}}</b><br>
{{firma_credenciales}}
</div>
</div>
HTML
];

// (3) IFNA Integrative Intake — convertida del PDF #2 (8bbba1bb)
$plantillas[] = [
    'nombre'    => '(3) IFNA Integrative Intake',
    'categoria' => 'Adult Assessment',
    'cuerpo'    => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#2b2b2b;">
<h4 style="color:#5a2d82;margin:0 0 8px 0;">Integrative &amp; Functional Nutrition Intake</h4>
<p><b>ID:</b> {{paciente_cedula}}<br>
<b>Name:</b> {{paciente_nombre}}<br>
<b>DOB:</b> {{paciente_dob}}</p>
<p><b>Reason for visit:</b> ______________________<br>
<b>Digestive function:</b> ______________________<br>
<b>Bowel movements:</b> Diarrhea — No &nbsp; Constipation — No<br>
<b>Signs and symptoms:</b> ______________________<br>
<b>Current health (rating):</b> ____<br>
<b>Typical energy level (rating):</b> ____</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Lab &amp; Diagnostic Data</h5>
<p>List or upload any labs or diagnostic studies (CT scan, MRI, bone density, colonoscopy, etc.) with data and age if known.<br>
<b>Lab and diagnostic data:</b> ______________________</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Medications &amp; Supplements</h5>
<p>List all prescription medications and nutritional supplements, herbs/botanicals currently taking, with month/year started.</p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Medication Name &amp; Date Started (Month / Year)</th><th>Dose &amp; Frequency</th><th>Reason</th></tr>
<tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr>
</table>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Supplement Name &amp; Date Started (Month/Year)</th><th>Dose &amp; Frequency</th><th>Reason</th></tr>
<tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr>
</table>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Anthropometrics</h5>
<p><b>Height:</b> {{talla}} &nbsp; <b>Weight:</b> {{peso}}<br>
<b>Ideal body weight (IBW):</b> ____ lbs (Hamwi equation) &nbsp; <b>%IBW:</b> ____<br>
<b>Weight change:</b> ____ lbs since last session and ____ lbs total since first session, equaling ____ .<br>
<b>Lean body mass:</b> ______ &nbsp; <b>Predictive 1 repetition mass:</b> ______<br>
<b>Caloric values per gram:</b> ______<br>
<b>Daily caloric deficit needed to achieve desired weight in set time frame:</b> ______<br>
<b>Total calories and percentage of calories:</b> ______<br>
<b>Karvonen formula heart rate reserve:</b> ______<br>
<b>Usual body weight:</b> ______ &nbsp; <b>Weight 1 year ago:</b> ______<br>
<b>Lowest adult weight:</b> ______ &nbsp; <b>Highest adult weight:</b> ______<br>
<b>Desired weight range (+/- 5 lbs):</b> ______<br>
<b>Does your weight affect how you feel about yourself?</b> ______ <b>Details:</b> ______</p>
<p><b>Total energy estimated needs:</b> ____ kcal/day &nbsp; REE = ____ kcal/day (Mifflin–St Jeor, activity = Moderate, stress/injury = ____, weight = ____ lbs actual)<br>
<b>Total protein estimated needs:</b> ____ g/day (normal nutrition, weight = ____ lbs actual)<br>
<b>Total carbohydrate estimated needs:</b> ____ (45 – 60 % total energy)<br>
<b>Total fiber estimated needs:</b> ____<br>
<b>Total fat estimated needs:</b> ____ (25 – 35 % total energy)<br>
<b>Total fluid estimated needs:</b> ____ (normal nutrition, weight = ____ lbs actual)</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Lifestyle</h5>
<p><b>Living, housing situation:</b> ______________________<br>
<b>Regular physical activity / exercise — Type:</b> ______<br>
<b>Session duration:</b> ______ &nbsp; <b>Frequency:</b> ______<br>
<b>Barriers to exercising — Details:</b> ______________________<br>
<b>Average number of hours slept per night during the week:</b> ______<br>
<b>Average number of hours slept per night on weekends:</b> ______<br>
<b>Overall quality of sleep (rating):</b> ____</p>
<p>On a scale of 1–10 (1 = low, 10 = high), how stressful are the following:<br>
<b>Daily stressors:</b> Work ____ · Family ____ · Social ____ · Finances ____ · Health ____ · Other ____<br>
<b>Relaxation:</b> ______________________</p>

<p><b>Please mark in the chart below with information about recent bowel movements:</b></p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Frequency</th><th></th><th>Color</th><th></th></tr>
<tr><td>More than 3 times a day</td><td></td><td>Dark brown</td><td></td></tr>
<tr><td>2-3 times a day</td><td></td><td>Medium brown</td><td></td></tr>
<tr><td>One time per day</td><td></td><td>Very dark or black</td><td></td></tr>
<tr><td>4-6 times a week</td><td></td><td>Greenish</td><td></td></tr>
<tr><td>2-3 times a week</td><td></td><td>Blood is visible</td><td></td></tr>
<tr><td>Once or fewer a week</td><td></td><td>Varies a lot</td><td></td></tr>
<tr><th>Consistency</th><th></th><td>Yellow, light brown</td><td></td></tr>
<tr><td>Soft and well formed</td><td></td><td>Greasy, shiny appearance</td><td></td></tr>
<tr><td>Often float</td><td></td><td></td><td></td></tr>
<tr><td>Difficult to pass</td><td></td><td></td><td></td></tr>
<tr><td>Diarrhea</td><td></td><td></td><td></td></tr>
<tr><td>Thin, long or narrow</td><td></td><td></td><td></td></tr>
<tr><td>Small and hard</td><td></td><td></td><td></td></tr>
<tr><td>Loose, but not watery</td><td></td><td></td><td></td></tr>
<tr><td>Alternating between hard and loose/watery</td><td></td><td></td><td></td></tr>
</table>

<p><b>Do you experience intestinal gas? (check all that apply)</b><br>
___ present with pain &nbsp; ___ foul smell &nbsp; ___ little odor &nbsp; ___ excessive daily &nbsp; ___ occasionally</p>
<p><b>Do you experience anal itching?</b><br>
___ frequently &nbsp; ___ occasionally &nbsp; ___ rarely &nbsp; ___ never</p>
<p><b>Do you experience any heartburn, chest pressure, or stomach pain?</b> ___ No &nbsp; ___ Yes<br>
<b>If yes, do you take anything for relief (list):</b> ______________________</p>
<p><b>Comments:</b> ______________________</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Diet &amp; Food Habits</h5>
<p><b>Following a particular diet/eating pattern:</b> No<br>
Vegan — No · Vegetarian — No · Low carb — No · Ketogenic — No · Paleo — No · Gluten free — No · Elimination diet — No · Other:<br>
<b>Comments:</b> ______________________<br>
<b>What are your personal challenges to eating well?</b> ______________________<br>
<b>Adverse food reactions (allergies/intolerances):</b> No · <b>Details:</b> ______<br>
<b>Percentage of meals eaten out:</b> ______<br>
<b>Meal most often eaten out:</b> Breakfast — No · Lunch — No · Dinner — No<br>
<b>Types of eating establishments most often frequented:</b> ______<br>
<b>Do you grocery shop? If not, who does?</b> ______<br>
<b>Do you cook? If not, who does?</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Food Log (3 typical days including a weekend)</h5>
<p><i>Do not change how you usually eat and include all food and beverages.</i></p>
<p><b>Date:</b> ______</p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th></th><th>Food &amp; Beverages</th><th>Comments or Symptoms</th></tr>
<tr><td><b>BREAKFAST</b> — Time:</td><td></td><td></td></tr>
<tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr>
<tr><td><b>SNACK</b></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td></tr>
<tr><td><b>LUNCH</b> — Time:</td><td></td><td></td></tr>
<tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr>
<tr><td><b>SNACK</b></td><td></td><td></td></tr>
<tr><td><b>DINNER</b> — Time:</td><td></td><td></td></tr>
<tr><td></td><td></td><td></td></tr>
<tr><td><b>ELIMINATION</b> — Time / Description</td><td></td><td></td></tr>
</table>

<p><b>Additional comments:</b> ______________________<br>
<b>Recommendations:</b> ______________________</p>

<div style="margin-top:30px;border-top:1px solid #ccc;padding-top:10px;">
Signed on: {{fecha_actual}}<br>
Signed by: <b>{{firma_nombre}}</b><br>
{{firma_credenciales}}
</div>
</div>
HTML
];

// (5) CKD Adult Assessment — convertida del PDF #4 (69be7dd9)
$plantillas[] = [
    'nombre'    => '(5) CKD Adult Assessment',
    'categoria' => 'Adult Assessment – Renal',
    'cuerpo'    => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#2b2b2b;">
<p><b>Name:</b> {{paciente_nombre}}<br>
<b>DOB:</b> {{paciente_dob}}<br>
<b>Referred by:</b> ______________________<br>
<b>Start time:</b> {{hora_inicio}}<br>
<b>Reason for visit:</b> ______________________<br>
<b>Medical diagnosis:</b> ______________________<br>
<b>Questions/concerns for today:</b> ______________________</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Subjective</h5>
<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Client Medical/Health History</h6>
<p><b>Current health (rating):</b> ____<br>
<b>Stress rating (0 = no stress, 10 = extreme stress) — Details:</b> ______________________<br>
<b>Current smoking status — Amount:</b> ______<br>
<b>Past smoking status — Amount:</b> ______<br>
<b>Medical history:</b> ______________________<br>
<b>Past surgeries / hospitalizations:</b> ______________________<br>
<b>Other:</b> ______________________</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Family Medical/Health History</h6>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><td>Cancer</td><td>High blood cholesterol</td><td>Liver disease</td></tr>
<tr><td>Diabetes</td><td>High blood pressure</td><td>Thyroid disease</td></tr>
<tr><td>Heart disease</td><td>Kidney disease</td><td>Obesity</td></tr>
</table>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Social History</h6>
<p><b>Social history:</b> ______________________<br>
<b>Marital status:</b> ______<br>
<b>Highest level of education:</b> ______<br>
<b>Occupation:</b> ______ · Retired: ______<br>
<b>Work hours:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Diet History</h6>
<p><b>Date:</b> ______</p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Meal</th><th>Time</th><th>Food/Beverages Consumed</th></tr>
<tr><td>Breakfast</td><td></td><td></td></tr>
<tr><td>Snack</td><td></td><td></td></tr>
<tr><td>Lunch</td><td></td><td></td></tr>
<tr><td>Snack</td><td></td><td></td></tr>
<tr><td>Evening meal</td><td></td><td></td></tr>
<tr><td>Evening snack</td><td></td><td></td></tr>
<tr><td>Other snacks</td><td></td><td></td></tr>
<tr><td>Other beverages</td><td></td><td></td></tr>
</table>

<p><b>Frequency of alcohol intake — Type:</b> ______ &nbsp; <b>Quantity:</b> ______<br>
<b>Total energy intake:</b> ____ kcal/day<br>
<b>Total protein intake:</b> ____ g/day<br>
<b>Total carbohydrate intake:</b> ____ g/day (breakfast ____, snack ____, lunch ____, snack ____, evening meal ____, evening snack ____).<br>
<b>Total fat intake:</b> ____ g/day<br>
<b>Sodium intake:</b> ____ &nbsp; <b>Potassium intake:</b> ____ &nbsp; <b>Calcium intake:</b> ____<br>
<b>Phosphorus intake:</b> ____ &nbsp; <b>Oral fluid intake:</b> ____ oz/day from ____<br>
<b>Other:</b> ______________________</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Food &amp; Nutrient Administration</h6>
<p><b>Food allergies:</b> ______<br>
<b>Food intolerances and sensitivities:</b> ______<br>
<b>Food likes:</b> ______ &nbsp; <b>Food dislikes:</b> ______<br>
<b>Taste changes:</b> ______ &nbsp; <b>Foods cravings:</b> ______<br>
<b>Dietary restrictions / limitations:</b> ______<br>
<b>Eating out frequency — Details:</b> ______<br>
<b>Grocery shopping:</b> ______ &nbsp; <b>Meal preparation and cooking:</b> ______<br>
<b>Eating environment:</b> ______<br>
<b>Summary of diet:</b> ______<br>
<b>Other:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Physical Activity</h6>
<p><b>Regular physical activity / exercise — Type:</b> ______<br>
<b>Session duration:</b> ______ &nbsp; <b>Frequency:</b> ______<br>
<b>Barriers to exercising — Details:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Knowledge/Beliefs/Attitudes</h6>
<p><b>Readiness to change (0 = low &amp; 10 = very high):</b> ____<br>
<b>Receptiveness to education:</b> ______<br>
<b>Understanding of education:</b> ______<br>
<b>Expected level of compliance:</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Objective</h5>
<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Anthropometric Measures</h6>
<p><b>Height:</b> {{talla}} &nbsp; <b>Weight:</b> {{peso}} &nbsp; <b>BMI:</b> {{imc}}<br>
<b>Dry weight:</b> ______ &nbsp; <b>Standard body weight:</b> ______ &nbsp; <b>Usual body weight:</b> ______<br>
<b>Triceps skinfold:</b> ____ mm &nbsp; <b>Mid-arm circumference:</b> ____ in<br>
<b>Waist circumference:</b> ____ in &nbsp; <b>Hip circumference:</b> ____ in &nbsp; <b>Waist-hip ratio:</b> ____<br>
<b>Recent weight gain:</b> No — <b>Amount:</b> ____ <b>Time:</b> ____<br>
<b>Recent weight loss:</b> No — <b>Amount:</b> ____ <b>Time:</b> ____<br>
<b>Lowest adult weight:</b> ____ <b>Age:</b> ____ &nbsp; <b>Highest adult weight:</b> ____ <b>Age:</b> ____<br>
<b>Other:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Medication &amp; Complementary/Alternative Medicine Use</h6>
<p><b>Dose/timing phosphate binders:</b> ______<br>
<b>Dose/timing iron supplements:</b> ______<br>
<b>Dose/timing sodium bicarbonate:</b> ______</p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Name of Medication / Supplement</th><th>Reason</th><th>Dose &amp; Frequency</th></tr>
<tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr>
</table>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Biochemical Data, Medical Test &amp; Procedures</h6>
<p><b>Number of glucose tests/day and timing:</b> ______<br>
<b>Average glucose results/patterns:</b> ______<br>
<b>Food journal:</b> No &nbsp; <b>Sick day plan:</b> No<br>
<b>Log book kept:</b> No<br>
<b>HbA1c:</b> ____ % , eAG = ____ mg/dL<br>
<b>Urine volume:</b> ____</p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Test</th><th>Result</th><th>Date</th><th>Test</th><th>Result</th><th>Date</th></tr>
<tr><td>Creatinine</td><td></td><td></td><td>BUN</td><td></td><td></td></tr>
<tr><td>Albumin</td><td></td><td></td><td>eGFR</td><td></td><td></td></tr>
<tr><td>Sodium</td><td></td><td></td><td>Potassium</td><td></td><td></td></tr>
<tr><td>Phosphorus</td><td></td><td></td><td>Calcium</td><td></td><td></td></tr>
<tr><td>Serum bicarbonate</td><td></td><td></td><td>A1c</td><td></td><td></td></tr>
<tr><td>Fasting glucose</td><td></td><td></td><td>Random glucose</td><td></td><td></td></tr>
<tr><td>Total cholesterol</td><td></td><td></td><td>HDL</td><td></td><td></td></tr>
<tr><td>LDL</td><td></td><td></td><td>Triglycerides</td><td></td><td></td></tr>
<tr><td>Vitamin D</td><td></td><td></td><td>BP</td><td>/</td><td></td></tr>
<tr><td>Hemoglobin</td><td></td><td></td><td>Hematocrit</td><td></td><td></td></tr>
<tr><td>Transferrin saturation</td><td></td><td></td><td>Ferritin</td><td></td><td></td></tr>
</table>
<p><b>Other lab results:</b> ______<br>
<b>Diagnostic studies:</b> ______<br>
<b>Other:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Nutrition Focused Physical Findings</h6>
<p><b>Nutrition-focused overall appearance:</b> ______</p>
<p><i>Gastrointestinal Related</i></p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><td>Abdominal bloating</td><td>Diarrhea</td><td>Nausea</td></tr>
<tr><td>Abdominal cramping</td><td>Early satiety</td><td>Pain on swallowing</td></tr>
<tr><td>Abdominal distension</td><td>Excessive appetite</td><td>Poor appetite</td></tr>
<tr><td>Abdominal pain</td><td>Excessive belching</td><td>Retching</td></tr>
<tr><td>Acid reflux</td><td>Excessive wind</td><td>Vomiting</td></tr>
<tr><td>Bulky stools</td><td>Heartburn</td><td>Other:</td></tr>
<tr><td>Constipation</td><td>Liquid stools</td><td>Other:</td></tr>
</table>
<p><b>PG-SGA — total score / Rating:</b> ______<br>
<b>Edema present:</b> No<br>
<b>Other:</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Assessment</h5>
<p><b>% SBW:</b> ____ &nbsp; <b>% UBW:</b> ____<br>
<b>Total energy estimated needs:</b> ____ kcal/day &nbsp; REE = ____ kcal/day (Mifflin–St Jeor, activity = Normal, stress/injury = None (1 - 1), weight = ____ lbs actual)<br>
<b>Total protein estimated needs:</b> ____ g/day for non-dialysis CKD (GFR &lt;50) (0.6 – 0.8 g/kg/day), weight = ____ lbs actual<br>
<b>Total carbohydrate estimated needs:</b> ____ (45 – 60 % total energy)<br>
<b>Total fiber estimated needs:</b> ____<br>
<b>Total fat estimated needs:</b> ____ (25 – 35 % total energy)<br>
<b>Other:</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Nutrition Diagnosis</h5>
<p><b>1.</b> ____________ is related to ____________ , as evidenced by ____________ .<br><b>Status:</b><br>
<b>2.</b> ____________ is related to ____________ , as evidenced by ____________ .<br><b>Status:</b><br>
<b>3.</b> ____________ is related to ____________ , as evidenced by ____________ .<br><b>Status:</b></p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Plan</h5>
<p><b>Interventions:</b> ______<br>
<b>Foods/ideas to emphasize:</b> ______<br>
<b>Foods to limit:</b> ______<br>
<b>Foods to avoid:</b> ______<br>
<b>Other notes:</b> ______<br>
<b>Nutrition counseling utilized:</b> ______<br>
<b>Education materials provided:</b> ______<br>
<b>Recommended sodium intake:</b> less than 2.4 g/day<br>
<b>Recommended potassium intake:</b> less than 2.4 g/day (Stages 3-4)<br>
<b>Recommended calcium intake:</b> less than 2 g/day (including dietary calcium, calcium supplementation, and calcium-based binders) (Stages 3-4)<br>
<b>Recommended phosphorus intake:</b> 800 – 1000 mg/d (if serum phosphorus &gt; 4.6 mg/dL or intact parathyroid hormone is elevated (Stages 3-4))<br>
<b>Recommended fluid intake:</b> ______<br>
<b>Recommended vitamin/mineral supplementation:</b> Vitamin D supplementation (if 25-hydroxyvitamin D &lt; 30 ng/mL). Iron: oral or intravenous supplementation (if serum ferritin &lt; 100 ng/mL and transferrin saturation &lt; 20%).</p>
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
<b>Knowledge and self-management skills — criteria:</b> ______<br>
<b>Food intake — Criteria:</b> ______<br>
<b>Nutrition status — PG-SGA result — Criteria:</b> ______<br>
<b>Weight — Criteria:</b> ______<br>
<b>Physical Activity/Function — Criteria:</b> ______<br>
<b>Biochemical data — Criteria:</b> ______<br>
<b>Blood Glucose — Criteria:</b> FBG 80–130 mg/dl, A1c &lt;7 %, pre-meal 80–130 mg/dl, post-prandial &lt;180 mg/dl.<br>
<b>Blood Pressure — Criteria:</b> &lt;130/80 mmHg.<br>
<b>Lipid Profile — Criteria:</b> Total Cholesterol &lt;200 mg/dL, LDL &lt;100 mg/dL, Triglycerides &lt;150 mg/dL.<br>
<b>Stop time:</b> ______ · <b># 15 min units:</b> ______<br>
<b>Summary notes/goals on review:</b> ______</p>

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
   . '<body class="p-4"><div class="container" style="max-width:720px;"><h4>Resultado — Lote 1</h4><ul class="list-group">';
foreach ($msgs as $m) echo '<li class="list-group-item">'.htmlspecialchars($m).'</li>';
echo '</ul><a class="btn btn-primary mt-3" href="plantillas_admin.php">Ir a Templates</a></div></body></html>';
