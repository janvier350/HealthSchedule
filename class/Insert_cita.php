<?php
require_once("funciones.php");
require_once("conexionBD.php");
require_once("email_cita.php");
$conexion = conectarse();
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../PHPMailer/src/Exception.php';
require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';

// Recoger y sanitizar datos del formulario
$fechafactura = $conexion->real_escape_string($_POST['fechafactura'] ?? '');
$IdPaciente   = (int)($_POST['IdPaciente'] ?? 0);
$timeIni      = $conexion->real_escape_string($_POST['timeIni']      ?? '');
$Idconsulta   = (int)($_POST['Idconsulta'] ?? 0);
$IdDoctor     = (int)($_POST['IdDoctor']   ?? 0);

if (!$fechafactura || !$IdPaciente || !$timeIni || !$Idconsulta || !$IdDoctor) {
    echo "<script>alert('Datos incompletos. Por favor complete todos los campos.'); history.back();</script>";
    exit;
}

// Calcular hora final (+30 min)
$timeFin = date("H:i", strtotime($timeIni) + 30 * 60);

// Verificar si el horario ya está ocupado (excluye canceladas)
$stmt_valida = $conexion->prepare(
    "SELECT IDCITA FROM AG_CITA
     WHERE FECHA_CITA = ? AND HORA_INICIO = ? AND ESTADO = 'A'
     AND ESTADO_CITA NOT IN ('Cancelada','Cancelado')"
);
$stmt_valida->bind_param("ss", $fechafactura, $timeIni);
$stmt_valida->execute();
$stmt_valida->store_result();

if ($stmt_valida->num_rows > 0) {
    $stmt_valida->close();
    echo "<script>alert('¡Ya existe una cita en ese horario!'); window.location.href = '../SCH_Calendar.php';</script>";
    exit;
}
$stmt_valida->close();

// Insertar la cita
$idUsuario = $_SESSION['iduser'] ?? 0;
$stmt_insert = $conexion->prepare(
    "INSERT INTO AG_CITA (IDPACIENTE, IDTIPOCONSULTA, IDDOCTOR, IDUSUARIO,
                          FECHA_CITA, HORA_INICIO, HORA_FIN, ESTADO_CITA, ESTADO, COMENTARIO)
     VALUES (?, ?, ?, ?, ?, ?, ?, 'Pendiente', 'A', '')"
);
$stmt_insert->bind_param("iiiisss",
    $IdPaciente, $Idconsulta, $IdDoctor, $idUsuario,
    $fechafactura, $timeIni, $timeFin
);

if (!$stmt_insert->execute()) {
    echo "<script>alert('Error al crear la cita: " . addslashes($stmt_insert->error) . "'); history.back();</script>";
    $stmt_insert->close();
    exit;
}
$stmt_insert->close();

// Obtener datos del paciente (nombre + correo) y tipo de consulta
$stmt_info = $conexion->prepare(
    "SELECT P.NOMBRES, P.APELLIDOS, P.EMAIL,
            TC.NOMBRES AS TIPO_CONSULTA,
            CONCAT(D.NOMBRES,' ',D.APELLIDOS) AS DOCTOR
     FROM AG_PACIENTE P
     LEFT JOIN AG_TIPOCONSULTA TC ON TC.IDTIPOCONSULTA = ?
     LEFT JOIN ADM_DOCTOR D       ON D.IDDOCTOR        = ?
     WHERE P.IDPACIENTE = ?"
);
$stmt_info->bind_param("iii", $Idconsulta, $IdDoctor, $IdPaciente);
$stmt_info->execute();
$info = $stmt_info->get_result()->fetch_assoc();
$stmt_info->close();

$nombrePaciente = trim(($info['NOMBRES'] ?? '') . ' ' . ($info['APELLIDOS'] ?? ''));
$correoPaciente = $info['EMAIL'] ?? '';
$tipoConsulta   = $info['TIPO_CONSULTA'] ?? 'Consulta';
$doctorNombre   = $info['DOCTOR'] ?? '';

// Formatear fecha en español
$fechaObj  = DateTime::createFromFormat('Y-m-d', $fechafactura);
$diasES    = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
$mesesES   = ['','enero','febrero','marzo','abril','mayo','junio',
              'julio','agosto','septiembre','octubre','noviembre','diciembre'];
$fechaBonita = $diasES[(int)$fechaObj->format('w')] . ', ' .
               (int)$fechaObj->format('j') . ' de ' .
               $mesesES[(int)$fechaObj->format('n')] . ' de ' .
               $fechaObj->format('Y');

// ── Enviar correo ───────────────────────────────────────────────────
if (!$correoPaciente) {
    // Sin correo registrado: redirigir sin enviar
    echo "<script>alert('Cita creada. El paciente no tiene correo registrado.'); window.location.href = '../SCH_Calendar.php';</script>";
    exit;
}

$langPaciente = patient_lang($conexion, (int)$IdPaciente);
$emailCita = build_cita_email('programada', $langPaciente, [
    'nombre'       => $nombrePaciente,
    'fecha'        => $fechafactura,
    'hora'         => $timeIni,
    'horaFin'      => $timeFin,
    'tipoConsulta' => $tipoConsulta,
    'doctorNombre' => $doctorNombre,
]);
$htmlBody = $emailCita['html'];
$textBody = $emailCita['text'];

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'mail.srossnutritions.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'citamedica@srossnutritions.com';
    $mail->Password   = 'QVseUdgYE7TAGRF6bUQf';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    $mail->CharSet    = 'UTF-8';
    $mail->Encoding   = 'base64';

    // Permite certificados SSL auto-firmados en hosting compartido
    $mail->SMTPOptions = ['ssl' => [
        'verify_peer'       => false,
        'verify_peer_name'  => false,
        'allow_self_signed' => true,
    ]];

    $mail->setFrom('citamedica@srossnutritions.com', $emailCita['fromName']);
    $mail->addAddress($correoPaciente, $nombrePaciente);
    $mail->addReplyTo('citamedica@srossnutritions.com', $emailCita['replyName']);

    $mail->isHTML(true);
    $mail->Subject  = '=?UTF-8?B?' . base64_encode($emailCita['subject']) . '?=';
    $mail->Body     = $htmlBody;
    $mail->AltBody  = $textBody;

    $mail->send();

    echo "<script>
        alert('Cita creada y notificación enviada a $correoPaciente');
        window.location.href = '../SCH_Calendar.php';
    </script>";

} catch (Exception $e) {
    // La cita YA fue guardada; solo el correo falló
    error_log("PHPMailer error para $correoPaciente: " . $e->getMessage());
    echo "<script>
        alert('Cita creada correctamente.\\nNota: no se pudo enviar el correo de confirmación.');
        window.location.href = '../SCH_Calendar.php';
    </script>";
}

$conexion->close();
?>
