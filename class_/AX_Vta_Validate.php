<?php
ob_start();
require_once("conexionBD.php");
$conexion = conectarse();

//recoger datos del formulario    
    $data = array();
	$id= $_POST['id'];     
    $sql = "SELECT * FROM vta_registro WHERE idVta_registro = '".$id."'";    
    $query = $conexion -> query ($sql) or die ("Problemas al insertar datos:<br>".mysqli_error($conexion));
    
    if($query->num_rows > 0){
        while ($valores = mysqli_fetch_array($query)) {
            if($valores['fecha_salida'] == '0000-00-00'){        
                $data['status'] = 'no'; 
                $data['msg'] = 'Falta Fecha de Salida'; 
            }else{
                $data['status'] = 'ok'; 
                $data['msg'] = 'Datos Completos'; 
            }
        }
    }        

echo json_encode($data);
ob_end_flush();
?>