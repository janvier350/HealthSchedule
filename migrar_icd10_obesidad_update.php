<?php
/**
 * migrar_icd10_obesidad_update.php
 * Actualiza el catálogo ICD-10 de obesidad según las guías de codificación:
 *  - Agrega la columna ACTIVO a ENFE_DIAG_COD (para poder ocultar códigos sin
 *    borrarlos, conservando los ya asignados a pacientes).
 *  - Agrega los códigos recomendados: E66.811/812/813 (obesidad clase 1/2/3),
 *    E66.89, Z68.4 y Z71.82 (consejería de actividad física).
 *  - Desactiva los códigos estigmatizantes a evitar: E66.0, E66.01, E66.09.
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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Actualizar ICD-10 Obesidad</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:720px;">
<h4>Actualizar catálogo ICD-10 — Obesidad</h4>
<p class="text-muted">Agrega E66.811/812/813, E66.89, Z68.4 y Z71.82; y <b>desactiva</b> E66.0, E66.01 y E66.09 (estigmatizantes). Idempotente.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar</button>
<a class="btn btn-link" href="PNC_CIE-10Crear.php">Volver</a></form></div></body></html>
    <?php
    exit;
}

$dbEsc = $conexion->real_escape_string($conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db']);
$msgs = [];

// 1) Columna ACTIVO
$tieneActivo = (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$dbEsc' AND TABLE_NAME='ENFE_DIAG_COD' AND COLUMN_NAME='ACTIVO'")->fetch_assoc()['c'] > 0;
if (!$tieneActivo) {
    if ($conexion->query("ALTER TABLE ENFE_DIAG_COD ADD COLUMN ACTIVO TINYINT(1) NOT NULL DEFAULT 1")) {
        $msgs[] = ['NEW','Columna ACTIVO agregada a ENFE_DIAG_COD.'];
    } else {
        $msgs[] = ['ERR','No se pudo agregar ACTIVO: '.$conexion->error];
    }
} else {
    $msgs[] = ['OK','La columna ACTIVO ya existía.'];
}

// 2) Categoría contenedora (reutiliza la de Nutrición si existe; si no, la crea)
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
$catObesidad  = getOrCreateCat($conexion, 'Overweight, Obesity & BMI', 'Sobrepeso, obesidad y códigos Z68 de BMI', 'MNT01');
$catConsejeria= getOrCreateCat($conexion, 'Counseling, Eating Disorders & Preventive', 'Consejería dietética, trastornos alimentarios, preventiva', 'MNT06');

// 3) Upsert de códigos nuevos (activos)
$nuevos = [
    [$catObesidad,  'E66.811', 'Obesity, class 1 (BMI 30.0–34.9)'],
    [$catObesidad,  'E66.812', 'Obesity, class 2 (BMI 35.0–39.9)'],
    [$catObesidad,  'E66.813', 'Obesity, class 3 / severe (BMI 40 or greater)'],
    [$catObesidad,  'E66.89',  'Other obesity not elsewhere classified'],
    [$catObesidad,  'Z68.4',   'Body mass index (BMI) 40 or greater, adult'],
    [$catConsejeria,'Z71.82',  'Exercise / physical activity counseling'],
];
$chkCod = $conexion->prepare("SELECT ID_ENFE_DIAG_COD FROM ENFE_DIAG_COD WHERE LOWER(TRIM(CODIGO))=LOWER(TRIM(?)) LIMIT 1");
$updCod = $conexion->prepare("UPDATE ENFE_DIAG_COD SET DESCRIPCION=?, ID_ENFERMEDAD=?, ACTIVO=1 WHERE ID_ENFE_DIAG_COD=?");
$insCod = $conexion->prepare("INSERT INTO ENFE_DIAG_COD (ID_ENFERMEDAD, CODIGO, DESCRIPCION, ACTIVO) VALUES (?,?,?,1)");
foreach ($nuevos as [$catId, $cod, $desc]) {
    $chkCod->bind_param('s', $cod); $chkCod->execute();
    $row = $chkCod->get_result()->fetch_assoc();
    if ($row) {
        $updCod->bind_param('sii', $desc, $catId, $row['ID_ENFE_DIAG_COD']);
        $msgs[] = $updCod->execute() ? ['OK',"$cod — ya existía, actualizado y activado."] : ['ERR',"$cod: ".$updCod->error];
    } else {
        $insCod->bind_param('iss', $catId, $cod, $desc);
        $msgs[] = $insCod->execute() ? ['NEW',"$cod — agregado."] : ['ERR',"$cod: ".$insCod->error];
    }
}
$chkCod->close(); $updCod->close(); $insCod->close();

// 4) Desactivar los estigmatizantes
$des = $conexion->query("UPDATE ENFE_DIAG_COD SET ACTIVO=0 WHERE CODIGO IN ('E66.0','E66.01','E66.09')");
$msgs[] = $des ? ['NEW','Desactivados E66.0 / E66.01 / E66.09 — filas afectadas: '.$conexion->affected_rows] : ['ERR','No se pudo desactivar: '.$conexion->error];

$cls=['NEW'=>'success','OK'=>'secondary','ERR'=>'danger'];
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Resultado</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="p-4"><div class="container" style="max-width:720px;"><h4>Resultado</h4><ul class="list-group">';
foreach ($msgs as $m) echo '<li class="list-group-item"><span class="badge bg-'.($cls[$m[0]]??'secondary').' me-2">'.htmlspecialchars($m[0]).'</span>'.htmlspecialchars($m[1]).'</li>';
echo '</ul><a class="btn btn-primary mt-3" href="PNC_CIE-10Crear.php">Volver al catálogo</a></div></body></html>';
