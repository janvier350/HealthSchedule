<?php
require_once("funciones.php");
require_once("conexionBD.php");
$conexion = conectarse();
session_start();
     
   
    
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idUsuario = $_POST["idUsuario"];
    $nombres = $_POST["nombres"];
    $apellidos = $_POST["apellidos"];
     $idRol = $_POST["idRol"];
      $idAgencia = $_POST["idAgencia"];
    $telefono = $_POST["telefono"];
    

   
    

    $sql = "UPDATE ADM_USUARIO SET 
                NOMBRES = ?, 
                APELLIDOS = ?, 
                 IDADM_ROL = ?, 
                 IDAGENCIA = ?,
                TELEFONO = ?  
            WHERE IDADM_USUARIO = ?";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sssssi", $nombres, $apellidos, $idRol, $idAgencia, $telefono, $idUsuario);

    if ($stmt->execute()) {
        echo "Usuario actualizado correctamente";
    } else {
        echo "Error al actualizar: " . $stmt->error;
    }

    $stmt->close();
}


?>

