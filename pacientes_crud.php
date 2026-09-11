<?php
/**
 * pacientes_crud.php
 * Lista de pacientes registrados con CRUD: editar (modal) y eliminar
 * (baja lógica). Columna de acciones. Buscador por nombre / cédula / teléfono.
 */
ob_start();
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/lang/i18n.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

if (!isset($_SESSION["rol"])) { header("Location: break.php"); exit(); }
if (isset($_SESSION['expire']) && time() > $_SESSION['expire']) {
    session_destroy(); header("Location: expirada.php"); exit();
}

$q    = trim($_GET['q'] ?? '');
$qEsc = $conexion->real_escape_string($q);

$where = "WHERE P.ESTADO = 'A'";
if ($q !== '') {
    $where .= " AND (P.NOMBRES LIKE '%$qEsc%'
                  OR P.APELLIDOS LIKE '%$qEsc%'
                  OR P.CEDULA   LIKE '%$qEsc%'
                  OR P.TELEFONO LIKE '%$qEsc%')";
}

$sql = "SELECT
            P.IDPACIENTE, P.NOMBRES, P.APELLIDOS, P.CEDULA, P.TELEFONO, P.EMAIL,
            P.FECHANACIMIENTO,
            (SELECT COUNT(*) FROM paciente_seguro PS WHERE PS.IDPACIENTE = P.IDPACIENTE AND PS.estado = 1) AS N_SEGUROS
        FROM AG_PACIENTE P
        $where
        ORDER BY P.APELLIDOS, P.NOMBRES";
$result    = $conexion->query($sql);
$totalRows = $result ? $result->num_rows : 0;

