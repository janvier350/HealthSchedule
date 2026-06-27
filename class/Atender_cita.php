<?php
require_once("funciones.php");
require_once("conexionBD.php");
$conexion = conectarse();
session_start();
?>
<?php       
    $IdCita =$_REQUEST['IdCita'];
  
        $sql= "UPDATE AG_CITA SET ESTADO_CITA ='Atendido' WHERE IDCITA = ".$IdCita ;                    
        $consulta = $conexion->query ($sql) or die ("Problemas al Guardar datos:<br>".mysqli_error($conexion));
        //Insert status
        if($consulta){
            echo 'Event data inserted successfully.. Event ID: '.$conexion->insert_id;
            echo "<script>javascript: alert('Datos Guardados Correctamente!') </script>";    
            echo "<Script language='JavaScript'>";
            echo 'self.location = "../Home.php"';
            echo"</script>"; 
        }else{
            echo 'Failed to insert '.$consulta.'event data'.mysqli_error($conexion);
        }    
    

?>

