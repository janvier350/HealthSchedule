<?php
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once("class/email_cita.php");
$conexion = conectarse();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

if (!isset($_SESSION["rol"])) {
    echo 'SIN_SESION';
    exit;
}

$idCita         = isset($_POST['idCita'])         ? (int)$_POST['idCita']         : 0;
$fecha          = isset($_POST['fecha'])          ? trim($_POST['fecha'])          : '';
$hora           = isset($_POST['hora'])           ? trim($_POST['hora'])           : '';
$idTipoConsulta = isset($_POST['idTipoConsulta']) ? (int)$_POST['idTipoConsulta'] : 0;
$idDoctor       = isset($_POST['idDoctor'])       ? (int)$_POST['idDoctor']       : 0;
$idAgencia      = isset($_POST['idAgencia'])      ? (int)$_POST['idAgencia']      : 0;

if (!$idCita || !$fecha || !$hora || !$idTipoConsulta || !$idDoctor) {
    echo 'DATOS_INCOMPLETOS';
    exit;
}

// Validar formato de fecha (YYYY-MM-DD) y hora (HH:MM, cualquier minuto)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !preg_match('/^\d{2}:\d{2}$/', $hora)) {
    echo 'FORMATO_INVALIDO';
    exit;
}

// Horario de atención: 07:00 a 22:30
if ($hora < '07:00' || $hora > '22:30') {
    echo 'HORA_FUERA_RANGO';
    exit;
}

// Hora fin = 30 minutos después
$horaFin = date('H:i', strtotime($hora) + 30 * 60);

// Verificar que la cita no se solape con otra cita activa del mismo día
// (excluyendo esta misma cita). Al permitir minutos libres ya no basta
// comparar la hora exacta: dos citas chocan si sus rangos se cruzan.
$stmt_valida = $conexion->prepare(
    "SELECT IDCITA FROM AG_CITA
     WHERE FECHA_CITA = ? AND ESTADO = 'A'
       AND ESTADO_CITA NOT IN ('Cancelada','Cancelado')
       AND IDCITA <> ?
       AND TIME(HORA_INICIO) < TIME(?)
       AND TIME(HORA_FIN)    > TIME(?)"
);
$stmt_valida->bind_param("siss", $fecha, $idCita, $horaFin, $hora);
$stmt_valida->execute();
$stmt_valida->store_result();

if ($stmt_valida->num_rows > 0) {
    $stmt_valida->close();
    echo 'HORARIO_OCUPADO';
    exit;
}
$stmt_valida->close();

// Leer la fecha/hora actuales ANTES de actualizar, para saber si cambian
// (solo se notifica al paciente cuando cambia la fecha o la hora).
$stmt_old = $conexion->prepare(
    "SELECT FECHA_CITA, HORA_INICIO FROM AG_CITA WHERE IDCITA = ? AND ESTADO = 'A'"
);
$stmt_old->bind_param("i", $idCita);
$stmt_old->execute();
$citaVieja = $stmt_old->get_result()->fetch_assoc();
$stmt_old->close();

if (!$citaVieja) {
    echo 'NO_ENCONTRADA';
    exit;
}

$fechaAnterior = $citaVieja['FECHA_CITA'];
$horaAnterior  = substr($citaVieja['HORA_INICIO'] ?? '', 0, 5);
$cambioFechaHora = ($fechaAnterior !== $fecha) || ($horaAnterior !== $hora);

// Editar = corregir datos de la cita; a diferencia de reagendar,
// el ESTADO_CITA se mantiene tal como está.
$stmt = $conexion->prepare(
    "UPDATE AG_CITA
     SET FECHA_CITA     = ?,
         HORA_INICIO    = ?,
         HORA_FIN       = ?,
         IDTIPOCONSULTA = ?,
         IDDOCTOR       = ?,
         IDAGENCIA      = NULLIF(?, 0)
     WHERE IDCITA = ? AND ESTADO = 'A'"
);
$stmt->bind_param("sssiiii", $fecha, $hora, $horaFin, $idTipoConsulta, $idDoctor, $idAgencia, $idCita);

if (!$stmt->execute()) {
    echo 'ERROR: ' . $stmt->error;
    $stmt->close();
    exit;
}
$stmt->close();

// Si NO cambió la fecha ni la hora, es una corrección interna: no se notifica.
if (!$cambioFechaHora) {
    echo 'OK';
    $conexion->close();
    exit;
}

// ── Cambió fecha u hora: notificar al paciente ──────────────────────
$stmt_info = $conexion->prepare(
    "SELECT P.IDPACIENTE, P.NOMBRES, P.APELLIDOS, P.EMAIL,
            TC.NOMBRES AS TIPO_CONSULTA,
            CONCAT(D.NOMBRES,' ',D.APELLIDOS) AS DOCTOR
     FROM AG_CITA A
     INNER JOIN AG_PACIENTE P      ON A.IDPACIENTE     = P.IDPACIENTE
     LEFT  JOIN AG_TIPOCONSULTA TC ON A.IDTIPOCONSULTA = TC.IDTIPOCONSULTA
     LEFT  JOIN ADM_USUARIO D      ON A.IDDOCTOR       = D.IDADM_USUARIO
     WHERE A.IDCITA = ?"
);
$stmt_info->bind_param("i", $idCita);
$stmt_info->execute();
$info = $stmt_info->get_result()->fetch_assoc();
$stmt_info->close();

$nombrePaciente = trim(($info['NOMBRES'] ?? '') . ' ' . ($info['APELLIDOS'] ?? ''));
$correoPaciente = $info['EMAIL'] ?? '';
$tipoConsulta   = $info['TIPO_CONSULTA'] ?? 'Consulta';
$doctorNombre   = $info['DOCTOR'] ?? '';

// Sin correo registrado: la cita ya se actualizó, solo avisamos
if (!$correoPaciente) {
    echo 'OK_SIN_CORREO';
    $conexion->close();
    exit;
}

$langPaciente = patient_lang($conexion, (int)($info['IDPACIENTE'] ?? 0));
$emailCita = build_cita_email('modificada', $langPaciente, [
    'nombre'        => $nombrePaciente,
    'fecha'         => $fecha,
    'hora'          => $hora,
    'horaFin'       => $horaFin ?? '',
    'tipoConsulta'  => $tipoConsulta,
    'doctorNombre'  => $doctorNombre,
    'fechaAnterior' => $fechaAnterior,
    'horaAnterior'  => $horaAnterior,
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

    echo 'OK_MODIFICADA';
} catch (Exception $e) {
    // La cita YA fue actualizada; solo el correo falló
    error_log("PHPMailer error (modificación) para $correoPaciente: " . $mail->ErrorInfo);
    echo 'OK_SIN_CORREO';
}

$conexion->close();
