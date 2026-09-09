<?php
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/lang/i18n.php");
$conexion = conectarse();

if (!isset($_SESSION["rol"])) { http_response_code(403); exit; }

$idPaciente = (int)($_GET['id'] ?? 0);
if (!$idPaciente) { echo '<p class="text-danger p-3">ID no válido.</p>'; exit; }

// ¿Existen las columnas opcionales?
$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$colExistePac = function ($col) use ($conexion, $dbName) {
    return (int)$conexion->query(
        "SELECT COUNT(*) c FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA='$dbName' AND TABLE_NAME='AG_PACIENTE' AND COLUMN_NAME='$col'"
    )->fetch_assoc()['c'] > 0;
};
$tieneAlerta = $colExistePac('ALERTA');
$tieneIcd10  = $colExistePac('IDICD10');

// Datos del paciente
$colsPac = "NOMBRES, APELLIDOS, CEDULA, TELEFONO, EMAIL, FECHANACIMIENTO, SEX, GENDER, FECHA_REGISTRO, NOTES, ADDNOTES"
         . ($tieneAlerta ? ", ALERTA"  : "")
         . ($tieneIcd10  ? ", IDICD10" : "");
$stmtP = $conexion->prepare(
    "SELECT $colsPac FROM AG_PACIENTE WHERE IDPACIENTE = ? LIMIT 1"
);
$stmtP->bind_param("i", $idPaciente);
$stmtP->execute();
$pac = $stmtP->get_result()->fetch_assoc();
$stmtP->close();
if ($pac && !$tieneAlerta) $pac['ALERTA'] = '';

// ICD-10 (código + descripción) si el paciente tiene asignado uno
$icd10Codigo = ''; $icd10Descripcion = '';
if ($pac && $tieneIcd10 && !empty($pac['IDICD10'])) {
    $sIcd = $conexion->prepare(
        "SELECT CODIGO, DESCRIPCION FROM ENFE_DIAG_COD WHERE ID_ENFE_DIAG_COD = ? LIMIT 1"
    );
    $sIcd->bind_param('i', $pac['IDICD10']);
    $sIcd->execute();
    if ($r = $sIcd->get_result()->fetch_assoc()) {
        $icd10Codigo      = $r['CODIGO'];
        $icd10Descripcion = $r['DESCRIPCION'];
    }
    $sIcd->close();
}

if (!$pac) { echo '<p class="text-danger p-3">Paciente no encontrado.</p>'; exit; }

// Talla más reciente registrada en atenciones
$stmtT = $conexion->prepare(
    "SELECT H.TALLA FROM AG_HISTORIAL H
     INNER JOIN AG_CITA C ON C.IDCITA = H.IDCITA
     WHERE C.IDPACIENTE = ? AND H.TALLA IS NOT NULL AND H.TALLA > 0
     ORDER BY H.FECHA_REGISTRO DESC LIMIT 1"
);
$stmtT->bind_param("i", $idPaciente);
$stmtT->execute();
$rowTalla = $stmtT->get_result()->fetch_assoc();
$stmtT->close();
$tallaActual = $rowTalla['TALLA'] ?? null;

// Edad calculada desde FECHANACIMIENTO
$edad = null;
$fn   = $pac['FECHANACIMIENTO'] ?? '';
if ($fn && $fn !== '0000-00-00' && $fn !== '') {
    try {
        $edad = (new DateTime($fn))->diff(new DateTime())->y;
    } catch (Exception $e) { $edad = null; }
}

