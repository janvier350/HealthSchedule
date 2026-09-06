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

// Recurrencia (opcional)
$recurrencia   = strtolower(trim($_POST['recurrencia']   ?? 'none'));
$recurEvery    = (int)($_POST['recurEvery']              ?? 1);
$recurEndMode  = strtolower(trim($_POST['recurEndMode']  ?? 'count'));
$recurCount    = (int)($_POST['recurCount']              ?? 1);
$recurEndDate  = trim($_POST['recurEndDate']             ?? '');

if (!$fechafactura || !$IdPaciente || !$timeIni || !$Idconsulta || !$IdDoctor) {
    echo "<script>alert('Datos incompletos. Por favor complete todos los campos.'); history.back();</script>";
    exit;
}

// Calcular hora final (+30 min)
$timeFin = date("H:i", strtotime($timeIni) + 30 * 60);

/**
 * Genera todas las fechas de la serie (incluida la primera).
 * Cap de seguridad: hasta 52 ocurrencias.
 */
function generarFechasSerie($primera, $patron, $intervaloCustom, $modo, $cuenta, $fechaFin) {
    $fechas   = [];
    $baseDT   = DateTime::createFromFormat('Y-m-d', $primera);
    if (!$baseDT) return [$primera];
    $limite   = 52;
    $endDT    = $fechaFin ? DateTime::createFromFormat('Y-m-d', $fechaFin) : null;
    if ($modo === 'count') {
        $cuenta = max(1, min($cuenta, $limite));
    } else {
        $cuenta = $limite; // hasta la fecha (con cap)
    }
    $step = null;
    switch ($patron) {
        case 'weekly':   $step = new DateInterval('P7D');  break;
        case 'biweekly': $step = new DateInterval('P14D'); break;
        case 'monthly':  $step = new DateInterval('P1M');  break;
        case 'daily':    $step = new DateInterval('P1D');  break;
        case 'custom':
            $n = max(1, min((int)$intervaloCustom, 90));
            $step = new DateInterval('P' . $n . 'D');
            break;
        default:
            return [$primera];
    }
    $cur = clone $baseDT;
    for ($i = 0; $i < $cuenta; $i++) {
        if ($endDT && $cur > $endDT) break;
        $fechas[] = $cur->format('Y-m-d');
        $cur->add($step);
    }
    return $fechas;
}

if ($recurrencia === 'none' || $recurrencia === '') {
    $fechasSerie = [$fechafactura];
} else {
    $fechasSerie = generarFechasSerie($fechafactura, $recurrencia, $recurEvery, $recurEndMode, $recurCount, $recurEndDate);
}

// Inserta cada fecha; salta las que choquen con otra cita activa.
$idUsuario   = $_SESSION['iduser'] ?? 0;
$stmt_valida = $conexion->prepare(
    "SELECT IDCITA FROM AG_CITA
     WHERE FECHA_CITA = ? AND HORA_INICIO = ? AND ESTADO = 'A'
     AND ESTADO_CITA NOT IN ('Cancelada','Cancelado')"
);
$stmt_insert = $conexion->prepare(
    "INSERT INTO AG_CITA (IDPACIENTE, IDTIPOCONSULTA, IDDOCTOR, IDUSUARIO,
                          FECHA_CITA, HORA_INICIO, HORA_FIN, ESTADO_CITA, ESTADO, COMENTARIO)
     VALUES (?, ?, ?, ?, ?, ?, ?, 'Pendiente', 'A', '')"
);

$primeraIdCita     = 0;
$primeraFechaOK    = '';
$creadas           = 0;
$saltadasPorChoque = [];

foreach ($fechasSerie as $f) {
    // ¿ya hay cita en ese día y hora?
    $stmt_valida->bind_param("ss", $f, $timeIni);
    $stmt_valida->execute();
    $stmt_valida->store_result();
    if ($stmt_valida->num_rows > 0) {
        $saltadasPorChoque[] = $f;
        $stmt_valida->free_result();
        continue;
    }
    $stmt_valida->free_result();

    $stmt_insert->bind_param("iiiisss",
        $IdPaciente, $Idconsulta, $IdDoctor, $idUsuario,
        $f, $timeIni, $timeFin
    );
    if (!$stmt_insert->execute()) {
        continue;
    }
    $creadas++;
    if ($primeraIdCita === 0) {
        $primeraIdCita  = $conexion->insert_id;
        $primeraFechaOK = $f;
    }
}
$stmt_valida->close();
$stmt_insert->close();

if ($creadas === 0) {
    echo "<script>alert('No se pudo crear ninguna cita: todas chocan con horarios ya ocupados.'); window.location.href = '../SCH_Calendar.php';</script>";
    exit;
}

// Si fue una serie con más de una cita creada, marca todas con IDSERIE = primeraIdCita.
$tieneSerie = false;
$dbNameQ = $conexion->query("SELECT DATABASE() AS db");
$dbName  = $dbNameQ ? $dbNameQ->fetch_assoc()['db'] : '';
if ($dbName) {
    $colExiste = (int)$conexion->query(
        "SELECT COUNT(*) c FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA='$dbName' AND TABLE_NAME='AG_CITA' AND COLUMN_NAME='IDSERIE'"
    )->fetch_assoc()['c'] > 0;
    if ($colExiste && $creadas > 1) {
        // Los últimos $creadas IDs son secuenciales desde $primeraIdCita
        $ultimoIdCita = $primeraIdCita + $creadas - 1;
        $stmtSerie = $conexion->prepare(
            "UPDATE AG_CITA SET IDSERIE = ?
             WHERE IDCITA BETWEEN ? AND ?
               AND IDPACIENTE = ? AND HORA_INICIO = ?"
        );
        $stmtSerie->bind_param("iiiis", $primeraIdCita, $primeraIdCita, $ultimoIdCita, $IdPaciente, $timeIni);
        $stmtSerie->execute();
        $stmtSerie->close();
        $tieneSerie = true;
    }
}

// El correo se enviará solo por la PRIMERA cita creada.
$fechafactura = $primeraFechaOK;

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

// Resumen de la serie (si aplica)
$resumen = '';
if ($creadas > 1) {
    $resumen = "\\nSerie recurrente: {$creadas} citas creadas.";
}
if (!empty($saltadasPorChoque)) {
    $resumen .= "\\nSaltadas por conflicto de horario: " . count($saltadasPorChoque);
}

// ── Enviar correo ───────────────────────────────────────────────────
if (!$correoPaciente) {
    // Sin correo registrado: redirigir sin enviar
    echo "<script>alert('Cita creada. El paciente no tiene correo registrado.$resumen'); window.location.href = '../SCH_Calendar.php';</script>";
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
        alert('Cita creada y notificación enviada a $correoPaciente$resumen');
        window.location.href = '../SCH_Calendar.php';
    </script>";

} catch (Exception $e) {
    // La cita YA fue guardada; solo el correo falló
    error_log("PHPMailer error para $correoPaciente: " . $e->getMessage());
    echo "<script>
        alert('Cita creada correctamente.\\nNota: no se pudo enviar el correo de confirmación.$resumen');
        window.location.href = '../SCH_Calendar.php';
    </script>";
}

$conexion->close();
?>
