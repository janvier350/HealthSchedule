
<script src="../sweetalert/src/sweetalert.min.js"></script>
<?php
require_once("conexionBD.php");

$conexion = conectarse();
?>

<?php    
    $id = $_REQUEST['id'];
    $apellido = $_REQUEST['apellido'];        
    $nombre = $_REQUEST['nombre'];
    $email = $_REQUEST['email'];        
    $agencia = $_REQUEST['agencia'];
    $rol = $_REQUEST['rol'];           
    $existe = TRUE;
           
    if ($existe){    
        $sql="update adm_usuario set nombre ='".$nombre."', apellido ='".$apellido."', email ='".$email."',idAdm_Agencia ='".$agencia."',idAdm_Roles ='".$rol."'  where idAdm_Usuario='".$id."'";   
        $consulta = $conexion->query ($sql) or die ("Problemas al insertar datos:<br>".mysqli_error($conexion));
        if($consulta){    
            echo "ok";
            echo "<script> swal('Usuario Actualizado Correctamente!','Ingreso de Datos.','success').then((value) =>{
                self.location ='../PNC_UsuarioListado.php';
            }); </script>";          
        }else{
            echo 'Failed to insert '.$consulta.'event data'.mysqli_error($conexion);
            echo "<script> swal('Error al Actualizar Usuario!','Ingreso de Datos.','error').then((value) =>{
                self.location ='../PNC_UsuarioListado.php';
            }); </script>"; 
        }
    }

?>
  
<?php
ob_end_flush();
?>


