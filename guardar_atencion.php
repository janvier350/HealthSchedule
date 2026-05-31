<?php
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

if (!isset($_SESSION["rol"])) {
    echo 'SIN_SESION';
    exit;
}

$idCita   = isset($_POST['idCita'])  ? (int)$_POST['idCita']  : 0;
$informe  = isset($_POST['informe']) ? $_POST['informe']       : '';
$peso     = isset($_POST['peso'])    ? (float)$_POST['peso']   : 0;
$talla    = isset($_POST['talla'])   ? (float)$_POST['talla']  : 0;
$imc      = isset($_POST['imc'])     ? (float)$_POST['imc']    : 0;

if ($idCita <= 0 || trim(strip_tags($informe)) === '') {
    echo 'DATOS_INCOMPLETOS';
    exit;
}

$informe = $conexion->real_escape_string($informe);

$sql = "INSERT INTO AG_HISTORIAL (IDCITA, CONTENIDO_INFORME, PESO, TALLA, IMC)
        VALUES ($idCita, '$informe', $peso, $talla, $imc)";

if ($conexion->query($sql)) {
    // Marcar la cita como atendida
    $conexion->query("UPDATE AG_CITA SET ESTADO_CITA = 'A' WHERE IDCITA = $idCita");
    echo 'OK';
} else {
    echo 'ERROR: ' . $conexion->error;
}
