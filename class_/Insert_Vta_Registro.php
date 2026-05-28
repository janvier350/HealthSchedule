
<?php
session_start();
require_once("conexionBD.php");
$conexion = conectarse();
$data = array();

    $idCliente = $_POST['idCliente'];            
    $fecha_salida = $_POST['fecha_salida'];
    $fecha_regreso = $_POST['fecha_regreso'];
    $aerolinea = $_POST['aerolinea'];
    $tpasajero = $_POST['tpasajero'];
    $valor_ofrecido = $_POST['valor_ofrecido'];
    $cnt = $_POST['cnt'];
    $total = $_POST['total'];
    $costo = $_POST['costo'];
    $requerimiento = $_POST['requerimiento'];
    $observacion = $_POST['observacion'];
    $origen = $_POST['origen'];
    $destino = $_POST['destino'];
    $tserv = $_POST['tserv'];
    // $forma_pago = $_POST['forma_pago'];
    // $forma_pago_fee= $_POST["forma_pago_fee"];
    $existe = TRUE;

    if (empty($fecha_salida))
    {
        $fecha_salida = '0/0/0';
    }
    if (empty($fecha_regreso))
    {
        $fecha_regreso = '0/0/0';
    }
        
    if ($existe){
        $sql="insert into vta_registro (idAdm_cliente, idAdm_ciudad_destino, Requerimiento, mes, 
                fecha_salida, fecha_regreso,valor_ofrecido,idAdm_Agencia,idAdm_Usuario,observacion,estado,
                origen,destino,costo,cantidad,total,forma_pago,forma_pago_fee,aerolinea,tipo_pasajero, tipo_servicio) 
            values ('".$idCliente."','1','".$requerimiento."','','".$fecha_salida."','".$fecha_regreso."',
                '".$valor_ofrecido."','".$_SESSION['idAgencia']."','".$_SESSION['iduser']."','".$observacion."',
                '3','".$origen."','".$destino."','".$costo."','".$cnt."','".$total."','Efectivo',
                'Efectivo','".$aerolinea."','".$tpasajero."','".$tserv."')";   
        $consulta = $conexion->query ($sql) or die ("Problemas al insertar datos:<br>".mysqli_error($conexion));        
        //Insert status
        if($consulta){
            $data['id'] = $conexion->insert_id;
            $data['status'] = "ok"; 
            $data['msg'] = "Venta Creada Correctamente!";          
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


