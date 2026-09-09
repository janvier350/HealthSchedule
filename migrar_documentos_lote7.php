<?php
/**
 * migrar_documentos_lote7.php
 * Inserta 1 documento educativo derivado del PDF 31:
 *   (31) Nutritionix Track Pro — Client Setup Guide
 *
 * Guía de configuración de la app Nutritionix Track para pacientes
 * (no es un formulario clínico ni un consentimiento legal, sino
 * material educativo/instructivo). Se registra en el módulo
 * Documents para poder enviarse al paciente.
 *
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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Migración docs lote 7</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Migrar documentos — Lote 7 (final)</h4>
<p class="text-muted">Inserta / actualiza <b>(31) Nutritionix Track Pro — Client Setup Guide</b>.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar migración</button>
<a class="btn btn-link" href="gestionar_documentos.php">Volver</a></form></div></body></html>
    <?php
    exit;
}

$documentos = [];

// (31) Nutritionix Track Pro — Client Setup Guide
$documentos[] = [
    'titulo' => '(31) Nutritionix Track Pro — Client Setup Guide',
    'contenido' => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#2b2b2b;line-height:1.45;">
<h4 style="color:#3fa34d;margin:0 0 10px 0;">Nutritionix Track Pro — Client Setup Guide</h4>

<p><b>Client:</b> {{paciente}} &nbsp; <b>DOB:</b> {{fecha_nacimiento}} &nbsp; <b>Date:</b> {{fecha}}</p>

<p><a href="https://www.nutritionix.com/" target="_blank" rel="noopener">Nutritionix Track</a> is a food, nutrient, exercise, and weight-tracking app developed and maintained by a team of registered dietitians. Your healthcare professional's EMR (electronic medical record) integrates with Nutritionix Track, allowing them to easily track your progress.</p>

<h5 style="color:#3fa34d;margin:14px 0 6px 0;">Step 1 — Set Up Your Nutritionix Pro Account</h5>
<p>Your healthcare professional will send you a personal link to create and/or link up your Nutritionix Track Pro account. The link may be shared as part of your appointment confirmation, an appointment reminder message, online paperwork, or by another method.</p>

<p><b>If you already have a Nutritionix Track account:</b> click the link, then click the <i>log in</i> link on the account-creation page and log into your Nutritionix Track account. This link-up process allows your healthcare professional to view your food and fitness logs, and it also gives you a free Nutritionix Track Pro subscription.</p>

<p><b>If you do not have a Nutritionix Track account:</b> fill out each field on the New Account page, review the terms of service and privacy policy, and click <b>Create Account</b>. Then complete the Finish Setup page. You now have a Nutritionix Track account.</p>

<p><i>Note: When your Nutritionix Track account setup is complete, you will receive a verification email. Remember to click the link in the email to activate your account.</i></p>

<h5 style="color:#3fa34d;margin:14px 0 6px 0;">Step 2 — Set Up the Track App (Optional)</h5>
<p>After setting up your free Nutritionix Track Pro account, you can add the app to your tablet or smartphone. Download it from the app store:</p>
<ul>
<li><b>iPad / iPhone:</b> search "Nutritionix Track" in the App Store.</li>
<li><b>Android:</b> search "Nutritionix Track" in Google Play.</li>
<li><b>Web:</b> Nutritionix Track is also supported at <a href="https://www.nutritionix.com/track/app" target="_blank" rel="noopener">nutritionix.com/track/app</a>.</li>
</ul>
<p>Once you have downloaded the app, open it and select <b>Login Via Email</b>. Log in using the email address and password you set up in Step 1.</p>

<h5 style="color:#3fa34d;margin:14px 0 6px 0;">Step 3 — Use Nutritionix Track Pro</h5>
<p>Nutritionix Track allows you to log all your foods in as little as 60 seconds per day.</p>

<p><b>Basic Food Logging</b></p>
<ol>
<li>Press the green <b>+ Track</b> button at the bottom of your screen to access <i>Freeform</i> mode.</li>
<li>Select <i>Freeform</i> mode to add foods from the Common Foods dataset by typing or speaking freely (tap the microphone icon to create an entry by voice) about what you ate.</li>
<li>Once the food or meal is entered, tap <b>Add to Basket</b>.</li>
<li>Nutritionix Track will break your meal down into its component foods.</li>
<li>Scroll down to the bottom if you would also like to share a photo of your meal — tap <b>Add Photo</b>.</li>
<li>When you are happy with your entry, select <b>Log Foods</b>. Your meal is now logged.</li>
</ol>

<h5 style="color:#3fa34d;margin:14px 0 6px 0;">Other Nutritionix Pro Features</h5>
<ul>
<li>Add a photo to your food log</li>
<li>Add notes to your food log</li>
<li>Enter a recipe</li>
<li>Log a packaged food using the barcode scanner</li>
<li>Enter your exercise log</li>
<li>Log your water intake</li>
<li>Sync with Fitbit</li>
<li>View your energy and nutrient intake for the day</li>
</ul>

<p style="margin-top:14px;">If you need help, contact <a href="mailto:support@nutritionix.com">support@nutritionix.com</a> or reach out to your healthcare professional at <b>SRoss Nutrition PLLC</b>.</p>
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
   . '<body class="p-4"><div class="container" style="max-width:720px;"><h4>Resultado — Documentos lote 7</h4><ul class="list-group">';
foreach ($msgs as $m) echo '<li class="list-group-item">'.htmlspecialchars($m).'</li>';
echo '</ul><a class="btn btn-primary mt-3" href="gestionar_documentos.php">Ir a Documents</a></div></body></html>';
