<?php
/**
 * Update_CieCode.php — Actualiza un código ICD-10 existente.
 * POST: id, cieCode, description [, category]
 */
require_once("funciones.php");
require_once("conexionBD.php");
session_start();
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

if (!isset($_SESSION['rol'])) { echo 'SIN_SESION'; exit; }

$id          = (int)($_POST['id'] ?? 0);
$cieCode     = trim($_POST['cieCode']     ?? '');
$description = trim($_POST['description'] ?? '');
$category    = trim($_POST['category']    ?? '');

if ($id <= 0 || $cieCode === '' || $description === '') { echo 'DATOS_INCOMPLETOS'; exit; }

// Verificar que no colisiona con OTRO registro que ya use ese CODIGO
$stmt = $conexion->prepare(
    "SELECT ID_ENFE_DIAG_COD FROM ENFE_DIAG_COD
      WHERE LOWER(TRIM(CODIGO)) = LOWER(TRIM(?)) AND ID_ENFE_DIAG_COD <> ? LIMIT 1"
);
$stmt->bind_param("si", $cieCode, $id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) { $stmt->close(); echo 'DUP'; exit; }
$stmt->close();

if ($category !== '') {
    $stmt = $conexion->prepare("UPDATE ENFE_DIAG_COD SET CODIGO = ?, DESCRIPCION = ?, ID_ENFERMEDAD = ? WHERE ID_ENFE_DIAG_COD = ?");
    $stmt->bind_param("sssi", $cieCode, $description, $category, $id);
} else {
    $stmt = $conexion->prepare("UPDATE ENFE_DIAG_COD SET CODIGO = ?, DESCRIPCION = ? WHERE ID_ENFE_DIAG_COD = ?");
    $stmt->bind_param("ssi", $cieCode, $description, $id);
}
echo $stmt->execute() ? 'OK' : ('ERROR: ' . $stmt->error);
$stmt->close();
$conexion->close();
