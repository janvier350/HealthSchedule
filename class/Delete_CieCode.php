<?php
/**
 * Delete_CieCode.php — Elimina un código ICD-10 por su ID.
 * GET/POST: id
 * Si el código está referenciado por AG_PACIENTE.IDICD10, sólo redirige
 * con un aviso (no borra) — así no rompe integridad de pacientes.
 */
require_once("funciones.php");
require_once("conexionBD.php");
session_start();
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

if (!isset($_SESSION['rol'])) { header('Location: ../PNC_CIE-10Crear.php'); exit; }

$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
if ($id <= 0) { header('Location: ../PNC_CIE-10Crear.php?err=id'); exit; }

// ¿Alguna cita/paciente lo está usando?
$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$colIcd10 = (int)$conexion->query(
    "SELECT COUNT(*) c FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA='$dbName' AND TABLE_NAME='AG_PACIENTE' AND COLUMN_NAME='IDICD10'"
)->fetch_assoc()['c'] > 0;
if ($colIcd10) {
    $st = $conexion->prepare("SELECT COUNT(*) c FROM AG_PACIENTE WHERE IDICD10 = ?");
    $st->bind_param('i', $id);
    $st->execute();
    $usos = (int)$st->get_result()->fetch_assoc()['c'];
    $st->close();
    if ($usos > 0) {
        header('Location: ../PNC_CIE-10Crear.php?err=inuse&count=' . $usos); exit;
    }
}

$stmt = $conexion->prepare("DELETE FROM ENFE_DIAG_COD WHERE ID_ENFE_DIAG_COD = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    header('Location: ../PNC_CIE-10Crear.php?deleted=1'); exit;
}
$err = $stmt->error;
$stmt->close();
header('Location: ../PNC_CIE-10Crear.php?err=' . urlencode($err));
