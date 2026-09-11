<?php
/**
 * paciente_eliminar_crud.php
 * Baja lógica (soft-delete) de un paciente para el CRUD.
 * Prepared statement (seguro), responde texto plano para AJAX.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

if (!isset($_SESSION["rol"])) { http_response_code(403); echo 'SIN_SESION'; exit; }

$id = (int)($_POST['idPaciente'] ?? $_POST['id'] ?? 0);
if ($id <= 0) { echo 'ID_INVALIDO'; exit; }

$stmt = $conexion->prepare("UPDATE AG_PACIENTE SET ESTADO = 'I' WHERE IDPACIENTE = ? AND ESTADO = 'A'");
$stmt->bind_param('i', $id);
if ($stmt->execute()) {
    echo $stmt->affected_rows > 0 ? 'OK' : 'NO_CAMBIO';
} else {
    echo 'ERROR: ' . $stmt->error;
}
$stmt->close();
