<?php
require_once("conexionBD.php");
$conexion = conectarse();
?>
<?php
if ($conexion->connect_error) {
 die("La conexion falló: " . $conexion->connect_error);
}
$username = $_POST['email'];
$password = $_POST['pass'];
$sql = "SELECT A.idAdm_Usuario, A.nombre, A.apellido,A.email, A.password, B.idAdm_Roles, B.nombre_rol,A.idAdm_Agencia, A.imagen 
    FROM adm_usuario A INNER JOIN adm_roles B ON A.idAdm_Roles = B.idAdm_Roles
    WHERE A.email = '$username' ";

$result = $conexion->query($sql);


if ($result->num_rows > 0) {     
 }
    $row = mysqli_fetch_array($result);
    $PASS =$row['password'];
 if (password_verify($password, $row['password'])) { 
    session_start();
    $_SESSION['loggedin'] = true;
    $_SESSION['username'] = $username;
    $_SESSION['user'] = $row['nombre'].' '.$row['apellido'];;
    $_SESSION['iduser'] = $row['idAdm_Usuario'];
    $_SESSION['idAgencia'] = $row['idAdm_Agencia'];
    $_SESSION['rol'] = $row['nombre_rol'];
    $_SESSION['idrol'] = $row['idAdm_Roles'];
    $_SESSION['imagen'] = $row['imagen'];
    $_SESSION['start'] = time();
    $_SESSION['expire'] = $_SESSION['start'] + (3156000);
    $_SESSION['idcia'] = "1"; 
    require_once("control_rol_class.php");  

//    echo "Bienvenido! " . $_SESSION['username'];
//    echo "<br><br><a href=../Home.php>Panel de Control</a>";
   /* if($row['idAdm_Roles']==1){
        echo "<Script language='JavaScript'>";
        echo 'self.location = "../home_adm.php"';
        echo"</script>"; 
    }else if($row['idAdm_Roles']==4){
        echo "<Script language='JavaScript'>";
        echo 'self.location = "../home_control.php"';
        echo"</script>";
    }else if($row['idAdm_Roles']==3){
        echo "<Script language='JavaScript'>";
        echo 'self.location = "../home_gerente.php"';
        echo"</script>";
    }else if($row['idAdm_Roles']==2){
        echo "<Script language='JavaScript'>";
        echo 'self.location = "../home_vta.php"';
        echo"</script>";
    }else if($row['idAdm_Roles']==5){
        echo "<Script language='JavaScript'>";
        echo 'self.location = "../home_paq.php"';
        echo"</script>";
    } else if($row['idAdm_Roles']==6){
        echo "<Script language='JavaScript'>";
        echo 'self.location = "../home_control.php"';
        echo"</script>";
    }else if($row['idAdm_Roles']==7){
        echo "<Script language='JavaScript'>";
        echo 'self.location = "../home_control.php"';
        echo"</script>";
    }  */
   

 } else { 
   echo "Usuario o Password estan incorrectos.";
   echo "<br><a href='../index.php'>Volver a Intentarlo</a>";
 }
 mysqli_close($conexion); 
 ?>

