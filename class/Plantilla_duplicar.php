<?php
/**
 * Plantilla_duplicar.php — Clona una plantilla existente añadiendo "(copy)" al nombre.
 * POST: id
 * Devuelve JSON: { ok, id? , error? }
 */
session_start();
require_once("funciones.php");
require_once("conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
header('Content-Type: application/json; charset=utf-8');

$rol = strtoupper($_SESSION['rol'] ?? '');
if (!in_array($rol, ['SISTEMA', 'DOCTOR'], true)) { echo json_encode(['ok'=>false,'error'=>'SIN_SESION']); exit; }

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { echo json_encode(['ok'=>false,'error'=>'ID']); exit; }

$stmt = $conexion->prepare("SELECT nombre_plantilla, categoria, cuerpo_html FROM cat_plantillas_nutricion WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row) { echo json_encode(['ok'=>false,'error'=>'NO_ENCONTRADO']); exit; }

$nuevoNombre = $row['nombre_plantilla'] . ' (copy)';
$stmt = $conexion->prepare("INSERT INTO cat_plantillas_nutricion (nombre_plantilla, categoria, cuerpo_html) VALUES (?, ?, ?)");
$stmt->bind_param('sss', $nuevoNombre, $row['categoria'], $row['cuerpo_html']);
if (!$stmt->execute()) { echo json_encode(['ok'=>false,'error'=>$stmt->error]); exit; }
$nuevoId = (int)$conexion->insert_id;
$stmt->close();
echo json_encode(['ok'=>true,'id'=>$nuevoId]);
