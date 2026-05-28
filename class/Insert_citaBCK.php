<?php
require_once("funciones.php");
require_once("conexionBD.php");
$conexion = conectarse();
session_start();
?>
<?php
    //Insert datetime into the database
    $fechafactura =$_POST['fechafactura'];
    $IdPaciente =$_POST['IdPaciente'];
    $timeIni =$_POST['timeIni'];
//    $timeFin =$_POST['timeFin'];
    $Idconsulta =$_POST['Idconsulta'];
    $IdDoctor =$_POST['IdDoctor'];
    $comentario =$_POST['comentario'];
    $seg_timeIni=strtotime($timeIni);
    $seg_minutoAnadir=30*60;
    $timeFin=date("H:i",$seg_timeIni+$seg_minutoAnadir);
    $existe;
//    $IDUSER= $_SESSION['iduser']
//    $timeIni = $conexion->real_escape_string($_POST['timeIni']);
//    $timeFin = $conexion->real_escape_string($_POST['timeFin']);    
    $sqlValida= "SELECT * FROM AG_CITA WHERE FECHA_CITA = '".$fechafactura."' and HORA_INICIO ='".$timeIni."' and estado ='A'";                
    $result = $conexion->query($sqlValida);
    if ($result->num_rows > 0) {     
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $existe = FALSE;
    }else{
        $existe = TRUE;
    }
    
  
    if ($existe){
        $sql= "INSERT INTO AG_CITA (IDPACIENTE,IDTIPOCONSULTA,IDDOCTOR,IDUSUARIO,FECHA_CITA,HORA_INICIO, HORA_FIN,ESTADO_CITA,COMENTARIO,ESTADO) ".                 "VALUES('".$IdPaciente."','".$Idconsulta."','".$IdDoctor."','".$_SESSION['iduser']."','".$fechafactura."','".$timeIni."','".$timeFin."','Pendiente','".$comentario."','A')";    
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
        echo "<script>javascript: alert('Cita ya existe!') </script>";    
        echo "<Script language='JavaScript'>";
        echo 'self.location = "../Home.php"';
        echo"</script>"; 
    }    

?>

