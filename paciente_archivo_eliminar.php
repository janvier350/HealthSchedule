<?php
/**
 * paciente_archivo_eliminar.php — Elimina (baja lógica) un documento de paciente
 * y borra el archivo físico. Requiere sesión y permiso pac.archivos.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/class/permisos.php");
require_once(__DIR__ . "/class/auditoria.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["rol"])) { echo json_encode(['ok'=>false,'error'=>'SIN_SESION']); exit; }
if (!puede('pac.archivos')) { echo json_encode(['ok'=>false,'error'=>'SIN_PERMISO']); exit; }

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { echo json_encode(['ok'=>false,'error'=>'ID_INVALIDO']); exit; }

$st = $conexion->prepare("SELECT IDPACIENTE, nombre_original, archivo FROM paciente_archivos WHERE id=? AND estado='A' LIMIT 1");
$st->bind_param('i', $id); $st->execute();
$row = $st->get_result()->fetch_assoc(); $st->close();
if (!$row) { echo json_encode(['ok'=>false,'error'=>'NO_ENCONTRADO']); exit; }

$upd = $conexion->prepare("UPDATE paciente_archivos SET estado='I' WHERE id=?");
$upd->bind_param('i', $id);
if (!$upd->execute()) { echo json_encode(['ok'=>false,'error'=>$upd->error]); exit; }
$upd->close();

// Borrar el archivo físico (ya no se necesita).
$abs = __DIR__ . '/documentos_pacientes/' . basename($row['archivo']);
if (is_file($abs)) @unlink($abs);

auditar($conexion, 'Documentos', 'eliminar', 'AG_PACIENTE', (int)$row['IDPACIENTE'], 'Eliminó documento: '.$row['nombre_original']);

echo json_encode(['ok'=>true]);
