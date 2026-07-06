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

// Validar formato de fecha (YYYY-MM-DD) y hora (HH:MM)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !preg_match('/^\d{2}:\d{2}$/', $hora)) {
    echo 'FORMATO_INVALIDO';
    exit;
}

// Hora fin = 30 minutos después
$horaFin = date('H:i', strtotime($hora) + 30 * 60);

// Verificar que no exista otra cita activa en ese horario (excluyendo esta misma cita)
$stmt_valida = $conexion->prepare(
    "SELECT IDCITA FROM AG_CITA
     WHERE FECHA_CITA = ? AND HORA_INICIO = ? AND ESTADO = 'A'
       AND ESTADO_CITA NOT IN ('Cancelada','Cancelado')
       AND IDCITA <> ?"
);
$stmt_valida->bind_param("ssi", $fecha, $hora, $idCita);
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
