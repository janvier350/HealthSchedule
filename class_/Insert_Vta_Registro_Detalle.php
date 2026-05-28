
<?php
session_start();
require_once("conexionBD.php");
$conexion = conectarse();
$data = array();

    $idVenta = $_POST['idVenta'];            
    $cantidad = $_POST['cantidad'];
    $detalle = $_POST['detalle'];
    $precio = $_POST['precio'];
    $costo = $_POST['costo'];
    $total = $_POST['total'];
    $existe = FALSE;
        
    if (!$existe){
        $sql="insert into vta_registro_detalle (idVta_registro, cantidad, detalle, precio,costo, total) 
            values ('".$idVenta."','".$cantidad."','".$detalle."','".$precio."','".$costo."','".$total."')";   
        $consulta = $conexion->query ($sql) or die ("Problemas al insertar datos:<br>".mysqli_error($conexion));        
        //Insert status
        if($consulta){
            $data['id'] = $conexion->insert_id;
            $data['status'] = "ok"; 
            $data['msg'] = "Detalle Venta Creada Correctamente!";          
        }else{
            $data['id'] = "0";
            $data['status'] = "err";
            $data['msg'] = mysqli_error($conexion);             
        }
    }else{    
        $data['id'] = "0";
        $data['status'] = "existe";
        $data['msg'] = "Venta ya Existe, Revise los datos ingresados."; 
    }   

    echo json_encode($data);
    ob_end_flush();
?>


