<?php
/**
 * auditoria_export.php — Exporta la bitácora de auditoría a CSV (se abre en Excel).
 * Respeta los mismos filtros que auditoria.php. Requiere permiso panel.auditoria.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/lang/i18n.php");
require_once(__DIR__ . "/class/permisos.php");
require_once(__DIR__ . "/class/geoip.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

if (!isset($_SESSION["rol"])) { header("Location: break.php"); exit(); }
if (isset($_SESSION['expire']) && time() > $_SESSION['expire']) { session_destroy(); header("Location: expirada.php"); exit(); }
requerir('panel.auditoria');

$dbEsc  = $conexion->real_escape_string($conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db']);
$existe = (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.TABLES WHERE TABLE_SCHEMA='$dbEsc' AND TABLE_NAME='auditoria'")->fetch_assoc()['c'] > 0;
$tieneDisp = $existe && (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$dbEsc' AND TABLE_NAME='auditoria' AND COLUMN_NAME='dispositivo'")->fetch_assoc()['c'] > 0;

// Filtros (idénticos a auditoria.php)
$fModulo = trim($_GET['modulo']  ?? '');
$fAccion = trim($_GET['accion']  ?? '');
$fUser   = (int)($_GET['usuario'] ?? 0);
$fDesde  = trim($_GET['desde']   ?? '');
$fHasta  = trim($_GET['hasta']   ?? '');
$fQ      = trim($_GET['q']       ?? '');

$rows = [];
if ($existe) {
    $where = "WHERE 1=1"; $tipos=''; $vals=[];
    if ($fModulo !== '') { $where.=" AND modulo=?";     $tipos.='s'; $vals[]=$fModulo; }
    if ($fAccion !== '') { $where.=" AND accion=?";     $tipos.='s'; $vals[]=$fAccion; }
    if ($fUser   >  0)   { $where.=" AND id_usuario=?"; $tipos.='i'; $vals[]=$fUser; }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/',$fDesde)) { $where.=" AND fecha>=?"; $tipos.='s'; $vals[]=$fDesde.' 00:00:00'; }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/',$fHasta)) { $where.=" AND fecha<=?"; $tipos.='s'; $vals[]=$fHasta.' 23:59:59'; }
    if ($fQ !== '') { $where.=" AND (nombre LIKE ? OR usuario LIKE ? OR detalle LIKE ? OR entidad_id LIKE ?)"; $tipos.='ssss'; $like='%'.$fQ.'%'; array_push($vals,$like,$like,$like,$like); }

    $colDisp = $tieneDisp ? ", dispositivo" : "";
    $sql = "SELECT fecha, id_usuario, usuario, nombre, rol, modulo, accion, entidad, entidad_id, detalle, ip$colDisp
            FROM auditoria $where ORDER BY fecha DESC, id DESC LIMIT 50000";
    if ($stmt = $conexion->prepare($sql)) {
        if ($tipos !== '') $stmt->bind_param($tipos, ...$vals);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($x = $res->fetch_assoc()) $rows[] = $x;
        $stmt->close();
    }
}

// Resolver el paciente por registro
$pacIds = []; $citaIds = [];
foreach ($rows as $r) {
    if ($r['entidad_id'] === null || !ctype_digit((string)$r['entidad_id'])) continue;
    if ($r['entidad'] === 'AG_PACIENTE') $pacIds[(int)$r['entidad_id']] = true;
    elseif ($r['entidad'] === 'AG_CITA') $citaIds[(int)$r['entidad_id']] = true;
}
$pacNombre = []; $citaPac = [];
if ($pacIds) {
    $ids = implode(',', array_map('intval', array_keys($pacIds)));
    if ($q = $conexion->query("SELECT IDPACIENTE, CONCAT(NOMBRES,' ',APELLIDOS) nom FROM AG_PACIENTE WHERE IDPACIENTE IN ($ids)"))
        while ($x = $q->fetch_assoc()) $pacNombre[(int)$x['IDPACIENTE']] = trim($x['nom']);
}
if ($citaIds) {
    $ids = implode(',', array_map('intval', array_keys($citaIds)));
    if ($q = $conexion->query("SELECT A.IDCITA, CONCAT(P.NOMBRES,' ',P.APELLIDOS) nom FROM AG_CITA A INNER JOIN AG_PACIENTE P ON A.IDPACIENTE=P.IDPACIENTE WHERE A.IDCITA IN ($ids)"))
        while ($x = $q->fetch_assoc()) $citaPac[(int)$x['IDCITA']] = trim($x['nom']);
}
function _pacFila($r, $pacNombre, $citaPac) {
    if ($r['entidad_id'] === null || !ctype_digit((string)$r['entidad_id'])) return '';
    $id = (int)$r['entidad_id'];
    if ($r['entidad'] === 'AG_PACIENTE') return $pacNombre[$id] ?? '';
    if ($r['entidad'] === 'AG_CITA')     return $citaPac[$id]  ?? '';
    return '';
}

// País/ciudad aproximados por IP (caché)
$geoMap = [];
$ipsUnicas = [];
foreach ($rows as $r) { if (!empty($r['ip'])) $ipsUnicas[$r['ip']] = true; }
if ($ipsUnicas) { $geoMap = geo_para_ips($conexion, array_keys($ipsUnicas)); }
function _ubic($ip, $geoMap) {
    if (empty($ip) || empty($geoMap[$ip])) return '';
    $g = $geoMap[$ip];
    if (($g['pais'] ?? '') === 'Local') return 'Local';
    return trim(implode(', ', array_filter([$g['ciudad'] ?? '', $g['pais'] ?? ''])));
}

// ── Salida CSV (UTF-8 con BOM para Excel) ─────────────────────────────
$fname = 'bitacora_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $fname . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 para que Excel muestre bien los acentos
fputcsv($out, ['Fecha/hora', 'Usuario', 'Rol', 'Módulo', 'Paciente', 'Acción', 'Detalle', 'Dispositivo', 'Ubicación', 'IP']);
foreach ($rows as $r) {
    fputcsv($out, [
        date('m/d/Y H:i', strtotime($r['fecha'])),
        trim($r['nombre']) ?: ($r['usuario'] ?: ''),
        $r['rol'] ?: '',
        $r['modulo'],
        _pacFila($r, $pacNombre, $citaPac),
        $r['accion'],
        $r['detalle'] ?: '',
        $r['dispositivo'] ?? '',
        _ubic($r['ip'] ?? '', $geoMap),
        $r['ip'] ?: '',
    ]);
}
fclose($out);
exit;
