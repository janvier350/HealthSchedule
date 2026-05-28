

<?php
require_once("conexionBD.php");

$conexion = conectarse();
?>

<?php    
    $id = $_REQUEST['id'];
    $usuario = $_REQUEST['usuario'];           
    $existe = TRUE;
           
    if ($existe){    
        $sql="update Web_PaqueteReservado set idAdm_Usuario ='".$usuario."' where idWeb_PaqReserva='".$id."'";   
        $consulta = $conexion->query ($sql) or die ("Problemas al insertar datos:<br>".mysqli_error($conexion));
        //Insert status
        if($consulta){
            echo 'Event data inserted successfully.. Event ID: '.$conexion->insert_id.$usuario;
            echo "<script>swal('Empleado Creado Correctamente!','','success'); </script>";   
            echo "<Script language='JavaScript'>";
            echo 'self.location = "../VTA_Reserva.php"';
            echo"</script>";             
        }else{
            echo 'Failed to insert '.$consulta.'event data'.mysqli_error($conexion);
        }
    }else{
        echo "<script>swal('Empleado ya existe!','','error') </script>";    
        echo "<Script language='JavaScript'>";
        echo 'self.location = "../VTA_Reserva.php"';
        echo"</script>";     
    }   

?>
  
<?php
ob_end_flush();
?>


