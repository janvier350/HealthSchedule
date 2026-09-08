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

$res = $conexion->query(
    "SELECT ID_ENFE_DIAG_COD AS id, CODIGO AS codigo, DESCRIPCION AS descripcion
       FROM ENFE_DIAG_COD
      ORDER BY CODIGO"
);
$rows = [];
if ($res) { while ($r = $res->fetch_assoc()) { $rows[] = $r; } }
echo json_encode($rows, JSON_UNESCAPED_UNICODE);
