<?php
/**
 * migrar_plantillas_bulk_lote7.php
 * LOTE 7 (extra) — 1 plantilla adicional recibida después del lote 6:
 *   (32) Adult Nutrition Assessment (IBW Hamwi + Mifflin-St Jeor + Waist/Hip)
 *      — Adult Assessment
 *
 * Es una variante del (2) Adult Nutrition Assessment (short) con:
 *   - Ideal Body Weight (Hamwi) y % IBW
 *   - Circunferencia de cintura / cadera y waist-hip ratio
 *   - Necesidades energéticas por Mifflin-St Jeor y macros con carbohidratos
 *   - Fluidos calculados con adjusted body weight
 * No incluye PES / Intervention Nutricional que sí trae el (2), así que
 * queda como plantilla independiente para que la Dra. elija según caso.
 *
 * Idempotente: upsert por nombre. SISTEMA-only, POST-driven.
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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Migración lote 7 (plantillas)</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Migrar plantillas — Lote 7 (extra)</h4>
<p class="text-muted">Inserta / actualiza <b>(32) Adult Nutrition Assessment (IBW Hamwi + Mifflin-St Jeor + Waist/Hip)</b>.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar migración</button>
<a class="btn btn-link" href="plantillas_admin.php">Volver</a></form></div></body></html>
    <?php
    exit;
}

$plantillas = [];

// (32) Adult Nutrition Assessment (IBW Hamwi + Mifflin-St Jeor + Waist/Hip)
$plantillas[] = [
    'nombre'    => '(32) Adult Nutrition Assessment (IBW Hamwi + Mifflin-St Jeor + Waist/Hip)',
    'categoria' => 'Adult Assessment',
    'cuerpo'    => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#2b2b2b;">
<p><b>Name:</b> {{paciente_nombre}}<br>
<b>DOB:</b> {{paciente_dob}}<br>
<b>Referred by:</b> ______________________<br>
<b>Start time:</b> {{hora_inicio}}</p>

<h5 style="color:#1f4e79;margin:14px 0 6px 0;border-bottom:1px solid #cfd8e3;padding-bottom:3px;">Subjective</h5>
<p><b>Reason for visit:</b> ______________________<br>
<b>Medical diagnosis:</b> ______________________<br>
<b>Other:</b> ______</p>

<h5 style="color:#1f4e79;margin:14px 0 6px 0;border-bottom:1px solid #cfd8e3;padding-bottom:3px;">Health &amp; Medical History</h5>
<p><b>Medical history:</b> ______<br>
<b>Food allergies:</b> ______<br>
<b>Food intolerances and sensitivities:</b> ______<br>
<b>Other:</b> ______</p>

<h5 style="color:#1f4e79;margin:14px 0 6px 0;border-bottom:1px solid #cfd8e3;padding-bottom:3px;">Family Medical History</h5>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><td>☐ Cancer</td><td>☐ High blood cholesterol</td><td>☐ Liver disease</td></tr>
<tr><td>☐ Diabetes</td><td>☐ High blood pressure</td><td>☐ Thyroid disease</td></tr>
<tr><td>☐ Heart disease</td><td>☐ Kidney disease</td><td>☐ Obesity</td></tr>
</table>
<p><b>Other family medical history:</b> ______________________</p>

<h5 style="color:#1f4e79;margin:14px 0 6px 0;border-bottom:1px solid #cfd8e3;padding-bottom:3px;">Social History</h5>
<p><b>Social history:</b> ______________________</p>

<h5 style="color:#1f4e79;margin:14px 0 6px 0;border-bottom:1px solid #cfd8e3;padding-bottom:3px;">Diet History</h5>
<p><b>Date:</b> ______</p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><th>Meal</th><th>Time</th><th>Food / Beverages Consumed</th></tr>
<tr><td>Breakfast</td><td></td><td></td></tr>
<tr><td>Snack</td><td></td><td></td></tr>
<tr><td>Lunch</td><td></td><td></td></tr>
<tr><td>Snack</td><td></td><td></td></tr>
<tr><td>Evening meal</td><td></td><td></td></tr>
<tr><td>Evening snack</td><td></td><td></td></tr>
<tr><td>Other snacks</td><td></td><td></td></tr>
<tr><td>Other beverages</td><td></td><td></td></tr>
</table>
<p><b>Dietary restrictions / limitations:</b> ______<br>
<b>Summary of diet:</b> ______<br>
<b>Other:</b> ______</p>

<h5 style="color:#1f4e79;margin:14px 0 6px 0;border-bottom:1px solid #cfd8e3;padding-bottom:3px;">Physical Activity</h5>
<p><b>Regular physical activity / exercise:</b> ☐ Yes &nbsp; ☐ No &nbsp; <b>Type:</b> ______<br>
<b>Session duration:</b> ______ &nbsp; <b>Frequency:</b> ______<br>
<b>Barriers to exercising:</b> ______ &nbsp; <b>Details:</b> ______<br>
<b>Other:</b> ______</p>

<h5 style="color:#1f4e79;margin:14px 0 6px 0;border-bottom:1px solid #cfd8e3;padding-bottom:3px;">Objective — Weight History</h5>
<p><b>Weight:</b> {{peso}} lbs (measured weight)<br>
<b>Height:</b> {{talla}} in (reported height)<br>
<b>Body mass index:</b> {{imc}}<br>
<b>Weight history:</b> ______<br>
<b>Ideal body weight (IBW):</b> ______ lbs (Hamwi equation) &nbsp; <b>% IBW:</b> ______<br>
<b>Waist circumference:</b> ______ in &nbsp; <b>Hip circumference:</b> ______ in &nbsp; <b>Waist–hip ratio:</b> ______<br>
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
<b>Other:</b> ______</p>

<h5 style="color:#1f4e79;margin:14px 0 6px 0;border-bottom:1px solid #cfd8e3;padding-bottom:3px;">Assessment — Estimated Energy &amp; Nutrition Needs</h5>
<p><b>Total energy estimated needs:</b> ______ kcal/day — REE = ______ kcal/day using <b>Mifflin–St Jeor Equation (Adults)</b>; Activity Factor = Normal activities of daily living ( ______ ); Stress/Injury Factor = None (1.0–1.0); weight = {{peso}} lbs (actual body weight).<br>
<b>Total protein estimated needs:</b> ______ g/day for normal nutrition; weight = {{peso}} lbs (actual body weight).<br>
<b>Total carbohydrate estimated needs:</b> ______ (45–60 % total energy).<br>
<b>Total fiber estimated needs:</b> ______<br>
<b>Total fat estimated needs:</b> ______ (25–35 % total energy).<br>
<b>Total fluid estimated needs:</b> ______ (normal nutrition using weight = ______ lbs adjusted body weight).<br>
<b>Other:</b> ______</p>

<h5 style="color:#1f4e79;margin:14px 0 6px 0;border-bottom:1px solid #cfd8e3;padding-bottom:3px;">Plan</h5>
<p><b>Short-Term Dietary Goals</b><br>
<b>1.</b> ______ &nbsp; <b>Status:</b> New goal<br>
<b>2.</b> ______ &nbsp; <b>Status:</b> New goal<br>
<b>3.</b> ______ &nbsp; <b>Status:</b> New goal</p>

<p><b>Other goals:</b> ______<br>
<b>Food log:</b> ______ for ______<br>
<b>Meal plan:</b> ______<br>
<b>Follow up:</b> ______<br>
<b>Stop time:</b> ______ &nbsp; <b># 15-min units:</b> ______<br>
<b>Summary notes / goals on review:</b> ______</p>

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
   . '<body class="p-4"><div class="container" style="max-width:720px;"><h4>Resultado — Lote 7 (plantillas extra)</h4><ul class="list-group">';
foreach ($msgs as $m) echo '<li class="list-group-item">'.htmlspecialchars($m).'</li>';
echo '</ul><a class="btn btn-primary mt-3" href="plantillas_admin.php">Ir a Templates</a></div></body></html>';
