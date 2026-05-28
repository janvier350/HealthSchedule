<?php
ob_start();
require_once("conexionBD.php");
$conexion = conectarse();
 
    $data = array();
	$id= $_REQUEST['id'];     

    $sql3= "UPDATE adm_usuario SET estado = 2 WHERE idAdm_Usuario = ".$id; 
    $consulta = $conexion->query ($sql3) or die ("Problemas al insertar datos:<br>".mysqli_error($conexion));
    if($consulta){                    
        $data['id'] = $id;
        $data['status'] = "ok"; 
        $data['msg'] = "Usuario Eliminado Correctamente!";          
    }else{
        $data['id'] = "0";
        $data['status'] = "err";
        $data['msg'] = mysqli_error($conexion);     
    }

echo json_encode($data);
ob_end_flush();
?>