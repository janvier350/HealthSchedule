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

    // Idioma preferido del paciente (para correos): solo se guarda si es válido y existe la columna
    $idioma = strtolower(trim($_POST['idioma'] ?? ''));
    if ($idioma !== 'en' && $idioma !== 'es') $idioma = '';
    $dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
    $tieneIdioma = (int)$conexion->query(
        "SELECT COUNT(*) c FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA='$dbName' AND TABLE_NAME='AG_PACIENTE' AND COLUMN_NAME='IDIOMA'"
    )->fetch_assoc()['c'] > 0;

    $campos = ["NOMBRES = ?", "APELLIDOS = ?", "EMAIL = ?", "TELEFONO = ?",
               "FECHANACIMIENTO = ?", "CEDULA = ?", "TITLE = ?", "SEX = ?", "GENDER = ?"];
    $tipos  = "sssssssss";
    $vals   = [$nombres, $apellidos, $email, $telefono, $fecNac, $identificacion, $title, $sex, $gender];

    if ($tieneIdioma && $idioma !== '') { $campos[] = "IDIOMA = ?"; $tipos .= "s"; $vals[] = $idioma; }

    $sql = "UPDATE AG_PACIENTE SET " . implode(", ", $campos) . " WHERE IDPACIENTE = ?";
    $tipos .= "i";
    $vals[] = $idPaciente;

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param($tipos, ...$vals);

    if ($stmt->execute()) {
        echo "Paciente actualizado correctamente";
    } else {
        echo "Error al actualizar: " . $stmt->error;
    }

    $stmt->close();
}


?>

