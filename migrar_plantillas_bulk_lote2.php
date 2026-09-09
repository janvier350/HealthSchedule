<?php
/**
 * migrar_plantillas_bulk_lote2.php
 * Inserta el LOTE 2 de plantillas Kalix:
 *   (6)  Food Diary                       — Handouts / Food Log
 *   (7)  Insurance Appeal Letter (Celiac) — Insurance Letters
 *   (8)  Pediatric Nutrition Assessment   — Pediatric Assessment
 *   (9)  LEAP Health History Intake       — Adult Assessment
 *   (10) LEAP Food Reintroduction Chart   — Meal Plans / Handouts
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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Migración lote 2</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Migrar plantillas — Lote 2</h4>
<p class="text-muted">Inserta / actualiza:
(6) Food Diary, (7) Insurance Appeal Letter (Celiac), (8) Pediatric Nutrition Assessment,
(9) LEAP Health History Intake, (10) LEAP Food Reintroduction Chart.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar migración</button>
<a class="btn btn-link" href="plantillas_admin.php">Volver</a></form></div></body></html>
    <?php
    exit;
}

$plantillas = [];

// (6) Food Diary
$plantillas[] = [
    'nombre'    => '(6) Food Diary',
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
<li>Select the location the food or drink is prepared (home, restaurant or other). If prepared in a restaurant, enter the restaurant's name. If other, describe (e.g., my mother's place, work Christmas party).</li>
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

// (7) Insurance Appeal Letter (Celiac Disease)
$plantillas[] = [
    'nombre'    => '(7) Insurance Appeal Letter (Celiac)',
    'categoria' => 'Insurance Letters',
    'cuerpo'    => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#2b2b2b;">
<p><b>{{firma_nombre}}</b>, LEAP Therapist, MS, RD, LD, CLT<br>
SRoss Nutrition PLLC<br>
Phone: ______<br>
Email: ______</p>

<p style="margin-top:24px;"><b>Re:</b> {{paciente_nombre}} &nbsp; <b>DOB:</b> {{paciente_dob}} &nbsp; <b>ID:</b> {{paciente_cedula}}</p>

<p>To whom it may concern:</p>

<p>I saw ______ on ______ because she was diagnosed with Celiac Disease on XX. Celiac disease is an autoimmune disease where proteins found in wheat, barley, and rye trigger an immune system response that results in damage to the small intestine. Failure to follow a gluten free diet can cause a host of preventable diseases and conditions, such as anemia, osteoporosis, malnutrition, cancer, pregnancy complication or other autoimmune diseases. Not only can this be life threatening, but most of these secondary conditions also require a lot of costly testing and treatments. The only treatment for Celiac disease is a lifelong strict gluten free diet. There is no drug or other treatment.</p>

<p>I received a denial of XX claim on XX, and I would like to appeal on her behalf, on the basis that her knowledge and ability to follow a gluten free diet is vital, because a gluten free diet is the only form of treatment available. It is as essential as insulin is for someone with Type 1 diabetes. Nutrition services are necessary for her care, and are specifically recommended in the NIH Consensus statement on Celiac Disease as one of the key elements for the management of CD. <a href="http://consensus.nih.gov/2004/2004CeliacDisease118..." target="_blank">http://consensus.nih.gov/2004/2004CeliacDisease118...</a> Most research centers and gastrointestinal groups recommend seeing a knowledgeable dietitian, as people with Celiac Disease, specific needs to insure both short term recovery and long-term health.</p>

<p>I hope you will consider covering this session. Not only is nutrition counseling necessary for clients with Celiac Disease, but this care will also translate into significant monetary savings in the long run.</p>

<p>Thank you for your consideration.</p>

<p>Respectfully,</p>

<p>SRoss Nutrition PLLC</p>

<div style="margin-top:30px;border-top:1px solid #ccc;padding-top:10px;">
Signed on: {{fecha_actual}}<br>
Signed by: <b>{{firma_nombre}}</b><br>
{{firma_credenciales}}
</div>
</div>
HTML
];

// (8) Pediatric Nutrition Assessment
$plantillas[] = [
    'nombre'    => '(8) Pediatric Nutrition Assessment',
    'categoria' => 'Pediatric Assessment',
    'cuerpo'    => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#2b2b2b;">
<p><b>ID:</b> {{paciente_cedula}}<br>
<b>Name:</b> {{paciente_nombre}}<br>
<b>DOB:</b> {{paciente_dob}}</p>

<p><b>Referred by:</b> ______________________<br>
<b>Start time:</b> {{hora_inicio}}</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Subjective</h5>
<p><b>Reason for visit:</b> ______________________<br>
<b>History of presenting condition:</b> ______________________<br>
<b>Questions/concerns for today:</b> ______________________</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Health &amp; Medical History</h6>
<p><b>Medical history:</b> ______<br>
<b>Immunizations up-to-date — Details:</b> ______<br>
<b>Past surgeries / hospitalizations:</b> ______<br>
<b>Food allergies:</b> ______<br>
<b>Food intolerances and sensitivities:</b> ______<br>
<b>Other allergies:</b> ______<br>
<b>Other:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Family Medical History</h6>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><td>Cancer</td><td>High blood cholesterol</td><td>Liver disease</td></tr>
<tr><td>Diabetes</td><td>High blood pressure</td><td>Thyroid disease</td></tr>
<tr><td>Heart disease</td><td>Kidney disease</td><td>Obesity</td></tr>
</table>
<p><b>Other family medical history:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Social History</h6>
<p><b>School:</b> ______ &nbsp; <b>Year/grade level:</b> ______<br>
<b>School contact — Phone:</b> ______<br>
<b>Living arrangements:</b> ______<br>
<b>Siblings:</b> ______<br>
<b>Other:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Diet History</h6>
<p><b>Date:</b> ______</p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Meal</th><th>Place Prepared</th><th>Food &amp; Beverages</th></tr>
<tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr>
</table>
<p><b>Date:</b> ______</p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Meal</th><th>Place Prepared</th><th>Food &amp; Beverages</th></tr>
<tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr>
</table>

<p><b>Summary of diet:</b></p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><td>Fast eater</td><td>High milk intake</td><td>Limited variety of foods (&lt;5/day)</td></tr>
<tr><td>Erratic eating pattern</td><td>Low fruit/vegetable intake</td><td>Eats fewer than 3 times a day</td></tr>
<tr><td>Eats too much</td><td>High sugar/sweet intake</td><td>Refuses meals</td></tr>
<tr><td>Dislikes healthy food</td><td>&gt;50% of meals eaten away from home</td><td>Eats too little</td></tr>
<tr><td>Poor snack choices</td><td>Refuses solid foods</td><td>Has a poor appetite</td></tr>
<tr><td>Use food as a bribe or reward</td><td>Refuses many foods</td><td>Sensory issues with food</td></tr>
<tr><td>Erratic mealtimes</td><td>Picky eater</td><td>Meals eaten away from the table</td></tr>
<tr><td>Snacks too much</td><td>Prefers cold food</td><td>Family members eat meals separately</td></tr>
<tr><td>High juice intake</td><td>Prefers hot food</td><td>Other:</td></tr>
</table>
<p><b>Food likes:</b> ______<br>
<b>Food dislikes:</b> ______<br>
<b>Dietary restrictions / limitations:</b> ______<br>
<b>Eating out frequency — Details:</b> ______<br>
<b>Grocery shopping:</b> ______ &nbsp; <b>Meal preparation and cooking:</b> ______<br>
<b>Eating environment:</b> ______<br>
<b>Other:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Physical Activity</h6>
<p><b>Regular physical activity / exercise — Type:</b> ______<br>
<b>Session duration:</b> ______ &nbsp; <b>Frequency:</b> ______<br>
<b>Barriers to exercising — Details:</b> ______<br>
<b>Favorite activities:</b> ______<br>
<b>Screen time:</b> ______<br>
<b>Other:</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Objective</h5>
<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Weight History</h6>
<p><b>Weight:</b> {{peso}} &nbsp; <b>Height:</b> {{talla}} &nbsp; <b>BMI:</b> {{imc}}<br>
<b>Growth pattern indices, percentile ranks (Weight-for-age, 0–20 yrs):</b> ____ percentile rank (CDC Growth Charts)<br>
<b>Growth pattern indices, percentile ranks (Length-for-age, 0–20 yrs):</b> ____ percentile rank (CDC Growth Charts)<br>
<b>Growth pattern indices, percentile ranks (BMI-for-age, 2–20 yrs):</b> ____ percentile rank (CDC Growth Charts)<br>
<b>Other:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Physical Findings — Gastrointestinal</h6>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><td>Abdominal bloating</td><td>Diarrhea</td><td>Nausea</td></tr>
<tr><td>Abdominal cramping</td><td>Early satiety</td><td>Pain on swallowing</td></tr>
<tr><td>Abdominal distension</td><td>Excessive appetite</td><td>Poor appetite</td></tr>
<tr><td>Abdominal pain</td><td>Excessive belching</td><td>Retching</td></tr>
<tr><td>Acid reflux</td><td>Excessive wind</td><td>Vomiting</td></tr>
<tr><td>Bulky stools</td><td>Heartburn</td><td>Other:</td></tr>
<tr><td>Constipation</td><td>Liquid stools</td><td>Other:</td></tr>
</table>
<p><b>Other:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Medication &amp; Supplements</h6>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Name of Medication / Supplement</th><th>Reason</th><th>Dose &amp; Frequency</th></tr>
<tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr>
</table>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Lab Results</h6>
<p><b>Lab results:</b> ______<br>
<b>Diagnostic studies:</b> ______<br>
<b>Other:</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Assessment</h5>
<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Estimated Energy &amp; Nutrition Needs</h6>
<p><b>Total energy estimated needs:</b> ____ kcal/day &nbsp; REE = ____ kcal/day using EER for Healthy Children &amp; Infants (0–18 yrs), Activity Factor = Normal ( - ), Stress/Injury Factor = None (1 - 1), weight = ____ lbs (actual body weight).<br>
<b>Total protein estimated needs:</b> ____ g/day, for normal nutrition ( - ), weight = ____ lbs (actual body weight)<br>
<b>Total fiber estimated needs:</b> ____<br>
<b>Total fat estimated needs:</b> ____ (25 – 35 % total energy)<br>
<b>Total fluid estimated needs:</b> ____ (normal nutrition using weight = ____ lbs actual body weight)</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Comprehension &amp; Motivation</h6>
<p><b>Readiness to change (0 = low &amp; 10 = very high):</b> ____<br>
<b>Receptiveness to education:</b> ______<br>
<b>Understanding of education:</b> ______<br>
<b>Expected level of compliance:</b> ______<br>
<b>Other:</b> ______<br>
<b>Stop time:</b> ______ &nbsp; <b># 15 min units:</b> ______<br>
<b>Summary notes/goals on review:</b> ______<br>
<b>Recommendations:</b> ______</p>

<div style="margin-top:30px;border-top:1px solid #ccc;padding-top:10px;">
Electronically Signed By: <b>{{firma_nombre}}</b><br>
{{firma_credenciales}}
</div>
</div>
HTML
];

// (9) LEAP Health History Intake
$plantillas[] = [
    'nombre'    => '(9) LEAP Health History Intake',
    'categoria' => 'Adult Assessment',
    'cuerpo'    => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#2b2b2b;">
<h4 style="color:#5a9d3a;margin:0 0 8px 0;">LEAP — Lifestyle Eating and Performance</h4>

<p><b>Legal name:</b> {{paciente_nombre}}<br>
<b>Referring physician/dietitian:</b> ______<br>
<b>Gender:</b> {{sexo_paciente}}<br>
<b>Address:</b> ______ &nbsp; <b>City:</b> ______<br>
<b>State:</b> ______ &nbsp; <b>Zip code:</b> ______<br>
<b>Primary phone number:</b> {{paciente_telefono}}<br>
<b>Secondary phone number:</b> ______<br>
<b>Email address:</b> {{paciente_email}}<br>
<b>Preferred contact method/s:</b> Email — Phone —<br>
<b>Can we leave a message:</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Health History</h5>
<p><b>Chief Complaints:</b> ______<br>
<b>Treatment History (What have you tried?):</b> ______<br>
<b>Ever tested for Celiac Disease? When / Results?</b> ______</p>

<p><b>What Medications and Supplements are you currently taking</b> for this or any other condition? (Over-the-counter &amp; Prescription — specify which medications for which condition):</p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Name of Medication</th><th>Reason</th><th>Dose &amp; Frequency</th><th>Notes</th></tr>
<tr><td></td><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td><td></td></tr>
</table>

<p><b>What supplement do you currently take?</b> ______<br>
<b>Does anyone in your family, including you, have allergies of any kind</b> (in other words, cat, dust, pollen, food, meds, etc.)? ______<br>
<b>Are there any foods that "don't agree" with you?</b> ______</p>

<p>Do you experience any of the following symptoms or conditions on a frequent basis? Please indicate frequency and severity below:</p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Symptom</th><th>Frequency</th><th>Severity</th><th>Symptom</th><th>Frequency</th><th>Severity</th></tr>
<tr><td>Fatigued</td><td></td><td></td><td>Dark circles / puffy eyes</td><td></td><td></td></tr>
<tr><td>Restless / hyperactive</td><td></td><td></td><td>Muscle or joint pain</td><td></td><td></td></tr>
<tr><td>Sleepy during day, insomnia at night</td><td></td><td></td><td>Water retention / weight fluctuations (shoes, jewelry, watches, clothes fit tighter or looser day-to-day or weekly)</td><td></td><td></td></tr>
<tr><td>General malaise (feel lousy)</td><td></td><td></td><td>Heartburn / reflux</td><td></td><td></td></tr>
<tr><td>Depressed / mood swings / irritability</td><td></td><td></td><td>Diarrhea / loose stools</td><td></td><td></td></tr>
<tr><td>Headaches other than migraine</td><td></td><td></td><td>Constipation</td><td></td><td></td></tr>
<tr><td>Migraine</td><td></td><td></td><td>Bloating, distention, gas</td><td></td><td></td></tr>
<tr><td>Stuffy nose</td><td></td><td></td><td>Abdominal pain</td><td></td><td></td></tr>
<tr><td>Throat clearing</td><td></td><td></td><td></td><td></td><td></td></tr>
</table>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Eating Habits / Lifestyle Considerations</h5>
<p><b>What is your occupation:</b> ______<br>
<b>How often do you cook from scratch?</b> ______<br>
<b>How often do you eat out?</b> ______<br>
<b>Do you tend to skip meals?</b> ______<br>
<b>Do you ever eat for comfort?</b> ______<br>
<b>What situation(s) cause you to eat for comfort?</b> ______<br>
<b>What areas of your life do your health problems interfere with?</b> ______<br>
<b>What foods (if any) do you crave?</b> ______<br>
<b>Is there any food you could not give up for 2 weeks?</b> ______<br>
<b>On a scale from 1–10, how badly are these problems affecting your life?</b> ____<br>
<b>On a scale from 1–10, how committed are you to getting better?</b> ____</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Notes / Recommended Therapy Options</h5>
<p><b>Anything else you'd like to share:</b> ______</p>

<div style="margin-top:30px;border-top:1px solid #ccc;padding-top:10px;">
Signed on: {{fecha_actual}}<br>
Signed by: <b>{{firma_nombre}}</b><br>
{{firma_credenciales}}
</div>
</div>
HTML
];

// (10) LEAP Food Reintroduction Chart
$plantillas[] = [
    'nombre'    => '(10) LEAP Food Reintroduction Chart',
    'categoria' => 'Meal Plans / Handouts',
    'cuerpo'    => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#2b2b2b;">
<h4 style="color:#5a9d3a;margin:0 0 8px 0;">LEAP — Food Reintroduction Chart</h4>

<p><b>ID:</b> {{paciente_cedula}}<br>
<b>Name:</b> {{paciente_nombre}}<br>
<b>DOB:</b> {{paciente_dob}}</p>

<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Phase 1 (Days 1–14)</th><th>Phase 2 *</th><th>Phase 3 *</th><th>Phase 4 *</th><th>Phase 5 *</th></tr>
<tr><td colspan="5"><b>Proteins</b></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td colspan="5"><b>Grains &amp; Starches</b></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td colspan="5"><b>Vegetables</b></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td colspan="5"><b>Fruits</b></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td colspan="5"><b>Dairy &amp; Miscellaneous</b></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td colspan="5"><b>Nuts &amp; Seeds &amp; Oils</b></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td colspan="5"><b>Flavor Enhancers</b></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td colspan="5"><b>Other</b></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td></tr>
</table>

<p><small>* Add foods only after the previous phase has been tolerated. Consult your dietitian before reintroducing.</small></p>

<div style="margin-top:30px;border-top:1px solid #ccc;padding-top:10px;">
Signed on: {{fecha_actual}}<br>
Signed by: <b>{{firma_nombre}}</b><br>
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
   . '<body class="p-4"><div class="container" style="max-width:720px;"><h4>Resultado — Lote 2</h4><ul class="list-group">';
foreach ($msgs as $m) echo '<li class="list-group-item">'.htmlspecialchars($m).'</li>';
echo '</ul><a class="btn btn-primary mt-3" href="plantillas_admin.php">Ir a Templates</a></div></body></html>';
