<?php
require_once("funciones.php");
require_once("conexionBD.php");

session_start();

// Solo el rol SISTEMA puede restablecer contraseñas de otros usuarios
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'SISTEMA') {
    echo 'SIN_PERMISO';
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo 'METODO_INVALIDO';
    exit;
}

$idUsuario = (int)($_POST["idUsuarioCl"] ?? 0);
$clave     = $_POST['clave'] ?? '';

if (!$idUsuario || trim($clave) === '') {
    echo 'INCOMPLETO';
    exit;
}
if (strlen($clave) < 6) {
    echo 'CORTA';
    exit;
}

$conexion = conectarse();
if (!$conexion) {
    echo 'ERROR: ' . mysqli_connect_error();
    exit;
}

$cifrar = password_hash($clave, PASSWORD_DEFAULT);

$stmt = $conexion->prepare("UPDATE ADM_USUARIO SET CONTRASENA = ? WHERE IDADM_USUARIO = ?");
if (!$stmt) {
    echo 'ERROR: ' . $conexion->error;
    exit;
}
$stmt->bind_param("si", $cifrar, $idUsuario);

if ($stmt->execute()) {
    echo 'OK';
} else {
    echo 'ERROR: ' . $stmt->error;
}

$stmt->close();
$conexion->close();
