<?php
require_once("funciones.php");
require_once("conexionBD.php");
$conexion = conectarse();
session_start();
?>
<?php       
    $idDoctor =$_REQUEST['idDoctor'];
  
        $sql= "UPDATE ADM_DOCTOR SET ESTADO ='A' WHERE IDDOCTOR = ".$idDoctor ;                    
        $consulta = $conexion->query ($sql) or die ("Problemas al Desactivar datos:<br>".mysqli_error($conexion));
        //Insert status
        if($consulta){
            echo 'Event data inserted successfully.. Event ID: '.$conexion->insert_id;
            echo "<script>javascript: alert('Doctor Activado Correctamente!') </script>";    
            echo "<Script language='JavaScript'>";
             echo 'self.location = "../PNC_DoctorCrear.php"';
            // echo 'self.location = "../Pacientes.php"';
            
            echo"</script>"; 
        }else{
            echo 'Failed to insert '.$consulta.'event data'.mysqli_error($conexion);
        }    
    

?>

