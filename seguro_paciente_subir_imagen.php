<?php
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

if (!isset($_SESSION["rol"])) { http_response_code(403); echo 'No autorizado'; exit; }

$id   = (int)($_POST['id_paciente_seguro'] ?? 0);
$lado = ($_POST['lado'] ?? '') === 'reverso' ? 'reverso' : 'frente';

if (!$id || !isset($_FILES['imagen'])) { echo 'Datos incompletos'; exit; }

$f = $_FILES['imagen'];
if ($f['error'] !== UPLOAD_ERR_OK) { echo 'Error de subida (' . (int)$f['error'] . ')'; exit; }
if ($f['size'] > 5 * 1024 * 1024) { echo 'La imagen supera el límite de 5MB'; exit; }

// Validar que sea una imagen real
$info = @getimagesize($f['tmp_name']);
$permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
if (!$info || !isset($permitidos[$info['mime']])) {
    echo 'Formato no permitido. Use JPG, PNG o WEBP.';
    exit;
}
$ext = $permitidos[$info['mime']];

// Carpeta de subidas (se crea si no existe; el deploy no la toca)
$dir = __DIR__ . '/uploads/seguros';
if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
    echo 'No se pudo crear la carpeta de subidas';
    exit;
}

$nombre  = 'seg_' . $id . '_' . $lado . '_' . time() . '.' . $ext;
$destRel = 'uploads/seguros/' . $nombre;
$destAbs = $dir . '/' . $nombre;

if (!move_uploaded_file($f['tmp_name'], $destAbs)) {
    echo 'No se pudo guardar el archivo';
    exit;
}

$col = $lado === 'reverso' ? 'img_reverso' : 'img_frente';

// Borrar la imagen anterior (si había)
$stmtOld = $conexion->prepare("SELECT $col AS img FROM paciente_seguro WHERE id_paciente_seguro = ?");
$stmtOld->bind_param("i", $id);
$stmtOld->execute();
$old = $stmtOld->get_result()->fetch_assoc()['img'] ?? '';
$stmtOld->close();
if ($old && strpos($old, 'uploads/seguros/') === 0 && is_file(__DIR__ . '/' . $old)) {
    @unlink(__DIR__ . '/' . $old);
}

$stmt = $conexion->prepare("UPDATE paciente_seguro SET $col = ? WHERE id_paciente_seguro = ?");
$stmt->bind_param("si", $destRel, $id);
if ($stmt->execute()) {
    echo 'OK:' . $destRel;
} else {
    echo 'Error al guardar en BD: ' . $stmt->error;
}
$stmt->close();
