<?php
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

if (!isset($_SESSION["rol"], $_SESSION["iduser"])) {
    echo 'SIN_SESION';
    exit;
}

$idUsuario  = (int)$_SESSION['iduser'];
$claveActual = $_POST['claveActual'] ?? '';
$claveNueva  = $_POST['claveNueva']  ?? '';

if ($claveActual === '' || $claveNueva === '') {
    echo 'DATOS_INCOMPLETOS';
    exit;
}

if (strlen($claveNueva) < 6) {
    echo 'CLAVE_CORTA';
    exit;
}

// Verificar la contraseña actual
$stmt = $conexion->prepare("SELECT CONTRASENA FROM ADM_USUARIO WHERE IDADM_USUARIO = ? AND ESTADO = 'A'");
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo 'NO_ENCONTRADO';
    exit;
}

if (!password_verify($claveActual, $row['CONTRASENA'])) {
    echo 'CLAVE_ACTUAL_INCORRECTA';
    exit;
}

// Actualizar a la nueva contraseña
$cifrar = password_hash($claveNueva, PASSWORD_DEFAULT);
$stmtU = $conexion->prepare("UPDATE ADM_USUARIO SET CONTRASENA = ? WHERE IDADM_USUARIO = ?");
$stmtU->bind_param("si", $cifrar, $idUsuario);

if ($stmtU->execute()) {
    echo 'OK';
} else {
    echo 'ERROR: ' . $stmtU->error;
}

$stmtU->close();
$conexion->close();
