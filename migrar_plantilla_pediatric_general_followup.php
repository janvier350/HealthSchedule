<?php
/**
 * migrar_plantilla_pediatric_general_followup.php
 *
 * Inserta (o actualiza si ya existe) la plantilla nutricional
 *   "Follow-up- General Nutrition (Pediatric)"
 * en cat_plantillas_nutricion.
 *
 * Los datos del paciente/cita se rellenan al cargar la plantilla en el
 * editor de Atención mediante los placeholders {{...}} ya soportados.
 *
 * Es idempotente.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'SISTEMA') {
    die('<p style="color:red;font-family:sans-serif;padding:2rem;">Acceso restringido — solo SISTEMA.</p>');
}

$nombre    = 'Follow-up- General Nutrition (Pediatric)';
$categoria = '6. Follow-up Chart Notes';

$cuerpo = <<<'HTML'
<div style="font-family:Arial, Helvetica, sans-serif; font-size:13px; color:#2b2b2b;">

<!-- ── ENCABEZADO ──────────────────────────────────────────────── -->
<h4 style="color:#5a2d82; margin:0 0 10px 0;">Follow-up Note — General Nutrition (Pediatric)</h4>
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

<!-- ── SUBJECTIVE ─────────────────────────────────────────────── -->
<h5 style="color:#5a2d82; margin:12px 0 6px 0;">Subjective</h5>
<p><b>Reason for visit:</b> ______________________</p>
<p><b>Medical diagnosis:</b> ______________________
&nbsp;<i>Diagnosis codes:</i>&nbsp; A) ______ &nbsp; B) ______ &nbsp; C) ______ &nbsp; D) ______</p>
<p><b>Questions / concerns for today:</b> ______________________</p>
<p><b>Progress towards goals:</b> ______________________</p>
<p><b>Areas for improvement:</b> ______________________</p>
<p><b>Other:</b> ______________________</p>

<!-- ── DIET HISTORY ───────────────────────────────────────────── -->
<h5 style="color:#5a2d82; margin:12px 0 6px 0;">Diet History</h5>
<p><b>Summary of diet:</b> ______________________</p>

<!-- ── PHYSICAL ACTIVITY ──────────────────────────────────────── -->
<h5 style="color:#5a2d82; margin:12px 0 6px 0;">Physical Activity</h5>
<p><b>Regular physical activity / exercise:</b> ______ &nbsp; <b>Type:</b> ______________________</p>
<p><b>Session duration:</b> ______ - ______ mins &nbsp;&nbsp; <b>Frequency:</b> ______ - ______ times / week</p>
<p><b>Barriers to exercising:</b> ______ &nbsp; <b>Details:</b> ______________________</p>
<p><b>Screen time:</b> ______ - ______ hr/day &nbsp; ______________________</p>
<p><b>Other:</b> ______________________</p>

<!-- ── OBJECTIVE / WEIGHT HISTORY ─────────────────────────────── -->
<h5 style="color:#5a2d82; margin:14px 0 6px 0;">Objective</h5>
<p style="margin:6px 0 4px 0;"><b>Weight History</b></p>
<table style="border-collapse:collapse; width:100%; border:1px solid #ccc; margin-bottom:8px;">
<tr style="background:#f2edf7;">
  <th style="padding:6px 8px; text-align:left; border:1px solid #ccc;">Parámetro</th>
  <th style="padding:6px 8px; text-align:left; border:1px solid #ccc;">Valor</th>
</tr>
<tr>
  <td style="padding:6px 8px; border:1px solid #ccc;">Weight</td>
  <td style="padding:6px 8px; border:1px solid #ccc;">{{peso}} (measured weight, at the office)</td>
</tr>
<tr>
  <td style="padding:6px 8px; border:1px solid #ccc;">Height</td>
  <td style="padding:6px 8px; border:1px solid #ccc;">{{talla}} (measured height)</td>
</tr>
<tr>
  <td style="padding:6px 8px; border:1px solid #ccc;">Body mass index</td>
  <td style="padding:6px 8px; border:1px solid #ccc;">{{imc}} kg/m² &nbsp; <i>(ver categoría en la tarjeta de Valoración pediátrica)</i></td>
</tr>
<tr>
  <td style="padding:6px 8px; border:1px solid #ccc;">Weight change since last session</td>
  <td style="padding:6px 8px; border:1px solid #ccc;">______ lbs</td>
</tr>
<tr>
  <td style="padding:6px 8px; border:1px solid #ccc;">Total weight change since first session</td>
  <td style="padding:6px 8px; border:1px solid #ccc;">______ lbs &nbsp; ( ______ % total change )</td>
</tr>
<tr>
  <td style="padding:6px 8px; border:1px solid #ccc;">Other</td>
  <td style="padding:6px 8px; border:1px solid #ccc;">______________________</td>
</tr>
</table>

<!-- ── PHYSICAL FINDINGS ──────────────────────────────────────── -->
<p style="margin:8px 0 4px 0;"><b>Physical Findings</b></p>
<p><b>Gastrointestinal:</b>
<span style="color:#666;">(marcar con [x] los que apliquen)</span></p>
<table style="border-collapse:collapse; width:100%; border:1px solid #ccc; margin-bottom:8px;">
<tr>
  <td style="padding:4px 8px; border:1px solid #ccc;">[ ] Abdominal bloating</td>
  <td style="padding:4px 8px; border:1px solid #ccc;">[ ] Diarrhea</td>
  <td style="padding:4px 8px; border:1px solid #ccc;">[ ] Nausea</td>
</tr>
<tr>
  <td style="padding:4px 8px; border:1px solid #ccc;">[ ] Abdominal cramping</td>
  <td style="padding:4px 8px; border:1px solid #ccc;">[ ] Early satiety</td>
  <td style="padding:4px 8px; border:1px solid #ccc;">[ ] Pain on swallowing</td>
</tr>
<tr>
  <td style="padding:4px 8px; border:1px solid #ccc;">[ ] Abdominal distension</td>
  <td style="padding:4px 8px; border:1px solid #ccc;">[ ] Excessive appetite</td>
  <td style="padding:4px 8px; border:1px solid #ccc;">[ ] Poor appetite</td>
</tr>
<tr>
  <td style="padding:4px 8px; border:1px solid #ccc;">[ ] Abdominal pain</td>
  <td style="padding:4px 8px; border:1px solid #ccc;">[ ] Excessive belching</td>
  <td style="padding:4px 8px; border:1px solid #ccc;">[ ] Retching</td>
</tr>
<tr>
  <td style="padding:4px 8px; border:1px solid #ccc;">[ ] Acid reflux</td>
  <td style="padding:4px 8px; border:1px solid #ccc;">[ ] Excessive wind</td>
  <td style="padding:4px 8px; border:1px solid #ccc;">[ ] Vomiting</td>
</tr>
<tr>
  <td style="padding:4px 8px; border:1px solid #ccc;">[ ] Bulky stools</td>
  <td style="padding:4px 8px; border:1px solid #ccc;">[ ] Heartburn</td>
  <td style="padding:4px 8px; border:1px solid #ccc;">Other: __________</td>
</tr>
<tr>
  <td style="padding:4px 8px; border:1px solid #ccc;">[ ] Constipation</td>
  <td style="padding:4px 8px; border:1px solid #ccc;">[ ] Liquid stools</td>
  <td style="padding:4px 8px; border:1px solid #ccc;">Other: __________</td>
</tr>
</table>
<p><b>Other:</b> ______________________</p>

<!-- ── MEDICATION & SUPPLEMENTS ───────────────────────────────── -->
<p style="margin:8px 0 4px 0;"><b>Medication &amp; Supplements</b></p>
<table style="border-collapse:collapse; width:100%; border:1px solid #ccc; margin-bottom:8px;">
<tr style="background:#f2edf7;">
  <th style="padding:6px 8px; text-align:left; border:1px solid #ccc; width:40%;">Name of Medication / Supplement</th>
  <th style="padding:6px 8px; text-align:left; border:1px solid #ccc; width:30%;">Reason</th>
  <th style="padding:6px 8px; text-align:left; border:1px solid #ccc; width:30%;">Dose &amp; Frequency</th>
</tr>
<tr><td style="padding:6px 8px; border:1px solid #ccc;">&nbsp;</td><td style="padding:6px 8px; border:1px solid #ccc;">&nbsp;</td><td style="padding:6px 8px; border:1px solid #ccc;">&nbsp;</td></tr>
<tr><td style="padding:6px 8px; border:1px solid #ccc;">&nbsp;</td><td style="padding:6px 8px; border:1px solid #ccc;">&nbsp;</td><td style="padding:6px 8px; border:1px solid #ccc;">&nbsp;</td></tr>
<tr><td style="padding:6px 8px; border:1px solid #ccc;">&nbsp;</td><td style="padding:6px 8px; border:1px solid #ccc;">&nbsp;</td><td style="padding:6px 8px; border:1px solid #ccc;">&nbsp;</td></tr>
<tr><td style="padding:6px 8px; border:1px solid #ccc;">&nbsp;</td><td style="padding:6px 8px; border:1px solid #ccc;">&nbsp;</td><td style="padding:6px 8px; border:1px solid #ccc;">&nbsp;</td></tr>
</table>

<!-- ── LAB RESULTS ────────────────────────────────────────────── -->
<p style="margin:8px 0 4px 0;"><b>Lab Results</b></p>
<p><b>Lab results:</b> ______________________</p>
<p><b>Diagnostic studies:</b> ______________________</p>
<p><b>Other:</b> ______________________</p>

<!-- ── ASSESSMENT ─────────────────────────────────────────────── -->
<h5 style="color:#5a2d82; margin:14px 0 6px 0;">Assessment</h5>

<p style="margin:8px 0 4px 0;"><b>Estimated Energy &amp; Nutrition Needs</b></p>
<ul style="margin:4px 0 8px 20px;">
  <li>Total energy estimated needs: ______ - ______ kcal/day ( ______ ) — REE = ______ kcal/day using
      <b>EER for Healthy Children &amp; Infants (0-18 yrs)</b>, Activity Factor = Normal activities of daily living ( ______ - ______ ), Stress/Injury Factor = None (1 - 1), weight = {{peso}} (actual body weight).</li>
  <li>Total protein estimated needs: ______ - ______ g/day, for normal nutrition ( ______ - ______ g/kg/day ), weight = {{peso}} (actual body weight).</li>
  <li>Total carbohydrate estimated needs: ______ - ______ g/day ( 45 - 65 % total energy ).</li>
  <li>Total fiber estimated needs: ______ g/day.</li>
  <li>Total fat estimated needs: ______ - ______ g/day ( 25 - 35 % total energy ).</li>
  <li>Total fluid estimated needs: ______ mL/day (normal nutrition) — ______ mL/kg/day using weight = {{peso}} (actual body weight).</li>
</ul>

<p style="margin:8px 0 4px 0;"><b>Comprehension &amp; Motivation</b></p>
<ul style="margin:4px 0 8px 20px;">
  <li>Readiness to change (0 = low &amp; 10 = very high): ______</li>
  <li>Receptiveness to education: ______</li>
  <li>Understanding of education: ______</li>
  <li>Expected level of compliance: ______</li>
</ul>

<p style="margin:8px 0 4px 0;"><b>Nutrition Diagnosis</b></p>

<ul style="margin:4px 0 8px 20px;">
  <li><b>Interventions:</b> ______________________</li>
  <li><b>Nutrition counseling utilized:</b> ______________________</li>
  <li><b>Education materials provided:</b> ______________________</li>
  <li><b>Food log for:</b> ______ days</li>
  <li><b>Meal plan:</b> ______________________</li>
  <li><b>Other goals:</b> ______________________</li>
  <li><b>Action plan:</b> ______________________</li>
</ul>

<p><b>Stop time:</b> {{hora_fin}} &nbsp;&nbsp; <b># 15 min units:</b> ______</p>
<p><b>Summary notes / goals on review:</b> ______________________</p>

</div>
HTML;

// ── Detectar columnas disponibles en la tabla ─────────────────────
$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$colsRes = $conexion->query(
    "SELECT COLUMN_NAME FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA='$dbName' AND TABLE_NAME='cat_plantillas_nutricion'"
);
$cols = [];
if ($colsRes) {
    while ($r = $colsRes->fetch_assoc()) { $cols[] = strtolower($r['COLUMN_NAME']); }
}

$ejecutar = isset($_POST['ejecutar']) && $_POST['ejecutar'] === '1';
$log = [];
$errores = 0;

if (!$cols) {
    $log[] = ['err', "No se encontró la tabla cat_plantillas_nutricion en la base de datos."];
    $errores++;
} elseif ($ejecutar) {
    $stmtChk = $conexion->prepare(
        "SELECT id FROM cat_plantillas_nutricion WHERE nombre_plantilla = ? LIMIT 1"
    );
    $stmtChk->bind_param('s', $nombre);
    $stmtChk->execute();
    $exists = $stmtChk->get_result()->fetch_assoc();
    $stmtChk->close();

    if ($exists) {
        $stmt = $conexion->prepare(
            "UPDATE cat_plantillas_nutricion SET cuerpo_html = ?, categoria = ? WHERE id = ?"
        );
        $stmt->bind_param('ssi', $cuerpo, $categoria, $exists['id']);
        if ($stmt->execute()) {
            $log[] = ['ok', "✅ Plantilla ya existía. Se actualizó su cuerpo (id={$exists['id']})."];
        } else {
            $log[] = ['err', "Error al actualizar: " . $stmt->error]; $errores++;
        }
        $stmt->close();
    } else {
        $fields = ['nombre_plantilla', 'cuerpo_html'];
        $values = [$nombre, $cuerpo];
        $types  = 'ss';
        if (in_array('categoria', $cols, true)) { $fields[] = 'categoria'; $values[] = $categoria; $types .= 's'; }
        if (in_array('estado',    $cols, true)) { $fields[] = 'estado';    $values[] = 'A';        $types .= 's'; }

        $ph  = rtrim(str_repeat('?,', count($fields)), ',');
        $sql = "INSERT INTO cat_plantillas_nutricion (" . implode(',', $fields) . ") VALUES ($ph)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param($types, ...$values);
        if ($stmt->execute()) {
            $log[] = ['ok', "✅ Plantilla creada correctamente (id=" . $conexion->insert_id . ")."];
        } else {
            $log[] = ['err', "Error al insertar: " . $stmt->error]; $errores++;
        }
        $stmt->close();
    }

    $log[] = $errores === 0
        ? ['ok', "✅ Migración completada."]
        : ['err', "⚠️ Migración con $errores error(es)."];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Migración: Plantilla Pediátrica de Follow-up (General)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>pre{background:#1e1e1e;color:#d4d4d4;padding:1rem;border-radius:6px;font-size:.8rem;}.log-ok{color:#4caf50;}.log-err{color:#f44336;}</style>
</head>
<body class="bg-light">
<div class="container py-4" style="max-width:820px;">
    <div class="card shadow">
        <div class="card-header text-white" style="background:#5a2d82;">
            <h5 class="mb-0"><i class="bi bi-file-earmark-medical me-2"></i>Migración: Follow-up General Nutrition (Pediatric)</h5>
        </div>
        <div class="card-body">
            <?php if (!$ejecutar): ?>
            <div class="alert alert-warning d-flex gap-2">
                <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0 mt-1"></i>
                <div><strong>Haz un backup antes de continuar.</strong> Esta migración inserta o actualiza una plantilla en <code>cat_plantillas_nutricion</code>.</div>
            </div>
            <div class="alert alert-info py-2">
                Se creará (o actualizará) la plantilla:
                <ul class="mb-0">
                    <li><b>Nombre:</b> <?php echo htmlspecialchars($nombre); ?></li>
                    <li><b>Categoría:</b> <?php echo htmlspecialchars($categoria); ?></li>
                </ul>
                Usa los placeholders automáticos que ya se sustituyen en Atención al paciente:
                {{fecha_actual}}, {{paciente_nombre}}, {{paciente_dob}}, {{edad_paciente}},
                {{sexo_paciente}}, {{hora_inicio}}, {{hora_fin}}, {{peso}}, {{talla}}, {{imc}},
                {{paciente_cedula}}.
            </div>
            <form method="POST" onsubmit="return confirm('¿Confirmas la migración? Asegúrate de tener backup.');">
                <input type="hidden" name="ejecutar" value="1">
                <button type="submit" class="btn btn-danger"><i class="bi bi-play-circle me-1"></i> Ejecutar migración</button>
                <a href="home.php" class="btn btn-outline-secondary ms-2">Cancelar</a>
            </form>
            <?php else: ?>
            <h6><i class="bi bi-terminal me-1"></i>Resultado</h6>
            <pre><?php foreach ($log as [$tipo, $msg]): ?>
<span class="log-<?php echo $tipo; ?>"><?php echo htmlspecialchars($msg); ?></span>
<?php endforeach; ?></pre>
            <a href="visor_plantillas.php" class="btn btn-primary mt-2"><i class="bi bi-eye me-1"></i> Ver en el visor</a>
            <a href="SCH_Calendar.php" class="btn btn-outline-secondary mt-2 ms-1"><i class="bi bi-calendar me-1"></i> Ir al calendario</a>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
