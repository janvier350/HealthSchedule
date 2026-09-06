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
    $npi       = trim($_POST["npi"] ?? '');
    $licenseId = trim($_POST["license_id"] ?? '');

    // ¿Existen las columnas NPI y LICENSE_ID? (las agrega migrar_credenciales_doctor.php)
    $dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
    $colUsr = function($col) use ($conexion, $dbName) {
        return (int)$conexion->query(
            "SELECT COUNT(*) c FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA='$dbName' AND TABLE_NAME='ADM_USUARIO' AND COLUMN_NAME='$col'"
        )->fetch_assoc()['c'] > 0;
    };
    $tieneNpi     = $colUsr('NPI');
    $tieneLicense = $colUsr('LICENSE_ID');

    $campos = ["NOMBRES = ?", "APELLIDOS = ?", "IDADM_ROL = ?", "IDAGENCIA = ?", "TELEFONO = ?"];
    $tipos  = "sssss";
    $vals   = [$nombres, $apellidos, $idRol, $idAgencia, $telefono];

    if ($tieneNpi)     { $campos[] = "NPI = ?";        $tipos .= "s"; $vals[] = ($npi       === '' ? null : $npi); }
    if ($tieneLicense) { $campos[] = "LICENSE_ID = ?"; $tipos .= "s"; $vals[] = ($licenseId === '' ? null : $licenseId); }

    $sql = "UPDATE ADM_USUARIO SET " . implode(", ", $campos) . " WHERE IDADM_USUARIO = ?";
    $tipos .= "i";
    $vals[] = $idUsuario;

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param($tipos, ...$vals);

    if ($stmt->execute()) {
        echo "Usuario actualizado correctamente";
    } else {
        echo "Error al actualizar: " . $stmt->error;
    }

    $stmt->close();
}


?>

