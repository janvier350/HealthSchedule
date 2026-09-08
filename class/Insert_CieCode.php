<?php
/**
 * Insert_CieCode.php — Crea un código ICD-10 en ENFE_DIAG_COD.
 * Valida que el CODIGO no exista ya (comparación case-insensitive).
 */
require_once("funciones.php");
require_once("conexionBD.php");
session_start();
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

if (!isset($_SESSION['rol'])) {
    header('Location: ../PNC_CIE-10Crear.php'); exit;
}

$cieCode     = trim($_POST['cieCode']     ?? '');
$description = trim($_POST['description'] ?? '');
$category    = trim($_POST['category']    ?? '1');

if ($cieCode === '' || $description === '') {
    header('Location: ../PNC_CIE-10Crear.php?err=empty'); exit;
}

// El código ICD-10 es único: bloquear duplicados por CÓDIGO (case-insensitive)
$stmt = $conexion->prepare("SELECT 1 FROM ENFE_DIAG_COD WHERE LOWER(TRIM(CODIGO)) = LOWER(TRIM(?)) LIMIT 1");
$stmt->bind_param("s", $cieCode);
$stmt->execute();
$existe = $stmt->get_result()->num_rows > 0;
$stmt->close();

if ($existe) {
    header('Location: ../PNC_CIE-10Crear.php?err=dup&codigo=' . urlencode($cieCode)); exit;
}

$stmt = $conexion->prepare("INSERT INTO ENFE_DIAG_COD (ID_ENFERMEDAD, CODIGO, DESCRIPCION) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $category, $cieCode, $description);
if ($stmt->execute()) {
    header('Location: ../PNC_CIE-10Crear.php?ok=1'); exit;
}
$err = $stmt->error;
$stmt->close();
header('Location: ../PNC_CIE-10Crear.php?err=' . urlencode($err));
