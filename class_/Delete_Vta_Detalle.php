<?php
ob_start();
require_once("conexionBD.php");
$conexion = conectarse();
 
    $data = array();
	$id= $_REQUEST['id'];     

    $sql3= "delete from vta_registro_detalle WHERE id_registro_detalle = ".$id; 
    $consulta = $conexion->query ($sql3) or die ("Problemas al insertar datos:<br>".mysqli_error($conexion));
    if($consulta){                    
        $data['id'] = $id;
        $data['status'] = "ok"; 
        $data['msg'] = "Registro Eliminado Correctamente!";          
    }else{
        $data['id'] = "0";
        $data['status'] = "err";
        $data['msg'] = mysqli_error($conexion);     
    }

echo json_encode($data);
ob_end_flush();
?>