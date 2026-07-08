<?php
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["rol"])) { echo json_encode(['error' => 'SIN_SESION']); exit; }

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['error' => 'ID_INVALIDO']); exit; }

// ¿Existe la columna ALERTA? (la agrega migrar_notas_paciente.php)
$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$tieneAlerta = (int)$conexion->query(
    "SELECT COUNT(*) c FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA='$dbName' AND TABLE_NAME='AG_PACIENTE' AND COLUMN_NAME='ALERTA'"
)->fetch_assoc()['c'] > 0;

$cols = "NOMBRES, APELLIDOS, CEDULA, TELEFONO, EMAIL, FECHANACIMIENTO, SEX, GENDER, TITLE, ADDRESS, NOTES, ADDNOTES"
      . ($tieneAlerta ? ", ALERTA" : "");

$stmt = $conexion->prepare("SELECT $cols FROM AG_PACIENTE WHERE IDPACIENTE = ? AND ESTADO = 'A' LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$p) { echo json_encode(['error' => 'NO_ENCONTRADO']); exit; }

if (!$tieneAlerta) $p['ALERTA'] = '';
if (($p['FECHANACIMIENTO'] ?? '') === '0000-00-00') $p['FECHANACIMIENTO'] = '';

$p['_tieneAlerta'] = $tieneAlerta;
echo json_encode($p);
