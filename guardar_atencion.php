<?php
/**
 * guardar_atencion.php — Guarda el informe de una atención (AG_HISTORIAL)
 * y marca la cita como Atendida SOLO si el informe se guardó correctamente.
 *
 * Comportamiento importante:
 *   - Usa prepare/bind_param (informe puede ser grande, hasta LONGTEXT).
 *   - UPSERT por IDCITA: si ya existe un historial para esa cita, se
 *     actualiza en vez de insertar una fila nueva (evita duplicados).
 *   - Si el INSERT/UPDATE falla, NO marca la cita como Atendida y
 *     devuelve el error real al frontend para que muestre un aviso.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once("class/auditoria.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

if (!isset($_SESSION["rol"])) { echo 'SIN_SESION'; exit; }

$idCita  = isset($_POST['idCita'])  ? (int)$_POST['idCita']  : 0;
$informe = isset($_POST['informe']) ? (string)$_POST['informe'] : '';
$peso    = isset($_POST['peso'])    ? (float)$_POST['peso']   : 0.0;
$talla   = isset($_POST['talla'])   ? (float)$_POST['talla']  : 0.0;
$imc     = isset($_POST['imc'])     ? (float)$_POST['imc']    : 0.0;

if ($idCita <= 0 || trim(strip_tags($informe)) === '') {
    echo 'DATOS_INCOMPLETOS';
    exit;
}

// ¿Ya existe un historial para esta cita? — para hacer UPSERT y evitar duplicados
$chk = $conexion->prepare("SELECT IDHISTORIAL FROM AG_HISTORIAL WHERE IDCITA = ? LIMIT 1");
$chk->bind_param('i', $idCita);
$chk->execute();
$row = $chk->get_result()->fetch_assoc();
$chk->close();

if ($row && !empty($row['IDHISTORIAL'])) {
    // UPDATE del historial existente
    $idHist = (int)$row['IDHISTORIAL'];
    $stmt = $conexion->prepare(
        "UPDATE AG_HISTORIAL SET CONTENIDO_INFORME = ?, PESO = ?, TALLA = ?, IMC = ? WHERE IDHISTORIAL = ?"
    );
    // 's' para el informe (permite envíos grandes hasta max_allowed_packet)
    $stmt->bind_param('sdddi', $informe, $peso, $talla, $imc, $idHist);
} else {
    // INSERT nuevo
    $stmt = $conexion->prepare(
        "INSERT INTO AG_HISTORIAL (IDCITA, CONTENIDO_INFORME, PESO, TALLA, IMC) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('isddd', $idCita, $informe, $peso, $talla, $imc);
}

if (!$stmt->execute()) {
    // NO marcar la cita como Atendida si el informe no se pudo guardar
    $err = $stmt->error;
    $stmt->close();
    $conexion->close();
    // Log opcional para diagnosticar
    error_log('guardar_atencion.php - fallo al guardar informe idCita=' . $idCita . ' err=' . $err);
    echo 'ERROR: ' . $err;
    exit;
}
$stmt->close();

// ── Signos y laboratorio (opcional): glucosa, presión, lípidos ────────────
// Se guardan como números por cita para poder graficar su evolución por fecha.
@$conexion->query("CREATE TABLE IF NOT EXISTS atencion_signos (
    id_cita INT NOT NULL PRIMARY KEY,
    id_paciente INT NULL,
    glucosa DECIMAL(6,1) NULL, hba1c DECIMAL(4,1) NULL,
    pa_sistolica SMALLINT NULL, pa_diastolica SMALLINT NULL,
    col_total SMALLINT NULL, col_ldl SMALLINT NULL, col_hdl SMALLINT NULL, triglic SMALLINT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if (!function_exists('_sg_val')) {
    function _sg_val($k) {
        $v = $_POST[$k] ?? '';
        if (is_string($v)) { $v = trim($v); }
        if ($v === '' || $v === null || !is_numeric($v)) { return null; }
        return $v + 0;
    }
}
$idPac = isset($_POST['idPaciente']) ? (int)$_POST['idPaciente'] : 0;
$g = _sg_val('sg_glucosa'); $a = _sg_val('sg_hba1c');
$ps = _sg_val('sg_pa_sistolica'); $pd = _sg_val('sg_pa_diastolica');
$ct = _sg_val('sg_col_total'); $ld = _sg_val('sg_col_ldl'); $hd = _sg_val('sg_col_hdl'); $tg = _sg_val('sg_triglic');
// Solo guardar si llegó al menos un valor (no crear filas vacías).
if ($g !== null || $a !== null || $ps !== null || $pd !== null || $ct !== null || $ld !== null || $hd !== null || $tg !== null) {
    if ($sst = $conexion->prepare(
        "INSERT INTO atencion_signos (id_cita, id_paciente, glucosa, hba1c, pa_sistolica, pa_diastolica, col_total, col_ldl, col_hdl, triglic)
         VALUES (?,?,?,?,?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE id_paciente=VALUES(id_paciente), glucosa=VALUES(glucosa), hba1c=VALUES(hba1c),
            pa_sistolica=VALUES(pa_sistolica), pa_diastolica=VALUES(pa_diastolica),
            col_total=VALUES(col_total), col_ldl=VALUES(col_ldl), col_hdl=VALUES(col_hdl), triglic=VALUES(triglic), fecha=NOW()"
    )) {
        $sst->bind_param('iiddiiiiii', $idCita, $idPac, $g, $a, $ps, $pd, $ct, $ld, $hd, $tg);
        @$sst->execute();
        $sst->close();
    }
}

// Marcar la cita como Atendida (sólo llegamos aquí si el informe se guardó bien)
$updCita = $conexion->prepare("UPDATE AG_CITA SET ESTADO_CITA = 'A' WHERE IDCITA = ?");
$updCita->bind_param('i', $idCita);
if (!$updCita->execute()) {
    $err = $updCita->error;
    $updCita->close();
    $conexion->close();
    error_log('guardar_atencion.php - informe guardado pero fallo al marcar Atendida idCita=' . $idCita . ' err=' . $err);
    // El informe SÍ quedó guardado; sólo el marcado como Atendida falló.
    echo 'PARCIAL: ' . $err;
    exit;
}
$updCita->close();

auditar($conexion, 'Atención', 'atender', 'AG_CITA', $idCita, 'Guardó la nota de consulta y marcó la cita como Atendida.');

$conexion->close();

echo 'OK';
