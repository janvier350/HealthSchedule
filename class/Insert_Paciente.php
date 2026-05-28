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
    $email =$_POST['email'];
    $feNac =$_POST['feNac'];
    $telefono =$_POST['telefono'];
    $sqlValida= "SELECT * FROM AG_PACIENTE WHERE TELEFONO = '".$telefono."' and ESTADO ='A'";                
    $result = $conexion->query($sqlValida);
    if ($result->num_rows > 0) {     
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $existe = FALSE;
    }else{
        $existe = TRUE;
    }  
    if ($existe){
        $sql= "INSERT INTO AG_PACIENTE (NOMBRES,APELLIDOS,EMAIL, FECHANACIMIENTO,TELEFONO,ESTADO) ".                 "VALUES('".$nombres."','".$apellidos."','".$email."','".$feNac."','".$telefono."','A')";    
        $consulta = $conexion->query ($sql) or die ("Problemas al insertar datos:<br>".mysqli_error($conexion));
        //Insert status
        if($consulta){
            echo 'Event data inserted successfully.. Event ID: '.$conexion->insert_id;
            echo "<script>javascript: alert('Datos Creados Correctamente!') </script>";    
            echo "<Script language='JavaScript'>";
            echo 'self.location = "../Home.php"';
            echo"</script>"; 
        }else{
            echo 'Failed to insert '.$consulta.'event data'.mysqli_error($conexion);
        }    
    }else{
        echo "<script>javascript: alert('Paciente ya existe!') </script>";    
        echo "<Script language='JavaScript'>";
        echo 'self.location = "../Home.php"';
        echo"</script>"; 
    }   

?>

