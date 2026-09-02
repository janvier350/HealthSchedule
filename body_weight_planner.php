<?php
ob_start();
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

if (!isset($_SESSION["rol"], $_SESSION["iduser"])) {
    header("Location: break.php");
    exit();
}
if (isset($_SESSION['expire']) && time() > $_SESSION['expire']) {
    session_destroy();
    header("Location: expirada.php");
    exit();
}

// Lista de pacientes activos para la precarga
$pacientes = [];
$resP = $conexion->query("SELECT IDPACIENTE, NOMBRES, APELLIDOS FROM AG_PACIENTE WHERE ESTADO='A' ORDER BY APELLIDOS, NOMBRES");
if ($resP) {
    while ($r = $resP->fetch_assoc()) { $pacientes[] = $r; }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <!-- Favicon de la app -->
    <link rel="icon" type="image/svg+xml" href="images/favicon.svg">
    <link rel="alternate icon" type="image/png" href="images/favicon.png">
    <link rel="apple-touch-icon" href="images/favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Planificador de Peso Corporal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./main.css" rel="stylesheet">
    <script src="js/jquery.min.js"></script>
    <style>
        .bwp-unit-btn.active { background:#1a3a5c; color:#fff; }
        .result-card { border:none; border-radius:10px; color:#fff; display:flex; flex-direction:column; justify-content:center; min-height:96px; }
        .result-num { font-size:2rem; font-weight:700; line-height:1; }
        .result-lbl { font-size:.8rem; opacity:.9; }
        .field-label { font-size:.8rem; font-weight:600; color:#33475b; }
        .help-box { font-size:.85rem; color:#555; }
        #bwpChartWrap { position:relative; width:100%; }
        canvas { max-width:100%; }
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
        <div class="app-header__content">
            <div class="app-header-left"></div>
            <div class="app-header-right">
                <div class="header-btn-lg pr-0">
                    <div class="widget-content p-0"><div class="widget-content-wrapper">
                        <div class="widget-content-left ml-3 header-user-info">
                            <div class="widget-heading"><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></div>
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
                            <div class="page-title-icon"><i class="pe-7s-graph3 icon-gradient bg-tempting-azure"></i></div>
                            <div>
                                Planificador de Peso Corporal
                                <div class="page-title-subheading">
                                    Basado en el modelo dinámico del NIH (Hall et al., Lancet 2011): calcula las calorías
                                    para alcanzar y mantener un peso meta.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Precarga desde paciente -->
                <div class="card shadow-sm mb-3">
                    <div class="card-body py-3">
                        <label class="field-label mb-1"><i class="bi bi-person-badge me-1"></i>Cargar datos de un paciente (opcional)</label>
                        <div class="row g-2 align-items-center">
                            <div class="col-md-8">
                                <input list="listaPacientes" id="pacienteBuscar" class="form-control"
                                       placeholder="Escribe el nombre del paciente y selecciónalo…">
                                <datalist id="listaPacientes">
                                    <?php foreach ($pacientes as $p): ?>
                                        <option value="<?php echo htmlspecialchars($p['APELLIDOS'].', '.$p['NOMBRES'].'  ·  #'.$p['IDPACIENTE']); ?>"></option>
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            <div class="col-md-4">
                                <span id="pacienteMsg" class="small text-muted"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row align-items-start">
                    <!-- ── Formulario ─────────────────────────────── -->
                    <div class="col-lg-5 mb-3">
                        <div class="card shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center py-2">
                                <span><i class="bi bi-input-cursor-text me-1"></i>Información inicial</span>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" id="btnUS" class="btn btn-outline-secondary bwp-unit-btn active" onclick="setUnidades('us')">U.S.</button>
                                    <button type="button" id="btnMetric" class="btn btn-outline-secondary bwp-unit-btn" onclick="setUnidades('metric')">Métrico</button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <label class="field-label">Peso actual</label>
                                    <div class="input-group">
                                        <input type="number" step="0.1" id="peso" class="form-control">
                                        <span class="input-group-text" id="pesoUnit">lbs</span>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="field-label">Sexo</label>
                                    <select id="sexo" class="form-select">
                                        <option value="">Seleccionar…</option>
                                        <option value="0">Hombre</option>
                                        <option value="1">Mujer</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="field-label">Edad (años)</label>
                                    <input type="number" id="edad" class="form-control">
                                </div>
                                <div class="mb-2">
                                    <label class="field-label">Estatura</label>
                                    <!-- US: pies + pulgadas -->
                                    <div class="input-group" id="alturaUS">
                                        <input type="number" id="alturaFt" class="form-control" placeholder="0">
                                        <span class="input-group-text">ft</span>
                                        <input type="number" id="alturaIn" class="form-control" placeholder="0">
                                        <span class="input-group-text">in</span>
                                    </div>
                                    <!-- Métrico: cm -->
                                    <div class="input-group d-none" id="alturaMetric">
                                        <input type="number" step="0.1" id="alturaCm" class="form-control">
                                        <span class="input-group-text">cm</span>
                                    </div>
                                </div>
                                <div class="mb-1">
                                    <label class="field-label">Nivel de actividad física (PAL)</label>
                                    <input type="number" step="0.05" id="pal" class="form-control" value="1.6">
                                    <div class="help-box mt-1">
                                        Rango típico: 1.4 (sedentario) a 2.5 (muy activo). El valor por defecto 1.6
                                        describe actividad muy ligera en el trabajo (mayormente sentado) y actividad
                                        moderada (caminar/pedalear) al menos una vez por semana.
                                    </div>
                                </div>

                                <hr>
                                <h6 class="text-muted mb-2"><i class="bi bi-flag me-1"></i>Meta</h6>
                                <div class="mb-2">
                                    <label class="field-label">Peso meta</label>
                                    <div class="input-group">
                                        <input type="number" step="0.1" id="pesoMeta" class="form-control">
                                        <span class="input-group-text" id="pesoMetaUnit">lbs</span>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="field-label">Fecha meta</label>
                                    <input type="date" id="fechaMeta" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="field-label">Actividad física durante la meta (PAL)</label>
                                    <input type="number" step="0.05" id="palMeta" class="form-control" placeholder="Igual que el actual">
                                    <div class="help-box mt-1">Déjalo vacío para mantener el mismo nivel de actividad.</div>
                                </div>

                                <button type="button" class="btn btn-primary w-100" onclick="calcularBWP()">
                                    <i class="bi bi-calculator me-1"></i> Calcular
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ── Resultados ─────────────────────────────── -->
                    <div class="col-lg-7 mb-3">
                        <div id="bwpResultados" class="d-none">
                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <div class="result-card p-3" style="background:#546e7a;">
                                        <div class="result-num" id="resMantenerActual">—</div>
                                        <div class="result-lbl">cal/día para mantener tu peso actual</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="result-card p-3" style="background:#1976d2;">
                                        <div class="result-num" id="resAlcanzar">—</div>
                                        <div class="result-lbl">cal/día para alcanzar la meta en la fecha</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="result-card p-3" style="background:#2e7d32;">
                                        <div class="result-num" id="resMantenerMeta">—</div>
                                        <div class="result-lbl">cal/día para mantener el peso meta</div>
                                    </div>
                                </div>
                            </div>

                            <div id="bwpAviso" class="alert alert-warning py-2 d-none"></div>

                            <div class="d-flex align-items-center gap-2 mb-3">
                                <button type="button" class="btn btn-success btn-sm" id="btnGuardarPlan" onclick="guardarPlan()" disabled>
                                    <i class="bi bi-save me-1"></i> Guardar en la ficha del paciente
                                </button>
                                <span id="guardarHint" class="small text-muted">Selecciona un paciente arriba para poder guardar el plan.</span>
                            </div>

                            <div class="card shadow-sm">
                                <div class="card-header py-2"><i class="bi bi-graph-down me-1"></i>Trayectoria estimada del peso</div>
                                <div class="card-body">
                                    <div id="bwpChartWrap"><canvas id="bwpChart" height="300"></canvas></div>
                                    <div class="small text-muted mt-2" id="bwpResumen"></div>
                                </div>
                            </div>
                        </div>

                        <div id="bwpPlaceholder" class="card shadow-sm">
                            <div class="card-body text-center text-muted py-5">
                                <i class="bi bi-clipboard-data" style="font-size:2.5rem;"></i>
                                <p class="mt-2 mb-0">Completa la información y presiona <strong>Calcular</strong> para ver los resultados.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Planes guardados del paciente ─────────────── -->
                <div class="card shadow-sm mb-3" id="bwpPlanesCard">
                    <div class="card-header py-2 d-flex align-items-center">
                        <i class="bi bi-clock-history me-2"></i>
                        <span>Planes guardados del paciente</span>
                        <span id="bwpPlanesPaciente" class="text-muted small ms-2"></span>
                        <button type="button" class="btn btn-sm btn-outline-secondary ms-auto py-0 px-2"
                                onclick="cargarPlanesPaciente()" title="Actualizar">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                    <div class="card-body py-2" id="bwpPlanesLista">
                        <div class="text-muted small">Selecciona un paciente arriba para ver sus planes guardados.</div>
                    </div>
                </div>

                <div class="alert alert-light border small text-muted">
                    <strong>Aviso:</strong> Esta herramienta es para adultos de 18 años o más, no para menores ni para
                    mujeres embarazadas o en lactancia. No sustituye el consejo médico. El profesional de salud que ha
                    examinado al paciente y conoce su historia clínica es quien mejor puede orientar el tratamiento.
                    Modelo basado en Hall KD, et al., <em>The Lancet</em> 2011;378:826-37 (NIH Body Weight Planner).
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script type="text/javascript" src="./assets/scripts/main.js"></script>
<script src="js/bwplanner.js"></script>
<script>
// ── Unidades ─────────────────────────────────────────────────────────
var unidades = 'us';
var LB_PER_KG = 2.2;   // se replica el factor del planificador del NIH
var IN_PER_M  = 100 / 2.54;

function setUnidades(u) {
    if (u === unidades) return;
    // Convertir valores existentes
    var peso = parseFloat(document.getElementById('peso').value);
    var pesoMeta = parseFloat(document.getElementById('pesoMeta').value);
    var alturaM = leerAlturaM();

    unidades = u;
    document.getElementById('btnUS').classList.toggle('active', u === 'us');
    document.getElementById('btnMetric').classList.toggle('active', u === 'metric');
    document.getElementById('pesoUnit').textContent     = (u === 'us') ? 'lbs' : 'kg';
    document.getElementById('pesoMetaUnit').textContent = (u === 'us') ? 'lbs' : 'kg';
    document.getElementById('alturaUS').classList.toggle('d-none', u !== 'us');
    document.getElementById('alturaMetric').classList.toggle('d-none', u === 'us');

    // Reescribir pesos
    if (!isNaN(peso))     document.getElementById('peso').value     = round1(u === 'us' ? peso * LB_PER_KG : peso / LB_PER_KG);
    if (!isNaN(pesoMeta)) document.getElementById('pesoMeta').value = round1(u === 'us' ? pesoMeta * LB_PER_KG : pesoMeta / LB_PER_KG);
    // Reescribir altura
    if (alturaM) escribirAltura(alturaM);
}

function leerAlturaM() {
    if (unidades === 'us') {
        var ft = parseFloat(document.getElementById('alturaFt').value) || 0;
        var inch = parseFloat(document.getElementById('alturaIn').value) || 0;
        var totalIn = ft * 12 + inch;
        return totalIn > 0 ? totalIn * 2.54 / 100 : null;
    } else {
        var cm = parseFloat(document.getElementById('alturaCm').value);
        return (!isNaN(cm) && cm > 0) ? cm / 100 : null;
    }
}
function escribirAltura(m) {
    if (unidades === 'us') {
        var totalIn = m * IN_PER_M;
        var ft = Math.floor(totalIn / 12);
        var inch = Math.round(totalIn - ft * 12);
        if (inch === 12) { ft += 1; inch = 0; }
        document.getElementById('alturaFt').value = ft;
        document.getElementById('alturaIn').value = inch;
    } else {
        document.getElementById('alturaCm').value = round1(m * 100);
    }
}
function round1(x) { return Math.round(x * 10) / 10; }

// Peso mostrado → kg
function pesoAKg(v) { return unidades === 'us' ? v / LB_PER_KG : v; }
// kg → peso mostrado
function kgAPeso(kg) { return unidades === 'us' ? kg * LB_PER_KG : kg; }

// ── Precarga de paciente ─────────────────────────────────────────────
var pacienteSelId = null;
var pacienteSelNombre = '';

document.getElementById('pacienteBuscar').addEventListener('change', function () {
    var m = this.value.match(/#(\d+)\s*$/);
    var msg = document.getElementById('pacienteMsg');
    if (!m) { return; }
    var id = m[1];
    msg.textContent = 'Cargando…';
    $.getJSON('get_paciente_bwp.php', { id: id })
        .done(function (d) {
            if (d.error) { msg.innerHTML = '<span class="text-danger">No se pudo cargar.</span>'; return; }
            pacienteSelId = id;
            pacienteSelNombre = d.nombre || '';
            actualizarBotonGuardar();
            if (d.sex === 0 || d.sex === 1) document.getElementById('sexo').value = String(d.sex);
            if (d.edad)   document.getElementById('edad').value = d.edad;
            if (d.pesoKg) document.getElementById('peso').value = round1(kgAPeso(d.pesoKg));
            if (d.tallaM) escribirAltura(d.tallaM);
            var faltan = [];
            if (!d.pesoKg) faltan.push('peso');
            if (!d.tallaM) faltan.push('estatura');
            if (d.sex === null) faltan.push('sexo');
            msg.innerHTML = faltan.length
                ? '<span class="text-warning">Cargado. Completa manualmente: ' + faltan.join(', ') + '.</span>'
                : '<span class="text-success">Datos cargados de ' + (d.nombre || 'paciente') + '.</span>';
            cargarPlanesPaciente();
        })
        .fail(function () { msg.innerHTML = '<span class="text-danger">Error de conexión.</span>'; });
});

// ── Cálculo ──────────────────────────────────────────────────────────
var bwpChart = null;

function calcularBWP() {
    var pesoKg = pesoAKg(parseFloat(document.getElementById('peso').value));
    var sexo   = document.getElementById('sexo').value;
    var edad   = parseInt(document.getElementById('edad').value, 10);
    var alturaM= leerAlturaM();
    var pal    = parseFloat(document.getElementById('pal').value);
    var metaKg = pesoAKg(parseFloat(document.getElementById('pesoMeta').value));
    var fechaMeta = document.getElementById('fechaMeta').value;
    var palMetaRaw = document.getElementById('palMeta').value;
    var palMeta = palMetaRaw ? parseFloat(palMetaRaw) : pal;

    // Validaciones
    if (isNaN(pesoKg) || pesoKg <= 0) { alert('Ingresa un peso actual válido.'); return; }
    if (sexo !== '0' && sexo !== '1') { alert('Selecciona el sexo.'); return; }
    if (isNaN(edad) || edad < 18)     { alert('La edad debe ser 18 años o más.'); return; }
    if (!alturaM || alturaM <= 0)     { alert('Ingresa una estatura válida.'); return; }
    if (isNaN(pal) || pal < 1.4 || pal > 2.5) { alert('El PAL debe estar entre 1.4 y 2.5.'); return; }
    if (isNaN(metaKg) || metaKg <= 0) { alert('Ingresa un peso meta válido.'); return; }
    if (!fechaMeta)                    { alert('Selecciona la fecha meta.'); return; }

    var hoy = new Date(); hoy.setHours(0,0,0,0);
    var fm = new Date(fechaMeta + 'T00:00:00');
    var dias = Math.round((fm - hoy) / 86400000);
    if (dias < 7) { alert('La fecha meta debe ser al menos 7 días en el futuro.'); return; }

    // Modelo
    var opts = { sex: parseInt(sexo,10), age: edad, height: alturaM, weightInitial: pesoKg, palInitial: pal };

    var pMantener = new BWPerson(opts);
    var mantenerActual = pMantener.intakeInitial; // = PAL × RMR

    var pAlcanzar = new BWPerson(opts);
    pAlcanzar.pal = palMeta;
    var alcanzar = pAlcanzar.findIntake(metaKg, dias);

    var pMantenerMeta = new BWPerson(opts);
    pMantenerMeta.pal = palMeta;
    var mantenerMeta = pMantenerMeta.findMaintenanceIntake(metaKg);

    // Trayectoria con la ingesta calculada
    var pTray = new BWPerson(opts);
    pTray.pal = palMeta;
    pTray.intake = alcanzar;
    var tray = pTray.trajectory(dias);

    // Mostrar
    document.getElementById('bwpPlaceholder').classList.add('d-none');
    document.getElementById('bwpResultados').classList.remove('d-none');
    document.getElementById('resMantenerActual').textContent = Math.round(mantenerActual).toLocaleString();
    document.getElementById('resAlcanzar').textContent       = Math.round(alcanzar).toLocaleString();
    document.getElementById('resMantenerMeta').textContent   = Math.round(mantenerMeta).toLocaleString();

    // Aviso si la meta es agresiva
    var aviso = document.getElementById('bwpAviso');
    if (alcanzar < 1000) {
        aviso.classList.remove('d-none');
        aviso.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i>Para alcanzar la meta en esa fecha se '
            + 'requerirían menos de 1000 cal/día, lo cual no suele ser seguro. Considera una fecha más lejana o una meta menos exigente.';
    } else {
        aviso.classList.add('d-none');
    }

    // Resumen
    var unidadPeso = (unidades === 'us') ? 'lbs' : 'kg';
    var cambio = kgAPeso(metaKg - pesoKg);
    var signo = cambio > 0 ? '+' : '';
    document.getElementById('bwpResumen').textContent =
        'Cambio de ' + signo + round1(cambio) + ' ' + unidadPeso + ' en ' + dias + ' días '
        + '(de ' + round1(kgAPeso(pesoKg)) + ' a ' + round1(kgAPeso(metaKg)) + ' ' + unidadPeso + '). '
        + 'Actividad durante la meta: PAL ' + palMeta + '.';

    dibujarChart(tray, unidadPeso);

    // Guardar el último cálculo para poder registrarlo en la ficha
    ultimoCalculo = {
        unidades: unidades,
        sexo: sexo,
        edad: edad,
        estaturaM: alturaM,
        pesoKg: pesoKg,
        pal: pal,
        metaKg: metaKg,
        fechaMeta: fechaMeta,
        palMeta: palMeta,
        calMantenerActual: Math.round(mantenerActual),
        calAlcanzar: Math.round(alcanzar),
        calMantenerMeta: Math.round(mantenerMeta)
    };
    actualizarBotonGuardar();
}

var ultimoCalculo = null;

function actualizarBotonGuardar() {
    var btn = document.getElementById('btnGuardarPlan');
    var hint = document.getElementById('guardarHint');
    if (!btn) return;
    if (!ultimoCalculo) {
        btn.disabled = true;
        hint.textContent = 'Primero presiona Calcular.';
        return;
    }
    if (!pacienteSelId) {
        btn.disabled = true;
        hint.textContent = 'Selecciona un paciente arriba para poder guardar el plan.';
        return;
    }
    btn.disabled = false;
    hint.textContent = 'Se guardará en la ficha de ' + (pacienteSelNombre || 'el paciente') + '.';
}

function guardarPlan() {
    if (!pacienteSelId) { alert('Selecciona un paciente para guardar el plan.'); return; }
    if (!ultimoCalculo) { alert('Primero calcula el plan.'); return; }

    var btn = document.getElementById('btnGuardarPlan');
    btn.disabled = true;

    var datos = Object.assign({ idPaciente: pacienteSelId }, ultimoCalculo);
    $.post('guardar_plan_peso.php', datos, function (res) {
        res = (res || '').trim();
        if (res === 'OK') {
            alert('Plan guardado en la ficha del paciente.');
            cargarPlanesPaciente();
        } else if (res === 'SIN_TABLA') {
            alert('Falta crear la tabla de planes. Pide al administrador ejecutar migrar_plan_peso.php una vez.');
        } else if (res === 'SIN_SESION') {
            alert('Tu sesión expiró. Vuelve a iniciar sesión.');
            window.location.href = 'index.php';
        } else {
            alert('No se pudo guardar: ' + res);
        }
        btn.disabled = false;
    }).fail(function () {
        alert('Error de conexión al guardar.');
        btn.disabled = false;
    });
}

// ── Planes guardados del paciente ────────────────────────────────────
function cargarPlanesPaciente() {
    var cont = document.getElementById('bwpPlanesLista');
    var etq  = document.getElementById('bwpPlanesPaciente');
    if (!cont) return;
    if (!pacienteSelId) {
        etq.textContent = '';
        cont.innerHTML = '<div class="text-muted small">Selecciona un paciente arriba para ver sus planes guardados.</div>';
        return;
    }
    etq.textContent = pacienteSelNombre ? '· ' + pacienteSelNombre : '';
    cont.innerHTML = '<div class="text-muted small">Cargando…</div>';
    $.get('plan_peso_listar.php', { id_paciente: pacienteSelId })
        .done(function (html) { cont.innerHTML = html; })
        .fail(function () { cont.innerHTML = '<div class="text-danger small">No se pudieron cargar los planes.</div>'; });
}

function eliminarPlanPeso(idPlan) {
    if (!confirm('¿Eliminar este plan guardado?')) return;
    $.post('plan_peso_eliminar.php', { idPlan: idPlan }, function (res) {
        res = (res || '').trim();
        if (res === 'OK') {
            cargarPlanesPaciente();
        } else if (res === 'SIN_SESION') {
            alert('Tu sesión expiró. Vuelve a iniciar sesión.');
            window.location.href = 'index.php';
        } else {
            alert('No se pudo eliminar: ' + res);
        }
    }).fail(function () { alert('Error de conexión al eliminar.'); });
}

// ── Gráfica en canvas (sin librerías externas) ───────────────────────
function dibujarChart(tray, unidadPeso) {
    var canvas = document.getElementById('bwpChart');
    var wrap = document.getElementById('bwpChartWrap');
    var W = canvas.width = wrap.clientWidth;
    var H = canvas.height = 300;
    var ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, W, H);

    var padL = 55, padR = 15, padT = 15, padB = 30;
    var plotW = W - padL - padR, plotH = H - padT - padB;

    var xs = tray.map(function (p) { return p.day; });
    var ys = tray.map(function (p) { return kgAPeso(p.weight); });
    var xMin = 0, xMax = xs[xs.length - 1] || 1;
    var yMin = Math.min.apply(null, ys), yMax = Math.max.apply(null, ys);
    var pad = (yMax - yMin) * 0.1 || 1;
    yMin -= pad; yMax += pad;

    function X(d) { return padL + (d - xMin) / (xMax - xMin) * plotW; }
    function Y(w) { return padT + (1 - (w - yMin) / (yMax - yMin)) * plotH; }

    // Ejes y grilla
    ctx.strokeStyle = '#e0e0e0'; ctx.fillStyle = '#888'; ctx.lineWidth = 1;
    ctx.font = '11px sans-serif'; ctx.textBaseline = 'middle';
    var yTicks = 5;
    for (var i = 0; i <= yTicks; i++) {
        var wv = yMin + (yMax - yMin) * i / yTicks;
        var yy = Y(wv);
        ctx.beginPath(); ctx.moveTo(padL, yy); ctx.lineTo(W - padR, yy); ctx.stroke();
        ctx.textAlign = 'right';
        ctx.fillText(round1(wv) + '', padL - 6, yy);
    }
    // Etiquetas X (0, mitad, fin)
    ctx.textAlign = 'center'; ctx.textBaseline = 'top';
    [0, Math.round(xMax / 2), Math.round(xMax)].forEach(function (d) {
        ctx.fillText(d + ' d', X(d), H - padB + 6);
    });

    // Línea de peso
    ctx.strokeStyle = '#1976d2'; ctx.lineWidth = 2.5; ctx.beginPath();
    tray.forEach(function (p, idx) {
        var xx = X(p.day), yy = Y(kgAPeso(p.weight));
        if (idx === 0) ctx.moveTo(xx, yy); else ctx.lineTo(xx, yy);
    });
    ctx.stroke();

    // Punto final
    var last = tray[tray.length - 1];
    ctx.fillStyle = '#2e7d32';
    ctx.beginPath(); ctx.arc(X(last.day), Y(kgAPeso(last.weight)), 4, 0, 2 * Math.PI); ctx.fill();

    // Título eje Y
    ctx.save();
    ctx.translate(14, padT + plotH / 2); ctx.rotate(-Math.PI / 2);
    ctx.fillStyle = '#666'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
    ctx.fillText('Peso (' + unidadPeso + ')', 0, 0);
    ctx.restore();
}

// Fecha meta por defecto: 6 meses adelante
(function () {
    var d = new Date(); d.setMonth(d.getMonth() + 6);
    document.getElementById('fechaMeta').value = d.toISOString().slice(0, 10);
})();
</script>
</body>
</html>