// Todas las citas del paciente
$stmtC = $conexion->prepare(
    "SELECT C.IDCITA, C.FECHA_CITA, C.HORA_INICIO, C.HORA_FIN,
            C.ESTADO_CITA,
            TC.NOMBRES AS TIPO_CONSULTA,
            CONCAT(D.NOMBRES,' ',D.APELLIDOS) AS DOCTOR,
            H.IDHISTORIAL, H.PESO, H.TALLA, H.IMC, H.FECHA_REGISTRO
     FROM AG_CITA C
     LEFT JOIN AG_TIPOCONSULTA TC ON TC.IDTIPOCONSULTA = C.IDTIPOCONSULTA
     LEFT JOIN ADM_USUARIO D      ON D.IDADM_USUARIO   = C.IDDOCTOR
     LEFT JOIN AG_HISTORIAL H     ON H.IDCITA          = C.IDCITA
     WHERE C.IDPACIENTE = ? AND C.ESTADO = 'A'
     ORDER BY C.FECHA_CITA DESC, C.HORA_INICIO DESC"
);
$stmtC->bind_param("i", $idPaciente);
$stmtC->execute();
$citas = $stmtC->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtC->close();

// Contadores
$totalCitas = count($citas);
$atendidas  = 0; $pendientes = 0; $canceladas = 0;
foreach ($citas as $_c) {
    if ($_c['IDHISTORIAL']) $atendidas++;
    if ($_c['ESTADO_CITA'] === 'Pendiente') $pendientes++;
    if (in_array($_c['ESTADO_CITA'], ['Cancelada','Cancelado'])) $canceladas++;
}

// IMC promedio
$imcs    = array_filter(array_column($citas, 'IMC'));
$imcProm = count($imcs) ? number_format(array_sum($imcs) / count($imcs), 1) : null;

// Planes de peso guardados (Planificador de Peso Corporal) — si la tabla existe
$planesPeso = [];
$tienePlanPeso = (int)$conexion->query(
    "SELECT COUNT(*) c FROM information_schema.TABLES
     WHERE TABLE_SCHEMA='$dbName' AND TABLE_NAME='AG_PLAN_PESO'"
)->fetch_assoc()['c'] > 0;
if ($tienePlanPeso) {
    $stmtPl = $conexion->prepare(
        "SELECT P.PESO_INICIAL, P.PESO_META, P.FECHA_META, P.PAL_META,
                P.CAL_MANTENER_ACTUAL, P.CAL_ALCANZAR, P.CAL_MANTENER_META, P.UNIDADES,
                P.FECHA_REGISTRO, CONCAT(U.NOMBRES,' ',U.APELLIDOS) AS CREADO_POR
         FROM AG_PLAN_PESO P
         LEFT JOIN ADM_USUARIO U ON U.IDADM_USUARIO = P.IDADM_USUARIO
         WHERE P.IDPACIENTE = ? AND P.ESTADO = 'A'
         ORDER BY P.FECHA_REGISTRO DESC"
    );
    $stmtPl->bind_param("i", $idPaciente);
    $stmtPl->execute();
    $resPl = $stmtPl->get_result();
    while ($rp = $resPl->fetch_assoc()) { $planesPeso[] = $rp; }
    $stmtPl->close();
}

// Documentos enviados a este paciente
$documentosPac = [];
$stmtDoc = $conexion->prepare(
    "SELECT e.id_envio, e.estado, e.fecha_envio, e.fecha_firma, d.titulo
       FROM documento_envio e
       INNER JOIN documentos d ON d.id_documento = e.id_documento
      WHERE e.IDPACIENTE = ?
   ORDER BY e.fecha_envio DESC"
);
$stmtDoc->bind_param("i", $idPaciente);
$stmtDoc->execute();
$documentosPac = $stmtDoc->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtDoc->close();

