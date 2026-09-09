<?php
/**
 * get_plantilla_json.php — Devuelve una plantilla en JSON para editarla.
 * GET: id
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
header('Content-Type: application/json; charset=utf-8');

$rol = strtoupper($_SESSION['rol'] ?? '');
if (!in_array($rol, ['SISTEMA', 'DOCTOR'], true)) { echo json_encode(['error' => 'SIN_SESION']); exit; }

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { echo json_encode(['error' => 'ID']); exit; }

$stmt = $conexion->prepare("SELECT id, nombre_plantilla, categoria, cuerpo_html FROM cat_plantillas_nutricion WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row) { echo json_encode(['error' => 'NO_ENCONTRADO']); exit; }
echo json_encode($row, JSON_UNESCAPED_UNICODE);
