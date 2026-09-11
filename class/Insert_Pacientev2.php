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

// Idioma preferido del paciente (para correos): 'es' | 'en', por defecto 'es'
$idioma = strtolower(trim($_POST['idioma'] ?? 'es'));
if ($idioma !== 'en' && $idioma !== 'es') $idioma = 'es';

// ¿Existe la columna IDIOMA? (la agrega migrar_idioma_paciente.php)
$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$colExiste = function($col) use ($conexion, $dbName) {
    return (int)$conexion->query(
        "SELECT COUNT(*) c FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA='$dbName' AND TABLE_NAME='AG_PACIENTE' AND COLUMN_NAME='$col'"
    )->fetch_assoc()['c'] > 0;
};
$tieneIdioma = $colExiste('IDIOMA');
$tieneIcd10  = $colExiste('IDICD10');

// ICD-10 (opcional): id del catálogo ENFE_DIAG_COD
$idicd10 = isset($_POST['idicd10']) && ctype_digit((string)$_POST['idicd10']) ? (int)$_POST['idicd10'] : 0;

$sqlValida = "SELECT * FROM AG_PACIENTE WHERE TELEFONO = '".$telefono."' and ESTADO ='A'";
$result = $conexion->query($sqlValida);
if ($result->num_rows > 0) {     
    $row = $result->fetch_array(MYSQLI_ASSOC);
    $existe = FALSE;
} else {
    $existe = TRUE;
}  

if ($existe) {
    $colIdioma = $tieneIdioma ? ", IDIOMA" : "";
    $valIdioma = $tieneIdioma ? ", '".$conexion->real_escape_string($idioma)."'" : "";
    $colIcd10  = ($tieneIcd10 && $idicd10 > 0) ? ", IDICD10" : "";
    $valIcd10  = ($tieneIcd10 && $idicd10 > 0) ? ", ".$idicd10 : "";
    $sql = "INSERT INTO AG_PACIENTE (NOMBRES, APELLIDOS, EMAIL, FECHANACIMIENTO, TELEFONO, CEDULA, TITLE, SEX, GENDER, ESTADO, ADDRESS, NOTES, ADDNOTES".$colIdioma.$colIcd10.")
            VALUES ('".$nombres."', '".$apellidos."', '".$email."', '".$feNac."', '".$telefono."', '".$cedula."', '".$title."', '".$sex."', '".$gender."', 'A','".$address."', '".$notes."', '".$addNotes."'".$valIdioma.$valIcd10.")";

    $consulta = $conexion->query($sql) or die("Problemas al insertar datos:<br>".mysqli_error($conexion));

    // Insert status
    if ($consulta) {
        $nuevoId = (int)$conexion->insert_id;

        // Seguro primario (opcional): si el usuario eligió una aseguradora en el mismo
        // formulario de Create Patient, se guarda junto con el paciente. Así evitamos
        // el paso separado de "agregar seguro" tras crear el paciente.
        $primSeg   = isset($_POST['primary_seguro'])    && ctype_digit((string)$_POST['primary_seguro']) ? (int)$_POST['primary_seguro'] : 0;
        $primPol   = trim($_POST['primary_poliza']    ?? '');
        $primPrio  = trim($_POST['primary_prioridad'] ?? 'Primario');
        if (!in_array($primPrio, ['Primario', 'Secundario', 'Terciario'], true)) {
            $primPrio = 'Primario';
        }
        if ($primSeg > 0) {
            $stmtSeg = $conexion->prepare(
                "INSERT INTO paciente_seguro (IDPACIENTE, Id_seguro, num_poliza, prioridad, estado)
                 VALUES (?, ?, ?, ?, 1)"
            );
            if ($stmtSeg) {
                $stmtSeg->bind_param("iiss", $nuevoId, $primSeg, $primPol, $primPrio);
                @$stmtSeg->execute();
                $stmtSeg->close();
            }
        }

        // Redirige a la pantalla de creación con el ID recién creado, para que
        // el banner permita agregar más seguros (secundario/terciario) si hace falta.
        echo "<script>javascript: alert('Datos Creados Correctamente!') </script>";
        echo "<Script language='JavaScript'>";
        echo 'self.location = "../PNC_PacienteCrear.php?nuevo=' . $nuevoId . '"';
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