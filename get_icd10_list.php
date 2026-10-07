<?php
/**
 * get_icd10_list.php — Devuelve el catálogo ICD-10 activo como JSON.
 * Formato: [{id, codigo, descripcion}, ...]
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['rol'])) { echo '[]'; exit; }

// Filtra por ACTIVO sólo si la columna existe (para no romper antes de la migración).
$dbEsc = $conexion->real_escape_string($conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db']);
$tieneActivo = (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$dbEsc' AND TABLE_NAME='ENFE_DIAG_COD' AND COLUMN_NAME='ACTIVO'")->fetch_assoc()['c'] > 0;
$filtroActivo = $tieneActivo ? "WHERE COALESCE(ACTIVO,1)=1" : "";

$res = $conexion->query(
    "SELECT ID_ENFE_DIAG_COD AS id, CODIGO AS codigo, DESCRIPCION AS descripcion
       FROM ENFE_DIAG_COD
       $filtroActivo
      ORDER BY CODIGO"
);
$rows = [];
if ($res) { while ($r = $res->fetch_assoc()) { $rows[] = $r; } }
echo json_encode($rows, JSON_UNESCAPED_UNICODE);
