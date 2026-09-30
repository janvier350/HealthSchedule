<?php
/**
 * citas_por_fecha.php — Revisión rápida de citas por fecha para depurar las mal
 * creadas (p. ej. series recurrentes erróneas) y darlas de baja SIN correo.
 * Requiere sesión y permiso agenda.revision.
 */
ob_start();
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/lang/i18n.php");
require_once(__DIR__ . "/class/permisos.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

if (!isset($_SESSION["rol"])) { header("Location: break.php"); exit(); }
if (isset($_SESSION['expire']) && time() > $_SESSION['expire']) { session_destroy(); header("Location: expirada.php"); exit(); }
requerir('agenda.revision');

$en = (current_lang() === 'en');
$dbEsc = $conexion->real_escape_string($conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db']);
$tieneSerie = (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$dbEsc' AND TABLE_NAME='AG_CITA' AND COLUMN_NAME='IDSERIE'")->fetch_assoc()['c'] > 0;

// Filtros
$hoy    = date('Y-m-d');
$desde  = trim($_GET['desde'] ?? $hoy);
$hasta  = trim($_GET['hasta'] ?? $desde);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) $desde = $hoy;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) $hasta = $desde;
$fDoctor = (int)($_GET['doctor'] ?? 0);
$fQ      = trim($_GET['q'] ?? '');

// Doctores para el filtro y la edición
$doctores = [];
$rd = $conexion->query("SELECT U.IDADM_USUARIO, U.NOMBRES, U.APELLIDOS
                         FROM ADM_USUARIO U INNER JOIN ADM_ROL R ON U.IDADM_ROL=R.IDADM_ROL
                        WHERE R.CARGO='DOCTOR' AND U.ESTADO='A' ORDER BY U.NOMBRES");
if ($rd) while ($d = $rd->fetch_assoc()) $doctores[] = $d;

// Catálogos para editar
$tiposConsulta = [];
$rt = $conexion->query("SELECT IDTIPOCONSULTA, NOMBRES FROM AG_TIPOCONSULTA WHERE ESTADO='A' ORDER BY NOMBRES");
if ($rt) while ($t = $rt->fetch_assoc()) $tiposConsulta[] = $t;
$agencias = [];
$ra = $conexion->query("SELECT IDAGENCIA, DESCRIPCION FROM ADM_AGENCIA WHERE ESTADO=1 ORDER BY DESCRIPCION");
if ($ra) while ($a = $ra->fetch_assoc()) $agencias[] = $a;

// Consulta de citas activas en el rango
$colSerie = $tieneSerie ? 'A.IDSERIE' : '0 AS IDSERIE';
$where = "A.ESTADO='A' AND A.FECHA_CITA BETWEEN ? AND ?";
$tipos = 'ss'; $vals = [$desde, $hasta];
if ($fDoctor > 0) { $where .= " AND A.IDDOCTOR = ?"; $tipos .= 'i'; $vals[] = $fDoctor; }
if ($fQ !== '')   { $where .= " AND CONCAT(P.NOMBRES,' ',P.APELLIDOS) LIKE ?"; $tipos .= 's'; $vals[] = '%'.$fQ.'%'; }

$sql = "SELECT A.IDCITA, $colSerie, A.FECHA_CITA, A.HORA_INICIO, A.HORA_FIN, A.ESTADO_CITA,
               A.IDTIPOCONSULTA, A.IDDOCTOR, A.IDAGENCIA,
               CONCAT(P.NOMBRES,' ',P.APELLIDOS) AS paciente,
               TC.NOMBRES AS tipo,
               CONCAT(D.NOMBRES,' ',D.APELLIDOS) AS doctor,
               AG.DESCRIPCION AS lugar
          FROM AG_CITA A
          INNER JOIN AG_PACIENTE P     ON P.IDPACIENTE = A.IDPACIENTE
          LEFT  JOIN AG_TIPOCONSULTA TC ON TC.IDTIPOCONSULTA = A.IDTIPOCONSULTA
          LEFT  JOIN ADM_USUARIO D      ON D.IDADM_USUARIO = A.IDDOCTOR
          LEFT  JOIN ADM_AGENCIA AG     ON AG.IDAGENCIA = A.IDAGENCIA
         WHERE $where
         ORDER BY A.FECHA_CITA ASC, A.HORA_INICIO ASC, paciente ASC
         LIMIT 1000";
$rows = [];
if ($stmt = $conexion->prepare($sql)) {
    $stmt->bind_param($tipos, ...$vals);
    $stmt->execute();
    $rs = $stmt->get_result();
    while ($x = $rs->fetch_assoc()) $rows[] = $x;
    $stmt->close();
}

function estBadge($e) {
    $map = [
        'Pendiente'=>'warning','Confirmada'=>'primary','A'=>'success','Atendida'=>'success',
        'Cancelada'=>'secondary','Cancelado'=>'secondary','No Asistió'=>'danger','No contestó'=>'dark',
    ];
    $c = $map[$e] ?? 'secondary';
    $txt = ($e === 'A') ? 'Atendida' : $e;
    return '<span class="badge bg-'.$c.'">'.htmlspecialchars($txt).'</span>';
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="images/favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $en?'Appointments by date':'Citas por fecha'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./main.css" rel="stylesheet">
    <script src="js/jquery.min.js"></script>
</head>
<body>
<div class="app-container app-theme-white body-tabs-shadow fixed-sidebar fixed-header">
    <div class="app-header header-shadow">
        <div class="app-header__logo"><div class="logo-src"></div>
            <div class="header__pane ml-auto">
                <button type="button" class="hamburger close-sidebar-btn hamburger--elastic" data-class="closed-sidebar">
                    <span class="hamburger-box"><span class="hamburger-inner"></span></span>
                </button>
            </div>
        </div>
        <div class="app-header__mobile-menu">
            <button type="button" class="hamburger hamburger--elastic mobile-toggle-nav">
                <span class="hamburger-box"><span class="hamburger-inner"></span></span>
            </button>
        </div>
        <div class="app-header__content"><div class="app-header-left"></div>
            <div class="app-header-right"><div class="header-btn-lg pr-0"><div class="widget-content p-0"><div class="widget-content-wrapper">
                <div class="widget-content-left ml-3 header-user-info">
                    <div class="widget-heading"><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></div>
                    <div class="widget-subheading"><?php echo htmlspecialchars($_SESSION['rol'] ?? ''); ?></div>
                </div>
                <div class="widget-content-left ms-3"><a href="salir.php" class="btn btn-sm btn-outline-secondary"><?php te('common.close'); ?></a></div>
            </div></div></div></div>
        </div>
    </div>
    <div class="app-main">
        <div class="app-sidebar sidebar-shadow"><?php include("./menu/menu_adm.php"); ?></div>
        <div class="app-main__outer"><div class="app-main__inner">

            <div class="app-page-title mb-3"><div class="page-title-wrapper"><div class="page-title-heading">
                <div class="page-title-icon"><i class="pe-7s-date icon-gradient bg-happy-itmeo"></i></div>
                <div><?php echo $en?'Appointments by date':'Citas por fecha'; ?>
                    <div class="page-title-subheading"><?php echo $en?'Review appointments in a date range and cancel the wrong ones (no email is sent).':'Revisa las citas de un rango de fechas y da de baja las erróneas (no envía correo).'; ?></div>
                </div>
            </div></div></div>

            <div class="card shadow-sm mb-3"><div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1"><?php echo $en?'From':'Desde'; ?></label>
                        <input type="date" name="desde" class="form-control form-control-sm" value="<?php echo htmlspecialchars($desde); ?>">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1"><?php echo $en?'To':'Hasta'; ?></label>
                        <input type="date" name="hasta" class="form-control form-control-sm" value="<?php echo htmlspecialchars($hasta); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1"><?php echo $en?'Doctor':'Doctor'; ?></label>
                        <select name="doctor" class="form-select form-select-sm">
                            <option value="0"><?php echo $en?'All':'Todos'; ?></option>
                            <?php foreach ($doctores as $d): ?>
                            <option value="<?php echo (int)$d['IDADM_USUARIO']; ?>" <?php echo $fDoctor===(int)$d['IDADM_USUARIO']?'selected':''; ?>>
                                <?php echo htmlspecialchars($d['NOMBRES'].' '.$d['APELLIDOS']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1"><?php echo $en?'Patient':'Paciente'; ?></label>
                        <input type="text" name="q" class="form-control form-control-sm" value="<?php echo htmlspecialchars($fQ); ?>" placeholder="<?php echo $en?'Search patient…':'Buscar paciente…'; ?>">
                    </div>
                    <div class="col-md-2 d-flex gap-1">
                        <button class="btn btn-primary btn-sm flex-grow-1" type="submit"><i class="bi bi-search"></i></button>
                        <a href="citas_por_fecha.php" class="btn btn-outline-secondary btn-sm"><?php echo $en?'Clear':'Limpiar'; ?></a>
                    </div>
                </form>
            </div></div>

            <div class="card shadow-sm"><div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <span class="text-muted small"><?php echo count($rows).' '.($en?'appointment(s)':'cita(s)'); ?></span>
                    <button type="button" class="btn btn-danger btn-sm" onclick="bajaSeleccionadas()">
                        <i class="bi bi-trash"></i> <?php echo $en?'Cancel selected':'Dar de baja seleccionadas'; ?>
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:34px;"><input type="checkbox" id="chkAll" onclick="toggleAll(this)"></th>
                                <th><?php echo $en?'Date':'Fecha'; ?></th>
                                <th><?php echo $en?'Time':'Hora'; ?></th>
                                <th><?php echo $en?'Patient':'Paciente'; ?></th>
                                <th><?php echo $en?'Type':'Tipo'; ?></th>
                                <th><?php echo $en?'Doctor':'Doctor'; ?></th>
                                <th><?php echo $en?'Status':'Estado'; ?></th>
                                <th class="text-end"><?php echo $en?'Actions':'Acciones'; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$rows): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4"><?php echo $en?'No appointments for these filters.':'No hay citas para estos filtros.'; ?></td></tr>
                        <?php else: foreach ($rows as $r): $serie=(int)$r['IDSERIE']; ?>
                            <tr id="fila-<?php echo (int)$r['IDCITA']; ?>"
                                data-id="<?php echo (int)$r['IDCITA']; ?>"
                                data-fecha="<?php echo htmlspecialchars($r['FECHA_CITA']); ?>"
                                data-hora="<?php echo htmlspecialchars(substr($r['HORA_INICIO'],0,5)); ?>"
                                data-tipo="<?php echo (int)$r['IDTIPOCONSULTA']; ?>"
                                data-doctor="<?php echo (int)$r['IDDOCTOR']; ?>"
                                data-agencia="<?php echo (int)$r['IDAGENCIA']; ?>"
                                data-serie="<?php echo $serie; ?>"
                                data-paciente="<?php echo htmlspecialchars($r['paciente']); ?>">
                                <td><input type="checkbox" class="chkCita" value="<?php echo (int)$r['IDCITA']; ?>"></td>
                                <td class="small text-nowrap"><?php echo htmlspecialchars(date('m/d/Y', strtotime($r['FECHA_CITA']))); ?></td>
                                <td class="small text-nowrap"><?php echo htmlspecialchars(substr($r['HORA_INICIO'],0,5)); ?><?php echo $r['HORA_FIN']?'–'.htmlspecialchars(substr($r['HORA_FIN'],0,5)):''; ?></td>
                                <td class="small"><?php echo htmlspecialchars($r['paciente']); ?><?php echo $serie>0?' <span class="badge bg-info text-dark" title="'.($en?'Part of a series':'Parte de una serie').'"><i class="bi bi-arrow-repeat"></i></span>':''; ?></td>
                                <td class="small"><?php echo htmlspecialchars($r['tipo'] ?: '—'); ?></td>
                                <td class="small"><?php echo htmlspecialchars(trim($r['doctor']) ?: '—'); ?></td>
                                <td><?php echo estBadge($r['ESTADO_CITA']); ?></td>
                                <td class="text-end text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" onclick="abrirEditar(<?php echo (int)$r['IDCITA']; ?>)"><i class="bi bi-pencil"></i> <?php echo $en?'Edit':'Editar'; ?></button>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="darBaja(<?php echo (int)$r['IDCITA']; ?>,'solo')"><?php echo $en?'Cancel':'Baja'; ?></button>
                                    <?php if ($serie>0): ?>
                                    <button type="button" class="btn btn-sm btn-outline-dark py-0 px-2" onclick="darBaja(<?php echo (int)$r['IDCITA']; ?>,'todas')"><?php echo $en?'Cancel series':'Baja serie'; ?></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div></div>

        </div></div>
    </div>
</div>
<!-- Modal editar cita / serie -->
<div class="modal fade" id="modalEditarSerie" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i><?php echo $en?'Edit appointment':'Editar cita'; ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="edId">
        <div class="mb-2"><span class="text-muted small"><?php echo $en?'Patient':'Paciente'; ?>:</span> <span id="edPaciente" class="fw-semibold"></span></div>
        <div class="row g-2">
          <div class="col-6"><label class="form-label small mb-1"><?php echo $en?'Date':'Fecha'; ?></label><input type="date" id="edFecha" class="form-control form-control-sm"></div>
          <div class="col-6"><label class="form-label small mb-1"><?php echo $en?'Time':'Hora'; ?></label><input type="time" id="edHora" class="form-control form-control-sm" min="07:00" max="22:30"></div>
          <div class="col-12"><label class="form-label small mb-1"><?php echo $en?'Type':'Tipo de consulta'; ?></label>
            <select id="edTipo" class="form-select form-select-sm">
              <?php foreach ($tiposConsulta as $t): ?><option value="<?php echo (int)$t['IDTIPOCONSULTA']; ?>"><?php echo htmlspecialchars($t['NOMBRES']); ?></option><?php endforeach; ?>
            </select></div>
          <div class="col-12"><label class="form-label small mb-1"><?php echo $en?'Doctor':'Doctor'; ?></label>
            <select id="edDoctor" class="form-select form-select-sm">
              <?php foreach ($doctores as $d): ?><option value="<?php echo (int)$d['IDADM_USUARIO']; ?>"><?php echo htmlspecialchars($d['NOMBRES'].' '.$d['APELLIDOS']); ?></option><?php endforeach; ?>
            </select></div>
          <div class="col-12"><label class="form-label small mb-1"><?php echo $en?'Location':'Lugar'; ?></label>
            <select id="edAgencia" class="form-select form-select-sm">
              <option value="0">—</option>
              <?php foreach ($agencias as $a): ?><option value="<?php echo (int)$a['IDAGENCIA']; ?>"><?php echo htmlspecialchars($a['DESCRIPCION']); ?></option><?php endforeach; ?>
            </select></div>
        </div>
        <div id="edSerieWrap" class="mt-3 d-none">
          <label class="form-label small mb-1"><?php echo $en?'Apply to':'Aplicar a'; ?></label>
          <div class="btn-group btn-group-sm w-100" role="group">
            <input type="radio" class="btn-check" name="edAlcance" id="edSolo" value="solo" checked>
            <label class="btn btn-outline-primary" for="edSolo"><?php echo $en?'Only this one':'Solo esta'; ?></label>
            <input type="radio" class="btn-check" name="edAlcance" id="edTodas" value="todas">
            <label class="btn btn-outline-primary" for="edTodas"><?php echo $en?'Whole series (future)':'Toda la serie (futuras)'; ?></label>
          </div>
          <div class="form-text"><?php echo $en?'"Whole series" moves this and all future appointments in the series.':'"Toda la serie" mueve esta y todas las futuras de la serie.'; ?></div>
        </div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?php echo $en?'Cancel':'Cancelar'; ?></button>
        <button type="button" class="btn btn-primary btn-sm" id="edGuardar" onclick="guardarEditar(false)"><i class="bi bi-check-lg"></i> <?php echo $en?'Save':'Guardar'; ?></button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
var L = {
    confirmSolo:  <?php echo json_encode($en?'Cancel this appointment? (no email is sent)':'¿Dar de baja esta cita? (no envía correo)'); ?>,
    confirmSerie: <?php echo json_encode($en?'Cancel this appointment AND all future ones in its series? (no email is sent)':'¿Dar de baja esta cita Y todas las futuras de su serie? (no envía correo)'); ?>,
    confirmBulk:  <?php echo json_encode($en?'Cancel the selected appointments? (no email is sent)':'¿Dar de baja las citas seleccionadas? (no envía correo)'); ?>,
    none:         <?php echo json_encode($en?'Select at least one appointment.':'Selecciona al menos una cita.'); ?>,
    err:          <?php echo json_encode($en?'Could not cancel: ':'No se pudo dar de baja: '); ?>,
    connErr:      <?php echo json_encode($en?'Connection error.':'Error de conexión.'); ?>,
    edFields:     <?php echo json_encode($en?'Complete date, time, type and doctor.':'Completa fecha, hora, tipo y doctor.'); ?>,
    edConfirm:    <?php echo json_encode($en?'Save these changes?':'¿Guardar estos cambios?'); ?>,
    edOk:         <?php echo json_encode($en?'Appointment updated.':'Cita actualizada.'); ?>,
    edErr:        <?php echo json_encode($en?'Could not save: ':'No se pudo guardar: '); ?>,
    slotTakenAsk: <?php echo json_encode($en?'The time is already taken. Schedule anyway (double-booking)?':'El horario ya está ocupado. ¿Agendar de todos modos (doble reserva)?'); ?>
};
var edModal = null;
function abrirEditar(id){
    var fila = document.getElementById('fila-'+id); if(!fila) return;
    document.getElementById('edId').value        = id;
    document.getElementById('edPaciente').textContent = fila.getAttribute('data-paciente') || '';
    document.getElementById('edFecha').value      = fila.getAttribute('data-fecha') || '';
    document.getElementById('edHora').value       = fila.getAttribute('data-hora') || '';
    document.getElementById('edTipo').value       = fila.getAttribute('data-tipo') || '';
    document.getElementById('edDoctor').value     = fila.getAttribute('data-doctor') || '';
    document.getElementById('edAgencia').value    = fila.getAttribute('data-agencia') || '0';
    var serie = parseInt(fila.getAttribute('data-serie')||'0',10) > 0;
    document.getElementById('edSerieWrap').classList.toggle('d-none', !serie);
    var solo = document.getElementById('edSolo'); if(solo) solo.checked = true;
    if(!edModal) edModal = new bootstrap.Modal(document.getElementById('modalEditarSerie'));
    edModal.show();
}
function guardarEditar(dobleReserva){
    var id     = document.getElementById('edId').value;
    var fecha  = document.getElementById('edFecha').value;
    var hora   = document.getElementById('edHora').value;
    var tipo   = document.getElementById('edTipo').value;
    var doctor = document.getElementById('edDoctor').value;
    var agencia= document.getElementById('edAgencia').value;
    if(!fecha || !hora || !tipo || !doctor){ alert(L.edFields); return; }
    var alcance = 'solo';
    var rTodas = document.getElementById('edTodas');
    if(rTodas && rTodas.checked && !document.getElementById('edSerieWrap').classList.contains('d-none')) alcance='todas';
    if(!dobleReserva && !confirm(L.edConfirm)) return;
    $.post('editar_cita.php', {
        idCita:id, fecha:fecha, hora:hora, idTipoConsulta:tipo, idDoctor:doctor,
        idAgencia:agencia, alcance:alcance, dobleReserva: dobleReserva ? 1 : 0
    }, function(res){
        res = (res||'').trim();
        if(res==='OK' || res==='OK_MODIFICADA' || res==='OK_SIN_CORREO'){ alert(L.edOk); location.reload(); }
        else if(res==='HORARIO_OCUPADO'){ if(confirm(L.slotTakenAsk)) guardarEditar(true); }
        else { alert(L.edErr + res); }
    }).fail(function(){ alert(L.connErr); });
}
function toggleAll(cb){ document.querySelectorAll('.chkCita').forEach(function(c){ c.checked = cb.checked; }); }
function darBaja(id, alcance){
    if(!confirm(alcance==='todas'?L.confirmSerie:L.confirmSolo)) return;
    $.post('cita_baja.php',{idCita:id, alcance:alcance}, function(res){
        if(res && res.ok){ location.reload(); }
        else { alert(L.err + (res && res.error ? res.error : '')); }
    },'json').fail(function(){ alert(L.connErr); });
}
function bajaSeleccionadas(){
    var ids = Array.prototype.map.call(document.querySelectorAll('.chkCita:checked'), function(c){ return c.value; });
    if(!ids.length){ alert(L.none); return; }
    if(!confirm(L.confirmBulk + ' ('+ids.length+')')) return;
    var pendientes = ids.length, errores = 0;
    ids.forEach(function(id){
        $.post('cita_baja.php',{idCita:id, alcance:'solo'}, function(res){
            if(!(res && res.ok)) errores++;
        },'json').always(function(){
            pendientes--;
            if(pendientes===0){ if(errores) alert(L.err+errores); location.reload(); }
        });
    });
}
</script>
</body>
</html>
<?php ob_end_flush(); ?>
