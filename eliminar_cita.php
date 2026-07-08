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

if (!$idCita) {
    echo 'DATOS_INCOMPLETOS';
    exit;
}

// Obtener los datos de la cita ANTES de eliminarla (para el correo de cancelación)
$stmt_info = $conexion->prepare(
    "SELECT P.NOMBRES, P.APELLIDOS, P.EMAIL,
            A.FECHA_CITA, A.HORA_INICIO, A.HORA_FIN,
            TC.NOMBRES AS TIPO_CONSULTA,
            CONCAT(D.NOMBRES,' ',D.APELLIDOS) AS DOCTOR
     FROM AG_CITA A
     INNER JOIN AG_PACIENTE P      ON A.IDPACIENTE     = P.IDPACIENTE
     LEFT  JOIN AG_TIPOCONSULTA TC ON A.IDTIPOCONSULTA = TC.IDTIPOCONSULTA
     LEFT  JOIN ADM_USUARIO D      ON A.IDDOCTOR       = D.IDADM_USUARIO
     WHERE A.IDCITA = ? AND A.ESTADO = 'A'"
);
$stmt_info->bind_param("i", $idCita);
$stmt_info->execute();
$info = $stmt_info->get_result()->fetch_assoc();
$stmt_info->close();

if (!$info) {
    echo 'NO_ENCONTRADA';
    exit;
}

// Eliminar la cita (borrado lógico: sale del calendario)
$stmt = $conexion->prepare(
    "UPDATE AG_CITA SET ESTADO = 'I', ESTADO_CITA = 'Cancelado'
     WHERE IDCITA = ? AND ESTADO = 'A'"
);
$stmt->bind_param("i", $idCita);

if (!$stmt->execute()) {
    echo 'ERROR: ' . $stmt->error;
    $stmt->close();
    exit;
}
$stmt->close();

// ── Preparar y enviar correo de cancelación ─────────────────────────
$nombrePaciente = trim(($info['NOMBRES'] ?? '') . ' ' . ($info['APELLIDOS'] ?? ''));
$correoPaciente = $info['EMAIL'] ?? '';
$tipoConsulta   = $info['TIPO_CONSULTA'] ?? 'Consulta';
$doctorNombre   = $info['DOCTOR'] ?? '';
$horaIni        = substr($info['HORA_INICIO'] ?? '', 0, 5);
$horaFin        = substr($info['HORA_FIN'] ?? '', 0, 5);

// Formatear fecha en español
$fechaObj  = DateTime::createFromFormat('Y-m-d', $info['FECHA_CITA']);
$diasES    = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
$mesesES   = ['','enero','febrero','marzo','abril','mayo','junio',
              'julio','agosto','septiembre','octubre','noviembre','diciembre'];
$fechaBonita = $fechaObj
    ? $diasES[(int)$fechaObj->format('w')] . ', ' .
      (int)$fechaObj->format('j') . ' de ' .
      $mesesES[(int)$fechaObj->format('n')] . ' de ' .
      $fechaObj->format('Y')
    : $info['FECHA_CITA'];

// Sin correo registrado: la cita ya se eliminó, solo avisamos
if (!$correoPaciente) {
    echo 'OK_SIN_CORREO';
    exit;
}

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
          <td style='background:#b02a37;padding:28px 32px;text-align:center;'>
            <h1 style='color:#ffffff;margin:0;font-size:22px;'>Cita Cancelada</h1>
            <p style='color:#f5d0d4;margin:6px 0 0;font-size:13px;'>Sross Nutritions</p>
          </td>
        </tr>

        <!-- Saludo -->
        <tr>
          <td style='padding:28px 32px 10px;'>
            <p style='font-size:15px;color:#333;margin:0;'>
              Hola, <strong>" . htmlspecialchars($nombrePaciente) . "</strong>
            </p>
            <p style='font-size:14px;color:#555;margin:10px 0 0;'>
              Le informamos que la siguiente cita ha sido <strong>cancelada</strong>:
            </p>
          </td>
        </tr>

        <!-- Detalles -->
        <tr>
          <td style='padding:16px 32px;'>
            <table width='100%' cellpadding='0' cellspacing='0'
                   style='background:#fdf2f3;border-radius:6px;border-left:4px solid #b02a37;'>
              <tr>
                <td style='padding:16px 20px;'>
                  <table width='100%' cellpadding='6' cellspacing='0' style='font-size:14px;color:#333;'>
                    <tr>
                      <td style='width:40%;color:#888;'>📅 Fecha</td>
                      <td><strong>" . htmlspecialchars($fechaBonita) . "</strong></td>
                    </tr>
                    <tr>
                      <td style='color:#888;'>🕐 Hora</td>
                      <td><strong>" . htmlspecialchars($horaIni) . " – " . htmlspecialchars($horaFin) . "</strong></td>
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

        <!-- Recordatorio -->
        <tr>
          <td style='padding:10px 32px 24px;'>
            <p style='font-size:13px;color:#777;margin:0;'>
              Si desea reagendar una nueva cita, comuníquese con nosotros. Lamentamos cualquier inconveniente.
            </p>
          </td>
        </tr>

        <!-- Pie -->
        <tr>
          <td style='background:#f7e9ea;padding:16px 32px;text-align:center;'>
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
          . "Le informamos que su cita ha sido CANCELADA:\n"
          . "Fecha: $fechaBonita\n"
          . "Hora: $horaIni - $horaFin\n"
          . "Tipo: $tipoConsulta\n"
          . ($doctorNombre ? "Doctor: $doctorNombre\n" : "")
          . "\nSi desea reagendar, comuníquese con nosotros.\nSross Nutritions";

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
    $mail->Subject  = '=?UTF-8?B?' . base64_encode('Cita Cancelada - Sross Nutritions') . '?=';
    $mail->Body     = $htmlBody;
    $mail->AltBody  = $textBody;

    $mail->send();

    echo 'OK';
} catch (Exception $e) {
    // La cita YA fue eliminada; solo el correo falló
    error_log("PHPMailer error (cancelación) para $correoPaciente: " . $mail->ErrorInfo);
    echo 'OK_SIN_CORREO';
}

$conexion->close();
