<?php
/**
 * restablecer_cita.php — Restablece una cita cancelada por error.
 * Pone ESTADO_CITA = 'Pendiente' y limpia la auditoría de cancelación
 * (motivo/fecha/quién) si esas columnas existen.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["rol"])) { echo json_encode(['ok'=>false,'error'=>'SIN_SESION']); exit; }

$id = (int)($_POST['idCita'] ?? $_POST['id'] ?? 0);
if (!$id) { echo json_encode(['ok'=>false,'error'=>'Falta el id de la cita.']); exit; }

// ¿Existe la cita?
$chk = $conexion->prepare("SELECT IDCITA FROM AG_CITA WHERE IDCITA=? AND ESTADO='A' LIMIT 1");
$chk->bind_param('i', $id); $chk->execute();
if (!$chk->get_result()->fetch_assoc()) { $chk->close(); echo json_encode(['ok'=>false,'error'=>'NO_ENCONTRADA']); exit; }
$chk->close();

// ¿Existen las columnas de auditoría de cancelación?
$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$tieneAudit = (int)$conexion->query(
    "SELECT COUNT(*) c FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA='".$conexion->real_escape_string($dbName)."'
       AND TABLE_NAME='AG_CITA' AND COLUMN_NAME='MOTIVO_CANCELACION'"
)->fetch_assoc()['c'] > 0;

if ($tieneAudit) {
    $stmt = $conexion->prepare("UPDATE AG_CITA SET ESTADO_CITA='Pendiente', MOTIVO_CANCELACION=NULL, FECHA_CANCELACION=NULL, CANCELADO_POR=NULL WHERE IDCITA=?");
} else {
    $stmt = $conexion->prepare("UPDATE AG_CITA SET ESTADO_CITA='Pendiente' WHERE IDCITA=?");
}
$stmt->bind_param('i', $id);
if ($stmt->execute()) { echo json_encode(['ok'=>true]); }
else { echo json_encode(['ok'=>false,'error'=>$stmt->error]); }
$stmt->close();
