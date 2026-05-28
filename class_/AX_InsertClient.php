<?php
ob_start();
require_once("conexionBD.php");
$conexion = conectarse();

    //recoger datos del formulario    
    $data = array();
	$apellido = $_POST['apellido'];        
    $nombre = $_POST['nombre'];
    $genero = "";
    $telefono = $_POST['telefono'];
    
    if ($genero =="Masculino" || $genero == ""){
        $imagen = "images/clientes/man.png";
    }else if ($genero =="Femenino"){
        $imagen = "images/clientes/woman.png";
    }

    $sqlValida= "SELECT * FROM adm_cliente  WHERE estado= 1 and  telefono = '".$telefono."'";                
    $result = $conexion->query($sqlValida);
    if ($result->num_rows > 0) {     
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $existe = FALSE;
    }else{
        $existe = TRUE;
    }

    if ($existe){  
        $sql="insert into adm_cliente (nombre_cliente, apellido_cliente, telefono, genero,imagen) values ('".$nombre."','".$apellido."','".$telefono."','".$genero."','".$imagen."')";   
        $consulta = $conexion->query ($sql) or die ("Problemas al insertar datos:<br>".mysqli_error($conexion));        
        if($consulta){   
            $data['id'] = $conexion->insert_id;
            $data['status'] = "ok"; 
            $data['msg'] = "Cliente Ingresado Correctamente.";
        }else{
            $data['id'] = "err";
            $data['status'] = "err";
            $data['msg'] = mysqli_error($conexion);  
        }
    }else{
        $data['id'] = "0";
        $data['status'] = "existe";
        $data['msg'] = "Cliente ya Existe, Revise los datos ingresados."; 
    }
        
echo json_encode($data);
ob_end_flush();
?>