<?php
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

if (!isset($_SESSION["rol"])) { http_response_code(403); echo 'No autorizado'; exit; }

$id = (int)($_POST['id'] ?? 0);
if (!$id) { echo 'ID inválido'; exit; }

$stmt = $conexion->prepare("UPDATE paciente_seguro SET estado = 0 WHERE id_paciente_seguro = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    echo 'OK';
} else {
    echo 'Error: ' . $stmt->error;
}
$stmt->close();
