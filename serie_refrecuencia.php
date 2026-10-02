<?php
/**
 * serie_refrecuencia.php — Cambia la frecuencia de las citas FUTURAS de una
 * serie (p. ej. de semanal a quincenal), re-espaciándolas desde la primera
 * cita futura pendiente. No toca las ya atendidas ni las pasadas.
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
if (!puede('agenda.revision')) { echo json_encode(['ok'=>false,'error'=>'SIN_PERMISO']); exit; }

$idCita = (int)($_POST['idCita'] ?? 0);
$dias   = (int)($_POST['dias'] ?? 0);   // 7, 14, 30, o personalizado 1..90
if ($idCita <= 0) { echo json_encode(['ok'=>false,'error'=>'ID_INVALIDO']); exit; }
if ($dias < 1 || $dias > 90) { echo json_encode(['ok'=>false,'error'=>'FRECUENCIA_INVALIDA']); exit; }

$dbEsc = $conexion->real_escape_string($conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db']);
$tieneSerie = (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$dbEsc' AND TABLE_NAME='AG_CITA' AND COLUMN_NAME='IDSERIE'")->fetch_assoc()['c'] > 0;
if (!$tieneSerie) { echo json_encode(['ok'=>false,'error'=>'SIN_SERIES']); exit; }

// Serie de la cita
$st = $conexion->prepare("SELECT IDSERIE, IDPACIENTE FROM AG_CITA WHERE IDCITA=? AND ESTADO='A' LIMIT 1");
$st->bind_param('i', $idCita); $st->execute();
$base = $st->get_result()->fetch_assoc(); $st->close();
if (!$base) { echo json_encode(['ok'=>false,'error'=>'NO_ENCONTRADA']); exit; }
$idSerie = (int)($base['IDSERIE'] ?? 0);
if ($idSerie <= 0) { echo json_encode(['ok'=>false,'error'=>'NO_ES_SERIE']); exit; }

// Citas FUTURAS pendientes de la serie (no atendidas, no canceladas), ordenadas
$hoy = date('Y-m-d');
$q = $conexion->prepare(
    "SELECT IDCITA, FECHA_CITA FROM AG_CITA
      WHERE (IDSERIE=? OR IDCITA=?) AND ESTADO='A'
        AND FECHA_CITA >= ?
        AND ESTADO_CITA NOT IN ('Cancelada','Cancelado','A','Atendida','No Asistió')
      ORDER BY FECHA_CITA ASC, HORA_INICIO ASC"
);
$q->bind_param('iis', $idSerie, $idSerie, $hoy);
$q->execute();
$rs = $q->get_result();
$futuras = [];
while ($x = $rs->fetch_assoc()) $futuras[] = $x;
$q->close();

if (count($futuras) < 2) { echo json_encode(['ok'=>false,'error'=>'POCAS_FUTURAS']); exit; }

// Re-espaciar: ancla = primera futura; las siguientes a +N, +2N, ...
$ancla = $futuras[0]['FECHA_CITA'];
$upd = $conexion->prepare("UPDATE AG_CITA SET FECHA_CITA=? WHERE IDCITA=? AND ESTADO='A'");
$cambiadas = 0;
for ($i = 1; $i < count($futuras); $i++) {
    $nueva = date('Y-m-d', strtotime($ancla . ' +' . ($dias * $i) . ' days'));
    $idc = (int)$futuras[$i]['IDCITA'];
    if ($nueva !== $futuras[$i]['FECHA_CITA']) {
        $upd->bind_param('si', $nueva, $idc);
        if ($upd->execute()) $cambiadas++;
    }
}
$upd->close();

auditar($conexion, 'Agenda', 'editar', 'AG_CITA', $idCita,
    'Cambió la frecuencia de la serie a cada '.$dias.' días (desde '.date('m/d/Y', strtotime($ancla)).'); '.$cambiadas.' cita(s) reubicada(s)');

echo json_encode(['ok'=>true, 'cambiadas'=>$cambiadas]);
