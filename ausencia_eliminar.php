<?php
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/class/permisos.php");
$conexion = conectarse();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["rol"])) { echo json_encode(['ok'=>false,'error'=>'SIN_SESION']); exit; }
if (!puede('agenda.ausencias')) { echo json_encode(['ok'=>false,'error'=>'SIN_PERMISO']); exit; }

$rol = strtoupper($_SESSION['rol'] ?? '');
$idU = (int)($_SESSION['iduser'] ?? 0);
$id  = (int)($_POST['id'] ?? 0);
if (!$id) { echo json_encode(['ok'=>false,'error'=>'Falta el id.']); exit; }

// El DOCTOR solo puede borrar sus propias ausencias.
if ($rol === 'DOCTOR') {
    $stmt = $conexion->prepare("UPDATE ausencias_doctor SET estado=0 WHERE id=? AND IDDOCTOR=?");
    $stmt->bind_param('ii', $id, $idU);
} else {
    $stmt = $conexion->prepare("UPDATE ausencias_doctor SET estado=0 WHERE id=?");
    $stmt->bind_param('i', $id);
}
if ($stmt->execute()) { echo json_encode(['ok'=>true]); }
else { echo json_encode(['ok'=>false,'error'=>$stmt->error]); }
$stmt->close();
