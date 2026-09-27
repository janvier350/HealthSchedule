<?php
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once("class/auditoria.php");
$conexion = conectarse();

if (!isset($_SESSION["rol"])) { echo 'SIN_SESION'; exit; }

$id        = (int)($_POST['idPaciente'] ?? 0);
$nombres   = trim($_POST['nombres']   ?? '');
$apellidos = trim($_POST['apellidos'] ?? '');
$cedula    = trim($_POST['cedula']    ?? '');
$telefono  = trim($_POST['telefono']  ?? '');
$email     = trim($_POST['email']     ?? '');
$fecNac    = trim($_POST['fecNac']    ?? '');
$sex       = trim($_POST['sex']       ?? '');
$gender    = trim($_POST['gender']    ?? '');
$address   = trim($_POST['address']   ?? '');
$city      = trim($_POST['city']      ?? '');
$state     = trim($_POST['state']     ?? '');
$zip       = trim($_POST['zip']       ?? '');
$alerta    = trim($_POST['alerta']    ?? '');   // Nota de Alerta
$notes     = trim($_POST['notes']     ?? '');   // Notas Importantes
$addNotes  = trim($_POST['addNotes']  ?? '');   // Notas de Facturación

if (!$id || $nombres === '' || $apellidos === '') {
    echo 'DATOS_INCOMPLETOS';
    exit;
}

// Fecha de nacimiento: vacía → NULL (evita '0000-00-00' en modo estricto)
$fecNacParam = ($fecNac === '') ? null : $fecNac;

// Idioma preferido (solo se guarda si es válido)
$idioma = strtolower(trim($_POST['idioma'] ?? ''));
if ($idioma !== 'en' && $idioma !== 'es') $idioma = '';

// ¿Qué columnas opcionales existen? (ALERTA, IDIOMA)
$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$colExiste = function ($col) use ($conexion, $dbName) {
    return (int)$conexion->query(
        "SELECT COUNT(*) c FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA='$dbName' AND TABLE_NAME='AG_PACIENTE' AND COLUMN_NAME='$col'"
    )->fetch_assoc()['c'] > 0;
};
$tieneAlerta = $colExiste('ALERTA');
$tieneIdioma = $colExiste('IDIOMA');
$tieneIcd10  = $colExiste('IDICD10');
$tieneCity   = $colExiste('CITY');
$tieneState  = $colExiste('STATE');
$tieneZip    = $colExiste('ZIP');

// Auto-provisión: si faltan las columnas de ciudad/estado/ZIP, crearlas aquí
// para no perder lo que el usuario escribe (evita que "se elimine" la ciudad
// o el estado cuando la migración no se ha ejecutado en el servidor).
if (!$tieneCity)  { if ($conexion->query("ALTER TABLE AG_PACIENTE ADD COLUMN CITY VARCHAR(120) NULL"))  $tieneCity  = true; }
if (!$tieneState) { if ($conexion->query("ALTER TABLE AG_PACIENTE ADD COLUMN STATE VARCHAR(60) NULL"))  $tieneState = true; }
if (!$tieneZip)   { if ($conexion->query("ALTER TABLE AG_PACIENTE ADD COLUMN ZIP VARCHAR(15) NULL"))     $tieneZip   = true; }

// Auto-ampliación: garantizar que ADDRESS y TELEFONO sean suficientemente
// amplias para una dirección completa y teléfonos largos (evita el error
// "Data too long" que hacía fallar todo el guardado).
$anchoCol = function ($col) use ($conexion, $dbName) {
    $r = $conexion->query(
        "SELECT DATA_TYPE, CHARACTER_MAXIMUM_LENGTH AS len FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA='$dbName' AND TABLE_NAME='AG_PACIENTE' AND COLUMN_NAME='$col' LIMIT 1"
    );
    return $r ? $r->fetch_assoc() : null;
};
foreach (['ADDRESS' => ['VARCHAR(255)', 255], 'TELEFONO' => ['VARCHAR(30)', 30]] as $col => $obj) {
    $info = $anchoCol($col);
    if (!$info) continue;
    $len = ($info['len'] === null) ? 0 : (int)$info['len'];
    $esTexto = in_array(strtolower($info['DATA_TYPE']), ['varchar','char','text','tinytext','mediumtext','longtext'], true);
    if (!$esTexto || $len < $obj[1]) { $conexion->query("ALTER TABLE AG_PACIENTE MODIFY COLUMN $col {$obj[0]} NULL"); }
}

$idicd10 = isset($_POST['idicd10']) && ctype_digit((string)$_POST['idicd10']) ? (int)$_POST['idicd10'] : 0;

