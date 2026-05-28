<?php
require_once("funciones.php");
require_once("conexionBD.php");
$conexion = conectarse();
session_start();
     
   
    
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idPaciente = $_POST["idPaciente"];
    $nombres = $_POST["nombres"];
    $apellidos = $_POST["apellidos"];
    $email = $_POST["email"];
    $telefono = $_POST["telefono"];
     $identificacion = $_POST["identificacion"];
    $fecNac = $_POST["fecNac"];
     $sex = $_POST["sex"];
      $title = $_POST["title"];
     $gender = $_POST["gender"];

    $sql = "UPDATE AG_PACIENTE SET 
                NOMBRES = ?, 
                APELLIDOS = ?, 
                EMAIL = ?, 
                TELEFONO = ?, 
                FECHANACIMIENTO = ?,
                CEDULA = ?,
                TITLE = ?,
                SEX = ?,
                GENDER = ?
            WHERE IDPACIENTE = ?";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sssssssssi", $nombres, $apellidos, $email, $telefono, $fecNac,$identificacion,$title,$sex,$gender, $idPaciente);

    if ($stmt->execute()) {
        echo "Paciente actualizado correctamente";
    } else {
        echo "Error al actualizar: " . $stmt->error;
    }

    $stmt->close();
}


?>

