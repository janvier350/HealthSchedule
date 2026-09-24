<?php
/**
 * migrar_icd10_mnt.php
 * Agrega al catálogo ICD-10 (ENFE_DIAG_COD) los códigos de la guía MNT
 * (Terapia Médica Nutricional): pérdida de peso, osteoporosis y artritis.
 * Upsert por CODIGO (no duplica). SISTEMA-only, POST-driven.
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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>ICD-10 MNT</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Códigos ICD-10 — Guía MNT</h4>
<p class="text-muted">Agrega R63.4, R64, M81.0, M80.00, M19.90 y M06.9 al catálogo ICD-10. Upsert por código (no duplica).</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar</button></form></div></body></html>
    <?php
    exit;
}

// Categoría contenedora "MNT" (crear si no existe).
$catId = 0;
$cc = $conexion->prepare("SELECT ID_ENFERMEDAD FROM ENFERMEDADES_DIAGNOSTICO WHERE CODIGO=? LIMIT 1");
$catCod = 'MNT';
$cc->bind_param('s', $catCod); $cc->execute();
if ($r = $cc->get_result()->fetch_assoc()) { $catId = (int)$r['ID_ENFERMEDAD']; }
$cc->close();
if (!$catId) {
    $ic = $conexion->prepare("INSERT INTO ENFERMEDADES_DIAGNOSTICO (CODIGO, NOMBRE, DESCRIPCION) VALUES (?,?,?)");
    $nom = 'MNT'; $des = 'Medical Nutrition Therapy';
    $ic->bind_param('sss', $catCod, $nom, $des);
    if ($ic->execute()) $catId = (int)$conexion->insert_id;
    $ic->close();
}

$codigos = [
    ['R63.4',  'Abnormal weight loss / Pérdida de peso anormal'],
    ['R64',    'Cachexia / Caquexia'],
    ['M81.0',  'Age-related osteoporosis without current pathological fracture'],
    ['M80.00', 'Age-related osteoporosis with current pathological fracture, unspecified site'],
    ['M19.90', 'Osteoarthritis, unspecified site / Osteoartritis primaria'],
    ['M06.9',  'Rheumatoid arthritis, unspecified / Artritis reumatoide'],
];

$chk = $conexion->prepare("SELECT ID_ENFE_DIAG_COD FROM ENFE_DIAG_COD WHERE LOWER(TRIM(CODIGO))=LOWER(TRIM(?)) LIMIT 1");
$upd = $conexion->prepare("UPDATE ENFE_DIAG_COD SET DESCRIPCION=? WHERE ID_ENFE_DIAG_COD=?");
$ins = $conexion->prepare("INSERT INTO ENFE_DIAG_COD (ID_ENFERMEDAD, CODIGO, DESCRIPCION) VALUES (?,?,?)");
$msgs = [];
foreach ($codigos as [$cod, $desc]) {
    $chk->bind_param('s', $cod); $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    if ($row) {
        $upd->bind_param('si', $desc, $row['ID_ENFE_DIAG_COD']); $upd->execute();
        $msgs[] = ['OK', "$cod — ya existía, descripción actualizada."];
    } else {
        $ins->bind_param('iss', $catId, $cod, $desc);
        if ($ins->execute()) $msgs[] = ['NEW', "$cod — agregado."];
        else $msgs[] = ['ERR', "$cod — error: ".$ins->error];
    }
}
$chk->close(); $upd->close(); $ins->close();

$cls=['NEW'=>'success','OK'=>'secondary','ERR'=>'danger'];
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Resultado</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="p-4"><div class="container" style="max-width:640px;"><h4>Resultado ICD-10 MNT</h4><ul class="list-group">';
foreach ($msgs as $m) echo '<li class="list-group-item"><span class="badge bg-'.($cls[$m[0]]??'secondary').' me-2">'.htmlspecialchars($m[0]).'</span>'.htmlspecialchars($m[1]).'</li>';
echo '</ul></div></body></html>';
