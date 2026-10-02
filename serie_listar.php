<?php
/**
 * serie_listar.php — Devuelve (JSON) todas las citas de la serie a la que
 * pertenece una cita, para gestionarlas (ver, dar de baja, cambiar frecuencia).
 * Requiere sesión y permiso agenda.revision.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/class/permisos.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["rol"])) { echo json_encode(['ok'=>false,'error'=>'SIN_SESION']); exit; }
if (!puede('agenda.revision')) { echo json_encode(['ok'=>false,'error'=>'SIN_PERMISO']); exit; }

$idCita = (int)($_GET['idCita'] ?? 0);
if ($idCita <= 0) { echo json_encode(['ok'=>false,'error'=>'ID_INVALIDO']); exit; }

$dbEsc = $conexion->real_escape_string($conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db']);
$tieneSerie = (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$dbEsc' AND TABLE_NAME='AG_CITA' AND COLUMN_NAME='IDSERIE'")->fetch_assoc()['c'] > 0;

// Datos base de la cita
$colSerie = $tieneSerie ? 'A.IDSERIE' : '0 AS IDSERIE';
$st = $conexion->prepare("SELECT $colSerie, A.IDPACIENTE,
                                 TRIM(CONCAT(COALESCE(P.NOMBRES,''),' ',COALESCE(P.APELLIDOS,''))) AS paciente
                            FROM AG_CITA A
                            INNER JOIN AG_PACIENTE P ON P.IDPACIENTE = A.IDPACIENTE
                            WHERE A.IDCITA = ? LIMIT 1");
$st->bind_param('i', $idCita); $st->execute();
$base = $st->get_result()->fetch_assoc(); $st->close();
if (!$base) { echo json_encode(['ok'=>false,'error'=>'NO_ENCONTRADA']); exit; }

$idSerie = $tieneSerie ? (int)($base['IDSERIE'] ?? 0) : 0;

// Si pertenece a una serie, traer toda la serie; si no, solo esta cita.
if ($idSerie > 0) {
    $q = $conexion->prepare("SELECT A.IDCITA, A.FECHA_CITA, A.HORA_INICIO, A.HORA_FIN, A.ESTADO_CITA
                               FROM AG_CITA A
                              WHERE (A.IDSERIE = ? OR A.IDCITA = ?) AND A.ESTADO='A'
                              ORDER BY A.FECHA_CITA ASC, A.HORA_INICIO ASC");
    $q->bind_param('ii', $idSerie, $idSerie);
} else {
    $q = $conexion->prepare("SELECT A.IDCITA, A.FECHA_CITA, A.HORA_INICIO, A.HORA_FIN, A.ESTADO_CITA
                               FROM AG_CITA A WHERE A.IDCITA=? AND A.ESTADO='A'");
    $q->bind_param('i', $idCita);
}
$q->execute();
$rs = $q->get_result();
$hoy = date('Y-m-d');
$lista = [];
while ($x = $rs->fetch_assoc()) {
    $lista[] = [
        'id'     => (int)$x['IDCITA'],
        'fecha'  => date('m/d/Y', strtotime($x['FECHA_CITA'])),
        'fechaISO'=> $x['FECHA_CITA'],
        'hora'   => substr($x['HORA_INICIO'],0,5) . ($x['HORA_FIN'] ? '–'.substr($x['HORA_FIN'],0,5) : ''),
        'estado' => $x['ESTADO_CITA'],
        'futura' => ($x['FECHA_CITA'] >= $hoy),
    ];
}
$q->close();

echo json_encode(['ok'=>true, 'paciente'=>$base['paciente'], 'idSerie'=>$idSerie, 'esSerie'=>($idSerie>0), 'lista'=>$lista]);
