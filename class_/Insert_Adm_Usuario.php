<!--alertas-->
    <script src="../sweetalert/src/sweetalert.min.js"></script>

<?php
require_once("conexionBD.php");

$conexion = conectarse();
?>

<?php    
    $apellido = $_REQUEST['apellido'];        
    $nombre = $_REQUEST['nombre'];
    $email = $_REQUEST['email'];
    $password = $_REQUEST['password'];
    $file = $_REQUEST['file'];
    $agencia = $_REQUEST['agencia'];
    $rol = $_REQUEST['rol'];
    $cifrar=crypt($password);


    $n_image = $_FILES['file']['name'];
    $non_image = strtolower($n_image);
    $ruta = "images/users/" . $_FILES['file']['name'];
    $destino = "images//users//client.jpg";
    move_uploaded_file($_FILES['file']['tmp_name'],  $_SERVER['DOCUMENT_ROOT'].'/sgsystem/'.$ruta);

    $sqlValida= "SELECT * FROM adm_usuario  WHERE email = '".$email."'";                
    $result = $conexion->query($sqlValida);
    if ($result->num_rows > 0) {     
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $existe = FALSE;
    }else{
        $existe = TRUE;
    }
        
    if ($existe){    
        $sql="insert into adm_usuario (nombre, apellido, email, password, imagen,idAdm_Agencia,idAdm_Roles) values ('".$nombre."','".$apellido."','".$email."','".$cifrar."','".$destino."','".$agencia."','".$rol."')";   
        $consulta = $conexion->query ($sql) or die ("Problemas al insertar datos:<br>".mysqli_error($conexion));
        //Insert status
        if($consulta){
            echo 'Event data inserted successfully.. Event ID: '.$conexion->insert_id;
            echo "<script> swal('Usuario Creado Correctamente!','Ingreso de Datos.','success').then((value) =>{
                self.location ='../PNC_UsuarioCrear.php';
            }); </script>";                
        }else{
            echo 'Failed to insert '.$consulta.'event data'.mysqli_error($conexion);
        }
    }else{
        echo "<script> swal('Usuario ya existe !','Ingreso de Datos.','warning').then((value) =>{
                self.location ='../PNC_UsuarioCrear.php';
            }); </script>";     
    }   

?>
  
<?php
ob_end_flush();
?>


