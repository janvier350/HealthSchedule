<?php
require_once("funciones.php");
require_once("conexionBD.php");
$conexion = conectarse();

?>

<?php

//recoger datos del formulario
        $user='jvaras';
	$pass=$_POST['password'];
 
//cifrar la contraseña para añadirla a la BD
$cifrar=password_hash('amoran01', PASSWORD_DEFAULT);


$sql="UPDATE ADM_USUARIO SET contrasena = '$cifrar' WHERE IDADM_USUARIO = 11 ";

$consulta = $conexion->query ($sql) or die ("Problemas al insertar datos:<br>".mysqli_error($conexion));
 
 ?>
