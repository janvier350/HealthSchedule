<?php
/**
 * plan_peso_listar.php — Devuelve (HTML) los planes de peso guardados de un paciente.
 * Uso: plan_peso_listar.php?id_paciente=123
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

if (!isset($_SESSION["rol"], $_SESSION["iduser"])) {
    echo '<div class="text-danger small">Sesión expirada.</div>';
    exit;
}

$idPaciente = (int)($_GET['id_paciente'] ?? 0);
if (!$idPaciente) {
    echo '<div class="text-muted small">Selecciona un paciente para ver sus planes guardados.</div>';
    exit;
}

// ¿Existe la tabla? (la crea migrar_plan_peso.php)
$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$tablaExiste = (int)$conexion->query(
    "SELECT COUNT(*) c FROM information_schema.TABLES
     WHERE TABLE_SCHEMA='$dbName' AND TABLE_NAME='AG_PLAN_PESO'"
)->fetch_assoc()['c'] > 0;
if (!$tablaExiste) {
    echo '<div class="alert alert-warning py-2 small mb-0">Aún no se ha creado la tabla de planes. '
       . 'Pide al administrador ejecutar <code>migrar_plan_peso.php</code> una vez.</div>';
    exit;
}

$LB_PER_KG = 2.2;

$stmt = $conexion->prepare(
    "SELECT P.IDPLAN, P.UNIDADES, P.PESO_INICIAL, P.PESO_META, P.FECHA_META,
            P.CAL_MANTENER_ACTUAL, P.CAL_ALCANZAR, P.CAL_MANTENER_META,
            P.FECHA_REGISTRO, U.NOMBRES AS UN, U.APELLIDOS AS UA
     FROM AG_PLAN_PESO P
     LEFT JOIN ADM_USUARIO U ON P.IDADM_USUARIO = U.IDADM_USUARIO
     WHERE P.IDPACIENTE = ? AND P.ESTADO = 'A'
     ORDER BY P.FECHA_REGISTRO DESC, P.IDPLAN DESC"
);
$stmt->bind_param("i", $idPaciente);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows === 0) {
    echo '<div class="text-muted small">Este paciente no tiene planes guardados todavía.</div>';
    exit;
}

function fmtPeso($kg, $unidades, $lbPerKg) {
    if ($kg === null || $kg === '') return '—';
    if ($unidades === 'us') {
        return number_format((float)$kg * $lbPerKg, 1) . ' lbs';
    }
    return number_format((float)$kg, 1) . ' kg';
}
function fmtFecha($f) {
    if (!$f || $f === '0000-00-00') return '—';
    $ts = strtotime($f);
    return $ts ? date('d/m/Y', $ts) : htmlspecialchars($f);
}
?>
<div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0" style="font-size:.85rem;">
        <thead class="table-light">
            <tr>
                <th>Registrado</th>
                <th>Peso inicial → meta</th>
                <th>Fecha meta</th>
                <th class="text-end">Mantener actual</th>
                <th class="text-end">Alcanzar meta</th>
                <th class="text-end">Mantener meta</th>
                <th>Registró</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php while ($p = $res->fetch_assoc()):
            $u = $p['UNIDADES'] ?: 'us';
            $registrador = trim(($p['UN'] ?? '') . ' ' . ($p['UA'] ?? ''));
        ?>
            <tr>
                <td><?php echo fmtFecha($p['FECHA_REGISTRO']); ?><br>
                    <small class="text-muted"><?php echo $p['FECHA_REGISTRO'] ? date('H:i', strtotime($p['FECHA_REGISTRO'])) : ''; ?></small>
                </td>
                <td><?php echo fmtPeso($p['PESO_INICIAL'], $u, $LB_PER_KG); ?>
                    <i class="bi bi-arrow-right mx-1"></i>
                    <strong><?php echo fmtPeso($p['PESO_META'], $u, $LB_PER_KG); ?></strong>
                </td>
                <td><?php echo fmtFecha($p['FECHA_META']); ?></td>
                <td class="text-end"><?php echo number_format((int)$p['CAL_MANTENER_ACTUAL']); ?></td>
                <td class="text-end"><?php echo number_format((int)$p['CAL_ALCANZAR']); ?></td>
                <td class="text-end"><?php echo number_format((int)$p['CAL_MANTENER_META']); ?></td>
                <td><small><?php echo htmlspecialchars($registrador !== '' ? $registrador : '—'); ?></small></td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1"
                            title="Eliminar plan"
                            onclick="eliminarPlanPeso(<?php echo (int)$p['IDPLAN']; ?>)">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php
$stmt->close();
$conexion->close();
