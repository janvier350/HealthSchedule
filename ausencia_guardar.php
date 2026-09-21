<?php
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/class/permisos.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["rol"])) { echo json_encode(['ok'=>false,'error'=>'SIN_SESION']); exit; }
if (!puede('agenda.ausencias')) { echo json_encode(['ok'=>false,'error'=>'SIN_PERMISO']); exit; }

$rol   = strtoupper($_SESSION['rol'] ?? '');
$idU   = (int)($_SESSION['iduser'] ?? 0);

$tipo        = ($_POST['tipo'] ?? 'vacacion') === 'bloqueo' ? 'bloqueo' : 'vacacion';
$idDoctor    = (int)($_POST['idDoctor'] ?? 0);
$fechaInicio = trim($_POST['fecha_inicio'] ?? '');
$fechaFin    = trim($_POST['fecha_fin'] ?? '');
$horaInicio  = trim($_POST['hora_inicio'] ?? '');
$horaFin     = trim($_POST['hora_fin'] ?? '');
$motivo      = trim($_POST['motivo'] ?? '');

// El DOCTOR solo puede gestionar sus propias ausencias.
if ($rol === 'DOCTOR') { $idDoctor = $idU; }
if (!$idDoctor) { echo json_encode(['ok'=>false,'error'=>'Falta el doctor.']); exit; }

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio)) { echo json_encode(['ok'=>false,'error'=>'Fecha inválida.']); exit; }

if ($tipo === 'bloqueo') {
    // Bloqueo de horas: una sola fecha + rango de horas.
    $fechaFin = $fechaInicio;
    if (!preg_match('/^\d{2}:\d{2}$/', $horaInicio) || !preg_match('/^\d{2}:\d{2}$/', $horaFin)) {
        echo json_encode(['ok'=>false,'error'=>'Horas inválidas.']); exit;
    }
    if ($horaFin <= $horaInicio) { echo json_encode(['ok'=>false,'error'=>'La hora fin debe ser mayor a la de inicio.']); exit; }
    $hi = $horaInicio; $hf = $horaFin;
} else {
    // Vacaciones: rango de días completos.
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin) || $fechaFin < $fechaInicio) { $fechaFin = $fechaInicio; }
    $hi = null; $hf = null;
}

$stmt = $conexion->prepare(
    "INSERT INTO ausencias_doctor (IDDOCTOR, tipo, fecha_inicio, fecha_fin, hora_inicio, hora_fin, motivo, creado_por)
     VALUES (?,?,?,?,?,?,?,?)"
);
$stmt->bind_param('issssssi', $idDoctor, $tipo, $fechaInicio, $fechaFin, $hi, $hf, $motivo, $idU);
if ($stmt->execute()) { echo json_encode(['ok'=>true,'id'=>$conexion->insert_id]); }
else { echo json_encode(['ok'=>false,'error'=>$stmt->error]); }
$stmt->close();
