<?php
require_once("funciones.php");
require_once("conexionBD.php");
$conexion = conectarse();
session_start();
?>
<?php       
    $IdPaciente =$_REQUEST['IdPaciente'];
  
        $sql= "UPDATE AG_PACIENTE SET ESTADO ='I' WHERE IDPACIENTE = ".$IdPaciente ;                    
        $consulta = $conexion->query ($sql) or die ("Problemas al Eliminar datos:<br>".mysqli_error($conexion));
        //Insert status
        if($consulta){
            echo 'Event data inserted successfully.. Event ID: '.$conexion->insert_id;
            echo "<script>javascript: alert('Datos Eliminados Correctamente!') </script>";    
            echo "<Script language='JavaScript'>";
            echo 'self.location = "../Pacientes.php"';
            echo"</script>"; 
        }else{
            echo 'Failed to insert '.$consulta.'event data'.mysqli_error($conexion);
        }    
    

?>

