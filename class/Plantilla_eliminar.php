<?php
/**
 * Plantilla_eliminar.php — Elimina una plantilla nutricional por id.
 * POST: id
 * Devuelve: 'OK' | 'SIN_SESION' | 'ID' | 'ERROR: ...'
 */
session_start();
require_once("funciones.php");
require_once("conexionBD.php");
$conexion = conectarse();

$rol = strtoupper($_SESSION['rol'] ?? '');
if (!in_array($rol, ['SISTEMA', 'DOCTOR'], true)) { echo 'SIN_SESION'; exit; }

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { echo 'ID'; exit; }

$stmt = $conexion->prepare("DELETE FROM cat_plantillas_nutricion WHERE id = ?");
$stmt->bind_param('i', $id);
echo $stmt->execute() ? 'OK' : ('ERROR: ' . $stmt->error);
$stmt->close();
