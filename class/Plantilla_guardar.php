<?php
/**
 * Plantilla_guardar.php — Inserta o actualiza una plantilla nutricional.
 * POST: id (opcional, 0 para nueva), nombre, categoria, cuerpo
 * Devuelve JSON: { ok, id, error? }
 */
session_start();
require_once("funciones.php");
require_once("conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

header('Content-Type: application/json; charset=utf-8');

$rol = strtoupper($_SESSION['rol'] ?? '');
if (!in_array($rol, ['SISTEMA', 'DOCTOR'], true)) {
    echo json_encode(['ok' => false, 'error' => 'SIN_SESION']); exit;
}

$id        = (int)($_POST['id']        ?? 0);
$nombre    = trim($_POST['nombre']     ?? '');
$categoria = trim($_POST['categoria']  ?? '');
$cuerpo    = (string)($_POST['cuerpo'] ?? '');

if ($nombre === '' || trim(strip_tags($cuerpo)) === '') {
    echo json_encode(['ok' => false, 'error' => 'DATOS_INCOMPLETOS']); exit;
}

if ($id > 0) {
    $stmt = $conexion->prepare("UPDATE cat_plantillas_nutricion SET nombre_plantilla = ?, categoria = ?, cuerpo_html = ? WHERE id = ?");
    $stmt->bind_param('sssi', $nombre, $categoria, $cuerpo, $id);
    if (!$stmt->execute()) { echo json_encode(['ok' => false, 'error' => $stmt->error]); exit; }
    $stmt->close();
    echo json_encode(['ok' => true, 'id' => $id]); exit;
}

$stmt = $conexion->prepare("INSERT INTO cat_plantillas_nutricion (nombre_plantilla, categoria, cuerpo_html) VALUES (?, ?, ?)");
$stmt->bind_param('sss', $nombre, $categoria, $cuerpo);
if (!$stmt->execute()) { echo json_encode(['ok' => false, 'error' => $stmt->error]); exit; }
$nuevoId = (int)$conexion->insert_id;
$stmt->close();
echo json_encode(['ok' => true, 'id' => $nuevoId]);
