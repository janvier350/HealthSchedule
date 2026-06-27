<?php
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo '<div class="alert alert-danger">ID inválido.</div>';
    exit;
}

$id  = (int)$_GET['id'];
$res = $conexion->query(
    "SELECT H.CONTENIDO_INFORME, P.NOMBRES, P.APELLIDOS, C.FECHA_CITA
     FROM AG_HISTORIAL H
     INNER JOIN AG_CITA C     ON H.IDCITA     = C.IDCITA
     INNER JOIN AG_PACIENTE P ON C.IDPACIENTE = P.IDPACIENTE
     WHERE H.IDHISTORIAL = $id LIMIT 1"
);

if (!$res || $res->num_rows === 0) {
    http_response_code(404);
    echo '<div class="alert alert-warning">Informe no encontrado.</div>';
    exit;
}

$row = $res->fetch_assoc();
header('Content-Type: text/html; charset=utf-8');
echo $row['CONTENIDO_INFORME'];
