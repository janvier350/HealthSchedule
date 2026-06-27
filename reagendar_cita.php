<?php
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

if (!isset($_SESSION["rol"])) {
    echo 'SIN_SESION';
    exit;
}

$idCita = isset($_POST['idCita']) ? (int)$_POST['idCita'] : 0;
$fecha  = isset($_POST['fecha'])  ? trim($_POST['fecha'])  : '';
$hora   = isset($_POST['hora'])   ? trim($_POST['hora'])   : '';

if (!$idCita || !$fecha || !$hora) {
    echo 'DATOS_INCOMPLETOS';
    exit;
}

// Validar formato de fecha (YYYY-MM-DD) y hora (HH:MM)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !preg_match('/^\d{2}:\d{2}$/', $hora)) {
    echo 'FORMATO_INVALIDO';
    exit;
}

$fecha = $conexion->real_escape_string($fecha);
$hora  = $conexion->real_escape_string($hora);

// Hora fin = 30 minutos después
$horaFin = date('H:i', strtotime($hora) + 30 * 60);

$sql = "UPDATE AG_CITA
        SET FECHA_CITA = '$fecha',
            HORA_INICIO = '$hora',
            HORA_FIN = '$horaFin',
            ESTADO_CITA = 'Pendiente'
        WHERE IDCITA = $idCita AND ESTADO = 'A'";

if ($conexion->query($sql)) {
    echo 'OK';
} else {
    echo 'ERROR: ' . $conexion->error;
}
