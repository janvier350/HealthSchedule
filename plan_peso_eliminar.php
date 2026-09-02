<?php
/**
 * plan_peso_eliminar.php — Baja lógica de un plan de peso guardado.
 * POST: idPlan
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

if (!isset($_SESSION["rol"], $_SESSION["iduser"])) { echo 'SIN_SESION'; exit; }

$idPlan = (int)($_POST['idPlan'] ?? 0);
if (!$idPlan) { echo 'PLAN_INVALIDO'; exit; }

$stmt = $conexion->prepare("UPDATE AG_PLAN_PESO SET ESTADO = 'I' WHERE IDPLAN = ?");
$stmt->bind_param("i", $idPlan);

if ($stmt->execute()) {
    echo 'OK';
} else {
    echo 'ERROR: ' . $stmt->error;
}
$stmt->close();
$conexion->close();
