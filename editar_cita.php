<?php
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

if (!isset($_SESSION["rol"])) {
    echo 'SIN_SESION';
    exit;
}

$idCita         = isset($_POST['idCita'])         ? (int)$_POST['idCita']         : 0;
$fecha          = isset($_POST['fecha'])          ? trim($_POST['fecha'])          : '';
$hora           = isset($_POST['hora'])           ? trim($_POST['hora'])           : '';
$idTipoConsulta = isset($_POST['idTipoConsulta']) ? (int)$_POST['idTipoConsulta'] : 0;
$idDoctor       = isset($_POST['idDoctor'])       ? (int)$_POST['idDoctor']       : 0;
$idAgencia      = isset($_POST['idAgencia'])      ? (int)$_POST['idAgencia']      : 0;

if (!$idCita || !$fecha || !$hora || !$idTipoConsulta || !$idDoctor) {
    echo 'DATOS_INCOMPLETOS';
    exit;
}

// Validar formato de fecha (YYYY-MM-DD) y hora (HH:MM, cualquier minuto)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !preg_match('/^\d{2}:\d{2}$/', $hora)) {
    echo 'FORMATO_INVALIDO';
    exit;
}

// Horario de atención: 07:00 a 22:30
if ($hora < '07:00' || $hora > '22:30') {
    echo 'HORA_FUERA_RANGO';
    exit;
}

// Hora fin = 30 minutos después
$horaFin = date('H:i', strtotime($hora) + 30 * 60);

// Verificar que la cita no se solape con otra cita activa del mismo día
// (excluyendo esta misma cita). Al permitir minutos libres ya no basta
// comparar la hora exacta: dos citas chocan si sus rangos se cruzan.
$stmt_valida = $conexion->prepare(
    "SELECT IDCITA FROM AG_CITA
     WHERE FECHA_CITA = ? AND ESTADO = 'A'
       AND ESTADO_CITA NOT IN ('Cancelada','Cancelado')
       AND IDCITA <> ?
       AND TIME(HORA_INICIO) < TIME(?)
       AND TIME(HORA_FIN)    > TIME(?)"
);
$stmt_valida->bind_param("siss", $fecha, $idCita, $horaFin, $hora);
$stmt_valida->execute();
$stmt_valida->store_result();

if ($stmt_valida->num_rows > 0) {
    $stmt_valida->close();
    echo 'HORARIO_OCUPADO';
    exit;
}
$stmt_valida->close();

// Editar = corregir datos de la cita; a diferencia de reagendar,
// el ESTADO_CITA se mantiene tal como está.
$stmt = $conexion->prepare(
    "UPDATE AG_CITA
     SET FECHA_CITA     = ?,
         HORA_INICIO    = ?,
         HORA_FIN       = ?,
         IDTIPOCONSULTA = ?,
         IDDOCTOR       = ?,
         IDAGENCIA      = NULLIF(?, 0)
     WHERE IDCITA = ? AND ESTADO = 'A'"
);
$stmt->bind_param("sssiiii", $fecha, $hora, $horaFin, $idTipoConsulta, $idDoctor, $idAgencia, $idCita);

if ($stmt->execute()) {
    echo 'OK';
} else {
    echo 'ERROR: ' . $stmt->error;
}

$stmt->close();
$conexion->close();
