<?php
/**
 * guardar_perfil.php — Actualiza los datos del usuario logueado
 * (nombres, apellidos, teléfono, correo, NPI, license_id).
 * El USUARIO (username) NO se puede cambiar desde el perfil.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

if (!isset($_SESSION["iduser"], $_SESSION["rol"])) { echo 'SIN_SESION'; exit; }

$idUsuario = (int)$_SESSION['iduser'];
if ($idUsuario <= 0) { echo 'SIN_SESION'; exit; }

$nombres   = trim($_POST['nombres']    ?? '');
$apellidos = trim($_POST['apellidos']  ?? '');
$telefono  = trim($_POST['telefono']   ?? '');
$correo    = trim($_POST['correo']     ?? '');
$npi       = trim($_POST['npi']        ?? '');
$license   = trim($_POST['license_id'] ?? '');

if ($nombres === '' || $apellidos === '') { echo 'DATOS_INCOMPLETOS'; exit; }

$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$colExiste = function($col) use ($conexion, $dbName) {
    return (int)$conexion->query(
        "SELECT COUNT(*) c FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA='$dbName' AND TABLE_NAME='ADM_USUARIO' AND COLUMN_NAME='$col'"
    )->fetch_assoc()['c'] > 0;
};
$tieneCorreo  = $colExiste('CORREO');
$tieneNpi     = $colExiste('NPI');
$tieneLicense = $colExiste('LICENSE_ID');

$campos = ['NOMBRES = ?', 'APELLIDOS = ?', 'TELEFONO = ?'];
$tipos  = 'sss';
$vals   = [$nombres, $apellidos, $telefono];
if ($tieneCorreo)  { $campos[] = 'CORREO = ?';     $tipos .= 's'; $vals[] = ($correo === '' ? null : $correo); }
if ($tieneNpi)     { $campos[] = 'NPI = ?';        $tipos .= 's'; $vals[] = ($npi === ''    ? null : $npi); }
if ($tieneLicense) { $campos[] = 'LICENSE_ID = ?'; $tipos .= 's'; $vals[] = ($license === '' ? null : $license); }

$sql = 'UPDATE ADM_USUARIO SET ' . implode(', ', $campos) . ' WHERE IDADM_USUARIO = ?';
$tipos .= 'i';
$vals[] = $idUsuario;

$stmt = $conexion->prepare($sql);
$stmt->bind_param($tipos, ...$vals);
if ($stmt->execute()) {
    // Refrescar la sesión (nombres/apellidos son visibles en la firma del informe, etc.)
    $_SESSION['nombres']   = $nombres;
    $_SESSION['apellidos'] = $apellidos;
    echo 'OK';
} else {
    echo 'ERROR: ' . $stmt->error;
}
$stmt->close();
$conexion->close();
