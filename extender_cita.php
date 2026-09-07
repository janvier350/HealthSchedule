<?php
/**
 * extender_cita.php — Ajusta HORA_FIN de una cita.
 *
 * POST:
 *   idCita   (obligatorio)
 *   horaFin  (opcional, HH:MM) → fija la hora fin exacta
 *   minutos  (opcional, por defecto 30) → si NO se envía horaFin, extiende
 *            la hora fin actual sumando `minutos`
 *
 * Devuelve JSON: { ok:true, horaFin:'HH:MM', minutos:N } | { ok:false, error:'...' }
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["rol"])) {
    echo json_encode(['ok' => false, 'error' => 'SIN_SESION']); exit;
}

$idCita     = (int)($_POST['idCita']  ?? 0);
$horaFinIn  = trim($_POST['horaFin'] ?? '');
$minutos    = (int)($_POST['minutos'] ?? 30);
if ($idCita <= 0) { echo json_encode(['ok'=>false,'error'=>'DATOS_INCOMPLETOS']); exit; }

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

$tsIni = strtotime($row['HORA_INICIO'] ?: '00:00');
$horaFinActual = $row['HORA_FIN'] ?: $row['HORA_INICIO'];
$tsFinActual   = strtotime($horaFinActual);
if ($tsIni === false || $tsFinActual === false) {
    echo json_encode(['ok'=>false,'error'=>'HORA_INVALIDA']); exit;
}

// Modo 1: hora fin absoluta
if ($horaFinIn !== '') {
    if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $horaFinIn)) {
        echo json_encode(['ok'=>false,'error'=>'HORA_INVALIDA']); exit;
    }
    $tsNueva = strtotime($horaFinIn);
    if ($tsNueva === false) { echo json_encode(['ok'=>false,'error'=>'HORA_INVALIDA']); exit; }
    if ($tsNueva <= $tsIni) { echo json_encode(['ok'=>false,'error'=>'FIN_ANTES_INICIO']); exit; }
    $minutos = (int)round(($tsNueva - $tsFinActual) / 60);
} else {
    // Modo 2: sumar minutos a la hora fin actual (retrocompatible)
    if ($minutos < -240 || $minutos > 480 || $minutos === 0) {
        echo json_encode(['ok'=>false,'error'=>'MINUTOS_INVALIDOS']); exit;
    }
    $tsNueva = $tsFinActual + $minutos * 60;
    if ($tsNueva <= $tsIni) { echo json_encode(['ok'=>false,'error'=>'FIN_ANTES_INICIO']); exit; }
}

// Tope de seguridad: no pasar del final del día (23:59)
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