// Catálogo de aseguradoras para el select de "agregar seguro" en el modal
$segurosCat = [];
$rSegCat = $conexion->query("SELECT Id_seguro, Empresa_seguro FROM seguros WHERE estado = 1 ORDER BY Empresa_seguro");
if ($rSegCat) { while ($sc = $rSegCat->fetch_assoc()) { $segurosCat[] = $sc; } }
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="images/favicon.svg">
    <link rel="alternate icon" type="image/png" href="images/favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php te('pcrud.title'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./main.css" rel="stylesheet">
    <script src="js/jquery.min.js"></script>
    <style>
        .avatar { width:36px; height:36px; border-radius:50%;
            background:linear-gradient(135deg,#667eea,#764ba2); color:#fff;
            font-weight:700; font-size:.85rem; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    </style>
</head>
<body>
<div class="app-container app-theme-white body-tabs-shadow fixed-sidebar fixed-header">

    <div class="app-header header-shadow">
        <div class="app-header__logo">
            <div class="logo-src"></div>
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
        <div class="app-header__menu">
            <button type="button" class="btn-icon btn-icon-only btn btn-primary btn-sm mobile-toggle-header-nav">
                <span class="btn-icon-wrapper"><i class="fa fa-ellipsis-v fa-w-6"></i></span>
            </button>
        </div>
        <div class="app-header__content">
            <div class="app-header-left"></div>
            <div class="app-header-right">
                <div class="header-btn-lg pr-0">
                    <div class="widget-content p-0"><div class="widget-content-wrapper">
                        <div class="widget-content-left ml-3 header-user-info">
                            <div class="widget-heading"><?php echo htmlspecialchars($_SESSION['nombres'] ?? ''); ?></div>
                            <div class="widget-subheading"><?php echo htmlspecialchars($_SESSION['rol'] ?? ''); ?></div>
                        </div>
                    </div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-main">
        <div class="app-sidebar sidebar-shadow">
            <?php include("./menu/menu_adm.php"); ?>
        </div>

        <div class="app-main__outer">
            <div class="app-main__inner">

                <div class="app-page-title mb-3">
                    <div class="page-title-wrapper">
                        <div class="page-title-heading">
                            <div class="page-title-icon"><i class="pe-7s-users icon-gradient bg-plum-plate"></i></div>
                            <div><?php te('pcrud.title'); ?>
                                <div class="page-title-subheading"><?php te('pcrud.subtitle'); ?></div>
                            </div>
                        </div>
                        <div class="page-title-actions">
                            <a href="registrar_paciente_seguro.php" class="btn btn-primary btn-sm">
                                <i class="bi bi-person-plus-fill me-1"></i> <?php te('menu.registerPatientInsurance'); ?>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Buscador -->
                <div class="card shadow-sm mb-3">
                    <div class="card-body py-2">
                        <form method="GET" class="row g-2 align-items-center">
                            <div class="col">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                    <input type="text" name="q" class="form-control border-start-0"
                                           placeholder="<?php te('plist.searchPh'); ?>"
                                           value="<?php echo htmlspecialchars($q); ?>" autofocus>
                                </div>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary"><?php te('common.search'); ?></button>
                                <?php if ($q !== ''): ?>
                                    <a href="pacientes_crud.php" class="btn btn-outline-secondary ms-1"><?php te('plist.clear'); ?></a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center py-2">
                        <span style="font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;color:#6c757d;font-weight:600;">
                            <i class="bi bi-people-fill me-1"></i>
                            <?php echo $totalRows; ?> <?php echo $totalRows !== 1 ? t('plist.patients') : t('plist.patient'); ?>
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:36px;"></th>
                                        <th><?php te('plist.th.patient'); ?></th>
                                        <th><?php te('pf.id'); ?></th>
                                        <th><?php te('pf.phone'); ?></th>
                                        <th><?php te('pf.email'); ?></th>
                                        <th><?php te('pf.dob'); ?></th>
                                        <th class="text-center">🛡️</th>
                                        <th class="text-end pe-3"><?php te('pcreate.th.actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while ($p = $result->fetch_assoc()):
                                        $ini = strtoupper(substr($p['NOMBRES'] ?? '',0,1) . substr($p['APELLIDOS'] ?? '',0,1));
                                        $nombreCompleto = trim($p['APELLIDOS'] . ', ' . $p['NOMBRES']);
                                    ?>
                                    <tr id="row-<?php echo (int)$p['IDPACIENTE']; ?>">
                                        <td><div class="avatar"><?php echo htmlspecialchars($ini); ?></div></td>
                                        <td><div class="fw-semibold" style="font-size:.9rem;"><?php echo htmlspecialchars($nombreCompleto); ?></div></td>
                                        <td><small class="text-muted"><?php echo htmlspecialchars($p['CEDULA'] ?: '—'); ?></small></td>
                                        <td><small><?php echo htmlspecialchars($p['TELEFONO'] ?: '—'); ?></small></td>
                                        <td><small class="text-muted"><?php echo htmlspecialchars($p['EMAIL'] ?: '—'); ?></small></td>
                                        <td><small><?php echo htmlspecialchars($p['FECHANACIMIENTO'] ?: '—'); ?></small></td>
                                        <td class="text-center">
                                            <?php if ((int)$p['N_SEGUROS'] > 0): ?>
                                                <span class="badge bg-info text-dark"><?php echo (int)$p['N_SEGUROS']; ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-3" style="white-space:nowrap;">
                                            <button class="btn btn-sm btn-outline-secondary py-0 px-2"
                                                    onclick="editarPaciente(<?php echo (int)$p['IDPACIENTE']; ?>)"
                                                    title="<?php te('common.edit'); ?>">
                                                <i class="bi bi-pencil-square"></i> <?php te('common.edit'); ?>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger py-0 px-2"
                                                    onclick="eliminarPaciente(<?php echo (int)$p['IDPACIENTE']; ?>, '<?php echo htmlspecialchars(addslashes($nombreCompleto)); ?>')"
                                                    title="<?php te('common.delete'); ?>">
                                                <i class="bi bi-trash"></i> <?php te('common.delete'); ?>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="bi bi-person-x fs-2 d-block mb-2"></i>
                                            <?php echo $q !== '' ? t('plist.noneFoundPre') . ' "' . htmlspecialchars($q) . '".' : t('plist.noneRegistered'); ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL EDITAR PACIENTE ══ -->
<div class="modal fade" id="modalEditarPaciente" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2" style="background:#5a2d82;">
                <h6 class="modal-title text-white mb-0"><i class="bi bi-pencil-square me-2"></i><?php te('plist.editTitleModal'); ?></h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarPaciente">
                    <input type="hidden" id="epId" name="idPaciente">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold"><?php te('pf.firstName'); ?> *</label>
                            <input type="text" id="epNombres" name="nombres" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold"><?php te('pf.lastName'); ?> *</label>
                            <input type="text" id="epApellidos" name="apellidos" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold"><?php te('pf.id'); ?></label>
                            <input type="text" id="epCedula" name="cedula" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold"><?php te('pf.phone'); ?></label>
                            <input type="text" id="epTelefono" name="telefono" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold"><?php te('pf.dob'); ?></label>
                            <input type="date" id="epFecNac" name="fecNac" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold"><?php te('pf.email'); ?></label>
                            <input type="email" id="epEmail" name="email" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold"><?php te('pf.sex'); ?></label>
                            <select id="epSex" name="sex" class="form-select">
                                <option value="N/A">Default Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold"><?php te('pf.gender'); ?></label>
                            <select id="epGender" name="gender" class="form-select">
                                <option value="Default Select">Default Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Transgender man/trans man/female-to-male(FTM)">Transgender man (FTM)</option>
                                <option value="Transgender woman/trans woman/male-to-female(MTF)">Transgender woman (MTF)</option>
                                <option value="Genderqueer/gender nonconforming">Genderqueer / nonconforming</option>
                                <option value="Decline to answer">Decline to answer</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold"><?php te('pf.address'); ?></label>
                            <input type="text" id="epAddress" name="address" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold"><i class="bi bi-translate"></i> <?php te('pf.language'); ?></label>
                            <select id="epIdioma" name="idioma" class="form-select">
                                <option value="es"><?php te('lang.spanish'); ?></option>
                                <option value="en"><?php te('lang.english'); ?></option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold"><i class="bi bi-clipboard2-pulse"></i> ICD-10</label>
                            <select id="epIcd10" name="idicd10" class="form-select"><option value="">—</option></select>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold"><?php te('pf.importantNotes'); ?></label>
                        <textarea id="epNotes" name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-1">
                        <label class="form-label small fw-semibold"><?php te('pf.billingNotes'); ?></label>
                        <textarea id="epAddNotes" name="addNotes" class="form-control" rows="2"></textarea>
                    </div>
                </form>

                <!-- 🛡️ Seguros del paciente: agregar / quitar / fotos -->
                <hr class="my-3">
                <h6 class="text-muted mb-2">🛡️ <?php te('pcreate.insurance'); ?></h6>
                <div class="row g-2 align-items-end mb-2">
                    <div class="col-md-5">
                        <label class="form-label small mb-1"><?php te('pcreate.insurer'); ?></label>
                        <select id="psSeguro" class="form-select form-select-sm">
                            <option value=""><?php te('pcreate.selectDash'); ?></option>
                            <?php foreach ($segurosCat as $sc): ?>
                                <option value="<?php echo (int)$sc['Id_seguro']; ?>"><?php echo htmlspecialchars($sc['Empresa_seguro']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1"><?php te('pcreate.policyNo'); ?></label>
                        <input type="text" id="psPoliza" class="form-control form-control-sm" maxlength="60">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1"><?php te('pcreate.priority'); ?></label>
                        <select id="psPrioridad" class="form-select form-select-sm">
                            <option value="Primario"><?php te('pcreate.priorityPrimary'); ?></option>
                            <option value="Secundario"><?php te('pcreate.prioritySecondary'); ?></option>
                            <option value="Terciario"><?php te('pcreate.priorityTertiary'); ?></option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <button type="button" class="btn btn-sm btn-success w-100" onclick="agregarSeguroPaciente()"><?php te('pcreate.addBtn'); ?></button>
                    </div>
                </div>
                <div id="psLista"><div class="text-muted small">—</div></div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?php te('common.cancel'); ?></button>
                <button type="button" class="btn btn-primary btn-sm" id="epGuardar" onclick="guardarPaciente()">
                    <i class="bi bi-check-lg"></i> <?php te('common.saveChanges'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
var T = {
    loadError:    <?php echo json_encode(t('plist.js.loadError')); ?>,
    loadHttp:     <?php echo json_encode(t('plist.js.loadHttp')); ?>,
    nameRequired: <?php echo json_encode(t('plist.js.nameRequired')); ?>,
    incomplete:   <?php echo json_encode(t('plist.js.incomplete')); ?>,
    saveError:    <?php echo json_encode(t('plist.js.saveError')); ?>,
    connError:    <?php echo json_encode(t('common.js.connError')); ?>,
    delConfirm:   <?php echo json_encode(t('pcrud.js.confirmDelete')); ?>,
    delOk:        <?php echo json_encode(t('pcrud.js.deleted')); ?>,
    delError:     <?php echo json_encode(t('pcrud.js.deleteError')); ?>,
    loading:      <?php echo json_encode(t('common.loading')); ?>,
    selectInsurer:<?php echo json_encode(t('pcreate.js.selectInsurer')); ?>,
    insDup:       <?php echo json_encode(t('pcreate.js.insDup')); ?>,
    insAddError:  <?php echo json_encode(t('pcreate.js.insAddError')); ?>,
    insRemoveConf:<?php echo json_encode(t('pcreate.js.insRemoveConf')); ?>,
    insRemoveErr: <?php echo json_encode(t('pcreate.js.insRemoveErr')); ?>,
    imgUploadErr: <?php echo json_encode(t('pcreate.js.imgUploadErr')); ?>
};

let epModal = null;
let icd10Lista = null;

// Setea el valor de un <select>; si el valor guardado no existe como opción
// (datos antiguos), lo agrega para no perderlo al guardar.
function _setSelectSafe(selectId, value) {
    const sel = document.getElementById(selectId);
    if (!sel) return;
    value = value || '';
    if (value !== '' && !Array.from(sel.options).some(o => o.value === value)) {
        const opt = document.createElement('option');
        opt.value = value; opt.textContent = value;
        sel.appendChild(opt);
    }
    sel.value = value;
}

function _cargarIcd10(cb) {
    if (icd10Lista) { cb(icd10Lista); return; }
    $.getJSON('get_icd10_list.php')
        .done(function(d){ icd10Lista = Array.isArray(d) ? d : []; cb(icd10Lista); })
        .fail(function(){ icd10Lista = []; cb(icd10Lista); });
}
function _poblarSelectIcd10(selectId, valorActual) {
    _cargarIcd10(function(rows){
        var $sel = $('#' + selectId);
        $sel.empty().append('<option value="">—</option>');
        rows.forEach(function(r){ $sel.append('<option value="'+r.id+'">'+r.codigo+' — '+r.descripcion+'</option>'); });
        if (valorActual) $sel.val(String(valorActual));
        if ($sel.data('select2')) $sel.select2('destroy');
        $sel.select2({
            dropdownParent: $sel.closest('.modal').length ? $sel.closest('.modal') : $(document.body),
            width: '100%', placeholder: 'Buscar código o descripción…', allowClear: true
        });
    });
}

function editarPaciente(id) {
    if (!epModal) epModal = new bootstrap.Modal(document.getElementById('modalEditarPaciente'));
    document.getElementById('formEditarPaciente').reset();
    document.getElementById('epId').value = id;
    $.getJSON('get_paciente.php', { id: id })
        .done(function(p){
            if (p.error) { alert(T.loadError + p.error); return; }
            document.getElementById('epNombres').value   = p.NOMBRES   || '';
            document.getElementById('epApellidos').value = p.APELLIDOS || '';
            document.getElementById('epCedula').value    = p.CEDULA    || '';
            document.getElementById('epTelefono').value  = p.TELEFONO  || '';
            document.getElementById('epFecNac').value    = p.FECHANACIMIENTO || '';
            document.getElementById('epEmail').value     = p.EMAIL     || '';
            _setSelectSafe('epSex',    p.SEX);
            _setSelectSafe('epGender', p.GENDER);
            document.getElementById('epAddress').value   = p.ADDRESS   || '';
            document.getElementById('epIdioma').value    = (p.IDIOMA === 'en') ? 'en' : 'es';
            document.getElementById('epNotes').value     = p.NOTES     || '';
            document.getElementById('epAddNotes').value  = p.ADDNOTES  || '';
            _poblarSelectIcd10('epIcd10', p.IDICD10 || '');
            // Cargar los seguros del paciente en el modal
            document.getElementById('psSeguro').value = '';
            document.getElementById('psPoliza').value = '';
            document.getElementById('psPrioridad').value = 'Primario';
            cargarSegurosPaciente(id);
            epModal.show();
        })
        .fail(function(xhr){ alert(T.loadHttp + xhr.status + ').'); });
}

// ── Seguros del paciente (agregar / quitar / fotos) ──────────────────
function cargarSegurosPaciente(idPaciente) {
    const cont = document.getElementById('psLista');
    if (!cont) return;
    cont.innerHTML = '<div class="text-muted small">' + T.loading + '</div>';
    fetch('seguro_paciente_listar.php?id_paciente=' + encodeURIComponent(idPaciente))
        .then(r => r.text())
        .then(html => { cont.innerHTML = html; })
        .catch(() => { cont.innerHTML = '<div class="text-danger small">' + T.connError + '</div>'; });
}

function agregarSeguroPaciente() {
    const idPaciente = document.getElementById('epId').value;
    const idSeguro   = document.getElementById('psSeguro').value;
    const poliza     = document.getElementById('psPoliza').value;
    const prioridad  = document.getElementById('psPrioridad').value;
    if (!idPaciente) return;
    if (!idSeguro) { alert(T.selectInsurer); return; }
    fetch('seguro_paciente_guardar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ id_paciente: idPaciente, id_seguro: idSeguro, num_poliza: poliza, prioridad: prioridad })
    })
    .then(r => r.text())
    .then(res => {
        res = res.trim();
        if (res === 'OK') {
            document.getElementById('psSeguro').value = '';
            document.getElementById('psPoliza').value = '';
            document.getElementById('psPrioridad').value = 'Primario';
            cargarSegurosPaciente(idPaciente);
        } else if (res === 'DUP') { alert(T.insDup); }
        else { alert(T.insAddError + res); }
    })
    .catch(() => alert(T.connError));
}

function eliminarSeguroPaciente(id) {
    if (!confirm(T.insRemoveConf)) return;
    const idPaciente = document.getElementById('epId').value;
    fetch('seguro_paciente_eliminar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ id: id })
    })
    .then(r => r.text())
    .then(res => { if (res.trim() === 'OK') cargarSegurosPaciente(idPaciente); else alert(T.insRemoveErr + res); })
    .catch(() => alert(T.connError));
}

function subirImagenSeguro(input, id, lado) {
    if (!input.files || !input.files[0]) return;
    const fd = new FormData();
    fd.append('id_paciente_seguro', id);
    fd.append('lado', lado);
    fd.append('imagen', input.files[0]);
    fetch('seguro_paciente_subir_imagen.php', { method: 'POST', body: fd })
        .then(r => r.text())
        .then(res => {
            if (res.trim().startsWith('OK')) cargarSegurosPaciente(document.getElementById('epId').value);
            else alert(T.imgUploadErr + res);
        })
        .catch(() => alert(T.connError));
}

function guardarPaciente() {
    const nombres   = document.getElementById('epNombres').value.trim();
    const apellidos = document.getElementById('epApellidos').value.trim();
    if (!nombres || !apellidos) { alert(T.nameRequired); return; }
    const btn = document.getElementById('epGuardar');
    btn.disabled = true;
    $.post('editar_paciente.php', $('#formEditarPaciente').serialize(), function(res){
        res = (res || '').trim();
        if (res === 'OK' || res === 'OK_SIN_ALERTA') {
            location.reload();
        } else if (res === 'DATOS_INCOMPLETOS') {
            alert(T.incomplete); btn.disabled = false;
        } else {
            alert(T.saveError + res); btn.disabled = false;
        }
    }).fail(function(){ alert(T.connError); btn.disabled = false; });
}

function eliminarPaciente(id, nombre) {
    if (!confirm(T.delConfirm.replace('%s', nombre))) return;
    $.post('paciente_eliminar_crud.php', { idPaciente: id }, function(res){
        res = (res || '').trim();
        if (res === 'OK' || res === 'NO_CAMBIO') {
            var row = document.getElementById('row-' + id);
            if (row) row.remove();
        } else {
            alert(T.delError + res);
        }
    }).fail(function(){ alert(T.connError); });
}
</script>
</body>
</html>
