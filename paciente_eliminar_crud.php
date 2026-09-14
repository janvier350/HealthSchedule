<?php
/**
 * paciente_eliminar_crud.php
 * Baja lógica (soft-delete) de un paciente para el CRUD.
 * Exige un MOTIVO obligatorio y guarda auditoría (motivo, fecha, usuario).
 * Prepared statements. Responde texto plano para AJAX.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

if (!isset($_SESSION["rol"])) { http_response_code(403); echo 'SIN_SESION'; exit; }

$id     = (int)($_POST['idPaciente'] ?? $_POST['id'] ?? 0);
$motivo = trim($_POST['motivo'] ?? '');
if ($id <= 0) { echo 'ID_INVALIDO'; exit; }
if ($motivo === '') { echo 'MOTIVO_REQUERIDO'; exit; }

// ¿Existen las columnas de auditoría?
$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$tieneAudit = (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='".$conexion->real_escape_string($dbName)."' AND TABLE_NAME='AG_PACIENTE' AND COLUMN_NAME='MOTIVO_ELIMINACION'")->fetch_assoc()['c']>0;

$idUser = (int)($_SESSION['iduser'] ?? 0);
if ($tieneAudit) {
    $stmt = $conexion->prepare("UPDATE AG_PACIENTE SET ESTADO='I', MOTIVO_ELIMINACION=?, FECHA_ELIMINACION=NOW(), ELIMINADO_POR=? WHERE IDPACIENTE=? AND ESTADO='A'");
    $stmt->bind_param('sii', $motivo, $idUser, $id);
} else {
    $stmt = $conexion->prepare("UPDATE AG_PACIENTE SET ESTADO='I' WHERE IDPACIENTE=? AND ESTADO='A'");
    $stmt->bind_param('i', $id);
}
if ($stmt->execute()) {
    echo $stmt->affected_rows > 0 ? 'OK' : 'NO_CAMBIO';
} else {
    echo 'ERROR: ' . $stmt->error;
}
$stmt->close();
