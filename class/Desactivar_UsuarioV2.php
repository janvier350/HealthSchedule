<?php
require_once("funciones.php");
require_once("conexionBD.php");
$conexion = conectarse();
session_start();
?>
<?php       
    $idUsuario =$_REQUEST['idUsuario'];
  
        $sql= "UPDATE ADM_USUARIO SET ESTADO ='I' WHERE IDADM_USUARIO = ".$idUsuario ;                    
        $consulta = $conexion->query ($sql) or die ("Problemas al Desactivar datos:<br>".mysqli_error($conexion));
        //Insert status
        if($consulta){
            echo 'Event data inserted successfully.. Event ID: '.$conexion->insert_id;
            echo "<script>javascript: alert('Datos Desactivados Correctamente!') </script>";    
            echo "<Script language='JavaScript'>";
             echo 'self.location = "../PNC_UsuarioCrear.php"';
            // echo 'self.location = "../Pacientes.php"';
            
            echo"</script>"; 
        }else{
            echo 'Failed to insert '.$consulta.'event data'.mysqli_error($conexion);
        }    
    

?>

