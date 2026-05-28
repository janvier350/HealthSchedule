<?php
ob_start();
require_once("conexionBD.php");
$conexion = conectarse();

//recoger datos del formulario    
    $data = array();
	$id= $_POST['id'];     

    $sql3= "UPDATE vta_registro SET estado = 1 WHERE idVta_registro = ".$id; 
    $consulta = $conexion->query ($sql3) or die ("Problemas al insertar datos:<br>".mysqli_error($conexion));
    $data['status'] = 'err'; 
    if($consulta){
        $data['status'] = 'ok'; 
    }

echo json_encode($data);
ob_end_flush();
?>