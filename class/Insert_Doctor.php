<?php
require_once("funciones.php");
require_once("conexionBD.php");
$conexion = conectarse();
session_start();
?>
<?php
    //Insert datetime into the database
    $nombres =$_POST['nombres'];
    $apellidos =$_POST['apellidos'];
    $especialidad =$_POST['especialidad'];
    
        $sql= "INSERT INTO ADM_DOCTOR (NOMBRES,APELLIDOS,ESPECIALIDAD,ESTADO) ".                 "VALUES('".$nombres."','".$apellidos."','".$especialidad."','A')";    
        $consulta = $conexion->query ($sql) or die ("Problemas al insertar datos:<br>".mysqli_error($conexion));
        //Insert status
        if($consulta){
            echo 'Event data inserted successfully.. Event ID: '.$conexion->insert_id;
            echo "<script>javascript: alert('Datos Creados Correctamente!') </script>";    
            echo "<Script language='JavaScript'>";
            echo 'self.location = "../Doctores.php"';
            echo"</script>"; 
        }else{
            echo 'Failed to insert '.$consulta.'event data'.mysqli_error($conexion);
        }    
    

?>

