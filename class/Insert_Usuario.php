<?php
require_once("funciones.php");
require_once("conexionBD.php");
$conexion = conectarse();
session_start();

// Capturar datos del formulario
$nombres = $_POST['nombres'];
$apellidos = $_POST['apellidos'];
$telefono = $_POST['telefono'];
$Idrol = $_POST['Idrol'];
$usuario = $_POST['usuario'];
$clave = $_POST['clave'];
$idAgencia = 1;
$imagen = 'https://mdbootstrap.com/img/new/avatars/8.jpg'; // Imagen como string
$cifrar = password_hash($clave, PASSWORD_DEFAULT);

// Validar si el usuario ya existe
$sqlValida = "SELECT * FROM ADM_USUARIO WHERE USUARIO = '$usuario' AND ESTADO = 'A'";                
$result = $conexion->query($sqlValida);

if ($result->num_rows > 0) {     
    echo "<script>alert('Usuario ya existe!'); window.location.href = '../PNC_UsuarioCrear.php';</script>";
    exit(); // Salir del script para evitar la ejecución del INSERT
}

// Insertar nuevo usuario
$sql = "INSERT INTO ADM_USUARIO (NOMBRES, APELLIDOS, TELEFONO, USUARIO, CONTRASENA, IDADM_ROL, IDAGENCIA, IMG, ESTADO) 
        VALUES ('$nombres', '$apellidos', '$telefono', '$usuario', '$cifrar', '$Idrol', '$idAgencia', '$imagen', 'A')";

if ($conexion->query($sql) === TRUE) {
    echo "<script>alert('Usuario Creado Correctamente!'); window.location.href = '../PNC_UsuarioCrear.php';</script>";
} else {
    echo "Error al insertar usuario: " . $conexion->error;
}
?>
