<?php
/**
 * migrar_plantilla_adult_assessment.php
 * Inserta (si no existe) la plantilla "Adult Nutrition Assessment" en cat_plantillas_nutricion.
 * Idempotente. Sólo SISTEMA.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'SISTEMA') {
    die('<p style="color:red;font-family:sans-serif;padding:2rem;">Acceso restringido — solo SISTEMA.</p>');
}

$nombre    = 'Adult Nutrition Assessment';
$categoria = '2. Adult Assessment';

$cuerpo = <<<'HTML'
<div style="font-family:Arial, Helvetica, sans-serif; font-size:13px; color:#2b2b2b;">

<h4 style="color:#5a2d82; margin:0 0 10px 0;">Adult Nutrition Assessment</h4>
<table style="border-collapse:collapse; width:100%; margin-bottom:12px;">
<tr>
  <td style="padding:4px 8px; width:50%;"><b>Date:</b> {{fecha_actual}}</td>
  <td style="padding:4px 8px;"><b>Client ID:</b> {{paciente_cedula}}</td>
</tr>
<tr>
  <td style="padding:4px 8px;"><b>Name:</b> {{paciente_nombre}}</td>
  <td style="padding:4px 8px;"><b>DOB:</b> {{paciente_dob}} &nbsp; <b>Age:</b> {{edad_paciente}} &nbsp; <b>Sex:</b> {{sexo_paciente}}</td>
</tr>
<tr>
  <td style="padding:4px 8px;"><b>Referred by:</b> ______________________</td>
  <td style="padding:4px 8px;"><b>Start time:</b> {{hora_inicio}}</td>
</tr>
</table>
<hr>

<h5 style="color:#5a2d82; margin:12px 0 6px 0;">Subjective</h5>
<p><b>Reason for visit / referral diagnosis:</b> {{diagnostico_referencia}}</p>
<p><b>Medical history:</b> ______________________</p>
<p><b>Medications / supplements:</b> ______________________</p>
<p><b>Diet recall:</b> ______________________</p>
<p><b>Food allergies / intolerances:</b> ______________________</p>
<p><b>Physical activity:</b> ______________________</p>

<h5 style="color:#5a2d82; margin:12px 0 6px 0;">Objective</h5>
<table style="border-collapse:collapse; width:100%; margin-bottom:12px;">
<tr>
  <td style="padding:4px 8px;"><b>Weight:</b> {{peso}}</td>
  <td style="padding:4px 8px;"><b>Height:</b> {{talla}}</td>
  <td style="padding:4px 8px;"><b>BMI:</b> {{imc}}</td>
</tr>
<tr>
  <td style="padding:4px 8px;"><b>Ideal weight:</b> ____________</td>
  <td style="padding:4px 8px;"><b>Adjusted body weight:</b> ____________</td>
  <td style="padding:4px 8px;"><b>Waist circumference:</b> ____________</td>
</tr>
</table>
<p><b>Biochemistry:</b> {{bioquimica}}</p>
<p><b>Physical findings:</b> {{hallazgos_fisicos}}</p>

<h5 style="color:#5a2d82; margin:12px 0 6px 0;">Estimated Needs</h5>
<p><i>Use the <b>Nutrition Calculator</b> button (top right) to compute BMR / TEE and the requirements for the selected clinical condition, then click <b>Insert into report</b>.</i></p>
<table style="border-collapse:collapse; width:100%; margin-bottom:12px;">
<tr>
  <td style="padding:4px 8px;"><b>Energy needs:</b> {{req_energia}}</td>
  <td style="padding:4px 8px;"><b>Intake:</b> {{ingesta_energia}}</td>
</tr>
<tr>
  <td style="padding:4px 8px;"><b>Protein needs:</b> {{req_proteina}}</td>
  <td style="padding:4px 8px;"><b>Intake:</b> {{ingesta_proteina}}</td>
</tr>
<tr>
  <td style="padding:4px 8px;"><b>Fluid needs:</b> {{req_fluidos}}</td>
  <td style="padding:4px 8px;"><b>Intake:</b> {{ingesta_fluidos}}</td>
</tr>
</table>

<h5 style="color:#5a2d82; margin:12px 0 6px 0;">Nutrition Diagnosis (PES)</h5>
<p>{{diagnostico_nutricional}}</p>

<h5 style="color:#5a2d82; margin:12px 0 6px 0;">Nutrition Intervention</h5>
<p><b>Prescription:</b> ______________________</p>
<p><b>Education / counseling:</b> {{intervencion}}</p>
<p><b>Short-term goals:</b> ______________________</p>
<p><b>Long-term goals:</b> ______________________</p>

<h5 style="color:#5a2d82; margin:12px 0 6px 0;">Monitoring & Evaluation</h5>
<p><b>Follow-up:</b> {{monitoreo}}</p>
<p><b>Indicators to track:</b> ______________________</p>

<div style="margin-top:40px; border-top:1px solid #ccc; padding-top:10px;">
    Electronically Signed By: <b>{{firma_nombre}}</b><br>
    {{firma_credenciales}}
</div>

</div>
HTML;

$stmt = $conexion->prepare("SELECT id FROM cat_plantillas_nutricion WHERE nombre_plantilla = ? LIMIT 1");
$stmt->bind_param('s', $nombre);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Migración</title>'
   . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>'
   . '<body class="p-4"><div class="container" style="max-width:640px;"><h4>Resultado</h4>';

if ($existing) {
    $up = $conexion->prepare("UPDATE cat_plantillas_nutricion SET categoria = ?, cuerpo_html = ? WHERE id = ?");
    $up->bind_param('ssi', $categoria, $cuerpo, $existing['id']);
    if ($up->execute()) echo '<div class="alert alert-info">OK — plantilla "'.htmlspecialchars($nombre).'" actualizada (id '.(int)$existing['id'].').</div>';
    else                echo '<div class="alert alert-danger">ERROR: '.htmlspecialchars($up->error).'</div>';
    $up->close();
} else {
    $ins = $conexion->prepare("INSERT INTO cat_plantillas_nutricion (nombre_plantilla, categoria, cuerpo_html) VALUES (?, ?, ?)");
    $ins->bind_param('sss', $nombre, $categoria, $cuerpo);
    if ($ins->execute()) echo '<div class="alert alert-success">OK — plantilla "'.htmlspecialchars($nombre).'" creada (id '.(int)$conexion->insert_id.').</div>';
    else                 echo '<div class="alert alert-danger">ERROR: '.htmlspecialchars($ins->error).'</div>';
    $ins->close();
}
echo '<a class="btn btn-secondary mt-3" href="home.php">Volver</a></div></body></html>';
