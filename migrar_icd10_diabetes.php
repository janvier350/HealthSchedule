<?php
/**
 * migrar_icd10_diabetes.php — Agrega al catálogo ICD-10 los códigos de Diabetes
 * Mellitus cubiertos por Medicare (guía CMS CY 2026): Tipo 2 (E11), Tipo 1 (E10),
 * estatus de fármacos (Z79) y screening/prediabetes (Z13.1, R73.x).
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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>ICD-10 Diabetes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:720px;">
<h4>Agregar ICD-10 — Diabetes (Medicare)</h4>
<p class="text-muted">Agrega los códigos de Diabetes Tipo 2 (E11), Tipo 1 (E10), uso de fármacos (Z79) y screening/prediabetes (Z13.1, R73.x). Idempotente.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar</button>
<a class="btn btn-link" href="PNC_CIE-10Crear.php">Volver</a></form></div></body></html>
    <?php
    exit;
}

$dbEsc = $conexion->real_escape_string($conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db']);
$msgs = [];

// Columna ACTIVO (por si aún no existe)
$tieneActivo = (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$dbEsc' AND TABLE_NAME='ENFE_DIAG_COD' AND COLUMN_NAME='ACTIVO'")->fetch_assoc()['c'] > 0;
if (!$tieneActivo) {
    if ($conexion->query("ALTER TABLE ENFE_DIAG_COD ADD COLUMN ACTIVO TINYINT(1) NOT NULL DEFAULT 1"))
        $msgs[] = ['NEW','Columna ACTIVO agregada.'];
}

function getOrCreateCat($conexion, $nombre, $desc, $codigo) {
    $chk = $conexion->prepare("SELECT ID_ENFERMEDAD FROM ENFERMEDADES_DIAGNOSTICO WHERE NOMBRE=? LIMIT 1");
    $chk->bind_param('s', $nombre); $chk->execute();
    $r = $chk->get_result()->fetch_assoc(); $chk->close();
    if ($r) return (int)$r['ID_ENFERMEDAD'];
    $ins = $conexion->prepare("INSERT INTO ENFERMEDADES_DIAGNOSTICO (CODIGO, NOMBRE, DESCRIPCION) VALUES (?,?,?)");
    $ins->bind_param('sss', $codigo, $nombre, $desc);
    $id = $ins->execute() ? (int)$conexion->insert_id : 0; $ins->close();
    return $id;
}
$catDiab = getOrCreateCat($conexion, 'Diabetes Mellitus & Related (Medicare)', 'Códigos de diabetes cubiertos por Medicare (CMS)', 'DIAB');

$codigos = [
    // Tipo 2 (E11)
    ['E11.9',   'Type 2 diabetes mellitus without complications'],
    ['E11.65',  'Type 2 diabetes mellitus with hyperglycemia'],
    ['E11.649', 'Type 2 diabetes mellitus with hypoglycemia without coma'],
    ['E11.21',  'Type 2 diabetes mellitus with diabetic nephropathy'],
    ['E11.22',  'Type 2 diabetes mellitus with diabetic chronic kidney disease'],
    ['E11.40',  'Type 2 diabetes mellitus with diabetic neuropathy, unspecified'],
    ['E11.42',  'Type 2 diabetes mellitus with diabetic polyneuropathy'],
    ['E11.319', 'Type 2 diabetes mellitus with unspecified diabetic retinopathy'],
    ['E11.51',  'Type 2 diabetes mellitus with diabetic peripheral angiopathy'],
    ['E11.621', 'Type 2 diabetes mellitus with foot ulcer'],
    // Tipo 1 (E10)
    ['E10.9',   'Type 1 diabetes mellitus without complications'],
    ['E10.65',  'Type 1 diabetes mellitus with hyperglycemia'],
    ['E10.649', 'Type 1 diabetes mellitus with hypoglycemia without coma'],
    ['E10.10',  'Type 1 diabetes mellitus with ketoacidosis without coma'],
    ['E10.22',  'Type 1 diabetes mellitus with diabetic chronic kidney disease'],
    ['E10.42',  'Type 1 diabetes mellitus with diabetic polyneuropathy'],
    // Estatus de fármacos (Z79)
    ['Z79.4',   'Long-term (current) use of insulin'],
    ['Z79.84',  'Long-term (current) use of oral hypoglycemic drugs'],
    ['Z79.85',  'Long-term (current) use of injectable non-insulin antidiabetic drugs'],
    // Screening / prediabetes
    ['Z13.1',   'Encounter for screening for diabetes mellitus'],
    ['R73.09',  'Other abnormal glucose (prediabetes)'],
    ['R73.03',  'Impaired fasting glucose'],
];

$chkCod = $conexion->prepare("SELECT ID_ENFE_DIAG_COD FROM ENFE_DIAG_COD WHERE LOWER(TRIM(CODIGO))=LOWER(TRIM(?)) LIMIT 1");
$hasActivoNow = (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$dbEsc' AND TABLE_NAME='ENFE_DIAG_COD' AND COLUMN_NAME='ACTIVO'")->fetch_assoc()['c'] > 0;
$updCod = $conexion->prepare($hasActivoNow
    ? "UPDATE ENFE_DIAG_COD SET DESCRIPCION=?, ID_ENFERMEDAD=?, ACTIVO=1 WHERE ID_ENFE_DIAG_COD=?"
    : "UPDATE ENFE_DIAG_COD SET DESCRIPCION=?, ID_ENFERMEDAD=? WHERE ID_ENFE_DIAG_COD=?");
$insCod = $conexion->prepare($hasActivoNow
    ? "INSERT INTO ENFE_DIAG_COD (ID_ENFERMEDAD, CODIGO, DESCRIPCION, ACTIVO) VALUES (?,?,?,1)"
    : "INSERT INTO ENFE_DIAG_COD (ID_ENFERMEDAD, CODIGO, DESCRIPCION) VALUES (?,?,?)");

foreach ($codigos as [$cod, $desc]) {
    $chkCod->bind_param('s', $cod); $chkCod->execute();
    $row = $chkCod->get_result()->fetch_assoc();
    if ($row) {
        $updCod->bind_param('sii', $desc, $catDiab, $row['ID_ENFE_DIAG_COD']);
        $msgs[] = $updCod->execute() ? ['OK',"$cod — ya existía, actualizado."] : ['ERR',"$cod: ".$updCod->error];
    } else {
        $insCod->bind_param('iss', $catDiab, $cod, $desc);
        $msgs[] = $insCod->execute() ? ['NEW',"$cod — agregado."] : ['ERR',"$cod: ".$insCod->error];
    }
}
$chkCod->close(); $updCod->close(); $insCod->close();

$cls=['NEW'=>'success','OK'=>'secondary','ERR'=>'danger'];
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Resultado</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="p-4"><div class="container" style="max-width:720px;"><h4>Resultado — Diabetes</h4><ul class="list-group">';
foreach ($msgs as $m) echo '<li class="list-group-item"><span class="badge bg-'.($cls[$m[0]]??'secondary').' me-2">'.htmlspecialchars($m[0]).'</span>'.htmlspecialchars($m[1]).'</li>';
echo '</ul><a class="btn btn-primary mt-3" href="PNC_CIE-10Crear.php">Volver al catálogo</a></div></body></html>';
