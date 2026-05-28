<?php
require_once("funciones.php");
require_once("conexionBD.php");
$conexion = conectarse();
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../PHPMailer/src/Exception.php';
require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';

// Recoger datos del formulario
$fechafactura = $_POST['fechafactura'];
$IdPaciente = $_POST['IdPaciente'];
$timeIni = $_POST['timeIni'];
$Idconsulta = $_POST['Idconsulta'];
$IdDoctor = $_POST['IdDoctor'];
// $comentario = $_POST['comentario'];

// Calcular hora final
$seg_timeIni = strtotime($timeIni);
$seg_minutoAnadir = 30 * 60;
$timeFin = date("H:i", $seg_timeIni + $seg_minutoAnadir);

// Inicializar variable de validación
$existe = false;

// Validar existencia de cita con consulta preparada
$sqlValida = "SELECT * FROM AG_CITA 
              WHERE FECHA_CITA = ? 
              AND HORA_INICIO = ? 
              AND estado = 'A'";

$stmt_valida = $conexion->prepare($sqlValida);
if (!$stmt_valida) {
    die("Error al preparar la consulta de validación: " . $conexion->error);
}

$stmt_valida->bind_param("ss", $fechafactura, $timeIni);
$stmt_valida->execute();
$result_valida = $stmt_valida->get_result();

if ($result_valida->num_rows > 0) {
    $existe = false;
} else {
    $existe = true;
}

if ($existe) {
    // Insertar cita con consulta preparada
    $sql_insert = "INSERT INTO AG_CITA (IDPACIENTE, IDTIPOCONSULTA, IDDOCTOR, IDUSUARIO, FECHA_CITA, HORA_INICIO, HORA_FIN, ESTADO_CITA, ESTADO) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, 'Pendiente', 'A')";
    
    $stmt_insert = $conexion->prepare($sql_insert);
    if (!$stmt_insert) {
        die("Error al preparar la inserción: " . $conexion->error);
    }
    
    $stmt_insert->bind_param("sssssss", 
        $IdPaciente, 
        $Idconsulta, 
        $IdDoctor, 
        $_SESSION['iduser'], 
        $fechafactura, 
        $timeIni, 
        $timeFin, 
        // $comentario
    );
    
    if ($stmt_insert->execute()) {
        // Obtener correo del paciente con consulta preparada
        $correo_paciente = '';
        $sql_correo = "SELECT EMAIL FROM AG_PACIENTE WHERE IDPACIENTE = ?";
         $correo_paciente = 'jvaras@overclocking.com.ec';
        $stmt_correo = $conexion->prepare($sql_correo);
        if (!$stmt_correo) {
            die("Error al preparar consulta de correo: " . $conexion->error);
        }
        
        $stmt_correo->bind_param("s", $IdPaciente);
        $stmt_correo->execute();
        $result_correo = $stmt_correo->get_result();
        
        if ($result_correo && $result_correo->num_rows > 0) {
            $fila = $result_correo->fetch_assoc();
            $correo_paciente = $fila['EMAIL'];
        } else {
            $correo_paciente = 'jvaras@overclocking.com.ec'; // Correo por defecto
            error_log("No se encontró correo para IDPACIENTE: $IdPaciente");
        }

        // Configurar y enviar correo
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'mail.srossnutritions.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'citamedica@srossnutritions.com';
            $mail->Password = 'QVseUdgYE7TAGRF6bUQf';
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            $mail->setFrom('citamedica@srossnutritions.com', 'Sistema de Citas SROSS');
            $mail->addAddress($correo_paciente);
            
            $mail->Subject = 'Cita Programada - Sross Nutritions';
            $mail->Body = "Estimado paciente,\n\n"
                        . "Su cita ha sido programada con los siguientes detalles:\n"
                        . "Fecha: $fechafactura\n"
                        . "Hora de inicio: $timeIni\n"
                        . "Hora de fin: $timeFin\n\n"
                        // . "Comentarios: $comentario\n\n"
                        . "¡Gracias por confiar en nosotros!";

            $mail->send();
            echo 'Correo enviado correctamente!';
        } catch (Exception $e) {
            error_log("Error al enviar correo: " . $e->getMessage());
            echo "Error al enviar notificación: " . $e->getMessage();
        }

        // Redirección exitosa
        echo "<script>
                alert('Cita creada y notificación enviada correctamente');
                window.location.href = '../SCH_Calendar.php';
              </script>";
    } else {
        echo "Error al crear la cita: " . $stmt_insert->error;
    }
} else {
    echo "<script>
            alert('¡La cita ya existe en este horario!');
            window.location.href = '../Home.php';
          </script>";
}

// Cerrar conexiones
if (isset($stmt_valida)) $stmt_valida->close();
if (isset($stmt_insert)) $stmt_insert->close();
if (isset($stmt_correo)) $stmt_correo->close();
$conexion->close();
?>