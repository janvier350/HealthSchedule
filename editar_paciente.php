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

// ¿Existe la columna ALERTA?
$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$tieneAlerta = (int)$conexion->query(
    "SELECT COUNT(*) c FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA='$dbName' AND TABLE_NAME='AG_PACIENTE' AND COLUMN_NAME='ALERTA'"
)->fetch_assoc()['c'] > 0;

if ($tieneAlerta) {
    $sql = "UPDATE AG_PACIENTE SET
                NOMBRES = ?, APELLIDOS = ?, CEDULA = ?, TELEFONO = ?, EMAIL = ?,
                FECHANACIMIENTO = ?, SEX = ?, GENDER = ?, ADDRESS = ?,
                NOTES = ?, ADDNOTES = ?, ALERTA = ?
            WHERE IDPACIENTE = ? AND ESTADO = 'A'";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param(
        "ssssssssssssi",
        $nombres, $apellidos, $cedula, $telefono, $email,
        $fecNacParam, $sex, $gender, $address,
        $notes, $addNotes, $alerta, $id
    );
} else {
    $sql = "UPDATE AG_PACIENTE SET
                NOMBRES = ?, APELLIDOS = ?, CEDULA = ?, TELEFONO = ?, EMAIL = ?,
                FECHANACIMIENTO = ?, SEX = ?, GENDER = ?, ADDRESS = ?,
                NOTES = ?, ADDNOTES = ?
            WHERE IDPACIENTE = ? AND ESTADO = 'A'";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param(
        "sssssssssssi",
        $nombres, $apellidos, $cedula, $telefono, $email,
        $fecNacParam, $sex, $gender, $address,
        $notes, $addNotes, $id
    );
}

if ($stmt->execute()) {
    echo $tieneAlerta ? 'OK' : 'OK_SIN_ALERTA';
} else {
    echo 'ERROR: ' . $stmt->error;
}

$stmt->close();
$conexion->close();
