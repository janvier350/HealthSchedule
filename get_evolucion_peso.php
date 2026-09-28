<?php
/**
 * get_evolucion_peso.php — Serie de Peso e IMC por fecha de consulta (JSON).
 * Uso: get_evolucion_peso.php?idPaciente=123
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['rol'])) { echo json_encode(['ok'=>false,'error'=>'SIN_SESION']); exit; }

$idPaciente = (int)($_GET['idPaciente'] ?? $_GET['id'] ?? 0);
if ($idPaciente <= 0) { echo json_encode(['ok'=>false,'error'=>'PACIENTE_INVALIDO']); exit; }

$labels = []; $pesos = []; $imcs = [];
if ($st = $conexion->prepare(
    "SELECT C.FECHA_CITA fecha, H.PESO peso, H.IMC imc
       FROM AG_HISTORIAL H INNER JOIN AG_CITA C ON C.IDCITA = H.IDCITA
      WHERE C.IDPACIENTE = ? AND H.PESO IS NOT NULL AND H.PESO > 0
      ORDER BY C.FECHA_CITA ASC, C.HORA_INICIO ASC")) {
    $st->bind_param('i', $idPaciente); $st->execute();
    $rs = $st->get_result();
    while ($r = $rs->fetch_assoc()) {
        $labels[] = date('m/d/Y', strtotime($r['fecha']));
        $pesos[]  = ($r['peso'] !== null) ? round((float)$r['peso'], 1) : null;
        $imcs[]   = ($r['imc']  !== null && (float)$r['imc'] > 0) ? round((float)$r['imc'], 1) : null;
    }
    $st->close();
}
echo json_encode(['ok'=>true, 'labels'=>$labels, 'pesos'=>$pesos, 'imcs'=>$imcs]);
