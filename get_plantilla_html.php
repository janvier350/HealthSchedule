<?php
require_once("class/conexionBD.php");
$conexion = conectarse();

if(isset($_GET['id'])) {
    $id = $conexion->real_escape_string($_GET['id']);
    
    // Consultamos el cuerpo_html de la tabla
    $sql = "SELECT cuerpo_html FROM cat_plantillas_nutricion WHERE id = '$id'";
    $res = $conexion->query($sql);
    
    if($row = $res->fetch_assoc()) {
        // Devolvemos el HTML puro para que Summernote lo renderice
        echo $row['cuerpo_html'];
    }
}
?>