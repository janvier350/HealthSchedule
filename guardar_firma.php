<?php
/**
 * guardar_firma.php — Guarda o elimina la firma del usuario logueado.
 * POST:
 *   accion=guardar  + firma=<data:image/png;base64,...>
 *   accion=eliminar
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

if (!isset($_SESSION["iduser"])) { echo 'SIN_SESION'; exit; }
$idUsuario = (int)$_SESSION['iduser'];
if ($idUsuario <= 0) { echo 'SIN_SESION'; exit; }

// Verifica que la columna exista (por si no se ha corrido la migración)
$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$existeCol = (int)$conexion->query(
    "SELECT COUNT(*) c FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA='$dbName' AND TABLE_NAME='ADM_USUARIO' AND COLUMN_NAME='FIRMA_IMG'"
)->fetch_assoc()['c'] > 0;
if (!$existeCol) { echo 'FALTA_MIGRACION'; exit; }

$accion = $_POST['accion'] ?? 'guardar';

if ($accion === 'eliminar') {
    $stmt = $conexion->prepare("UPDATE ADM_USUARIO SET FIRMA_IMG = NULL WHERE IDADM_USUARIO = ?");
    $stmt->bind_param("i", $idUsuario);
    echo $stmt->execute() ? 'OK' : ('ERROR: ' . $stmt->error);
    $stmt->close();
    exit;
}

$firma = $_POST['firma'] ?? '';
if (!preg_match('#^data:image/(png|jpeg|jpg);base64,#i', $firma)) {
    echo 'FORMATO_INVALIDO'; exit;
}
// Límite ~1.5 MB base64 (~1.1 MB binario) — más que suficiente para una firma
if (strlen($firma) > 1600000) { echo 'ARCHIVO_MUY_GRANDE'; exit; }

$stmt = $conexion->prepare("UPDATE ADM_USUARIO SET FIRMA_IMG = ? WHERE IDADM_USUARIO = ?");
$stmt->bind_param("si", $firma, $idUsuario);
echo $stmt->execute() ? 'OK' : ('ERROR: ' . $stmt->error);
$stmt->close();
$conexion->close();
