<?php
/**
 * actualizar_atencion.php — Actualiza el contenido del informe de una
 * atención ya guardada (AG_HISTORIAL.CONTENIDO_INFORME) por su IDHISTORIAL.
 *
 * POST: idHistorial, informe [, peso, talla, imc]
 * Devuelve: 'OK' | 'SIN_SESION' | 'DATOS_INCOMPLETOS' | 'NO_ENCONTRADO' | 'ERROR: ...'
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

if (!isset($_SESSION['rol'])) { echo 'SIN_SESION'; exit; }

$idHistorial = (int)($_POST['idHistorial'] ?? 0);
$informe     = $_POST['informe'] ?? '';
if ($idHistorial <= 0 || trim(strip_tags($informe)) === '') {
    echo 'DATOS_INCOMPLETOS'; exit;
}

// Peso/Talla/IMC son opcionales — sólo se actualizan si vienen numéricos > 0
$hasMedic = false;
$peso = $talla = $imc = 0.0;
if (isset($_POST['peso']) && is_numeric($_POST['peso']))   { $peso  = (float)$_POST['peso'];  $hasMedic = true; }
if (isset($_POST['talla']) && is_numeric($_POST['talla'])) { $talla = (float)$_POST['talla']; $hasMedic = true; }
if (isset($_POST['imc']) && is_numeric($_POST['imc']))     { $imc   = (float)$_POST['imc'];   $hasMedic = true; }

// Verificar que el registro existe
$chk = $conexion->prepare("SELECT IDHISTORIAL FROM AG_HISTORIAL WHERE IDHISTORIAL = ? LIMIT 1");
$chk->bind_param('i', $idHistorial);
$chk->execute();
$exists = $chk->get_result()->num_rows > 0;
$chk->close();
if (!$exists) { echo 'NO_ENCONTRADO'; exit; }

if ($hasMedic) {
    $stmt = $conexion->prepare(
        "UPDATE AG_HISTORIAL SET CONTENIDO_INFORME = ?, PESO = ?, TALLA = ?, IMC = ? WHERE IDHISTORIAL = ?"
    );
    $stmt->bind_param('sdddi', $informe, $peso, $talla, $imc, $idHistorial);
} else {
    $stmt = $conexion->prepare(
        "UPDATE AG_HISTORIAL SET CONTENIDO_INFORME = ? WHERE IDHISTORIAL = ?"
    );
    $stmt->bind_param('si', $informe, $idHistorial);
}
echo $stmt->execute() ? 'OK' : ('ERROR: ' . $stmt->error);
$stmt->close();
$conexion->close();
