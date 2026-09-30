<?php
/**
 * cita_baja.php — Da de baja (borrado lógico) una cita SIN enviar correo al
 * paciente. Pensado para la revisión/limpieza de citas mal creadas.
 * A diferencia de eliminar_cita.php, no notifica por correo.
 * Requiere sesión y permiso agenda.revision.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/class/permisos.php");
require_once(__DIR__ . "/class/auditoria.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["rol"])) { echo json_encode(['ok'=>false,'error'=>'SIN_SESION']); exit; }
if (isset($_SESSION['expire']) && time() > $_SESSION['expire']) { echo json_encode(['ok'=>false,'error'=>'SIN_SESION']); exit; }
if (!puede('agenda.revision')) { echo json_encode(['ok'=>false,'error'=>'SIN_PERMISO']); exit; }

$idCita  = (int)($_POST['idCita'] ?? 0);
$alcance = trim($_POST['alcance'] ?? 'solo');
if ($alcance !== 'todas') $alcance = 'solo';
$motivo  = trim($_POST['motivo'] ?? 'Baja por revisión');
$idUser  = (int)($_SESSION['iduser'] ?? 0);
if (!$idCita) { echo json_encode(['ok'=>false,'error'=>'ID_INVALIDO']); exit; }

$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$dbEsc  = $conexion->real_escape_string($dbName);
$tieneSerie = (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$dbEsc' AND TABLE_NAME='AG_CITA' AND COLUMN_NAME='IDSERIE'")->fetch_assoc()['c'] > 0;
$tieneAudit = (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$dbEsc' AND TABLE_NAME='AG_CITA' AND COLUMN_NAME='MOTIVO_CANCELACION'")->fetch_assoc()['c'] > 0;

// Datos de la cita (para saber serie/fecha y para la auditoría)
$colSerie = $tieneSerie ? ', A.IDSERIE' : '';
$info = null;
if ($st = $conexion->prepare("SELECT A.IDPACIENTE, A.FECHA_CITA $colSerie,
                                     TRIM(CONCAT(COALESCE(P.NOMBRES,''),' ',COALESCE(P.APELLIDOS,''))) AS paciente
                                FROM AG_CITA A INNER JOIN AG_PACIENTE P ON P.IDPACIENTE=A.IDPACIENTE
                               WHERE A.IDCITA=? AND A.ESTADO='A' LIMIT 1")) {
    $st->bind_param('i', $idCita); $st->execute();
    $info = $st->get_result()->fetch_assoc(); $st->close();
}
if (!$info) { echo json_encode(['ok'=>false,'error'=>'NO_ENCONTRADA']); exit; }

$idSerie   = $tieneSerie ? (int)($info['IDSERIE'] ?? 0) : 0;
$fechaOrig = $info['FECHA_CITA'];
$setCancel = $tieneAudit ? ", MOTIVO_CANCELACION = ?, FECHA_CANCELACION = NOW(), CANCELADO_POR = ?" : "";

if ($alcance === 'todas' && $idSerie > 0) {
    $sql = "UPDATE AG_CITA SET ESTADO='I', ESTADO_CITA='Cancelado'$setCancel
            WHERE (IDSERIE = ? OR IDCITA = ?) AND ESTADO='A' AND FECHA_CITA >= ?";
    $stmt = $conexion->prepare($sql);
    if ($tieneAudit) { $stmt->bind_param("siiis", $motivo, $idUser, $idSerie, $idCita, $fechaOrig); }
    else             { $stmt->bind_param("iis", $idSerie, $idCita, $fechaOrig); }
} else {
    $sql = "UPDATE AG_CITA SET ESTADO='I', ESTADO_CITA='Cancelado'$setCancel WHERE IDCITA=? AND ESTADO='A'";
    $stmt = $conexion->prepare($sql);
    if ($tieneAudit) { $stmt->bind_param("sii", $motivo, $idUser, $idCita); }
    else             { $stmt->bind_param("i", $idCita); }
}
if (!$stmt->execute()) { echo json_encode(['ok'=>false,'error'=>$stmt->error]); exit; }
$bajas = $stmt->affected_rows;
$stmt->close();

auditar($conexion, 'Agenda', 'eliminar', 'AG_CITA', $idCita,
    'Baja por revisión'.($alcance==='todas'?' (serie)':'').' — '.$info['paciente'].' — '.$fechaOrig.' — '.$bajas.' cita(s)');

echo json_encode(['ok'=>true, 'bajas'=>(int)$bajas]);
