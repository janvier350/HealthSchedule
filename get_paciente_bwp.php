<?php
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["rol"])) { echo json_encode(['error' => 'SIN_SESION']); exit; }

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['error' => 'ID_INVALIDO']); exit; }

// Datos del paciente
$stmt = $conexion->prepare(
    "SELECT NOMBRES, APELLIDOS, SEX, FECHANACIMIENTO FROM AG_PACIENTE WHERE IDPACIENTE = ? AND ESTADO = 'A' LIMIT 1"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$p) { echo json_encode(['error' => 'NO_ENCONTRADO']); exit; }

// Sexo → 1 = mujer, 0 = hombre, null = desconocido
$sexRaw = strtolower(trim($p['SEX'] ?? ''));
$sex = null;
if ($sexRaw !== '') {
    if (strpos($sexRaw, 'fem') !== false || $sexRaw === 'f' || $sexRaw === 'mujer') {
        $sex = 1;
    } elseif (strpos($sexRaw, 'masc') !== false || strpos($sexRaw, 'male') !== false
              || $sexRaw === 'm' || $sexRaw === 'hombre') {
        $sex = 0;
    }
}

// Edad desde FECHANACIMIENTO
$edad = null;
$fn = $p['FECHANACIMIENTO'] ?? '';
if ($fn && $fn !== '0000-00-00') {
    try { $edad = (new DateTime($fn))->diff(new DateTime())->y; } catch (Exception $e) { $edad = null; }
}

// Último peso y talla registrados en atenciones
$stmtM = $conexion->prepare(
    "SELECT H.PESO, H.TALLA
     FROM AG_HISTORIAL H
     INNER JOIN AG_CITA C ON C.IDCITA = H.IDCITA
     WHERE C.IDPACIENTE = ? AND H.PESO IS NOT NULL AND H.PESO > 0
     ORDER BY H.FECHA_REGISTRO DESC LIMIT 1"
);
$stmtM->bind_param("i", $id);
$stmtM->execute();
$m = $stmtM->get_result()->fetch_assoc();
$stmtM->close();

$peso  = ($m && $m['PESO']  > 0) ? (float)$m['PESO']  : null;   // kg
$talla = ($m && $m['TALLA'] > 0) ? (float)$m['TALLA'] : null;

// Si no hubo talla junto al último peso, buscar la talla más reciente disponible
if ($talla === null) {
    $stmtT = $conexion->prepare(
        "SELECT H.TALLA FROM AG_HISTORIAL H
         INNER JOIN AG_CITA C ON C.IDCITA = H.IDCITA
         WHERE C.IDPACIENTE = ? AND H.TALLA IS NOT NULL AND H.TALLA > 0
         ORDER BY H.FECHA_REGISTRO DESC LIMIT 1"
    );
    $stmtT->bind_param("i", $id);
    $stmtT->execute();
    $rt = $stmtT->get_result()->fetch_assoc();
    $stmtT->close();
    $talla = ($rt && $rt['TALLA'] > 0) ? (float)$rt['TALLA'] : null;
}

// Normalizar talla a metros (puede venir en cm)
$tallaM = null;
if ($talla !== null) {
    $tallaM = ($talla > 3) ? $talla / 100 : $talla;
}

echo json_encode([
    'nombre' => trim(($p['NOMBRES'] ?? '') . ' ' . ($p['APELLIDOS'] ?? '')),
    'sex'    => $sex,      // 1 mujer, 0 hombre, null desconocido
    'edad'   => $edad,     // años
    'pesoKg' => $peso,     // kg
    'tallaM' => $tallaM,   // metros
]);
