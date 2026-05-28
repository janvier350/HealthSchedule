<?php
require_once("conexionBD.php");
$conexion = conectarse();

?>

<?php

//recoger datos del formulario
        $user='JQUINDE';
	//$pass=$_POST['password'];
 
//cifrar la contraseña para añadirla a la BD
$cifrar=password_hash('123', PASSWORD_DEFAULT);
 
//$sql="insert into ADM_USUARIO (nombres, apellidos, telefono, usuario, contrasena,idadm_rol, estado) values ('pepe','de','233','$user', '$cifrar', 1,'A')";

$sql="UPDATE adm_usuario SET password = '$cifrar' WHERE idAdm_usuario = 3";


$consulta = $conexion->query ($sql) or die ("Problemas al insertar datos:<br>".mysqli_error($conexion));
 
 ?>
