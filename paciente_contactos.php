<?php
/**
 * paciente_contactos.php — Teléfonos/correos adicionales de un paciente.
 * Acciones (POST/GET 'accion'):
 *   listar   (idPaciente)                         -> JSON [{id,tipo,valor,etiqueta}]
 *   agregar  (idPaciente, tipo, valor, etiqueta)  -> JSON {ok, lista}
 *   eliminar (idPaciente, id)                     -> JSON {ok, lista}
 * Requiere sesión válida.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['rol'])) { echo json_encode(['ok'=>false,'error'=>'SIN_SESION']); exit; }

$accion     = $_REQUEST['accion'] ?? 'listar';
$idPaciente = (int)($_REQUEST['idPaciente'] ?? 0);
if ($idPaciente <= 0) { echo json_encode(['ok'=>false,'error'=>'PACIENTE_INVALIDO']); exit; }

$dbEsc = $conexion->real_escape_string($conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db']);
$tabla = (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.TABLES WHERE TABLE_SCHEMA='$dbEsc' AND TABLE_NAME='paciente_contactos'")->fetch_assoc()['c']>0;
if (!$tabla) { echo json_encode(['ok'=>false,'error'=>'FALTA_MIGRACION']); exit; }

function listar($conexion,$idPaciente){
    $out=[]; $st=$conexion->prepare("SELECT id, tipo, valor, etiqueta FROM paciente_contactos WHERE IDPACIENTE=? ORDER BY id ASC");
    $st->bind_param('i',$idPaciente); $st->execute();
    $rs=$st->get_result(); while($x=$rs->fetch_assoc()) $out[]=$x; $st->close(); return $out;
}

if ($accion === 'agregar') {
    $tipo  = trim($_POST['tipo'] ?? '');
    $valor = trim($_POST['valor'] ?? '');
    $etiq  = trim($_POST['etiqueta'] ?? '');
    if (!in_array($tipo, ['telefono','email'], true)) { echo json_encode(['ok'=>false,'error'=>'TIPO_INVALIDO']); exit; }
    if ($valor === '') { echo json_encode(['ok'=>false,'error'=>'VALOR_VACIO']); exit; }
    if ($tipo === 'email' && !filter_var($valor, FILTER_VALIDATE_EMAIL)) { echo json_encode(['ok'=>false,'error'=>'EMAIL_INVALIDO']); exit; }
    if (mb_strlen($valor) > 160) $valor = mb_substr($valor,0,160);
    if (mb_strlen($etiq)  > 60)  $etiq  = mb_substr($etiq,0,60);
    $idUser=(int)($_SESSION['iduser'] ?? 0);
    $st=$conexion->prepare("INSERT INTO paciente_contactos (IDPACIENTE, tipo, valor, etiqueta, id_usuario) VALUES (?,?,?,?,?)");
    $st->bind_param('isssi',$idPaciente,$tipo,$valor,$etiq,$idUser); $st->execute(); $st->close();
    echo json_encode(['ok'=>true,'lista'=>listar($conexion,$idPaciente)]); exit;
}

if ($accion === 'eliminar') {
    $id=(int)($_REQUEST['id'] ?? 0);
    if ($id<=0){ echo json_encode(['ok'=>false,'error'=>'ID_INVALIDO']); exit; }
    $st=$conexion->prepare("DELETE FROM paciente_contactos WHERE id=? AND IDPACIENTE=?");
    $st->bind_param('ii',$id,$idPaciente); $st->execute(); $st->close();
    echo json_encode(['ok'=>true,'lista'=>listar($conexion,$idPaciente)]); exit;
}

echo json_encode(['ok'=>true,'lista'=>listar($conexion,$idPaciente)]);
