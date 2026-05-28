<!--alertas-->
    <script src="../sweetalert/src/sweetalert.min.js"></script>

<?php
require_once("conexionBD.php");

$conexion = conectarse();
?>

<?php              
    $nombre = $_REQUEST['nombre'];
    $desc = $_REQUEST['desc'];
    $posicion = $_REQUEST['posicion'];

    $n_image = $_FILES['file']['name'];
    $non_image = strtolower($n_image);
    $ruta = "images/web_home/" . $_FILES['file']['name'];
    $destino = "images/web_home/".$non_image;
    move_uploaded_file($_FILES['file']['tmp_name'],  $_SERVER['DOCUMENT_ROOT'].'/sgsystem/'.$ruta);
   copy($_SERVER['DOCUMENT_ROOT'].'/sgsystem/'.$ruta,  $_SERVER['DOCUMENT_ROOT'].'/sgtour/'.$ruta);

    $sqlValida= "SELECT * FROM Web_home  WHERE nombre = '".$nombre."'";                
    $result = $conexion->query($sqlValida);
    if ($result->num_rows > 0) {     
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $existe = FALSE;
    }else{
        $existe = TRUE;
    }
        
    if ($existe){    
        $sql="insert into Web_home(nombre, image, posicion,descripcion,estado) values ('".$nombre."','".$destino."','".$posicion."','".$desc."','1')";   
        $consulta = $conexion->query ($sql) or die ("Problemas al insertar datos:<br>".mysqli_error($conexion));
        //Insert status
        if($consulta){
            echo 'Event data inserted successfully.. Event ID: '.$conexion->insert_id;
            echo "<script> swal('Datos Creado Correctamente!','Ingreso de Datos.','success').then((value) =>{
                self.location ='../WEB_HomeCrear.php';
            }); </script>";                
        }else{
            echo 'Failed to insert '.$consulta.'event data'.mysqli_error($conexion);
        }
    }else{
        echo "<script> swal('Nombre ya existe !','Ingreso de Datos.','warning').then((value) =>{
                self.location ='../WEB_HomeCrear.php';
            }); </script>";     
    }   

?>
  
<?php
ob_end_flush();
?>


