<?php
/**
 * extender_cita.php — Extiende HORA_FIN de una cita en 30 minutos.
 *
 * POST: idCita, minutos (opcional, por defecto 30)
 * Devuelve JSON: { ok:true, horaFin:'HH:MM' }  |  { ok:false, error:'...' }
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["rol"])) {
    echo json_encode(['ok' => false, 'error' => 'SIN_SESION']); exit;
}

$idCita  = (int)($_POST['idCita']  ?? 0);
$minutos = (int)($_POST['minutos'] ?? 30);
if ($idCita <= 0)              { echo json_encode(['ok'=>false,'error'=>'DATOS_INCOMPLETOS']); exit; }
if ($minutos < 5 || $minutos > 240) { echo json_encode(['ok'=>false,'error'=>'MINUTOS_INVALIDOS']); exit; }

// Traer HORA_INICIO y HORA_FIN actuales
$stmt = $conexion->prepare("SELECT HORA_INICIO, HORA_FIN FROM AG_CITA WHERE IDCITA = ? AND ESTADO = 'A'");
$stmt->bind_param('i', $idCita);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    echo json_encode(['ok'=>false,'error'=>'CITA_NO_ENCONTRADA']); exit;
}
$row = $res->fetch_assoc();
$stmt->close();

$horaFinActual = $row['HORA_FIN'] ?: $row['HORA_INICIO'];
$tsFin = strtotime($horaFinActual);
if ($tsFin === false) {
    echo json_encode(['ok'=>false,'error'=>'HORA_INVALIDA']); exit;
}
$tsNueva = $tsFin + $minutos * 60;

// Tope de seguridad: no pasar del final de la agenda (23:59)
$tsTope = strtotime('23:59');
if ($tsNueva > $tsTope) {
    echo json_encode(['ok'=>false,'error'=>'FUERA_DE_RANGO']); exit;
}
$horaFinNueva = date('H:i:s', $tsNueva);

$stmt = $conexion->prepare("UPDATE AG_CITA SET HORA_FIN = ? WHERE IDCITA = ?");
$stmt->bind_param('si', $horaFinNueva, $idCita);
if (!$stmt->execute()) {
    echo json_encode(['ok'=>false,'error'=>'ERROR: '.$stmt->error]); exit;
}
$stmt->close();
$conexion->close();

echo json_encode(['ok'=>true, 'horaFin'=>substr($horaFinNueva, 0, 5), 'minutos'=>$minutos]);
