<!--alertas-->
    <script src="../sweetalert/src/sweetalert.min.js"></script>

<?php
require_once("conexionBD.php");

$conexion = conectarse();
?>

<?php    
    $codigo = $_REQUEST['codigo'];        
    $nombre = $_REQUEST['nombre'];
    $precio1 = $_REQUEST['precio1'];
    $precio2 = $_REQUEST['precio2'];
    $resumen = $_REQUEST['resumen'];
    $incluye = $_REQUEST['incluye'];
    $noincluye = $_REQUEST['noincluye'];
    $notas = $_REQUEST['notas'];
    $posicion = $_REQUEST['posicion'];

    $n_image = $_FILES['file']['name'];
    $non_image = strtolower($n_image);
    $ruta = "images/web_paquete/" . $_FILES['file']['name'];
    $destino = "images/web_paquete/".$non_image;
    move_uploaded_file($_FILES['file']['tmp_name'],  $_SERVER['DOCUMENT_ROOT'].'/sgsystem/'.$ruta);
    copy($_SERVER['DOCUMENT_ROOT'].'/sgsystem/'.$ruta,  $_SERVER['DOCUMENT_ROOT'].'/sgtour/'.$ruta);


    $n_image2 = $_FILES['imagen2']['name'];
    $non_image2 = strtolower($n_image2);
    $ruta2 = "images/web_paquete/" . $_FILES['imagen2']['name'];
    $destino2 = "images/web_paquete/".$non_image2;
    move_uploaded_file($_FILES['imagen2']['tmp_name'],  $_SERVER['DOCUMENT_ROOT'].'/sgsystem/'.$ruta2);
    copy($_SERVER['DOCUMENT_ROOT'].'/sgsystem/'.$ruta2,  $_SERVER['DOCUMENT_ROOT'].'/sgtour/'.$ruta2);

    $sqlValida= "SELECT * FROM Web_Paquetes  WHERE nombre_paquete = '".$nombre."'";                
    $result = $conexion->query($sqlValida);
    if ($result->num_rows > 0) {     
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $existe = FALSE;
    }else{
        $existe = TRUE;
    }
        
    if ($existe){    
        $sql="insert into Web_Paquetes(nombre_paquete, precio, precio2, imagen,imagen_horizontal, resumen,tab_incluye,tab_noincluye,tab_notasimportantes,codigo_paq,posicion,estado) values ('".$nombre."','".$precio1."','".$precio2."','".$destino."','".$destino2."','".$resumen."','".$incluye."','".$noincluye."','".$notas."','".$codigo."','".$posicion."','1')";   
        $consulta = $conexion->query ($sql) or die ("Problemas al insertar datos:<br>".mysqli_error($conexion));
        //Insert status
        if($consulta){
            echo 'Event data inserted successfully.. Event ID: '.$conexion->insert_id;
            echo "<script> swal('Paquete Creado Correctamente!','Ingreso de Datos.','success').then((value) =>{
                self.location ='../WEB_PaqueteCrear.php';
            }); </script>";                
        }else{
            echo 'Failed to insert '.$consulta.'event data'.mysqli_error($conexion);
        }
    }else{
        echo 'Error';
        echo "<script> swal('Paquete ya existe !','Ingreso de Datos.','warning').then((value) =>{ 
            self.location ='../WEB_PaqueteCrear.php';
        }); </script>";     
    }   

?>
  
<?php
ob_end_flush();
?>


