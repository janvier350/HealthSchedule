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
$colExiste = function ($col) use ($conexion, $dbName) {
    return (int)$conexion->query(
        "SELECT COUNT(*) c FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA='$dbName' AND TABLE_NAME='AG_PACIENTE' AND COLUMN_NAME='$col'"
    )->fetch_assoc()['c'] > 0;
};
$tieneAlerta = $colExiste('ALERTA');
$tieneIdioma = $colExiste('IDIOMA');
$tieneIcd10  = $colExiste('IDICD10');

$cols = "NOMBRES, APELLIDOS, CEDULA, TELEFONO, EMAIL, FECHANACIMIENTO, SEX, GENDER, TITLE, ADDRESS, NOTES, ADDNOTES"
      . ($tieneAlerta ? ", ALERTA"  : "")
      . ($tieneIdioma ? ", IDIOMA"  : "")
      . ($tieneIcd10  ? ", IDICD10" : "");

$stmt = $conexion->prepare("SELECT $cols FROM AG_PACIENTE WHERE IDPACIENTE = ? AND ESTADO = 'A' LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$p) { echo json_encode(['error' => 'NO_ENCONTRADO']); exit; }

if (!$tieneAlerta) $p['ALERTA'] = '';
if (($p['FECHANACIMIENTO'] ?? '') === '0000-00-00') $p['FECHANACIMIENTO'] = '';
$p['IDIOMA'] = $tieneIdioma ? (strtolower($p['IDIOMA'] ?? 'es') ?: 'es') : 'es';

// ICD-10: cargar codigo + descripción si hay uno asignado
$p['IDICD10']         = $tieneIcd10 ? (int)($p['IDICD10'] ?? 0) : 0;
$p['ICD10_CODIGO']      = '';
$p['ICD10_DESCRIPCION'] = '';
if ($p['IDICD10'] > 0) {
    $s2 = $conexion->prepare("SELECT CODIGO, DESCRIPCION FROM ENFE_DIAG_COD WHERE ID_ENFE_DIAG_COD = ? LIMIT 1");
    $s2->bind_param('i', $p['IDICD10']);
    $s2->execute();
    if ($row = $s2->get_result()->fetch_assoc()) {
        $p['ICD10_CODIGO']      = $row['CODIGO'];
        $p['ICD10_DESCRIPCION'] = $row['DESCRIPCION'];
    }
    $s2->close();
}

$p['_tieneAlerta'] = $tieneAlerta;
$p['_tieneIdioma'] = $tieneIdioma;
$p['_tieneIcd10']  = $tieneIcd10;
echo json_encode($p);
