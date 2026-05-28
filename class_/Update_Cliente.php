
<script src="../sweetalert/src/sweetalert.min.js"></script>
<?php
require_once("conexionBD.php");

$conexion = conectarse();
?>

<?php    
    $id = $_REQUEST['id'];
    $apellido_cliente = $_REQUEST['apellido_cliente'];        
    $nombre_cliente = $_REQUEST['nombre_cliente'];
    $email = $_REQUEST['email'];        
    $identificacion = $_REQUEST['identificacion'];
    $telefono = $_REQUEST['telefono'];           
    $fecha_nacimiento = $_REQUEST['fecha_nacimiento'];           
    $genero = $_REQUEST['genero'];
    $tidentificacion = $_REQUEST['tidentificacion'];
    $existe = TRUE;
           
    if ($existe){    
        $sql="update adm_cliente set nombre_cliente ='".$nombre_cliente."', apellido_cliente ='".$apellido_cliente."', email ='".$email."',identificacion ='".$identificacion."',telefono ='".$telefono."',fecha_nacimiento ='".$fecha_nacimiento."',genero ='".$genero."',tipo_identificacion ='".$tidentificacion."'  where idAdm_cliente ='".$id."'";   
        $consulta = $conexion->query ($sql) or die ("Problemas al insertar datos:<br>".mysqli_error($conexion));
        if($consulta){
            echo "ok";            
            echo "<script> swal('Cliente Actualizado Correctamente!','Ingreso de Datos.','success').then((value) =>{
                self.location ='../PNC_ClienteListado.php';
            }); </script>";          
        }else{
            echo 'Failed to insert '.$consulta.'event data'.mysqli_error($conexion);
            echo "<script> swal('Error al Actualizar Cliente!','Ingreso de Datos.','error').then((value) =>{
                self.location ='../PNC_ClienteListado.php';
            }); </script>"; 
        }
    }

?>
  
<?php
ob_end_flush();
?>


