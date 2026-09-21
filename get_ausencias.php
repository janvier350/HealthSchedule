<?php
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["rol"])) { echo json_encode([]); exit; }

// ¿Existe la tabla? (si no se ha corrido la migración, devolver vacío/ sin conflicto)
$dbRow = $conexion->query("SELECT DATABASE() AS db");
$db = $dbRow ? $dbRow->fetch_assoc()['db'] : '';
$existe = $db ? (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.TABLES WHERE TABLE_SCHEMA='".$conexion->real_escape_string($db)."' AND TABLE_NAME='ausencias_doctor'")->fetch_assoc()['c'] : 0;

$accion = $_GET['accion'] ?? 'listar';

if ($accion === 'check') {
    // Comprueba si un doctor tiene vacación (día completo) o bloqueo (horas) en una fecha/hora.
    $idDoctor = (int)($_GET['idDoctor'] ?? 0);
    $fecha    = trim($_GET['fecha'] ?? '');
    $hora     = trim($_GET['hora'] ?? '');
    if (!$existe || !$idDoctor || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) { echo json_encode(['conflicto'=>false]); exit; }

    // Vacación: la fecha cae dentro del rango de días.
    $st = $conexion->prepare("SELECT id, motivo FROM ausencias_doctor WHERE estado=1 AND IDDOCTOR=? AND tipo='vacacion' AND ? BETWEEN fecha_inicio AND fecha_fin LIMIT 1");
    $st->bind_param('is', $idDoctor, $fecha); $st->execute();
    if ($r = $st->get_result()->fetch_assoc()) { $st->close(); echo json_encode(['conflicto'=>true,'tipo'=>'vacacion','motivo'=>$r['motivo']]); exit; }
    $st->close();

    // Bloqueo de horas: misma fecha y la hora cae dentro del rango.
    if (preg_match('/^\d{2}:\d{2}$/', $hora)) {
        $st = $conexion->prepare("SELECT id, hora_inicio, hora_fin, motivo FROM ausencias_doctor WHERE estado=1 AND IDDOCTOR=? AND tipo='bloqueo' AND fecha_inicio=? AND ? >= hora_inicio AND ? < hora_fin LIMIT 1");
        $st->bind_param('isss', $idDoctor, $fecha, $hora, $hora); $st->execute();
        if ($r = $st->get_result()->fetch_assoc()) { $st->close(); echo json_encode(['conflicto'=>true,'tipo'=>'bloqueo','hora_inicio'=>substr($r['hora_inicio'],0,5),'hora_fin'=>substr($r['hora_fin'],0,5),'motivo'=>$r['motivo']]); exit; }
        $st->close();
    }
    echo json_encode(['conflicto'=>false]); exit;
}

// accion = listar → todas las ausencias activas (opcional filtro por doctor)
if (!$existe) { echo json_encode([]); exit; }
$filtroDoc = (int)($_GET['idDoctor'] ?? 0);
$sql = "SELECT a.id, a.IDDOCTOR, a.tipo, a.fecha_inicio, a.fecha_fin, a.hora_inicio, a.hora_fin, a.motivo,
               CONCAT(U.NOMBRES,' ',U.APELLIDOS) AS doctor
        FROM ausencias_doctor a
        LEFT JOIN ADM_USUARIO U ON U.IDADM_USUARIO = a.IDDOCTOR
        WHERE a.estado=1 " . ($filtroDoc ? "AND a.IDDOCTOR=".$filtroDoc." " : "") . "
        ORDER BY a.fecha_inicio DESC";
$res = $conexion->query($sql);
$out = [];
if ($res) while ($x = $res->fetch_assoc()) {
    $out[] = [
        'id'          => (int)$x['id'],
        'idDoctor'    => (int)$x['IDDOCTOR'],
        'doctor'      => $x['doctor'],
        'tipo'        => $x['tipo'],
        'fecha_inicio'=> $x['fecha_inicio'],
        'fecha_fin'   => $x['fecha_fin'],
        'hora_inicio' => $x['hora_inicio'] ? substr($x['hora_inicio'],0,5) : null,
        'hora_fin'    => $x['hora_fin'] ? substr($x['hora_fin'],0,5) : null,
        'motivo'      => $x['motivo'],
    ];
}
echo json_encode($out);
