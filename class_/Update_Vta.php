<?php
require_once("conexionBD.php");

$conexion = conectarse();
$data = array();

    $id = $_REQUEST['idVta'];
    $fecha_salida = $_REQUEST['fecha_salida'];        
    $fecha_regreso = $_REQUEST['fecha_regreso'];
    $origen = $_REQUEST['origen'];        
    $destino = $_REQUEST['destino'];
    $requerimiento = $_REQUEST['requerimiento']; 
    $aerolinea= $_REQUEST["aerolinea"];
    $tipo_pasajero= $_REQUEST["tpasajero"];          
    $valor_ofrecido = $_REQUEST['valor_ofrecido'];
    $costo = $_REQUEST['costo'];
    $utilidad= $valor_ofrecido-$costo;
    $forma_pago = $_REQUEST['forma_pago'];
    $forma_pago_fee= $_REQUEST["forma_pago_fee"];
    $observacion = $_REQUEST['observacion'];
    $existe = TRUE;
           
    if ($existe){    
        $sql="update vta_registro set fecha_salida ='".$fecha_salida."', fecha_regreso ='".$fecha_regreso."', origen ='".$origen."',destino ='".$destino."',requerimiento ='".$requerimiento."',aerolinea ='".$aerolinea."',tipo_pasajero ='".$tipo_pasajero."', valor_ofrecido ='".$valor_ofrecido."', costo ='".$costo."', utilidad ='".$utilidad."',forma_pago ='".$forma_pago."',forma_pago_fee ='".$forma_pago_fee."',observacion ='".$observacion."'  where idVta_registro='".$id."'";   
        $consulta = $conexion->query ($sql) or die ("Problemas al insertar datos:<br>".mysqli_error($conexion));
        if($consulta){
            $data['id'] = $id;
            $data['status'] = "ok"; 
            $data['msg'] = "Venta Actualizada Correctamente!";          
        }else{
            $data['id'] = "0";
            $data['status'] = "err";
            $data['msg'] = mysqli_error($conexion);                
            
        }
    }

echo json_encode($data);
ob_end_flush();
?>


