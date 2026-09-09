<?php
/**
 * migrar_plantillas_bulk_lote4.php
 * Inserta el LOTE 4 de plantillas Kalix:
 *   (16) LEAP Elimination Diet Checklist  — Meal Plans / Handouts
 *   (17) Medications & Supplements Log     — Handouts / Food Log
 *   (18) IFNA Medical Symptoms Questionnaire (MSQ) — Adult Assessment
 *   (19) Adult General Wellness Assessment — Adult Assessment
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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Migración lote 4</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Migrar plantillas — Lote 4</h4>
<p class="text-muted">Inserta / actualiza:
(16) LEAP Elimination Diet Checklist,
(17) Medications &amp; Supplements Log,
(18) IFNA MSQ,
(19) Adult General Wellness Assessment.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar migración</button>
<a class="btn btn-link" href="plantillas_admin.php">Volver</a></form></div></body></html>
    <?php
    exit;
}

$plantillas = [];

// (16) LEAP Elimination Diet Checklist
$plantillas[] = [
    'nombre'    => '(16) LEAP Elimination Diet Checklist',
    'categoria' => 'Meal Plans / Handouts',
    'cuerpo'    => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#2b2b2b;">
<h4 style="color:#5a9d3a;margin:0 0 8px 0;">LEAP — Elimination Diet Checklist</h4>
<p><b>ID:</b> {{paciente_cedula}}<br>
<b>Name:</b> {{paciente_nombre}}<br>
<b>DOB:</b> {{paciente_dob}}<br>
<b>LEAP Therapist:</b> {{firma_nombre}}</p>

<p><i>Mark items to eliminate during the LEAP Elimination phase.</i></p>

<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr>
  <th style="width:50%;">Proteins</th>
  <th style="width:50%;">Starches</th>
</tr>
<tr>
  <td>
    <b>Meats:</b> No All meats · No Beef · No Lamb · No Pork<br>
    <b>Poultry:</b> No All poultry · No Chicken · No Egg (chicken) · No Turkey<br>
    <b>Seafood:</b> No All seafood · No All fish · No All shellfish · No Catfish · No Clam · No Codfish · No Crab · Salmon · No Scallop · No Shrimp · No Sole · No Tilapia · No Tuna<br>
    <b>Other Proteins:</b> No Garbanzo bean · No Lentil · No Pinto bean · No Soy bean
  </td>
  <td>
    <b>Grains:</b> No All gluten grains (wheat, spelt, kamut, rye, barley) · No Amaranth · No Barley · No Buckwheat · No Corn · No Kamut · No Millet · No Oat · No Quinoa · No Rice · No Rye · No Spelt · No Wheat<br>
    <b>Starchy Vegetables:</b> No Sweet potato · No White potato · No Tapioca
  </td>
</tr>
</table>

<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th style="width:50%;">Fruit</th><th style="width:50%;">Dairy / Misc</th></tr>
<tr>
  <td>No Apple · No Apricot · No Avocado · No Banana · No Blueberry · No Cantaloupe · No Cherry · No Cranberry · No Grape · No Grapefruit · No Honeydew · No Mango · No Olive · No Orange · No Papaya · No Peach · No Pear · No Pineapple · No Plum · No Raspberry · No Strawberry · No Watermelon</td>
  <td>No All dairy · No American cheese · No Cheddar cheese · No Coffee · No Cottage cheese · No Cow's milk · No Goat's milk · No Tapioca · No Tea · No Yeast · No Yogurt · No Whey</td>
</tr>
</table>

<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th style="width:50%;">Vegetables</th><th style="width:50%;">Flavor Enhancers</th></tr>
<tr>
  <td>No All gas producing vegetables (broccoli, cauliflower, cabbage) · No All nightshade vegetables (all peppers, eggplant, tomato, white potato) · No Asparagus · No Beet · No Broccoli · No Cabbage · No Carrot · No Cauliflower · No Celery · No Cucumber · No Eggplant · No Green pea · No Green pepper · No Lettuce · No Lima bean · No Mushroom · No Onion · No Spinach · No String bean · No Tomato · No Yellow squash · No Zucchini</td>
  <td>No Basil · No Black pepper · No Cane sugar · No Carob · No Cayenne pepper · No Cinnamon · No Cocoa · No Coconut · No Cumin · No Dill · No Garlic · No Ginger · No Honey · No Leek · No Lemon · No Maple · No Mint · No Mustard · No Oregano · No Paprika · No Parsley · No Sesame · No Turmeric · No Vanilla</td>
</tr>
</table>

<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Nuts &amp; Seeds &amp; Oils</th></tr>
<tr>
  <td>No All nuts · No Almond · No Cashew · No Corn oil · No Hazelnut · No Olive oil · No Peanut · No Peanut oil · No Pecan · No Pistachio · No Sesame · No Sesame oil · No Soybean oil · No Sunflower seed · No Walnut</td>
</tr>
</table>

<div style="margin-top:30px;border-top:1px solid #ccc;padding-top:10px;">
Signed on: {{fecha_actual}}<br>
Signed by: <b>{{firma_nombre}}</b><br>
{{firma_credenciales}}
</div>
</div>
HTML
];

// (17) Medications & Supplements Log
$plantillas[] = [
    'nombre'    => '(17) Medications & Supplements Log',
    'categoria' => 'Handouts / Food Log',
    'cuerpo'    => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#2b2b2b;">
<p><b>Name:</b> {{paciente_nombre}} &nbsp; <b>DOB:</b> {{paciente_dob}}</p>
<p><i>Please list all prescription and over-the-counter medications, vitamin, mineral and nutritional supplements, herbs/botanicals and diet aids you are currently taking with the date started and other details.</i></p>

<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr>
  <th style="width:14%;">Name of Medication</th>
  <th style="width:14%;">Type of Medication</th>
  <th style="width:14%;">Purpose</th>
  <th style="width:14%;">Date Started</th>
  <th style="width:14%;">Date Stopped</th>
  <th style="width:14%;">Dose</th>
  <th style="width:16%;">Notes</th>
</tr>
<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
</table>

<div style="margin-top:30px;border-top:1px solid #ccc;padding-top:10px;">
Signed on: {{fecha_actual}}<br>
Signed by: <b>{{firma_nombre}}</b><br>
{{firma_credenciales}}
</div>
</div>
HTML
];

// (18) IFNA Medical Symptoms Questionnaire (MSQ)
$plantillas[] = [
    'nombre'    => '(18) IFNA Medical Symptoms Questionnaire (MSQ)',
    'categoria' => 'Adult Assessment',
    'cuerpo'    => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#2b2b2b;">
<h4 style="color:#5a2d82;margin:0 0 8px 0;">Integrative &amp; Functional Nutrition Academy — Medical Symptoms Questionnaire (MSQ)</h4>
<p><b>ID:</b> {{paciente_cedula}}<br>
<b>Name:</b> {{paciente_nombre}}<br>
<b>DOB:</b> {{paciente_dob}}</p>
<p><i>Rate each of the following symptoms (0 to 5) based upon your typical health profile for the <u>past 30 days</u>.</i></p>

<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Points</th><th>Frequency &amp; Severity</th></tr>
<tr><td>0</td><td>Never</td></tr>
<tr><td>1</td><td>Rarely, Effect not severe</td></tr>
<tr><td>2</td><td>Occasionally, Effect not severe</td></tr>
<tr><td>3</td><td>Occasionally, Effect severe</td></tr>
<tr><td>4</td><td>Frequently, Effect not severe</td></tr>
<tr><td>5</td><td>Frequently, Effect severe</td></tr>
</table>

<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Head</th><th>Score</th><th>Eyes</th><th>Score</th><th>Ears</th><th>Score</th></tr>
<tr><td>Headaches</td><td></td><td>Watery / itchy eyes</td><td></td><td>Itchy ears</td><td></td></tr>
<tr><td>Faintness</td><td></td><td>Yellowing eyes</td><td></td><td>Earaches, ear infections</td><td></td></tr>
<tr><td>Dizziness</td><td></td><td>Swollen, reddened, sticky eyelids</td><td></td><td>Drainage from ear</td><td></td></tr>
<tr><td></td><td></td><td>Bags, dark circles</td><td></td><td>Ringing</td><td></td></tr>
<tr><td></td><td></td><td>Night vision problems</td><td></td><td>Hearing loss</td><td></td></tr>
<tr><td></td><td></td><td>Blurred vision</td><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td>Loss peripheral vision</td><td></td><td></td><td></td></tr>
<tr><td><b>Total</b></td><td><b>0</b></td><td><b>Total</b></td><td><b>0</b></td><td><b>Total</b></td><td><b>0</b></td></tr>
</table>

<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Nose</th><th>Score</th><th>Mouth / Throat</th><th>Score</th><th>Digestive Tract / GI</th><th>Score</th></tr>
<tr><td>Stuffy nose</td><td></td><td>Chronic cough</td><td></td><td>Nausea</td><td></td></tr>
<tr><td>Sinus problems</td><td></td><td>Gagging / throat clearing</td><td></td><td>Vomiting</td><td></td></tr>
<tr><td>Hay fever</td><td></td><td>Sore throat</td><td></td><td>Diarrhea</td><td></td></tr>
<tr><td>Sneezing attacks</td><td></td><td>Hoarseness</td><td></td><td>Constipation</td><td></td></tr>
<tr><td>Excessive mucous</td><td></td><td>Swollen / discolored tongue</td><td></td><td>Alternating diarrhea &amp; constipation</td><td></td></tr>
<tr><td>Loss sense of smell</td><td></td><td>Burning tongue</td><td></td><td>Bloating</td><td></td></tr>
<tr><td></td><td></td><td>Coating on tongue</td><td></td><td>Belching</td><td></td></tr>
<tr><td></td><td></td><td>Chewing problems</td><td></td><td>Gas / flatulence</td><td></td></tr>
<tr><td></td><td></td><td>Swallowing problems</td><td></td><td>Heartburn</td><td></td></tr>
<tr><td></td><td></td><td>Canker sores</td><td></td><td>Upper GI pain</td><td></td></tr>
<tr><td></td><td></td><td>Fever blisters</td><td></td><td>Lower abdominal pain</td><td></td></tr>
<tr><td></td><td></td><td>Cracks corner of mouth</td><td></td><td></td><td></td></tr>
<tr><td><b>Total</b></td><td><b>0</b></td><td><b>Total</b></td><td><b>0</b></td><td><b>Total</b></td><td><b>0</b></td></tr>
</table>

<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Nails</th><th>Score</th><th>Heart</th><th>Score</th><th>Joints / Muscle / Bone</th><th>Score</th></tr>
<tr><td>Spoon shaped</td><td></td><td>Irregular / skipped beats</td><td></td><td>Pain or aches in joints</td><td></td></tr>
<tr><td>Brittle, cracking</td><td></td><td>Rapid / pounding beats</td><td></td><td>Arthritis</td><td></td></tr>
<tr><td>Discolored</td><td></td><td>Chest pain</td><td></td><td>Stiffness / limited movement</td><td></td></tr>
<tr><td>White spots</td><td></td><td></td><td></td><td>Pain or aches in muscles</td><td></td></tr>
<tr><td>Lines / Stripes</td><td></td><td></td><td></td><td>Feeling of weakness or loss of strength</td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td>Restless legs</td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td>Bone pain</td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td>Broken bones</td><td></td></tr>
<tr><td><b>Total</b></td><td><b>0</b></td><td><b>Total</b></td><td><b>0</b></td><td><b>Total</b></td><td><b>0</b></td></tr>
</table>

<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Hair</th><th>Score</th><th>Lungs</th><th>Score</th><th>Weight</th><th>Score</th></tr>
<tr><td>Hair thinning</td><td></td><td>Chest congestion</td><td></td><td>Underweight</td><td></td></tr>
<tr><td>Hair loss</td><td></td><td>Asthma or bronchitis</td><td></td><td>Overweight</td><td></td></tr>
<tr><td>Loss of outer eyebrow hair</td><td></td><td>Shortness of breath</td><td></td><td>Obese</td><td></td></tr>
<tr><td>Premature graying</td><td></td><td>Difficulty breathing</td><td></td><td>Weight loss (&gt; 5 - 10 lbs)</td><td></td></tr>
<tr><td>Easy hair pluckability</td><td></td><td></td><td></td><td>Weight gain (&gt; 5 - 10 lbs)</td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td>Fluid retention</td><td></td></tr>
<tr><td><b>Total</b></td><td><b>0</b></td><td><b>Total</b></td><td><b>0</b></td><td><b>Total</b></td><td><b>0</b></td></tr>
</table>

<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Skin</th><th>Score</th><th>Energy / Sleep</th><th>Score</th><th>Emotions</th><th>Score</th></tr>
<tr><td>Acne</td><td></td><td>Fatigue</td><td></td><td>Mood swings</td><td></td></tr>
<tr><td>Hives, rashes</td><td></td><td>Lethargy</td><td></td><td>Anxiety, worry, fear, nervousness</td><td></td></tr>
<tr><td>Dry skin</td><td></td><td>Hyperactivity</td><td></td><td>Anger, irritability, agitation</td><td></td></tr>
<tr><td>Bumps on back of arms</td><td></td><td>Insomnia</td><td></td><td>Depression</td><td></td></tr>
<tr><td>Flushing</td><td></td><td>Sleep disruptions</td><td></td><td></td><td></td></tr>
<tr><td>Excessive sweating</td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td><b>Total</b></td><td><b>0</b></td><td><b>Total</b></td><td><b>0</b></td><td><b>Total</b></td><td><b>0</b></td></tr>
</table>

<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Immune</th><th>Score</th><th>Genitourinary</th><th>Score</th><th>Neurological</th><th>Score</th></tr>
<tr><td>Colds</td><td></td><td>Frequent or urgent urination</td><td></td><td>Poor memory</td><td></td></tr>
<tr><td>Flu</td><td></td><td>Itching</td><td></td><td>Confusion</td><td></td></tr>
<tr><td>Chronic infections</td><td></td><td>Discharge</td><td></td><td>Poor concentration / "brain fog"</td><td></td></tr>
<tr><td></td><td></td><td>Incontinence</td><td></td><td>Poor physical coordination</td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td>Loss of balance</td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td>Tingling in hands or feet</td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td>Stuttering or stammering</td><td></td></tr>
<tr><td></td><td></td><td></td><td></td><td>Slurred speech</td><td></td></tr>
<tr><td><b>Total</b></td><td><b>0</b></td><td><b>Total</b></td><td><b>0</b></td><td><b>Total</b></td><td><b>0</b></td></tr>
</table>

<p><b>GRAND TOTAL = 0</b><br>
<b>Key:</b> the higher the score, the greater the impact on the individual.</p>
<table style="border-collapse:collapse;width:60%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><td>0 - 15</td><td>Fair</td></tr>
<tr><td>16 - 25</td><td>Moderate</td></tr>
<tr><td>26 - 50</td><td>Major</td></tr>
<tr><td>&gt; 50</td><td>Severe</td></tr>
</table>

<div style="margin-top:30px;border-top:1px solid #ccc;padding-top:10px;">
Signed on: {{fecha_actual}}<br>
Signed by: <b>{{firma_nombre}}</b><br>
{{firma_credenciales}}
</div>
</div>
HTML
];

// (19) Adult General Wellness Assessment
$plantillas[] = [
    'nombre'    => '(19) Adult General Wellness Assessment',
    'categoria' => 'Adult Assessment',
    'cuerpo'    => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#2b2b2b;">
<p><b>Name:</b> {{paciente_nombre}}<br>
<b>DOB:</b> {{paciente_dob}}</p>

<p><b>Referred by:</b> ______________________<br>
<b>Start time:</b> {{hora_inicio}}</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Subjective</h5>
<p><b>Reason for visit:</b> ______________________<br>
<b>Medical diagnosis:</b> ______________________<br>
<b>History of presenting condition:</b> ______________________<br>
<b>Treatments tried:</b> ______________________<br>
<b>Questions/concerns for today:</b> ______________________</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Health &amp; Medical History</h6>
<p><b>Current health (rating):</b> ____<br>
<b>Stress rating (0 = no stress, 10 = extreme stress) — Details:</b> ______<br>
<b>Other:</b> ______<br>
<b>Other medical conditions:</b> ______<br>
<b>Past surgeries / hospitalizations:</b> ______<br>
<b>Food allergies:</b> ______ &nbsp; <b>Food intolerances and sensitivities:</b> ______ &nbsp; <b>Other allergies:</b> ______<br>
<b>Previous muscle, bone or joint illnesses/injuries — Details:</b> ______<br>
<b>Current muscle, bone or joint problems — Details:</b> ______<br>
<b>Currently pregnant:</b> ______ &nbsp; <b>Due date:</b> ______<br>
<b>Past pregnancies:</b> ______ &nbsp; <b>Details:</b> ______<br>
<b>Currently lactating:</b> ______ &nbsp; <b>Duration:</b> ______<br>
<b>Other:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Family Medical History</h6>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><td>Cancer</td><td>High blood cholesterol</td><td>Liver disease</td></tr>
<tr><td>Diabetes</td><td>High blood pressure</td><td>Thyroid disease</td></tr>
<tr><td>Heart disease</td><td>Kidney disease</td><td>Obesity</td></tr>
</table>
<p><b>Other family medical history:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Social History</h6>
<p><b>Marital status:</b> ______<br>
<b>Highest level of education:</b> ______<br>
<b>Occupation:</b> ______ &nbsp; <b>Retired:</b> ______<br>
<b>Work hours:</b> ______<br>
<b>Members of household:</b> ______<br>
<b>Current smoking status — Amount:</b> ______<br>
<b>Past smoking status — Amount:</b> ______<br>
<b>Regular recreational drug use:</b> ______<br>
<b>Problems with alcohol or drug use:</b> ______<br>
<b>Treatment for substance use:</b> ______ &nbsp; <b>Details:</b> ______<br>
<b>Amount of sleep on week nights:</b> ______<br>
<b>Amount of sleep on weekend nights:</b> ______</p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><td>Cannot get to sleep within 30 mins</td><td>Wake because of hunger</td><td>Wake due to pain</td></tr>
<tr><td>Wake up during the night</td><td>Feel too cold to sleep</td><td>Wake because of family members</td></tr>
<tr><td>Wake to use the bathroom</td><td>Feel too hot to sleep</td><td>Wake up tired</td></tr>
<tr><td>Cough or snore loudly</td><td>Have bad dreams</td><td>Feel sleepy during the day</td></tr>
</table>
<p><b>Other:</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Nutrition</h5>
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
<p><b>Summary of diet:</b> ______<br>
<b>Regular caffeine intake — Type:</b> ______ &nbsp; <b>Amount:</b> ______<br>
<b>Frequency of alcohol intake — Type:</b> ______ &nbsp; <b>Quantity:</b> ______<br>
<b>Food likes:</b> ______ &nbsp; <b>Food dislikes:</b> ______<br>
<b>Foods cravings:</b> ______<br>
<b>Dietary restrictions / limitations:</b> ______<br>
<b>Eating out frequency — Details:</b> ______<br>
<b>Other:</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Physical Activity</h5>
<p><b>Current fitness level:</b> ______</p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Activity Details</th><th>Frequency &amp; Duration</th><th>Difficulty / Intensity</th></tr>
<tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr>
</table>
<p><b>Barriers to exercising — Details:</b> ______<br>
<b>Favorite activities:</b> ______<br>
<b>Other:</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Objective</h5>
<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Weight History</h6>
<p><b>Height:</b> {{talla}} &nbsp; <b>Weight:</b> {{peso}} &nbsp; <b>BMI:</b> {{imc}}<br>
<b>Ideal body weight (IBW):</b> ____ lbs (Hamwi equation) &nbsp; <b>%IBW:</b> ____<br>
<b>Waist circumference:</b> ____ in &nbsp; <b>Hip circumference:</b> ____ in &nbsp; <b>Waist-hip ratio:</b> ____<br>
<b>Recent weight gain — Amount:</b> ____ <b>Time:</b> ____<br>
<b>Recent weight loss — Amount:</b> ____ <b>Time:</b> ____<br>
<b>Lowest adult weight:</b> ____ <b>Age:</b> ____<br>
<b>Highest adult weight:</b> ____ <b>Age:</b> ____<br>
<b>Goal weight:</b> ______<br>
<b>Other:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Physical Findings</h6>
<p><b>Details:</b> ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Medication &amp; Supplements</h6>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Name of Medication / Supplement</th><th>Reason</th><th>Dose &amp; Frequency</th></tr>
<tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr>
<tr><td></td><td></td><td></td></tr>
</table>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Lab Results</h6>
<p><b>Lab results:</b> ______<br>
<b>Blood pressure:</b> ______ / ______<br>
<b>Diagnostic studies:</b> ______<br>
<b>Other:</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Assessment</h5>
<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Estimated Energy &amp; Nutrition Intake</h6>
<p><b>Total energy intake:</b> ____ kcal/day<br>
<b>Total protein intake:</b> ____ g/day<br>
<b>Total carbohydrate intake:</b> ____ g/day (breakfast ____, snack ____, lunch ____, snack ____, evening meal ____, evening snack ____).<br>
<b>Total fiber intake:</b> ______<br>
<b>Total fat intake:</b> ____ g/day<br>
<b>Oral fluid intake:</b> ____ oz/day from ______</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Estimated Energy &amp; Nutrition Needs</h6>
<p><b>Total energy estimated needs:</b> ____ kcal/day (-200 kcal (-837 kJ) for weight loss) &nbsp; REE = ____ kcal/day using Mifflin–St Jeor Equation (Adults), Activity Factor = Light ( - ), Stress/Injury Factor = None (1 - 1), weight = ____ lbs (actual body weight).<br>
<b>Total protein estimated needs:</b> ____ g/day, for normal nutrition ( - ), weight = ____ lbs (actual body weight)<br>
<b>Total carbohydrate estimated needs:</b> - ____ (45 – 60 % total energy)<br>
<b>Total fiber estimated needs:</b> ______<br>
<b>Total fat estimated needs:</b> - ____ (25 – 35 % total energy)<br>
<b>Total fluid estimated needs:</b> ____ (normal nutrition using weight = ____ lbs actual body weight)</p>

<h6 style="color:#5a2d82;margin:10px 0 4px 0;">Comprehension &amp; Motivation</h6>
<p><b>Readiness to change (0 = low &amp; 10 = very high):</b> ____<br>
<b>Receptiveness to education:</b> ______<br>
<b>Understanding of education:</b> ______<br>
<b>Expected level of compliance:</b> ______<br>
<b>Other:</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Plan</h5>
<p><b>Education materials provided:</b> ______<br>
<b>Food log for:</b> ______<br>
<b>Meal plan:</b> ______</p>
<p><i><b>Short Term Dietary Goals</b></i><br>
<b>1.</b> ______ · <b>Status:</b><br>
<b>2.</b> ______ · <b>Status:</b><br>
<b>3.</b> ______ · <b>Status:</b></p>
<p><i><b>Short Term Exercise Goals</b></i><br>
<b>1.</b> ______ · <b>Status:</b><br>
<b>2.</b> ______ · <b>Status:</b><br>
<b>3.</b> ______ · <b>Status:</b></p>
<p><b>Other goals:</b> ______<br>
<b>Action plan:</b> ______<br>
<b>Follow up:</b> ______<br>
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
   . '<body class="p-4"><div class="container" style="max-width:720px;"><h4>Resultado — Lote 4</h4><ul class="list-group">';
foreach ($msgs as $m) echo '<li class="list-group-item">'.htmlspecialchars($m).'</li>';
echo '</ul><a class="btn btn-primary mt-3" href="plantillas_admin.php">Ir a Templates</a></div></body></html>';
