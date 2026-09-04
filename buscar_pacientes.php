<?php
/**
 * buscar_pacientes.php — Búsqueda de pacientes por nombre/apellido/cédula.
 * Uso: buscar_pacientes.php?q=texto  → JSON con hasta 12 coincidencias.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["rol"])) { echo json_encode([]); exit; }

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 2) { echo json_encode([]); exit; }

$like = '%' . $q . '%';
$stmt = $conexion->prepare(
    "SELECT IDPACIENTE, NOMBRES, APELLIDOS, CEDULA, TELEFONO, SEX, FECHANACIMIENTO
     FROM AG_PACIENTE
     WHERE ESTADO = 'A'
       AND (NOMBRES LIKE ? OR APELLIDOS LIKE ? OR CEDULA LIKE ?
            OR CONCAT(NOMBRES, ' ', APELLIDOS) LIKE ?
            OR CONCAT(APELLIDOS, ' ', NOMBRES) LIKE ?)
     ORDER BY APELLIDOS, NOMBRES
     LIMIT 12"
);
$stmt->bind_param("sssss", $like, $like, $like, $like, $like);
$stmt->execute();
$res = $stmt->get_result();

$out = [];
while ($r = $res->fetch_assoc()) {
    $out[] = [
        'id'       => (int)$r['IDPACIENTE'],
        'nombre'   => trim($r['APELLIDOS'] . ', ' . $r['NOMBRES']),
        'cedula'   => $r['CEDULA'] ?? '',
        'telefono' => $r['TELEFONO'] ?? '',
        'sex'      => $r['SEX'] ?? '',
        'dob'      => $r['FECHANACIMIENTO'] ?? '',
    ];
}
echo json_encode($out);
$stmt->close();
$conexion->close();
