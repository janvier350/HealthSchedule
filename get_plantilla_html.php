<?php
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo 'ID inválido';
    exit;
}

$id  = (int)$_GET['id'];
$res = $conexion->query("SELECT cuerpo_html FROM cat_plantillas_nutricion WHERE id = $id LIMIT 1");

if (!$res || $res->num_rows === 0) {
    http_response_code(404);
    echo 'Plantilla no encontrada';
    exit;
}

$row = $res->fetch_assoc();
header('Content-Type: text/html; charset=utf-8');
echo $row['cuerpo_html'];
