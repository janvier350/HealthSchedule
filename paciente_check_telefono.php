<?php
/**
 * paciente_check_telefono.php
 * Verifica si un teléfono ya está registrado en un paciente activo.
 * Devuelve JSON: { exists: bool, nombre: "Apellidos, Nombres", cedula: "..." }
 * Sólo lectura. Requiere sesión.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION["rol"])) { echo json_encode(['exists'=>false]); exit; }

$tel = trim($_GET['tel'] ?? '');
if ($tel === '') { echo json_encode(['exists'=>false]); exit; }

$stmt = $conexion->prepare("SELECT IDPACIENTE, NOMBRES, APELLIDOS, CEDULA FROM AG_PACIENTE WHERE TELEFONO = ? AND ESTADO='A' LIMIT 1");
$stmt->bind_param('s', $tel);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($row) {
    echo json_encode([
        'exists' => true,
        'id'     => (int)$row['IDPACIENTE'],
        'nombre' => trim($row['APELLIDOS'].', '.$row['NOMBRES']),
        'cedula' => $row['CEDULA'] ?: ''
    ]);
} else {
    echo json_encode(['exists'=>false]);
}