function badgeClass($est) {
    switch ($est) {
        case 'Confirmada':  return 'bg-success';
        case 'Pendiente':   return 'bg-warning text-dark';
        case 'Cancelada':
        case 'Cancelado':   return 'bg-danger';
        case 'A':           return 'badge-atendida';
        default:            return 'bg-secondary';
    }
}
function imcLabel($imc) {
    if ($imc < 18.5) return t('hp.bmi.underweight');
    if ($imc < 25)   return t('hp.bmi.normal');
    if ($imc < 30)   return t('hp.bmi.overweight');
    return t('hp.bmi.obesity');
}
function estadoLabel($est) {
    switch ($est) {
        case 'A':            return t('hp.attendedShort');
        case 'Pendiente':    return t('hp.st.pending');
        case 'Confirmada':   return t('hp.st.confirmed');
        case 'Cancelada':
        case 'Cancelado':    return t('hp.st.cancelled');
        default:             return $est;
    }
}
function imcColor($imc) {
    if ($imc < 18.5) return '#0dcaf0';
    if ($imc < 25)   return '#198754';
    if ($imc < 30)   return '#ffc107';
    return '#dc3545';
}
?>
<style>
    .badge-atendida { background:#6f42c1; color:#fff; }
    .info-label { font-size:.7rem; text-transform:uppercase; color:#888; font-weight:600; }
    .imc-pill { display:inline-block; padding:2px 8px; border-radius:20px; font-size:.75rem; font-weight:600; color:#fff; }
</style>

<!-- ── INFO DEL PACIENTE ─────────────────────────────────────── -->
<div class="px-4 pt-3 pb-2 border-bottom" style="background:#f8f9fa;">
    <div class="row g-3 align-items-start">
        <div class="col-md-7">
            <div class="d-flex gap-3 align-items-center mb-2">
                <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);
                            color:#fff;font-weight:700;font-size:1.2rem;display:flex;align-items:center;justify-content:center;">
                    <?php echo strtoupper(substr($pac['NOMBRES'],0,1) . substr($pac['APELLIDOS'],0,1)); ?>
                </div>
                <div>
                    <div class="fw-bold" style="font-size:1.05rem;">
                        <?php echo htmlspecialchars($pac['NOMBRES'] . ' ' . $pac['APELLIDOS']); ?>
                    </div>
                    <small class="text-muted">ID <?php echo htmlspecialchars($pac['CEDULA'] ?? '—'); ?></small>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-3" style="font-size:.85rem;">
                <?php if ($edad !== null): ?>
                    <span><i class="bi bi-person-fill text-muted me-1"></i><?php echo $edad; ?> <?php te('hp.years'); ?></span>
                <?php endif; ?>
                <?php if ($tallaActual): ?>
                    <?php $tallaM = $tallaActual > 3 ? $tallaActual / 100 : $tallaActual; ?>
                    <span><i class="bi bi-rulers text-muted me-1"></i><?php echo number_format($tallaM, 2); ?> m</span>
                <?php endif; ?>
                <?php
                    $sexo   = $pac['SEX']    ?? '';
                    $genero = $pac['GENDER'] ?? '';
                    $invalid = ['', 'Default Select', 'default select'];
                    $mostrar = !in_array($genero, $invalid) ? $genero : (!in_array($sexo, $invalid) ? $sexo : null);
                    if ($mostrar):
                ?>
                    <span><i class="bi bi-gender-ambiguous text-muted me-1"></i><?php echo htmlspecialchars($mostrar); ?></span>
                <?php endif; ?>
                <span><i class="bi bi-telephone text-muted me-1"></i><?php echo htmlspecialchars($pac['TELEFONO'] ?? '—'); ?></span>
                <span><i class="bi bi-envelope text-muted me-1"></i><?php echo htmlspecialchars($pac['EMAIL'] ?? '—'); ?></span>
                <?php if (!empty($pac['FECHA_REGISTRO'])): ?>
                    <span><i class="bi bi-calendar-plus text-muted me-1"></i><?php te('hp.registered'); ?>: <?php echo date('d/m/Y', strtotime($pac['FECHA_REGISTRO'])); ?></span>
                <?php endif; ?>
            </div>
            <?php if ($icd10Codigo !== ''): ?>
                <div class="mt-2" style="font-size:.85rem;">
                    <span class="badge bg-info text-dark"><i class="bi bi-clipboard2-pulse me-1"></i>ICD-10: <?php echo htmlspecialchars($icd10Codigo); ?></span>
                    <span class="text-muted ms-1"><?php echo htmlspecialchars($icd10Descripcion); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Estadísticas rápidas -->
        <div class="col-md-5">
            <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                <div class="text-center px-3 py-1 rounded" style="background:#e8f0fe;">
                    <div style="font-size:1.2rem;font-weight:700;color:#3d5af1;"><?php echo $totalCitas; ?></div>
                    <div class="info-label"><?php te('hp.totalAppts'); ?></div>
                </div>
                <div class="text-center px-3 py-1 rounded" style="background:#ede7f6;">
                    <div style="font-size:1.2rem;font-weight:700;color:#6f42c1;"><?php echo $atendidas; ?></div>
                    <div class="info-label"><?php te('hp.attended'); ?></div>
                </div>
                <div class="text-center px-3 py-1 rounded" style="background:#fff3cd;">
                    <div style="font-size:1.2rem;font-weight:700;color:#e67e22;"><?php echo $pendientes; ?></div>
                    <div class="info-label"><?php te('hp.pending'); ?></div>
                </div>
                <div class="text-center px-3 py-1 rounded" style="background:#fdecea;">
                    <div style="font-size:1.2rem;font-weight:700;color:#c0392b;"><?php echo $canceladas; ?></div>
                    <div class="info-label"><?php te('hp.cancelled'); ?></div>
                </div>
                <?php if ($imcProm): ?>
                <div class="text-center px-3 py-1 rounded" style="background:#e8f5e9;">
                    <div style="font-size:1.2rem;font-weight:700;color:#2e7d32;"><?php echo $imcProm; ?></div>
                    <div class="info-label"><?php te('hp.bmiAvg'); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
    $alertaTxt   = trim($pac['ALERTA']   ?? '');
    $notasTxt    = trim($pac['NOTES']    ?? '');
    $factNotasTxt= trim($pac['ADDNOTES'] ?? '');
?>
<!-- ── ALERTA (banner rojo, siempre visible si existe) ───────── -->
<?php if ($alertaTxt !== ''): ?>
<div class="mx-4 mt-3 p-2 rounded d-flex align-items-start gap-2"
     style="background:#fdecea;border-left:4px solid #dc3545;">
    <i class="bi bi-exclamation-triangle-fill" style="color:#dc3545;"></i>
    <div style="font-size:.85rem;color:#842029;white-space:pre-wrap;"><?php echo htmlspecialchars($alertaTxt); ?></div>
</div>
<?php endif; ?>

<!-- ── NOTAS DEL PACIENTE (importantes / facturación) ────────── -->
<?php if ($notasTxt !== '' || $factNotasTxt !== ''): ?>
<div class="mx-4 mt-2 mb-1 row g-2">
    <?php if ($notasTxt !== ''): ?>
    <div class="col-md-6">
        <div class="border rounded p-2 h-100" style="background:#fff;">
            <div class="info-label mb-1"><i class="bi bi-journal-text"></i> <?php te('hp.important'); ?></div>
            <div style="font-size:.83rem;white-space:pre-wrap;"><?php echo htmlspecialchars($notasTxt); ?></div>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($factNotasTxt !== ''): ?>
    <div class="col-md-6">
        <div class="border rounded p-2 h-100" style="background:#fff;">
            <div class="info-label mb-1"><i class="bi bi-receipt"></i> <?php te('hp.billing'); ?></div>
            <div style="font-size:.83rem;white-space:pre-wrap;"><?php echo htmlspecialchars($factNotasTxt); ?></div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ── PLANES DE PESO GUARDADOS ──────────────────────────────── -->
<?php if (!empty($planesPeso)): ?>
<div class="mx-4 mt-3">
    <div class="border rounded p-2" style="background:#fff;">
        <div class="info-label mb-2"><i class="bi bi-graph-down-arrow"></i> <?php te('hp.weightPlans'); ?></div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0" style="font-size:.8rem;">
                <thead class="table-light">
                    <tr>
                        <th><?php te('hp.date'); ?></th>
                        <th><?php te('hp.startWeightGoal'); ?></th>
                        <th><?php te('hp.goalDate'); ?></th>
                        <th class="text-center"><?php te('hp.maintainCurrent'); ?></th>
                        <th class="text-center"><?php te('hp.reachGoal'); ?></th>
                        <th class="text-center"><?php te('hp.maintainGoal'); ?></th>
                        <th><?php te('hp.registeredBy'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($planesPeso as $pl):
                    $esUS = (($pl['UNIDADES'] ?? '') === 'us');
                    $u    = $esUS ? 'lb' : 'kg';
                    $pi   = $esUS ? ($pl['PESO_INICIAL'] * 2.2) : $pl['PESO_INICIAL'];
                    $pm   = $esUS ? ($pl['PESO_META']    * 2.2) : $pl['PESO_META'];
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($pl['FECHA_REGISTRO']))); ?></td>
                        <td><?php echo number_format($pi, 1) . ' → ' . number_format($pm, 1) . ' ' . $u; ?></td>
                        <td><?php echo $pl['FECHA_META'] ? htmlspecialchars(date('d/m/Y', strtotime($pl['FECHA_META']))) : '—'; ?></td>
                        <td class="text-center"><?php echo number_format($pl['CAL_MANTENER_ACTUAL']); ?></td>
                        <td class="text-center"><strong><?php echo number_format($pl['CAL_ALCANZAR']); ?></strong></td>
                        <td class="text-center"><?php echo number_format($pl['CAL_MANTENER_META']); ?></td>
                        <td><?php echo htmlspecialchars(trim($pl['CREADO_POR'] ?? '') ?: '—'); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="text-muted mt-1" style="font-size:.7rem;"><?php te('hp.caloriesNote'); ?></div>
    </div>
</div>
<?php endif; ?>

<!-- ── TABLA DE CITAS ────────────────────────────────────────── -->
<div class="px-0">
    <?php if (empty($citas)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
            <?php te('hp.noAppts'); ?>
        </div>
    <?php else: ?>
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th><?php te('hp.date'); ?></th>
                <th><?php te('hp.time'); ?></th>
                <th><?php te('hp.type'); ?></th>
                <th><?php te('hp.doctor'); ?></th>
                <th class="text-center"><?php te('hp.status'); ?></th>
                <th class="text-center"><?php te('hp.bmi'); ?></th>
                <th class="text-center"><?php te('hp.report'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($citas as $c):
            $est      = $c['IDHISTORIAL'] ? 'A' : $c['ESTADO_CITA'];
            $estLabel = estadoLabel($est);
            $bc       = badgeClass($est);
        ?>
        <tr>
            <td><?php echo date('d/m/Y', strtotime($c['FECHA_CITA'])); ?></td>
            <td>
                <?php echo substr($c['HORA_INICIO'],0,5); ?>
                <?php if ($c['HORA_FIN']): ?>
                    <small class="text-muted">– <?php echo substr($c['HORA_FIN'],0,5); ?></small>
                <?php endif; ?>
            </td>
            <td><small><?php echo htmlspecialchars($c['TIPO_CONSULTA'] ?? '—'); ?></small></td>
            <td><small><?php echo htmlspecialchars(trim($c['DOCTOR']) ?: '—'); ?></small></td>
            <td class="text-center">
                <span class="badge <?php echo $bc; ?>"
                      <?php echo $est === 'A' ? 'style="background:#6f42c1"' : ''; ?>>
                    <?php echo $estLabel; ?>
                </span>
            </td>
            <td class="text-center">
                <?php if ($c['IMC']): ?>
                    <span class="imc-pill" style="background:<?php echo imcColor((float)$c['IMC']); ?>">
                        <?php echo number_format($c['IMC'],1); ?>
                    </span>
                    <div style="font-size:.68rem;color:#888;"><?php echo imcLabel((float)$c['IMC']); ?></div>
                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
            <td class="text-center">
                <?php if ($c['IDHISTORIAL']): ?>
                    <button class="btn btn-outline-secondary btn-sm py-0 px-2"
                            onclick="verInforme(<?php echo $c['IDHISTORIAL']; ?>)"
                            title="<?php te('hp.viewReport'); ?>">
                        <i class="bi bi-file-earmark-text"></i>
                    </button>
                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- ── DOCUMENTOS DEL PACIENTE (lista en divs para no mezclarse con las citas) ── -->
<?php if (!empty($documentosPac)): ?>
<div class="px-4 py-3 border-top">
    <h6 class="text-muted mb-2"><i class="bi bi-file-earmark-text"></i> <?php te('hp.docsSent'); ?> (<?php echo count($documentosPac); ?>)</h6>
    <?php foreach ($documentosPac as $doc): ?>
        <div class="d-flex justify-content-between align-items-center border rounded p-2 mb-1">
            <div>
                <strong><?php echo htmlspecialchars($doc['titulo']); ?></strong><br>
                <small class="text-muted">
                    <?php te('hp.sent'); ?>: <?php echo $doc['fecha_envio'] ? date('d/m/Y', strtotime($doc['fecha_envio'])) : '—'; ?>
                    <?php if ($doc['estado'] === 'Firmado' && $doc['fecha_firma']): ?>
                        &nbsp;·&nbsp; <?php te('hp.signed'); ?>: <?php echo date('d/m/Y', strtotime($doc['fecha_firma'])); ?>
                    <?php endif; ?>
                </small>
            </div>
            <div class="text-end" style="white-space:nowrap;">
                <?php if ($doc['estado'] === 'Firmado'): ?>
                    <span class="badge bg-success"><?php te('hp.signed'); ?></span>
                    <a href="ver_documento_firmado.php?id=<?php echo (int)$doc['id_envio']; ?>" target="_blank"
                       class="btn btn-outline-primary btn-sm py-0 px-2" title="<?php te('hp.viewSigned'); ?>"><i class="bi bi-eye"></i></a>
                <?php else: ?>
                    <span class="badge bg-warning text-dark"><?php te('hp.st.pending'); ?></span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ══ SEGUROS DEL PACIENTE ════════════════════════════════════════════ -->
<?php
$segurosDisponibles = [];
$resSeg = $conexion->query("SELECT Id_seguro, Empresa_seguro FROM seguros WHERE estado = 1 ORDER BY Empresa_seguro");
if ($resSeg) { while ($s = $resSeg->fetch_assoc()) $segurosDisponibles[] = $s; }
?>
<div class="p-3 border-top" id="hpSegurosWrap" data-id-paciente="<?php echo (int)$idPaciente; ?>">
    <h6 class="text-muted mb-2"><i class="bi bi-shield-check"></i> <?php te('pcreate.insurance'); ?></h6>

    <div class="row g-2 align-items-end mb-2">
        <div class="col-12 col-md-5">
            <label class="form-label small mb-1"><?php te('pcreate.insurer'); ?></label>
            <select id="hpSeguro" class="form-select form-select-sm">
                <option value=""><?php te('pcreate.selectDash'); ?></option>
                <?php foreach ($segurosDisponibles as $sp): ?>
                <option value="<?php echo (int)$sp['Id_seguro']; ?>"><?php echo htmlspecialchars($sp['Empresa_seguro']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small mb-1"><?php te('pcreate.policyNo'); ?></label>
            <input type="text" id="hpPoliza" class="form-control form-control-sm" maxlength="60">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1"><?php te('pcreate.priority'); ?></label>
            <select id="hpPrioridad" class="form-select form-select-sm">
                <option value="Primario"><?php te('pcreate.priorityPrimary'); ?></option>
                <option value="Secundario"><?php te('pcreate.prioritySecondary'); ?></option>
                <option value="Terciario"><?php te('pcreate.priorityTertiary'); ?></option>
            </select>
        </div>
        <div class="col-12 col-md-2">
            <button type="button" class="btn btn-sm btn-success w-100" onclick="mnuAgregarSeguroPaciente(<?php echo (int)$idPaciente; ?>)"><?php te('pcreate.addBtn'); ?></button>
        </div>
    </div>

    <div id="hpLista"><div class="text-muted small">—</div></div>
</div>
<script>
    // Cargar seguros al abrir; las funciones globales están en menu_adm.php
    (function(){
        if (typeof window.mnuCargarSegurosPaciente === 'function') {
            window.mnuCargarSegurosPaciente(<?php echo (int)$idPaciente; ?>);
        }
    })();
</script>
