<?php
/**
 * paciente_archivo_listar.php — Lista los documentos de un paciente (JSON).
 * Requiere sesión y permiso pac.archivos.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/class/permisos.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["rol"])) { echo json_encode(['ok'=>false,'error'=>'SIN_SESION']); exit; }
if (!puede('pac.archivos')) { echo json_encode(['ok'=>false,'error'=>'SIN_PERMISO']); exit; }

$idPaciente = (int)($_GET['idPaciente'] ?? 0);
if ($idPaciente <= 0) { echo json_encode(['ok'=>false,'error'=>'PACIENTE_INVALIDO']); exit; }

// ¿Existe la tabla? (por si no se ha corrido la migración)
$existe = (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='paciente_archivos'")->fetch_assoc()['c'] > 0;
if (!$existe) { echo json_encode(['ok'=>true, 'lista'=>[]]); exit; }

$lista = [];
$st = $conexion->prepare(
    "SELECT A.id, A.titulo, A.nombre_original, A.mime, A.tamano, A.fecha,
            TRIM(CONCAT(COALESCE(U.NOMBRES,''),' ',COALESCE(U.APELLIDOS,''))) AS usuario
       FROM paciente_archivos A
       LEFT JOIN ADM_USUARIO U ON U.IDADM_USUARIO = A.id_usuario
      WHERE A.IDPACIENTE = ? AND A.estado = 'A'
      ORDER BY A.fecha DESC, A.id DESC"
);
$st->bind_param('i', $idPaciente); $st->execute();
$rs = $st->get_result();
while ($x = $rs->fetch_assoc()) {
    $lista[] = [
        'id'      => (int)$x['id'],
        'titulo'  => $x['titulo'],
        'nombre'  => $x['nombre_original'],
        'mime'    => $x['mime'],
        'tamano'  => (int)$x['tamano'],
        'fecha'   => date('m/d/Y H:i', strtotime($x['fecha'])),
        'usuario' => trim($x['usuario']),
    ];
}
$st->close();
echo json_encode(['ok'=>true, 'lista'=>$lista]);
