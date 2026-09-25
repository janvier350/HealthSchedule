<?php
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
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
    echo $tieneAlerta ? 'OK' : 'OK_SIN_ALERTA';
} else {
    echo 'ERROR: ' . $stmt->error;
}

$stmt->close();
$conexion->close();
