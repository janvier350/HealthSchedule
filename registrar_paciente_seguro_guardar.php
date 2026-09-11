<?php
/**
 * registrar_paciente_seguro_guardar.php
 * Procesa el formulario unificado Paciente + Seguros (con fotos).
 * Crea el paciente, luego cada seguro y sube sus imágenes frente/reverso,
 * todo en un solo submit multipart. SISTEMA/DOCTOR/USUARIO (sesión válida).
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

if (!isset($_SESSION["rol"])) {
    header("Location: break.php"); exit();
}

function volverConError($msg) {
    header("Location: registrar_paciente_seguro.php?err=" . urlencode($msg));
    exit;
}

// ── Datos del paciente ────────────────────────────────────────────────
$cedula    = trim($_POST['cedula']    ?? '');
$title     = trim($_POST['title']     ?? '');
$nombres   = trim($_POST['nombres']   ?? '');
$apellidos = trim($_POST['apellidos'] ?? '');
$telefono  = trim($_POST['telefono']  ?? '');
$email     = trim($_POST['email']     ?? '');
$sex       = trim($_POST['sex']       ?? '');
$gender    = trim($_POST['gender']    ?? '');
$feNac     = trim($_POST['feNac']     ?? '');
$address   = trim($_POST['address']   ?? '');
$notes     = trim($_POST['notes']     ?? '');
$addNotes  = trim($_POST['addNotes']  ?? '');

if ($nombres === '' || $apellidos === '' || $telefono === '') {
    volverConError('Faltan datos obligatorios del paciente (nombre, apellido, teléfono).');
}

$idioma = strtolower(trim($_POST['idioma'] ?? 'es'));
if ($idioma !== 'en' && $idioma !== 'es') $idioma = 'es';

// Detección de columnas opcionales (idéntico criterio a Insert_Pacientev2.php)
$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$colExiste = function($col) use ($conexion, $dbName) {
    return (int)$conexion->query(
        "SELECT COUNT(*) c FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA='" . $conexion->real_escape_string($dbName) . "'
           AND TABLE_NAME='AG_PACIENTE' AND COLUMN_NAME='" . $conexion->real_escape_string($col) . "'"
    )->fetch_assoc()['c'] > 0;
};
$tieneIdioma = $colExiste('IDIOMA');
$tieneIcd10  = $colExiste('IDICD10');
$idicd10 = isset($_POST['idicd10']) && ctype_digit((string)$_POST['idicd10']) ? (int)$_POST['idicd10'] : 0;

// Evitar duplicado por teléfono (mismo criterio que el flujo actual)
$stmtDup = $conexion->prepare("SELECT IDPACIENTE FROM AG_PACIENTE WHERE TELEFONO = ? AND ESTADO = 'A' LIMIT 1");
$stmtDup->bind_param('s', $telefono);
$stmtDup->execute();
if ($stmtDup->get_result()->fetch_assoc()) {
    $stmtDup->close();
    volverConError('Ya existe un paciente activo con ese teléfono.');
}
$stmtDup->close();

// ── Insertar paciente (prepared statement, columnas dinámicas) ─────────
$cols = ['NOMBRES','APELLIDOS','EMAIL','FECHANACIMIENTO','TELEFONO','CEDULA','TITLE','SEX','GENDER','ESTADO','ADDRESS','NOTES','ADDNOTES'];
$vals = [$nombres,$apellidos,$email,$feNac,$telefono,$cedula,$title,$sex,$gender,'A',$address,$notes,$addNotes];
$types = str_repeat('s', count($vals));

if ($tieneIdioma) { $cols[] = 'IDIOMA'; $vals[] = $idioma; $types .= 's'; }
if ($tieneIcd10 && $idicd10 > 0) { $cols[] = 'IDICD10'; $vals[] = $idicd10; $types .= 'i'; }

$placeholders = implode(', ', array_fill(0, count($cols), '?'));
$sqlIns = "INSERT INTO AG_PACIENTE (" . implode(', ', $cols) . ") VALUES ($placeholders)";
$stmtIns = $conexion->prepare($sqlIns);
if (!$stmtIns) { volverConError('Error preparando inserción: ' . $conexion->error); }
$stmtIns->bind_param($types, ...$vals);
if (!$stmtIns->execute()) {
    $err = $stmtIns->error; $stmtIns->close();
    volverConError('No se pudo crear el paciente: ' . $err);
}
$nuevoId = (int)$conexion->insert_id;
$stmtIns->close();

// ── Seguros + fotos ───────────────────────────────────────────────────
$dir = __DIR__ . '/uploads/seguros';
if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
$permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

// Guarda una imagen subida (input tipo array) para un lado dado.
$guardarImagen = function($campo, $idx, $idSeguroPaciente, $lado) use ($conexion, $dir, $permitidos) {
    if (!isset($_FILES[$campo]) || !isset($_FILES[$campo]['tmp_name'][$idx])) return;
    $err = $_FILES[$campo]['error'][$idx] ?? UPLOAD_ERR_NO_FILE;
    if ($err !== UPLOAD_ERR_OK) return; // sin archivo o error → se ignora
    $tmp  = $_FILES[$campo]['tmp_name'][$idx];
    $size = $_FILES[$campo]['size'][$idx] ?? 0;
    if ($size <= 0 || $size > 5 * 1024 * 1024) return;
    $info = @getimagesize($tmp);
    if (!$info || !isset($permitidos[$info['mime']])) return;
    $ext = $permitidos[$info['mime']];
    $nombre  = 'seg_' . (int)$idSeguroPaciente . '_' . $lado . '_' . time() . '_' . $idx . '.' . $ext;
    $destRel = 'uploads/seguros/' . $nombre;
    $destAbs = $dir . '/' . $nombre;
    if (!move_uploaded_file($tmp, $destAbs)) return;
    $col = $lado === 'reverso' ? 'img_reverso' : 'img_frente';
    $up = $conexion->prepare("UPDATE paciente_seguro SET $col = ? WHERE id_paciente_seguro = ?");
    $up->bind_param('si', $destRel, $idSeguroPaciente);
    @$up->execute();
    $up->close();
};

$segIds    = $_POST['seg_id']        ?? [];
$segPols    = $_POST['seg_poliza']    ?? [];
$segPrios   = $_POST['seg_prioridad'] ?? [];
$nSeguros   = 0;

if (is_array($segIds)) {
    $insSeg = $conexion->prepare(
        "INSERT INTO paciente_seguro (IDPACIENTE, Id_seguro, num_poliza, prioridad, estado)
         VALUES (?, ?, ?, ?, 1)"
    );
    foreach ($segIds as $i => $rawId) {
        $idSeguro = ctype_digit((string)$rawId) ? (int)$rawId : 0;
        if ($idSeguro <= 0) continue; // fila vacía → se ignora
        $poliza = trim($segPols[$i]  ?? '');
        $prio   = trim($segPrios[$i] ?? 'Primario');
        if (!in_array($prio, ['Primario','Secundario','Terciario'], true)) $prio = 'Primario';

        $insSeg->bind_param('iiss', $nuevoId, $idSeguro, $poliza, $prio);
        if ($insSeg->execute()) {
            $idSegPac = (int)$conexion->insert_id;
            $nSeguros++;
            $guardarImagen('seg_frente',  $i, $idSegPac, 'frente');
            $guardarImagen('seg_reverso', $i, $idSegPac, 'reverso');
        }
    }
    $insSeg->close();
}

header("Location: registrar_paciente_seguro.php?ok=1&nom=" . urlencode(trim($nombres . ' ' . $apellidos)) . "&seg=" . (int)$nSeguros);
exit;
