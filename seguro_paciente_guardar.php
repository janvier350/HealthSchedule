<?php
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

if (!isset($_SESSION["rol"])) { http_response_code(403); echo 'No autorizado'; exit; }

$idPac     = (int)($_POST['id_paciente'] ?? 0);
$idSeguro  = (int)($_POST['id_seguro']   ?? 0);
$poliza    = trim($_POST['num_poliza']   ?? '');
$prioridad = trim($_POST['prioridad']    ?? 'Primario');

if (!in_array($prioridad, ['Primario', 'Secundario', 'Terciario'], true)) {
    $prioridad = 'Primario';
}
if (!$idPac || !$idSeguro) { echo 'Datos incompletos'; exit; }

// Evitar asignar el mismo seguro dos veces al mismo paciente
$stmtD = $conexion->prepare(
    "SELECT COUNT(*) AS n FROM paciente_seguro WHERE IDPACIENTE = ? AND Id_seguro = ? AND estado = 1"
);
$stmtD->bind_param("ii", $idPac, $idSeguro);
$stmtD->execute();
$dup = (int)($stmtD->get_result()->fetch_assoc()['n'] ?? 0);
$stmtD->close();
if ($dup > 0) { echo 'DUP'; exit; }

$stmt = $conexion->prepare(
    "INSERT INTO paciente_seguro (IDPACIENTE, Id_seguro, num_poliza, prioridad, estado)
     VALUES (?, ?, ?, ?, 1)"
);
$stmt->bind_param("iiss", $idPac, $idSeguro, $poliza, $prioridad);
if ($stmt->execute()) {
    echo 'OK';
} else {
    echo 'Error: ' . $stmt->error;
}
$stmt->close();
