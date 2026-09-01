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

// Construir el UPDATE dinámicamente
$campos = ["NOMBRES = ?", "APELLIDOS = ?", "CEDULA = ?", "TELEFONO = ?", "EMAIL = ?",
           "FECHANACIMIENTO = ?", "SEX = ?", "GENDER = ?", "ADDRESS = ?",
           "NOTES = ?", "ADDNOTES = ?"];
$tipos  = "sssssssssss";
$vals   = [$nombres, $apellidos, $cedula, $telefono, $email,
           $fecNacParam, $sex, $gender, $address, $notes, $addNotes];

if ($tieneAlerta) { $campos[] = "ALERTA = ?"; $tipos .= "s"; $vals[] = $alerta; }
if ($tieneIdioma && $idioma !== '') { $campos[] = "IDIOMA = ?"; $tipos .= "s"; $vals[] = $idioma; }

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
