<?php
/**
 * paciente_recuperar.php
 * Reactiva un paciente eliminado (ESTADO 'I' -> 'A') y limpia la auditoría.
 * SISTEMA-only, POST-driven (AJAX). Prepared statement.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
if (!isset($_SESSION["rol"]) || strtoupper($_SESSION["rol"]) !== 'SISTEMA') { http_response_code(403); echo 'NO_AUTORIZADO'; exit; }

$id = (int)($_POST['idPaciente'] ?? 0);
if ($id <= 0) { echo 'ID_INVALIDO'; exit; }

$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$tieneAudit = (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='".$conexion->real_escape_string($dbName)."' AND TABLE_NAME='AG_PACIENTE' AND COLUMN_NAME='MOTIVO_ELIMINACION'")->fetch_assoc()['c']>0;

if ($tieneAudit) {
    $stmt = $conexion->prepare("UPDATE AG_PACIENTE SET ESTADO='A', MOTIVO_ELIMINACION=NULL, FECHA_ELIMINACION=NULL, ELIMINADO_POR=NULL WHERE IDPACIENTE=? AND ESTADO='I'");
} else {
    $stmt = $conexion->prepare("UPDATE AG_PACIENTE SET ESTADO='A' WHERE IDPACIENTE=? AND ESTADO='I'");
}
$stmt->bind_param('i',$id);
echo $stmt->execute() ? ($stmt->affected_rows>0 ? 'OK' : 'NO_CAMBIO') : ('ERROR: '.$stmt->error);
$stmt->close();
