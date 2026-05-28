<?php
require_once("funciones.php");
require_once("conexionBD.php");
$conexion = conectarse();
session_start();

// Insert datetime into the database
$cedula = $_POST['cedula'];
$title = $_POST['title'];
$nombres = $_POST['nombres'];
$apellidos = $_POST['apellidos'];
$telefono = $_POST['telefono'];
$email = $_POST['email'];
$sex = $_POST['sex'];
$gender = $_POST['gender'];
$feNac = $_POST['feNac'];
$address = $_POST['address'];
$notes = $_POST['notes'];
$addNotes = $_POST['addNotes'];

// ESCAPAR TODOS LOS VALORES PARA PREVENIR ERRORES DE SQL INJECTION
$cedula = $conexion->real_escape_string($cedula);
$title = $conexion->real_escape_string($title);
$nombres = $conexion->real_escape_string($nombres);
$apellidos = $conexion->real_escape_string($apellidos);
$telefono = $conexion->real_escape_string($telefono);
$email = $conexion->real_escape_string($email);
$sex = $conexion->real_escape_string($sex);
$gender = $conexion->real_escape_string($gender);
$feNac = $conexion->real_escape_string($feNac);
$address = $conexion->real_escape_string($address);
$notes = $conexion->real_escape_string($notes);
$addNotes = $conexion->real_escape_string($addNotes);

$sqlValida = "SELECT * FROM AG_PACIENTE WHERE TELEFONO = '".$telefono."' and ESTADO ='A'";                
$result = $conexion->query($sqlValida);
if ($result->num_rows > 0) {     
    $row = $result->fetch_array(MYSQLI_ASSOC);
    $existe = FALSE;
} else {
    $existe = TRUE;
}  

if ($existe) {
    $sql = "INSERT INTO AG_PACIENTE (NOMBRES, APELLIDOS, EMAIL, FECHANACIMIENTO, TELEFONO, CEDULA, TITLE, SEX, GENDER, ESTADO, ADDRESS, NOTES, ADDNOTES) 
            VALUES ('".$nombres."', '".$apellidos."', '".$email."', '".$feNac."', '".$telefono."', '".$cedula."', '".$title."', '".$sex."', '".$gender."', 'A','".$address."', '".$notes."', '".$addNotes."')";    

    // Mostrar la consulta SQL antes de ejecutarla
    echo "<script>alert('Consulta SQL: " . addslashes($sql) . "');</script>";

    $consulta = $conexion->query($sql) or die("Problemas al insertar datos:<br>".mysqli_error($conexion));
    
    // Insert status
    if ($consulta) {
        echo 'Event data inserted successfully.. Event ID: '.$conexion->insert_id;
        echo "<script>javascript: alert('Datos Creados Correctamente!') </script>";    
        echo "<Script language='JavaScript'>";
        echo 'self.location = "../PNC_PacienteCrear.php"';
        echo "</script>"; 
    } else {
        echo 'Failed to insert '.$consulta.'event data'.mysqli_error($conexion);
    }    
} else {
    echo "<script>javascript: alert('Paciente ya existe!') </script>";    
    echo "<Script language='JavaScript'>";
    echo 'self.location = "../PNC_PacienteCrear.php"';
    echo "</script>"; 
}
?>