<!--alertas-->
    <script src="../sweetalert/src/sweetalert.min.js"></script>

<?php
require_once("conexionBD.php");

$conexion = conectarse();
?>

<?php    
    $apellido = $_REQUEST['apellido'];        
    $nombre = $_REQUEST['nombre'];
    $genero = $_REQUEST['genero'];
    $telefono = $_REQUEST['telefono'];
    $tidentificacion = $_REQUEST['tidentificacion'];
    // $identificacion = $_REQUEST['identificacion'];
    // $email = $_REQUEST['email'];
    // $ciudad = $_REQUEST['ciudad'];
    // $origen = $_REQUEST['origen'];
    // $file = $_REQUEST['file'];

    // $n_image = $_FILES['file']['name'];
    // $non_image = strtolower($n_image);
    // $ruta = "images/clientes/" . $_FILES['file']['name'];
    // $destino = "images/clientes/".$non_image;
    // move_uploaded_file($_FILES['file']['tmp_name'],  $_SERVER['DOCUMENT_ROOT'].'/sgsystem/'.$ruta);

    if ($genero =="Masculino"){
        $imagen = "images/clientes/man.png";
    }else if ($genero =="Femenino"){
        $imagen = "images/clientes/woman.png";
    }
    
    $sqlValida= "SELECT * FROM adm_cliente  WHERE telefono = '".$telefono."'";                
    $result = $conexion->query($sqlValida);
    if ($result->num_rows > 0) {     
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $existe = FALSE;
    }else{
        $existe = TRUE;
    }
        
    if ($existe){    
        $sql="insert into adm_cliente (nombre_cliente, apellido_cliente, telefono, genero,imagen, tipo_identificacion) values ('".$nombre."','".$apellido."','".$telefono."','".$genero."','".$imagen."','".$tidentificacion."')";   
        $consulta = $conexion->query ($sql) or die ("Problemas al insertar datos:<br>".mysqli_error($conexion));
        //Insert status
        if($consulta){
            echo 'Event data inserted successfully.. Event ID: '.$conexion->insert_id;
            echo "<script> swal('Cliente Creado Correctamente!','Ingreso de Datos.','success').then((value) =>{
                self.location ='../PNC_ClienteCrear.php';
            }); </script>";             
        }else{
            echo 'Failed to insert '.$consulta.'event data'.mysqli_error($conexion);
        }
    }else{
        echo "<script> swal('Cliente ya existe !','Ingreso de Datos.','warning').then((value) =>{
                self.location ='../PNC_ClienteCrear.php';
            }); </script>";    
    }   

?>
  
<?php
ob_end_flush();
?>


