<?php
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
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

$idCita = isset($_POST['idCita']) ? (int)$_POST['idCita'] : 0;
$fecha  = isset($_POST['fecha'])  ? trim($_POST['fecha'])  : '';
$hora   = isset($_POST['hora'])   ? trim($_POST['hora'])   : '';

if (!$idCita || !$fecha || !$hora) {
    echo 'DATOS_INCOMPLETOS';
    exit;
}

// Validar formato de fecha (YYYY-MM-DD) y hora (HH:MM)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !preg_match('/^\d{2}:\d{2}$/', $hora)) {
    echo 'FORMATO_INVALIDO';
    exit;
}

// Hora fin = 30 minutos después
$horaFin = date('H:i', strtotime($hora) + 30 * 60);

// Leer la fecha/hora actuales ANTES de reagendar, para incluir la anterior en el correo
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

// Reagendar: nueva fecha/hora y estado vuelve a Pendiente
$stmt = $conexion->prepare(
    "UPDATE AG_CITA
     SET FECHA_CITA  = ?,
         HORA_INICIO = ?,
         HORA_FIN    = ?,
         ESTADO_CITA = 'Pendiente'
     WHERE IDCITA = ? AND ESTADO = 'A'"
);
$stmt->bind_param("sssi", $fecha, $hora, $horaFin, $idCita);

if (!$stmt->execute()) {
    echo 'ERROR: ' . $stmt->error;
    $stmt->close();
    exit;
}
$stmt->close();

// ── Notificar al paciente del reagendamiento ────────────────────────
$stmt_info = $conexion->prepare(
    "SELECT P.NOMBRES, P.APELLIDOS, P.EMAIL,
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

// Sin correo registrado: la cita ya se reagendó, solo avisamos
if (!$correoPaciente) {
    echo 'OK_SIN_CORREO';
    $conexion->close();
    exit;
}

// Formatear fechas en español
$diasES  = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
$mesesES = ['','enero','febrero','marzo','abril','mayo','junio',
            'julio','agosto','septiembre','octubre','noviembre','diciembre'];
$formatearFecha = function ($f) use ($diasES, $mesesES) {
    $o = DateTime::createFromFormat('Y-m-d', $f);
    if (!$o) return $f;
    return $diasES[(int)$o->format('w')] . ', ' .
           (int)$o->format('j') . ' de ' .
           $mesesES[(int)$o->format('n')] . ' de ' .
           $o->format('Y');
};
$fechaNuevaBonita    = $formatearFecha($fecha);
$fechaAnteriorBonita = $formatearFecha($fechaAnterior);

$htmlBody = "
<!DOCTYPE html>
<html lang='es'>
<head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;background:#f4f6f9;font-family:Arial,sans-serif;'>
  <table width='100%' cellpadding='0' cellspacing='0' style='background:#f4f6f9;padding:30px 0;'>
    <tr><td align='center'>
      <table width='580' cellpadding='0' cellspacing='0' style='background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);'>

        <!-- Encabezado -->
        <tr>
          <td style='background:#0f766e;padding:28px 32px;text-align:center;'>
            <h1 style='color:#ffffff;margin:0;font-size:22px;'>Cita Reagendada</h1>
            <p style='color:#c7efe8;margin:6px 0 0;font-size:13px;'>Sross Nutritions</p>
          </td>
        </tr>

        <!-- Saludo -->
        <tr>
          <td style='padding:28px 32px 10px;'>
            <p style='font-size:15px;color:#333;margin:0;'>
              Hola, <strong>" . htmlspecialchars($nombrePaciente) . "</strong>
            </p>
            <p style='font-size:14px;color:#555;margin:10px 0 0;'>
              Le informamos que su cita ha sido <strong>reagendada</strong>. Estos son los nuevos datos:
            </p>
          </td>
        </tr>

        <!-- Detalles nuevos -->
        <tr>
          <td style='padding:16px 32px;'>
            <table width='100%' cellpadding='0' cellspacing='0'
                   style='background:#e6f5f2;border-radius:6px;border-left:4px solid #0f766e;'>
              <tr>
                <td style='padding:16px 20px;'>
                  <table width='100%' cellpadding='6' cellspacing='0' style='font-size:14px;color:#333;'>
                    <tr>
                      <td style='width:40%;color:#888;'>📅 Nueva fecha</td>
                      <td><strong>" . htmlspecialchars($fechaNuevaBonita) . "</strong></td>
                    </tr>
                    <tr>
                      <td style='color:#888;'>🕐 Nueva hora</td>
                      <td><strong>" . htmlspecialchars($hora) . " – " . htmlspecialchars($horaFin) . "</strong></td>
                    </tr>
                    <tr>
                      <td style='color:#888;'>🩺 Tipo de consulta</td>
                      <td><strong>" . htmlspecialchars($tipoConsulta) . "</strong></td>
                    </tr>
                    " . ($doctorNombre ? "
                    <tr>
                      <td style='color:#888;'>👨‍⚕️ Doctor</td>
                      <td><strong>" . htmlspecialchars($doctorNombre) . "</strong></td>
                    </tr>" : "") . "
                  </table>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- Fecha anterior -->
        <tr>
          <td style='padding:0 32px 8px;'>
            <p style='font-size:13px;color:#999;margin:0;'>
              Fecha anterior: <span style='text-decoration:line-through;'>" . htmlspecialchars($fechaAnteriorBonita) . " a las " . htmlspecialchars($horaAnterior) . "</span>
            </p>
          </td>
        </tr>

        <!-- Recordatorio -->
        <tr>
          <td style='padding:10px 32px 24px;'>
            <p style='font-size:13px;color:#777;margin:0;'>
              Si esta nueva fecha no le conviene, comuníquese con nosotros para coordinar otra.
            </p>
          </td>
        </tr>

        <!-- Pie -->
        <tr>
          <td style='background:#e0f0ed;padding:16px 32px;text-align:center;'>
            <p style='margin:0;font-size:12px;color:#999;'>
              Este correo fue generado automáticamente por el Sistema de Citas Sross Nutritions.<br>
              Por favor no responda a este mensaje.
            </p>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>";

$textBody = "Hola $nombrePaciente,\n\n"
          . "Le informamos que su cita ha sido REAGENDADA. Nuevos datos:\n"
          . "Fecha: $fechaNuevaBonita\n"
          . "Hora: $hora - $horaFin\n"
          . "Tipo: $tipoConsulta\n"
          . ($doctorNombre ? "Doctor: $doctorNombre\n" : "")
          . "\n(Fecha anterior: $fechaAnteriorBonita a las $horaAnterior)\n"
          . "\nSross Nutritions";

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

    $mail->setFrom('citamedica@srossnutritions.com', 'Sistema de Citas SROSS');
    $mail->addAddress($correoPaciente, $nombrePaciente);
    $mail->addReplyTo('citamedica@srossnutritions.com', 'Sross Nutritions');

    $mail->isHTML(true);
    $mail->Subject  = '=?UTF-8?B?' . base64_encode('Cita Reagendada - Sross Nutritions') . '?=';
    $mail->Body     = $htmlBody;
    $mail->AltBody  = $textBody;

    $mail->send();

    echo 'OK';
} catch (Exception $e) {
    // La cita YA fue reagendada; solo el correo falló
    error_log("PHPMailer error (reagenda) para $correoPaciente: " . $mail->ErrorInfo);
    echo 'OK_SIN_CORREO';
}

$conexion->close();