// ── Diff de campos para la bitácora: leer valores actuales y comparar ──
$campoLabel = [
    'NOMBRES'=>'Nombres','APELLIDOS'=>'Apellidos','CEDULA'=>'ID','TELEFONO'=>'Teléfono',
    'EMAIL'=>'Correo','FECHANACIMIENTO'=>'Fecha nac.','SEX'=>'Sexo','GENDER'=>'Género',
    'ADDRESS'=>'Dirección','NOTES'=>'Notas','ADDNOTES'=>'Notas fact.',
    'CITY'=>'Ciudad','STATE'=>'Estado','ZIP'=>'ZIP','ALERTA'=>'Alerta','IDIOMA'=>'Idioma',
];
$nuevo = [
    'NOMBRES'=>$nombres,'APELLIDOS'=>$apellidos,'CEDULA'=>$cedula,'TELEFONO'=>$telefono,
    'EMAIL'=>$email,'FECHANACIMIENTO'=>(string)($fecNacParam ?? ''),'SEX'=>$sex,'GENDER'=>$gender,
    'ADDRESS'=>$address,'NOTES'=>$notes,'ADDNOTES'=>$addNotes,
];
if ($tieneCity)   $nuevo['CITY']   = $city;
if ($tieneState)  $nuevo['STATE']  = $state;
if ($tieneZip)    $nuevo['ZIP']    = $zip;
if ($tieneAlerta) $nuevo['ALERTA'] = $alerta;
if ($tieneIdioma && $idioma !== '') $nuevo['IDIOMA'] = $idioma;

$actual = [];
$colsSel = array_keys($nuevo);
if ($sSel = $conexion->prepare("SELECT ".implode(',', $colsSel)." FROM AG_PACIENTE WHERE IDPACIENTE=? LIMIT 1")) {
    $sSel->bind_param('i', $id); $sSel->execute();
    $actual = $sSel->get_result()->fetch_assoc() ?: [];
    $sSel->close();
}
$corta = function ($s) {
    $s = preg_replace('/\s+/', ' ', strip_tags(trim((string)$s)));
    if ($s === '') return '(vacío)';
    return mb_strlen($s) > 40 ? mb_substr($s, 0, 40).'…' : $s;
};
$cambios = [];
foreach ($nuevo as $col => $valNuevo) {
    $valViejo = (string)($actual[$col] ?? '');
    if ($col === 'FECHANACIMIENTO' && $valViejo === '0000-00-00') $valViejo = '';
    if (trim($valViejo) === trim((string)$valNuevo)) continue;
    $cambios[] = ($campoLabel[$col] ?? $col).': "'.$corta($valViejo).'" → "'.$corta($valNuevo).'"';
}

// Construir el UPDATE dinámicamente
$campos = ["NOMBRES = ?", "APELLIDOS = ?", "CEDULA = ?", "TELEFONO = ?", "EMAIL = ?",
           "FECHANACIMIENTO = ?", "SEX = ?", "GENDER = ?", "ADDRESS = ?",
           "NOTES = ?", "ADDNOTES = ?"];
$tipos  = "sssssssssss";
$vals   = [$nombres, $apellidos, $cedula, $telefono, $email,
           $fecNacParam, $sex, $gender, $address, $notes, $addNotes];

if ($tieneCity)   { $campos[] = "CITY = ?";  $tipos .= "s"; $vals[] = $city; }
if ($tieneState)  { $campos[] = "STATE = ?"; $tipos .= "s"; $vals[] = $state; }
if ($tieneZip)    { $campos[] = "ZIP = ?";   $tipos .= "s"; $vals[] = $zip; }
if ($tieneAlerta) { $campos[] = "ALERTA = ?"; $tipos .= "s"; $vals[] = $alerta; }
if ($tieneIdioma && $idioma !== '') { $campos[] = "IDIOMA = ?"; $tipos .= "s"; $vals[] = $idioma; }
if ($tieneIcd10)  { $campos[] = "IDICD10 = ?"; $tipos .= "i"; $vals[] = $idicd10 > 0 ? $idicd10 : null; }

$sql = "UPDATE AG_PACIENTE SET " . implode(", ", $campos) . " WHERE IDPACIENTE = ? AND ESTADO = 'A'";
$tipos .= "i";
$vals[] = $id;

$stmt = $conexion->prepare($sql);
$stmt->bind_param($tipos, ...$vals);

if ($stmt->execute()) {
    $detalle = 'Editó paciente: '.trim($nombres.' '.$apellidos);
    $detalle .= $cambios ? ' — '.implode('; ', $cambios) : ' (sin cambios de datos)';
    auditar($conexion, 'Pacientes', 'editar', 'AG_PACIENTE', $id, $detalle);
    echo $tieneAlerta ? 'OK' : 'OK_SIN_ALERTA';
} else {
    echo 'ERROR: ' . $stmt->error;
}

$stmt->close();
$conexion->close();
