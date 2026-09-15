<?php
/**
 * paciente_icd10.php — Gestiona los diagnósticos ICD-10 de un paciente.
 * Acciones (POST/GET 'accion'):
 *   listar   (idPaciente)              -> JSON [{id_rel, id, codigo, descripcion}]
 *   agregar  (idPaciente, idIcd10)     -> JSON {ok, id_rel, id, codigo, descripcion}
 *   eliminar (idPaciente, idIcd10)     -> JSON {ok}
 * Mantiene AG_PACIENTE.IDICD10 sincronizado con el código principal.
 * Requiere sesión válida (lo usa quien atiende al paciente).
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
$idIcd10    = (int)($_REQUEST['idIcd10'] ?? 0);
if ($idPaciente <= 0) { echo json_encode(['ok'=>false,'error'=>'PACIENTE_INVALIDO']); exit; }

$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$dbEsc  = $conexion->real_escape_string($dbName);
$tieneTabla = (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.TABLES WHERE TABLE_SCHEMA='$dbEsc' AND TABLE_NAME='paciente_icd10'")->fetch_assoc()['c']>0;
if (!$tieneTabla) { echo json_encode(['ok'=>false,'error'=>'FALTA_MIGRACION']); exit; }
$tieneColPrincipal = (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$dbEsc' AND TABLE_NAME='AG_PACIENTE' AND COLUMN_NAME='IDICD10'")->fetch_assoc()['c']>0;

// Recalcula AG_PACIENTE.IDICD10 = código principal (el de menor id de la relación) o NULL.
function sincronizarPrincipal($conexion, $idPaciente, $tieneColPrincipal) {
    if (!$tieneColPrincipal) return;
    $q = $conexion->query("SELECT ID_ENFE_DIAG_COD FROM paciente_icd10 WHERE IDPACIENTE=".(int)$idPaciente." ORDER BY id ASC LIMIT 1");
    $princ = ($q && $r=$q->fetch_assoc()) ? (int)$r['ID_ENFE_DIAG_COD'] : 0;
    if ($princ > 0) { $conexion->query("UPDATE AG_PACIENTE SET IDICD10=$princ WHERE IDPACIENTE=".(int)$idPaciente); }
    else            { $conexion->query("UPDATE AG_PACIENTE SET IDICD10=NULL WHERE IDPACIENTE=".(int)$idPaciente); }
}

function listar($conexion, $idPaciente) {
    $out = [];
    $st = $conexion->prepare(
        "SELECT R.id AS id_rel, C.ID_ENFE_DIAG_COD AS id, C.CODIGO AS codigo, C.DESCRIPCION AS descripcion
           FROM paciente_icd10 R
           INNER JOIN ENFE_DIAG_COD C ON C.ID_ENFE_DIAG_COD = R.ID_ENFE_DIAG_COD
          WHERE R.IDPACIENTE = ?
          ORDER BY R.id ASC");
    $st->bind_param('i',$idPaciente); $st->execute();
    $rs=$st->get_result(); while($x=$rs->fetch_assoc()) $out[]=$x; $st->close();
    return $out;
}

if ($accion === 'agregar') {
    if ($idIcd10 <= 0) { echo json_encode(['ok'=>false,'error'=>'ICD_INVALIDO']); exit; }
    // Validar que el código exista en el catálogo
    $chk = $conexion->prepare("SELECT CODIGO, DESCRIPCION FROM ENFE_DIAG_COD WHERE ID_ENFE_DIAG_COD=? LIMIT 1");
    $chk->bind_param('i',$idIcd10); $chk->execute();
    $cat = $chk->get_result()->fetch_assoc(); $chk->close();
    if (!$cat) { echo json_encode(['ok'=>false,'error'=>'ICD_NO_EXISTE']); exit; }

    $idUser = (int)($_SESSION['iduser'] ?? 0);
    $ins = $conexion->prepare("INSERT IGNORE INTO paciente_icd10 (IDPACIENTE, ID_ENFE_DIAG_COD, id_usuario) VALUES (?,?,?)");
    $ins->bind_param('iii',$idPaciente,$idIcd10,$idUser);
    $ins->execute(); $ins->close();
    sincronizarPrincipal($conexion,$idPaciente,$tieneColPrincipal);
    echo json_encode(['ok'=>true, 'lista'=>listar($conexion,$idPaciente)]);
    exit;
}

if ($accion === 'eliminar') {
    if ($idIcd10 <= 0) { echo json_encode(['ok'=>false,'error'=>'ICD_INVALIDO']); exit; }
    $del = $conexion->prepare("DELETE FROM paciente_icd10 WHERE IDPACIENTE=? AND ID_ENFE_DIAG_COD=?");
    $del->bind_param('ii',$idPaciente,$idIcd10);
    $del->execute(); $del->close();
    sincronizarPrincipal($conexion,$idPaciente,$tieneColPrincipal);
    echo json_encode(['ok'=>true, 'lista'=>listar($conexion,$idPaciente)]);
    exit;
}

// listar (por defecto)
echo json_encode(['ok'=>true, 'lista'=>listar($conexion,$idPaciente)]);
