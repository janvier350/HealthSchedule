<?php
require_once("funciones.php");
require_once("conexionBD.php");
$conexion = conectarse();
session_start();
     
   
    
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idDoctor = $_POST["idDoctor"];
    $nombres = $_POST["nombresD"];
    $apellidos = $_POST["apellidosD"];
    
    $especialidad = $_POST["especialidadD"];
    

   
    

    $sql = "UPDATE ADM_DOCTOR SET 
                NOMBRES = ?, 
                APELLIDOS = ?, 
                 ESPECIALIDAD = ?
                 
            WHERE IDDOCTOR = ?";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sssi", $nombres, $apellidos, $especialidad, $idDoctor);

    if ($stmt->execute()) {
        echo "Datos Doctor actualizado correctamente";
    } else {
        echo "Error al actualizar: " . $stmt->error;
    }

    $stmt->close();
}


?>

