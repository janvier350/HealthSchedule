<?php
ob_start();
session_start();

require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/lang/i18n.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
$snLang = (current_lang() === 'en') ? 'en-US' : 'es-ES';

if(!isset($_SESSION["rol"])){
    header("Location: break.php");
    exit();
}
if (isset($_SESSION['expire']) && time() > $_SESSION['expire']) {
    session_destroy();
    header("Location: expirada.php");
    exit();
}

if(!isset($_GET['idCita']) || empty($_GET['idCita'])){
    die(htmlspecialchars(t('att.errNoId')));
}

$idCita = $conexion->real_escape_string($_GET['idCita']);

// ¿Existen las columnas NPI y LICENSE_ID en ADM_USUARIO? (las agrega migrar_credenciales_doctor.php)
$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$colDocExiste = function ($col) use ($conexion, $dbName) {
    return (int)$conexion->query(
        "SELECT COUNT(*) c FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA='$dbName' AND TABLE_NAME='ADM_USUARIO' AND COLUMN_NAME='$col'"
    )->fetch_assoc()['c'] > 0;
};
$tieneNpi     = $colDocExiste('NPI');
$tieneLicense = $colDocExiste('LICENSE_ID');
$tieneFirma   = $colDocExiste('FIRMA_IMG');
$selDocCreds  = ($tieneNpi     ? ", D.NPI        AS DOC_NPI"        : ", '' AS DOC_NPI")
              . ($tieneLicense ? ", D.LICENSE_ID AS DOC_LICENSE_ID" : ", '' AS DOC_LICENSE_ID")
              . ($tieneFirma   ? ", D.FIRMA_IMG  AS DOC_FIRMA_IMG"  : ", '' AS DOC_FIRMA_IMG");
// Credenciales del usuario de sesión (para la firma cuando el que atiende es el logueado)
$selUsrCreds  = ($tieneNpi     ? ", U.NPI        AS USR_NPI"        : ", '' AS USR_NPI")
              . ($tieneLicense ? ", U.LICENSE_ID AS USR_LICENSE_ID" : ", '' AS USR_LICENSE_ID")
              . ($tieneFirma   ? ", U.FIRMA_IMG  AS USR_FIRMA_IMG"  : ", '' AS USR_FIRMA_IMG");
$idUserSesion = (int)($_SESSION['iduser'] ?? 0);

$sql = "SELECT
            P.IDPACIENTE, P.NOMBRES, P.APELLIDOS, P.FECHANACIMIENTO, P.SEX,
            P.EMAIL, P.TELEFONO, P.CEDULA, P.ADDRESS,
            A.FECHA_CITA, A.HORA_INICIO, A.IDDOCTOR, A.IDAGENCIA,
            D.NOMBRES  AS DOC_NOMBRES,
            D.APELLIDOS AS DOC_APELLIDOS
            $selDocCreds
            $selUsrCreds,
            AG.DESCRIPCION AS AGENCIA_NOMBRE,
            AG.DIRECCION  AS AGENCIA_DIRECCION,
            AG.TELEFONO   AS AGENCIA_TEL,
            TC.NOMBRES    AS TIPO_CONSULTA
        FROM AG_CITA A
        INNER JOIN AG_PACIENTE     P  ON A.IDPACIENTE      = P.IDPACIENTE
        LEFT  JOIN ADM_USUARIO     D  ON A.IDDOCTOR         = D.IDADM_USUARIO
        LEFT  JOIN ADM_USUARIO     U  ON U.IDADM_USUARIO    = $idUserSesion
        LEFT  JOIN ADM_AGENCIA     AG ON AG.IDAGENCIA        = COALESCE(A.IDAGENCIA, 1)
        LEFT  JOIN AG_TIPOCONSULTA TC ON A.IDTIPOCONSULTA   = TC.IDTIPOCONSULTA
        WHERE A.IDCITA = '$idCita'";

$res = $conexion->query($sql);
if (!$res || $res->num_rows == 0) {
    die(htmlspecialchars(t('att.errNotFound')));
}
$d = $res->fetch_assoc();

$sessionNombres   = $_SESSION['nombres']   ?? '';
$sessionApellidos = $_SESSION['apellidos'] ?? '';
$docNombreCompleto = trim($d['DOC_NOMBRES'] . ' ' . $d['DOC_APELLIDOS']);
$doctorAtiende = ($sessionNombres || $sessionApellidos)
    ? trim($sessionNombres . ' ' . $sessionApellidos)
    : $docNombreCompleto;

// Mapea el sexo del paciente a 0 (male) / 1 (female) para las tablas WHO/CDC.
$sexoTexto = strtolower(trim($d['SEX'] ?? ''));
if (in_array($sexoTexto, ['male','m','masculino','hombre'], true))       $pacienteSexIdx = 0;
elseif (in_array($sexoTexto, ['female','fame','f','femenino','mujer'], true)) $pacienteSexIdx = 1;
else                                                                          $pacienteSexIdx = -1;

// Credenciales para la firma: si el usuario logueado tiene NPI/License, se usan
// las suyas (él es quien firma); si no, se caen al doctor asignado a la cita.
$firmaNpi     = trim($d['USR_NPI'] ?? '') !== '' ? $d['USR_NPI'] : ($d['DOC_NPI'] ?? '');
$firmaLicense = trim($d['USR_LICENSE_ID'] ?? '') !== '' ? $d['USR_LICENSE_ID'] : ($d['DOC_LICENSE_ID'] ?? '');
$firmaImg     = trim($d['USR_FIRMA_IMG'] ?? '') !== '' ? $d['USR_FIRMA_IMG'] : ($d['DOC_FIRMA_IMG'] ?? '');
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Favicon de la app -->
    <link rel="icon" type="image/svg+xml" href="images/favicon.svg">
    <link rel="alternate icon" type="image/png" href="images/favicon.png">
    <link rel="apple-touch-icon" href="images/favicon.png">
    <title><?php te('att.title'); ?> - <?php echo htmlspecialchars($d['NOMBRES'].' '.$d['APELLIDOS']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <link href="main.css" rel="stylesheet">
    <script src="js/jquery.min.js"></script>
    <style>
        .unit-toggle .btn { padding: 2px 8px; font-size: 0.78rem; }
        .medicion-group { display: flex; gap: 6px; align-items: center; }
        .medicion-group input { max-width: 100px; }

        /* ── Tarjetas de medición (acorde a la plantilla de la app) ── */
        .att-metrics .att-tile {
            background:#fff; border:1px solid #e6e9f0; border-radius:12px;
            padding:12px 14px; height:100%; box-shadow:0 1px 2px rgba(16,31,85,.04);
        }
        .att-tile-label {
            font-size:.72rem; font-weight:700; text-transform:uppercase;
            letter-spacing:.04em; color:#5b6b8c; margin-bottom:6px; display:block;
        }
        #imc { font-size:1.15rem; }
        #estado_imc { width:100%; }
        .att-toolbar { background:#f6f8fc; border:1px solid #e6e9f0; border-radius:12px; }
        /* Editor y cabecera de marca */
        .note-editor.note-frame { border-radius:12px; border-color:#e6e9f0; }
        .att-actionbar { border-top:1px solid #eef1f6; padding-top:16px; margin-top:4px; }

        /* ── Dictado por voz ── */
        @keyframes micPulse { 0%,100%{opacity:1} 50%{opacity:.25} }
        .mic-pulse { animation: micPulse 1s infinite; display:inline-block; }
        #btnMic { transition: all .2s; }
        #interimText {
            max-width: 520px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
</head>
<body>
<div class="app-container app-theme-white body-tabs-shadow fixed-sidebar fixed-header">

    <!-- HEADER -->
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
                    <div class="widget-content p-0">
                        <div class="widget-content-wrapper">
                            <div class="widget-content-left ml-3 header-user-info">
                                <div class="widget-heading"><?php echo htmlspecialchars($sessionNombres); ?></div>
                                <div class="widget-subheading"><?php echo htmlspecialchars($_SESSION['rol'] ?? ''); ?></div>
                            </div>
                            <div class="widget-content-left ms-3">
                                <div class="btn-group">
                                    <a data-toggle="dropdown" class="p-0 btn" href="#">
                                        <i class="fa fa-angle-down ml-2 opacity-8"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a href="salir.php" class="dropdown-item"><?php te('menu.logout'); ?></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-main">
        <!-- SIDEBAR -->
        <div class="app-sidebar sidebar-shadow">
            <?php include("./menu/menu_adm.php"); ?>
        </div>

        <div class="app-main__outer">
            <div class="app-main__inner">

<div class="app-page-title">
    <div class="page-title-wrapper">
        <div class="page-title-heading">
            <div class="page-title-icon">
                <i class="pe-7s-note2 icon-gradient bg-tempting-azure"></i>
            </div>
            <div>
                <?php echo htmlspecialchars($d['NOMBRES'].' '.$d['APELLIDOS']); ?>
                <div class="page-title-subheading">
                    <i class="bi bi-person-lines-fill me-1"></i>
                    <?php te('att.title'); ?> &nbsp;·&nbsp; <?php te('att.appt'); ?> #<?php echo $idCita; ?>
                </div>
            </div>
        </div>
        <div class="page-title-actions">
            <span class="badge rounded-pill fs-6" style="background:linear-gradient(135deg,#0e1f55,#1a3a8c);color:#fff;padding:.55rem .9rem;">
                <i class="bi bi-hash"></i><?php echo $idCita; ?>
            </span>
        </div>
    </div>
</div>

<div class="main-card mb-3 card">
    <div class="card-body">

        <!-- ── MEDICIONES ──────────────────────────────────────────── -->
        <div class="row g-3 mb-4 att-metrics align-items-stretch">

            <div class="col-6 col-md-3">
                <div class="att-tile">
                    <span class="att-tile-label"><i class="bi bi-speedometer2 me-1"></i><?php te('att.weight'); ?></span>
                    <div class="medicion-group">
                        <input type="number" id="peso" class="form-control" step="0.1"
                               placeholder="0.0" oninput="calcularIMC()">
                        <div class="btn-group unit-toggle" role="group">
                            <input type="radio" class="btn-check" name="unidadPeso" id="uKg" value="kg" checked onchange="calcularIMC()">
                            <label class="btn btn-outline-secondary" for="uKg">kg</label>
                            <input type="radio" class="btn-check" name="unidadPeso" id="uLbs" value="lbs" onchange="calcularIMC()">
                            <label class="btn btn-outline-secondary" for="uLbs">lbs</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="att-tile">
                    <span class="att-tile-label"><i class="bi bi-rulers me-1"></i><?php te('att.heightLbl'); ?></span>
                    <div class="medicion-group">
                        <input type="number" id="talla" class="form-control" step="0.1"
                               placeholder="0.0" oninput="calcularIMC()">
                        <div class="btn-group unit-toggle" role="group">
                            <input type="radio" class="btn-check" name="unidadTalla" id="uCm" value="cm" checked onchange="calcularIMC()">
                            <label class="btn btn-outline-secondary" for="uCm">cm</label>
                            <input type="radio" class="btn-check" name="unidadTalla" id="uM" value="m" onchange="calcularIMC()">
                            <label class="btn btn-outline-secondary" for="uM">m</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-2">
                <div class="att-tile">
                    <span class="att-tile-label"><?php te('common.bmi'); ?></span>
                    <input type="text" id="imc" class="form-control bg-white fw-bold border-0 px-0" readonly>
                </div>
            </div>

            <div class="col-6 col-md-2">
                <div class="att-tile">
                    <span class="att-tile-label"><?php te('att.status'); ?></span>
                    <div id="estado_imc" class="badge p-2 d-block fs-6">---</div>
                </div>
            </div>

            <div class="col-12 col-md-2">
                <div class="att-tile">
                    <span class="att-tile-label"><?php te('att.reportTemplate'); ?></span>
                    <select id="selPlantilla" class="form-select" onchange="cargarPlantilla(this.value)">
                        <option value=""><?php te('att.selectNoteType'); ?></option>
                        <?php
                        $plantillas = $conexion->query(
                            "SELECT id, nombre_plantilla FROM cat_plantillas_nutricion ORDER BY categoria, nombre_plantilla"
                        );
                        while($p = $plantillas->fetch_assoc()):
                        ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nombre_plantilla']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- ── VALORACIÓN PEDIÁTRICA (auto, WHO 2-19 años) ───────── -->
        <div id="pedAssess" class="mb-3 d-none">
            <div class="card border-0" style="background:#e8f4f4;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <div class="fw-bold text-uppercase small" style="color:#0e2c3a;letter-spacing:.05em;">
                            <i class="bi bi-clipboard2-pulse me-1"></i>
                            <?php te('att.ped.title'); ?>
                        </div>
                        <button type="button" id="btnInsertPed"
                                class="btn btn-sm btn-outline-primary"
                                onclick="insertarTablaPediatrica()">
                            <i class="bi bi-file-earmark-plus me-1"></i>
                            <?php te('att.ped.insertIntoReport'); ?>
                        </button>
                    </div>
                    <div id="pedTableWrap"></div>
                </div>
            </div>
        </div>

        <!-- ── BARRA DE DICTADO ────────────────────────────────────── -->
        <div class="d-flex align-items-center gap-3 mb-2 p-2 att-toolbar">
            <button type="button" id="btnMic"
                    class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1"
                    onclick="toggleDictado()">
                <i class="bi bi-mic-fill"></i> <?php te('att.startDictation'); ?>
            </button>

            <!-- Toggle idioma -->
            <div class="d-flex align-items-center gap-1" title="<?php te('att.micLanguage'); ?>">
                <span id="langES" class="badge"
                      style="cursor:pointer;background:#0264d6;font-size:.7rem;"
                      onclick="setLang('es')">ES</span>
                <span id="langEN" class="badge bg-secondary"
                      style="cursor:pointer;font-size:.7rem;"
                      onclick="setLang('en')">EN</span>
            </div>

            <div id="micStatus" class="d-none d-flex align-items-center gap-2">
                <span class="badge bg-danger d-flex align-items-center gap-1">
                    <span class="mic-pulse">●</span> <?php te('att.listening'); ?>
                </span>
                <small id="interimText" class="text-muted fst-italic"></small>
            </div>

            <small class="text-muted ms-auto d-none d-sm-inline">
                <i class="bi bi-info-circle me-1"></i>
                Chrome / Edge
            </small>
            <button type="button"
                    class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1"
                    data-bs-toggle="modal" data-bs-target="#modalDictadoAyuda"
                    title="<?php te('att.helpBtn'); ?>">
                <i class="bi bi-question-circle"></i>
                <span class="d-none d-md-inline"><?php te('att.helpBtn'); ?></span>
            </button>
        </div>

        <!-- ── EDITOR ──────────────────────────────────────────────── -->
        <div class="mb-3">
            <textarea id="editorInforme" name="informe"></textarea>
        </div>

        <!-- ── BOTONES ─────────────────────────────────────────────── -->
        <div class="d-flex justify-content-between align-items-center att-actionbar flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="SCH_Calendar.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> <?php te('att.back'); ?>
                </a>
                <span class="badge rounded-pill" id="attTimeRange"
                      style="background:#eef1f6;color:#33475b;font-weight:600;font-size:.8rem;padding:.5rem .7rem;">
                    <i class="bi bi-clock me-1"></i>
                    <span id="attTimeText">—</span>
                </span>
                <div class="dropdown">
                    <button type="button" id="btnAjustarHora"
                            class="btn btn-outline-warning btn-sm d-flex align-items-center gap-1"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside"
                            aria-expanded="false" title="<?php te('att.adjustTip'); ?>">
                        <i class="bi bi-clock-history"></i>
                        <span><?php te('att.adjustBtn'); ?></span>
                    </button>
                    <div class="dropdown-menu p-3 shadow" style="min-width:260px;">
                        <label class="form-label small fw-semibold mb-1" for="inpHoraFin">
                            <?php te('att.adjustEndLabel'); ?>
                        </label>
                        <input type="time" id="inpHoraFin" class="form-control form-control-sm mb-2">
                        <div class="small text-muted mb-1"><?php te('att.adjustQuick'); ?></div>
                        <div class="btn-group btn-group-sm w-100 mb-2" role="group">
                            <button type="button" class="btn btn-outline-secondary" onclick="extenderCita(15)">+15</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="extenderCita(30)">+30</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="extenderCita(60)">+60</button>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary btn-sm flex-grow-1" onclick="ajustarHoraFin()">
                                <i class="bi bi-check-lg"></i> <?php te('att.adjustSave'); ?>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="bootstrap.Dropdown.getInstance(document.getElementById('btnAjustarHora'))?.hide()">
                                <?php te('att.adjustCancel'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-outline-info" type="button" onclick="abrirCalculadora()">
                    <i class="bi bi-calculator"></i> <?php te('nc.title'); ?>
                </button>
                <button class="btn btn-outline-primary" onclick="imprimirInforme()">
                    <i class="bi bi-printer"></i> <?php te('att.previewPrint'); ?>
                </button>
                <button class="btn btn-success px-4" onclick="guardarAtencion()">
                    <i class="bi bi-file-earmark-check"></i> <?php te('att.saveFinish'); ?>
                </button>
            </div>
        </div>

    </div>
</div><!-- /main-card -->

<!-- ══ MODAL CALCULADORA NUTRICIONAL ══════════════════════════════════ -->
<div class="modal fade" id="modalNutriCalc" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2" style="background:#5a2d82;">
                <h6 class="modal-title text-white mb-0"><i class="bi bi-calculator me-2"></i><?php te('nc.title'); ?></h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php include(__DIR__ . '/calculadora_nutricional_widget.php'); ?>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?php te('common.close'); ?></button>
            </div>
        </div>
    </div>
</div>

            </div><!-- /app-main__inner -->
        </div><!-- /app-main__outer -->
    </div><!-- /app-main -->
</div><!-- /app-container -->

<script>
const DATOS_CITA = {
    idCita:          "<?php echo $idCita; ?>",
    pacienteNombre:  "<?php echo addslashes(htmlspecialchars($d['NOMBRES'].' '.$d['APELLIDOS'])); ?>",
    pacienteDOB:     "<?php echo $d['FECHANACIMIENTO']; ?>",
    pacienteEmail:   "<?php echo addslashes($d['EMAIL']); ?>",
    pacienteTel:     "<?php echo addslashes($d['TELEFONO']); ?>",
    pacienteCedula:  "<?php echo addslashes($d['CEDULA']); ?>",
    pacienteSexIdx:  <?php echo (int)$pacienteSexIdx; ?>,
    horaInicio:      "<?php echo substr($d['HORA_INICIO'] ?? '', 0, 5); ?>",
    horaFin:         "<?php echo substr($d['HORA_FIN']    ?? '', 0, 5); ?>",
    docNombre:       "<?php echo addslashes($docNombreCompleto); ?>",
    docApellido:     "<?php echo addslashes($d['DOC_APELLIDOS']); ?>",
    docEspecialidad: "",
    atiendNombre:    "<?php echo addslashes($doctorAtiende); ?>",
    atiendApellido:  "<?php echo addslashes($sessionApellidos); ?>",
    agenciaNombre:   "<?php echo addslashes($d['AGENCIA_NOMBRE']); ?>",
    agenciaDirec:    "<?php echo addslashes($d['AGENCIA_DIRECCION']); ?>",
    agenciaTel:      "<?php echo addslashes($d['AGENCIA_TEL']); ?>",
    tipoConsulta:    "<?php echo addslashes($d['TIPO_CONSULTA']); ?>",
    fechaCita:       "<?php echo $d['FECHA_CITA']; ?>",
    fechaHoy:        "<?php echo date('d/m/Y'); ?>",
    firmaNpi:        "<?php echo addslashes($firmaNpi); ?>",
    firmaLicense:    "<?php echo addslashes($firmaLicense); ?>",
    firmaImg:        <?php echo json_encode($firmaImg ?: ''); ?>
};

// ── Textos traducibles (i18n) ────────────────────────────────────────
const ATT = {
    snLang:            <?php echo json_encode($snLang); ?>,
    editorPlaceholder: <?php echo json_encode(t('att.js.editorPlaceholder')); ?>,
    templateLoadError: <?php echo json_encode(t('att.js.templateLoadError')); ?>,
    emptyReport:       <?php echo json_encode(t('att.js.emptyReport')); ?>,
    confirmFinish:     <?php echo json_encode(t('att.js.confirmFinish')); ?>,
    savedOk:           <?php echo json_encode(t('att.js.savedOk')); ?>,
    savedPartial:      <?php echo json_encode(t('att.js.savedPartial')); ?>,
    saveError:         <?php echo json_encode(t('att.js.saveError')); ?>,
    saveConnError:     <?php echo json_encode(t('att.js.saveConnError')); ?>,
    reportTitle:       <?php echo json_encode(t('att.js.reportTitle')); ?>,
    noSpeech:          <?php echo json_encode(t('att.js.noSpeech')); ?>,
    micDenied:         <?php echo json_encode(t('att.js.micDenied')); ?>,
    underweight:       <?php echo json_encode(t('att.js.underweight')); ?>,
    normal:            <?php echo json_encode(t('att.js.normal')); ?>,
    overweight:        <?php echo json_encode(t('att.js.overweight')); ?>,
    obesity:           <?php echo json_encode(t('att.js.obesity')); ?>,
    extendOk:          <?php echo json_encode(t('att.js.extendOk')); ?>,
    extendOutOfRange:  <?php echo json_encode(t('att.js.extendOutOfRange')); ?>,
    extendError:       <?php echo json_encode(t('att.js.extendError')); ?>,
    adjustOk:          <?php echo json_encode(t('att.js.adjustOk')); ?>,
    endBeforeStart:    <?php echo json_encode(t('att.js.endBeforeStart')); ?>,
    invalidTime:       <?php echo json_encode(t('att.js.invalidTime')); ?>,
    ped: {
        inserted:      <?php echo json_encode(t('att.js.pedInserted')); ?>
    },
    // Etiquetas pediátricas WHO (2-19)
    imc: {
        severeUnderweight: <?php echo json_encode(t('att.js.pedSevereUnderweight')); ?>,
        underweight:       <?php echo json_encode(t('att.js.pedUnderweight')); ?>,
        healthyWeight:     <?php echo json_encode(t('att.js.pedHealthy')); ?>,
        overweight:        <?php echo json_encode(t('att.js.pedOverweight')); ?>,
        obesity:           <?php echo json_encode(t('att.js.pedObesity')); ?>,
        severeObesity:     <?php echo json_encode(t('att.js.pedSevereObesity')); ?>
    },
    startDictation:    <?php echo json_encode(t('att.startDictation')); ?>,
    stopDictation:     <?php echo json_encode(t('att.stopDictation')); ?>,
    drTitle:           <?php echo json_encode(t('att.drTitle')); ?>,
    ph: {
        biochem:           <?php echo json_encode(t('att.ph.biochem')); ?>,
        physical:          <?php echo json_encode(t('att.ph.physical')); ?>,
        clientHistory:     <?php echo json_encode(t('att.ph.clientHistory')); ?>,
        nutritionPlan:     <?php echo json_encode(t('att.ph.nutritionPlan')); ?>,
        objectives:        <?php echo json_encode(t('att.ph.objectives')); ?>,
        recommendations:   <?php echo json_encode(t('att.ph.recommendations')); ?>,
        diagnosis:         <?php echo json_encode(t('att.ph.diagnosis')); ?>,
        treatment:         <?php echo json_encode(t('att.ph.treatment')); ?>,
        nutritionDiagnosis:<?php echo json_encode(t('att.ph.nutritionDiagnosis')); ?>,
        intervention:      <?php echo json_encode(t('att.ph.intervention')); ?>,
        monitoring:        <?php echo json_encode(t('att.ph.monitoring')); ?>,
        nutritionHistory:  <?php echo json_encode(t('att.ph.nutritionHistory')); ?>,
        pesDiagnosis:      <?php echo json_encode(t('att.ph.pesDiagnosis')); ?>,
        prescription:      <?php echo json_encode(t('att.ph.prescription')); ?>,
        actionPlan:        <?php echo json_encode(t('att.ph.actionPlan')); ?>,
        indicator:         <?php echo json_encode(t('att.ph.indicator')); ?>,
        goal:              <?php echo json_encode(t('att.ph.goal')); ?>,
        progress:          <?php echo json_encode(t('att.ph.progress')); ?>
    }
};
</script>

<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script type="text/javascript" src="./assets/scripts/main.js"></script>
<script src="js/who_growth.js"></script>
<script src="js/cdc_growth.js"></script>
<script src="js/nutri_calc.js"></script>
<script src="js/nutri_calc_ui.js"></script>
<script>

// ── INICIALIZAR EDITOR ───────────────────────────────────────────────
$(document).ready(function(){
    $('#editorInforme').summernote({
        placeholder: ATT.editorPlaceholder,
        tabsize: 2,
        height: 520,
        lang: ATT.snLang,
        toolbar: [
            ['style',  ['style']],
            ['font',   ['bold','underline','clear']],
            ['color',  ['color']],
            ['para',   ['ul','ol','paragraph']],
            ['table',  ['table']],
            ['insert', ['link','picture']],
            ['view',   ['fullscreen','codeview','help']]
        ]
    });
});

// ── CALCULAR IMC ─────────────────────────────────────────────────────
// Edad del paciente en meses (a partir del DOB); null si no hay fecha válida.
function edadPacienteMeses(){
    var dobStr = DATOS_CITA.pacienteDOB || '';
    if (!/^\d{4}-\d{2}-\d{2}/.test(dobStr)) return null;
    var dob = new Date(dobStr + 'T00:00:00');
    if (isNaN(dob)) return null;
    var hoy = new Date();
    var meses = (hoy.getFullYear() - dob.getFullYear()) * 12 + (hoy.getMonth() - dob.getMonth());
    if (hoy.getDate() < dob.getDate()) meses -= 1;
    return meses;
}

// Última tabla pediátrica generada (para poder insertarla en el informe).
var _pedTablaHtml = null;

function renderPediatricAssessment(sexIdx, meses, pesoKg, tallaCm) {
    var wrap = document.getElementById('pedAssess');
    var body = document.getElementById('pedTableWrap');
    if (!wrap || !body) return;
    if (!window.WHO_GROWTH || meses == null || meses < 24 || meses > 228 || !(sexIdx === 0 || sexIdx === 1) || !(pesoKg > 0) || !(tallaCm > 0)) {
        wrap.classList.add('d-none');
        _pedTablaHtml = null;
        return;
    }
    var lang = (document.documentElement.lang || 'es').toLowerCase().slice(0, 2);
    var r = WHO_GROWTH.buildTable(sexIdx, meses, pesoKg, tallaCm, lang);
    if (!r || !r.html) { wrap.classList.add('d-none'); _pedTablaHtml = null; return; }
    body.innerHTML = r.html;
    _pedTablaHtml = r.html;
    wrap.classList.remove('d-none');
}

function insertarTablaPediatrica() {
    if (!_pedTablaHtml) return;
    var $ed = $('#editorInforme');
    if ($ed.length && $ed.summernote) {
        $ed.summernote('pasteHTML', '<div>' + _pedTablaHtml + '<br></div>');
        alert(ATT.ped.inserted);
    }
}

function calcularIMC(){
    const pesoInput  = parseFloat($('#peso').val());
    const tallaInput = parseFloat($('#talla').val());
    const uPeso  = $('input[name="unidadPeso"]:checked').val();
    const uTalla = $('input[name="unidadTalla"]:checked').val();

    var meses = edadPacienteMeses();
    var sexIdx = (typeof DATOS_CITA.pacienteSexIdx === 'number') ? DATOS_CITA.pacienteSexIdx : -1;

    if(!pesoInput || !tallaInput) { renderPediatricAssessment(sexIdx, meses, 0, 0); return; }
    const pesoKg  = uPeso  === 'lbs' ? pesoInput  * 0.453592 : pesoInput;
    const tallaM  = uTalla === 'cm'  ? tallaInput / 100       : tallaInput;
    const tallaCm = tallaM * 100;
    const imcNum  = pesoKg / (tallaM * tallaM);
    const imc     = imcNum.toFixed(2);
    $('#imc').val(imc);
    const est = $('#estado_imc');

    // Pediátrico (2-19 años): actualiza tarjeta con tabla PediTools-style + insignia.
    if (window.WHO_GROWTH && meses !== null && meses >= 24 && meses <= 228 && (sexIdx === 0 || sexIdx === 1)) {
        renderPediatricAssessment(sexIdx, meses, pesoKg, tallaCm);
        var r = WHO_BMI.classify(sexIdx, meses, imcNum);
        if (r) {
            // Regla adicional (AAP/CDC): IMC >= 35 kg/m² siempre es obesidad severa.
            if (imcNum >= 35 && r.key !== 'severeObesity') {
                r.key = 'severeObesity'; r.label = 'Severe obesity'; r.badgeClass = 'bg-dark';
            }
            var label = ATT.imc[r.key] || r.label;
            est.text(label).attr('class', 'badge p-2 d-block fs-6 ' + r.badgeClass)
               .attr('title', 'z=' + r.z.toFixed(2) + '  ·  P' + r.percentile.toFixed(1) + '  ·  WHO 2-19');
            return;
        }
    }
    // Adulto: oculta la tarjeta pediátrica
    renderPediatricAssessment(sexIdx, meses, 0, 0);

    // Adulto (>= 20 años) o pediátrico sin datos suficientes: clasificación adulto (OMS).
    est.removeAttr('title');
    if      (imcNum < 18.5) est.text(ATT.underweight).attr('class','badge p-2 d-block fs-6 bg-info');
    else if (imcNum < 25)   est.text(ATT.normal)     .attr('class','badge p-2 d-block fs-6 bg-success');
    else if (imcNum < 30)   est.text(ATT.overweight) .attr('class','badge p-2 d-block fs-6 bg-warning text-dark');
    else                    est.text(ATT.obesity)    .attr('class','badge p-2 d-block fs-6 bg-danger');
}

// Quita anchos/max-width fijos y márgenes-auto de los contenedores
// más externos del HTML de una plantilla, para que ocupe todo el ancho
// del editor en vez de verse encolumnada al centro.
function _expandirAnchoTemplate(html){
    var wrap = document.createElement('div');
    wrap.innerHTML = html;

    function limpiar(el){
        if (!el || !el.style) return;
        if (el.style.maxWidth) el.style.maxWidth = '';
        if (el.style.width && /^\s*\d+(\.\d+)?\s*px\s*$/i.test(el.style.width)) el.style.width = '';
        if (/auto/i.test(el.style.marginLeft) && /auto/i.test(el.style.marginRight)) {
            el.style.marginLeft = '';
            el.style.marginRight = '';
        }
        if (el.getAttribute && el.getAttribute('width') && /^\d+$/.test(el.getAttribute('width'))) {
            el.removeAttribute('width');
        }
    }

    // Nivel raíz y primer nivel (donde suele estar el wrapper max-width)
    Array.prototype.forEach.call(wrap.children, function(child){
        limpiar(child);
        if (child.tagName === 'DIV' || child.tagName === 'SECTION' || child.tagName === 'ARTICLE') {
            Array.prototype.forEach.call(child.children, limpiar);
        }
    });

    // <table> con width fijo → 100 %
    Array.prototype.forEach.call(wrap.querySelectorAll('table'), function(t){
        if (t.getAttribute('width') && /^\d+$/.test(t.getAttribute('width'))) t.removeAttribute('width');
        if (t.style && t.style.width && /^\s*\d+(\.\d+)?\s*px\s*$/i.test(t.style.width)) t.style.width = '100%';
        if (t.style && t.style.maxWidth) t.style.maxWidth = '';
    });

    return wrap.innerHTML;
}

// ── CARGAR PLANTILLA ─────────────────────────────────────────────────
function cargarPlantilla(id){
    if(!id) return;
    const uPeso  = $('input[name="unidadPeso"]:checked').val()  || 'kg';
    const uTalla = $('input[name="unidadTalla"]:checked').val() || 'cm';
    const pesoVal  = $('#peso').val()  || '---';
    const tallaVal = $('#talla').val() || '---';
    const imcVal   = $('#imc').val()   || '---';
    const fechaNacJS = new Date(DATOS_CITA.pacienteDOB + 'T00:00:00');
    const dobFormateada = fechaNacJS.toLocaleDateString('en-US',{month:'long',day:'numeric',year:'numeric'});
    // Firma: nombre del profesional + credenciales (NPI, License ID)
    const credsLines = [];
    if (DATOS_CITA.firmaNpi)     credsLines.push('NPI: '        + DATOS_CITA.firmaNpi);
    if (DATOS_CITA.firmaLicense) credsLines.push('License ID: ' + DATOS_CITA.firmaLicense);
    const credsHtml = credsLines.length
        ? credsLines.map(function(l){ return '<span>' + l + '</span>'; }).join('<br>') + '<br>'
        : '';
    const firmaImgHtml = DATOS_CITA.firmaImg
        ? '<img src="' + DATOS_CITA.firmaImg + '" alt="firma" style="max-height:80px;max-width:260px;margin-bottom:4px;"><br>'
        : '';
    const firmaHtml = `<br><br>
        <div style="margin-top:40px;border-top:1px solid #ccc;padding-top:10px;font-family:Arial,sans-serif;">
            ${firmaImgHtml}<strong>${DATOS_CITA.atiendNombre}</strong><br>
            ${credsHtml}
        </div>`;
    // Edad y sexo del paciente para plantillas
    var _meses = edadPacienteMeses();
    var edadStr = '';
    if (_meses !== null && _meses >= 0) {
        var _y = Math.floor(_meses / 12);
        var _m = _meses % 12;
        edadStr = _y + ' año' + (_y === 1 ? '' : 's') + ' y ' + _m + ' mes' + (_m === 1 ? '' : 'es');
    }
    var sexoStr = (DATOS_CITA.pacienteSexIdx === 1) ? 'Femenino' : (DATOS_CITA.pacienteSexIdx === 0 ? 'Masculino' : '');

    $.ajax({
        url: 'get_plantilla_html.php', type: 'GET', data: { id: id },
        success: function(html){
            // ¿La plantilla ya trae su propio bloque de firma?
            // Marcadores: cualquiera de los placeholders de firma o los textos habituales.
            const yaTraeFirma = /\{\{firma_(nombre|credenciales|npi|licencia|imagen)\}\}|Electronically Signed By|Firmado electr[oó]nicamente/i.test(html);
            // Si la plantilla ya trae la firma pero NO tiene {{firma_imagen}}, colocamos
            // la imagen justo antes de {{firma_nombre}} (o de "Electronically Signed By")
            // para que aparezca encima del nombre del profesional.
            if (yaTraeFirma && firmaImgHtml && html.indexOf('{{firma_imagen}}') === -1) {
                if (html.indexOf('{{firma_nombre}}') !== -1) {
                    html = html.replace('{{firma_nombre}}', firmaImgHtml + '{{firma_nombre}}');
                } else {
                    html = html.replace(/(Electronically Signed By|Firmado electr[oó]nicamente por)/i, firmaImgHtml + '$1');
                }
            }
            const vars = {
                '{{fecha_actual}}': DATOS_CITA.fechaHoy,
                '{{fecha_evaluacion}}': DATOS_CITA.fechaHoy,
                '{{fecha_cita}}': DATOS_CITA.fechaCita,
                '{{hora_inicio}}': DATOS_CITA.horaInicio || '',
                '{{hora_fin}}': DATOS_CITA.horaFin || '',
                '{{edad_paciente}}': edadStr,
                '{{sexo_paciente}}': sexoStr,
                '{{paciente_nombre}}': DATOS_CITA.pacienteNombre,
                '{{paciente_dob}}': dobFormateada,
                '{{paciente_email}}': DATOS_CITA.pacienteEmail,
                '{{paciente_telefono}}': DATOS_CITA.pacienteTel,
                '{{paciente_cedula}}': DATOS_CITA.pacienteCedula,
                '{{doctor_nombre}}': DATOS_CITA.docNombre,
                '{{nombre_doctor}}': DATOS_CITA.docNombre,
                '{{apellido_doctor}}': DATOS_CITA.docApellido,
                '{{titulo_doctor}}': ATT.drTitle,
                '{{especialidad}}': DATOS_CITA.docEspecialidad,
                '{{firma_nombre}}': DATOS_CITA.atiendNombre,
                '{{firma_credenciales}}': credsLines.join(' · '),
                '{{firma_npi}}': DATOS_CITA.firmaNpi,
                '{{firma_licencia}}': DATOS_CITA.firmaLicense,
                '{{firma_imagen}}': firmaImgHtml,
                '{{practica_nombre}}': DATOS_CITA.agenciaNombre,
                '{{direccion_1}}': DATOS_CITA.agenciaDirec,
                '{{direccion_2}}': '',
                '{{ciudad}}': DATOS_CITA.agenciaNombre,
                '{{estado}}': '',
                '{{codigo_postal}}': '',
                '{{telefono_clinica}}': DATOS_CITA.agenciaTel,
                '{{tipo_consulta}}': DATOS_CITA.tipoConsulta,
                '{{diagnostico_referencia}}': DATOS_CITA.tipoConsulta,
                '{{peso}}': `${pesoVal} ${uPeso}`,
                '{{talla}}': `${tallaVal} ${uTalla}`,
                '{{imc}}': imcVal,
                '{{antropometria}}': `<?php echo t('att.weight'); ?>: ${pesoVal} ${uPeso}, <?php echo t('att.heightLbl'); ?>: ${tallaVal} ${uTalla}, <?php echo t('common.bmi'); ?>: ${imcVal}`,
                '{{bioquimica}}': ATT.ph.biochem,
                '{{hallazgos_fisicos}}': ATT.ph.physical,
                '{{historial_cliente}}': ATT.ph.clientHistory,
                '{{plan_nutricional}}': ATT.ph.nutritionPlan,
                '{{objetivos}}': ATT.ph.objectives,
                '{{recomendaciones}}': ATT.ph.recommendations,
                '{{diagnostico}}': ATT.ph.diagnosis,
                '{{tratamiento}}': ATT.ph.treatment,
                '{{diagnostico_nutricional}}': ATT.ph.nutritionDiagnosis,
                '{{intervencion}}': ATT.ph.intervention,
                '{{monitoreo}}': ATT.ph.monitoring,
                '{{historial_nutricional_seguimiento}}': ATT.ph.nutritionHistory,
                '{{datos_seguimiento}}': `<?php echo t('att.weight'); ?>: ${pesoVal} ${uPeso}, <?php echo t('att.heightLbl'); ?>: ${tallaVal} ${uTalla}, <?php echo t('common.bmi'); ?>: ${imcVal}`,
                '{{diagnostico_pes_seguimiento}}': ATT.ph.pesDiagnosis,
                '{{prescripcion_nutricional}}': ATT.ph.prescription,
                '{{plan_accion_seguimiento}}': ATT.ph.actionPlan,
                '{{monitoreo_indicador}}': ATT.ph.indicator,
                '{{monitoreo_meta}}': ATT.ph.goal,
                '{{monitoreo_progreso}}': ATT.ph.progress,
                '{{req_energia}}': '---',
                '{{ingesta_energia}}': '---',
                '{{req_proteina}}': '---',
                '{{ingesta_proteina}}': '---',
                '{{req_fluidos}}': '---',
                '{{ingesta_fluidos}}': '---',
            };
            for(const [tag, val] of Object.entries(vars)){
                const regex = new RegExp(tag.replace(/[.*+?^${}()|[\]\\]/g,'\\$&'),'g');
                html = html.replace(regex, val);
            }
            // Sólo agregamos el bloque de firma auto-generado si la plantilla NO
            // trae uno propio; en caso contrario ya se sustituyó/insertó dentro
            // del propio texto de la plantilla (ver `yaTraeFirma` arriba).
            if (!yaTraeFirma) {
                html += firmaHtml;
            }

            // Gráficos CDC (Height / Weight / BMI for age) para plantillas pediátricas
            // en pacientes de 2 a 20 años. Se insertan justo antes del bloque de firma.
            var esPediatric = /pediatric|pediátric/i.test(html);
            var pesoKg  = uPeso  === 'lbs' ? (parseFloat(pesoVal)  || 0) * 0.453592 : (parseFloat(pesoVal)  || 0);
            var tallaCm = uTalla === 'm'   ? (parseFloat(tallaVal) || 0) * 100      : (parseFloat(tallaVal) || 0);
            var imcNum  = (tallaCm > 0) ? (pesoKg / ((tallaCm/100)*(tallaCm/100))) : 0;
            if (esPediatric && window.CDC_GROWTH && _meses !== null && _meses >= 24 && _meses <= 240
                && (DATOS_CITA.pacienteSexIdx === 0 || DATOS_CITA.pacienteSexIdx === 1)) {
                var langChart = ATT.snLang && ATT.snLang.indexOf('es') === 0 ? 'es' : 'en';
                var partes = [
                    CDC_GROWTH.buildSvg({ kind:'stat', sexIdx:DATOS_CITA.pacienteSexIdx, ageMonths:_meses, value: tallaCm > 0 ? tallaCm : null, lang: langChart }),
                    CDC_GROWTH.buildSvg({ kind:'wt',   sexIdx:DATOS_CITA.pacienteSexIdx, ageMonths:_meses, value: pesoKg  > 0 ? pesoKg  : null, lang: langChart }),
                    CDC_GROWTH.buildSvg({ kind:'bmi',  sexIdx:DATOS_CITA.pacienteSexIdx, ageMonths:_meses, value: imcNum  > 0 ? imcNum  : null, lang: langChart })
                ];
                var graficosHtml = '<div class="cdc-charts" style="margin-top:16px;">'
                    + partes.map(function(svg){ return '<div style="margin:8px 0;">' + svg + '</div>'; }).join('')
                    + '</div>';
                // Insertar antes del texto "Electronically Signed By" / firma dibujada;
                // si no encuentra el marcador, va al final.
                if (/Electronically Signed By/i.test(html)) {
                    html = html.replace(/(Electronically Signed By)/i, graficosHtml + '$1');
                } else if (firmaImgHtml && html.indexOf(firmaImgHtml) !== -1) {
                    html = html.replace(firmaImgHtml, graficosHtml + firmaImgHtml);
                } else {
                    html += graficosHtml;
                }
            }

            html = _expandirAnchoTemplate(html);
            $('#editorInforme').summernote('code', html);
        },
        error: function(){ alert(ATT.templateLoadError); }
    });
}

// ── GUARDAR ATENCIÓN ─────────────────────────────────────────────────
function guardarAtencion(){
    const contenido = $('#editorInforme').summernote('code');
    if(contenido.replace(/<[^>]+>/g,'').trim().length < 10){
        alert(ATT.emptyReport);
        return;
    }
    const uPeso  = $('input[name="unidadPeso"]:checked').val()  || 'kg';
    const uTalla = $('input[name="unidadTalla"]:checked').val() || 'cm';
    let pesoVal  = parseFloat($('#peso').val())  || 0;
    let tallaVal = parseFloat($('#talla').val()) || 0;
    const pesoKg  = uPeso  === 'lbs' ? pesoVal  * 0.453592 : pesoVal;
    const tallaCm = uTalla === 'm'   ? tallaVal * 100       : tallaVal;
    const imcVal  = parseFloat($('#imc').val()) || 0;
    if(!confirm(ATT.confirmFinish)) return;
    $.ajax({
        url: 'guardar_atencion.php', type: 'POST',
        data: { idCita: DATOS_CITA.idCita, informe: contenido,
                peso: pesoKg.toFixed(2), talla: tallaCm.toFixed(1), imc: imcVal },
        success: function(res){
            var r = (res || '').trim();
            if (r === 'OK') {
                alert(ATT.savedOk);
                window.location.href = 'SCH_Calendar.php';
            } else if (r.indexOf('PARCIAL:') === 0) {
                // Informe guardado, pero no se pudo marcar la cita como Atendida.
                alert(ATT.savedPartial + '\n\n' + r.substring(8).trim());
                window.location.href = 'SCH_Calendar.php';
            } else if (r === 'DATOS_INCOMPLETOS') {
                alert(ATT.emptyReport);
            } else if (r === 'SIN_SESION') {
                alert(ATT.saveConnError);
                window.location.href = 'index.php';
            } else {
                alert(ATT.saveError + ' ' + r);
            }
        },
        error: function(){ alert(ATT.saveConnError); }
    });
}

// ── AJUSTAR HORA FIN DE LA CITA ───────────────────────────────────────
function renderTimeRange(){
    var span = document.getElementById('attTimeText');
    if (!span) return;
    var ini = DATOS_CITA.horaInicio || '';
    var fin = DATOS_CITA.horaFin || '';
    if (!ini) { span.textContent = '—'; return; }
    span.textContent = fin ? (ini + '  →  ' + fin) : ini;
}

function _postAjuste(payload, feedbackOk){
    if (!DATOS_CITA.idCita) return;
    var btn = document.getElementById('btnAjustarHora');
    if (btn) btn.disabled = true;
    payload.idCita = DATOS_CITA.idCita;
    $.post('extender_cita.php', payload, function(res){
        if (btn) btn.disabled = false;
        var d = null;
        try { d = (typeof res === 'string') ? JSON.parse(res) : res; } catch(e){}
        if (d && d.ok) {
            DATOS_CITA.horaFin = d.horaFin;
            renderTimeRange();
            var inp = document.getElementById('inpHoraFin');
            if (inp) inp.value = d.horaFin;
            alert(feedbackOk.replace('{min}', d.minutos).replace('{fin}', d.horaFin));
            var dd = bootstrap.Dropdown.getInstance(btn);
            if (dd) dd.hide();
        } else {
            var err = (d && d.error) ? d.error : 'ERROR';
            if      (err === 'FUERA_DE_RANGO')   alert(ATT.extendOutOfRange);
            else if (err === 'FIN_ANTES_INICIO') alert(ATT.endBeforeStart);
            else if (err === 'HORA_INVALIDA')    alert(ATT.invalidTime);
            else if (err === 'SIN_SESION')       { alert(ATT.saveConnError); window.location.href='index.php'; }
            else                                   alert(ATT.extendError + ' ' + err);
        }
    }, 'json').fail(function(){
        if (btn) btn.disabled = false;
        alert(ATT.saveConnError);
    });
}

// Chips rápidos (+15 / +30 / +60) — mantiene el contrato antiguo
function extenderCita(min){
    _postAjuste({ minutos: (min || 30) }, ATT.extendOk);
}

// Hora fin exacta desde el input
function ajustarHoraFin(){
    var inp = document.getElementById('inpHoraFin');
    var val = inp ? inp.value : '';
    if (!/^([01]\d|2[0-3]):([0-5]\d)$/.test(val || '')) { alert(ATT.invalidTime); return; }
    _postAjuste({ horaFin: val }, ATT.adjustOk);
}

// Al abrir el dropdown, precargar el input con la hora fin actual
$(document).on('shown.bs.dropdown', function(ev){
    if (ev.target && ev.target.id === 'btnAjustarHora') {
        var inp = document.getElementById('inpHoraFin');
        if (inp && !inp.value) inp.value = DATOS_CITA.horaFin || '';
    }
});

// Renderizar rango al cargar la página
$(document).ready(renderTimeRange);

// ── CALCULADORA NUTRICIONAL ──────────────────────────────────────────
// Mover el modal a <body> para escapar del stacking context del
// .app-container (evita que el backdrop de Bootstrap tape el modal).
(function(){
    function moverAlBody(){
        var el = document.getElementById('modalNutriCalc');
        if (el && el.parentNode !== document.body) document.body.appendChild(el);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', moverAlBody);
    else moverAlBody();
})();

var _nutriCalcMounted = false;
function abrirCalculadora(){
    var modal = new bootstrap.Modal(document.getElementById('modalNutriCalc'));
    modal.show();
    if (!_nutriCalcMounted) {
        _nutriCalcMounted = true;
        NutriCalcUI.mount('#ncRoot', { lang: <?php echo json_encode(current_lang()); ?> });
    }
    // Prefill con datos del paciente actual
    var uPeso  = $('input[name="unidadPeso"]:checked').val()  || 'kg';
    var uTalla = $('input[name="unidadTalla"]:checked').val() || 'cm';
    var pesoV  = parseFloat($('#peso').val())  || 0;
    var tallaV = parseFloat($('#talla').val()) || 0;
    var pesoKg  = uPeso  === 'lbs' ? pesoV  * 0.453592 : pesoV;
    var tallaCm = uTalla === 'm'   ? tallaV * 100      : tallaV;
    // Edad en años (desde meses)
    var meses = edadPacienteMeses();
    var ageYrs = meses != null ? Math.floor(meses / 12) : 0;
    var sex = (DATOS_CITA.pacienteSexIdx === 1) ? 'F' : 'M';
    NutriCalcUI.prefill({ sex: sex, age: ageYrs, weightKg: pesoKg, heightCm: tallaCm });
}
// Handler global que la calculadora invoca al pulsar "Insertar en el informe"
window._nutriInsertHandler = function(html){
    $('#editorInforme').summernote('pasteHTML', html);
    var m = bootstrap.Modal.getInstance(document.getElementById('modalNutriCalc'));
    if (m) m.hide();
};

// ── IMPRIMIR ──────────────────────────────────────────────────────────
function imprimirInforme(){
    const contenido = $('#editorInforme').summernote('code');
    const ventana = window.open('','_blank','height=800,width=900');
    ventana.document.write(`<html><head>
        <title>${ATT.reportTitle} - ${DATOS_CITA.pacienteNombre}</title>
        <style>body{font-family:Arial,sans-serif;padding:40px;color:#333;}</style>
        </head><body>${contenido}</body></html>`);
    ventana.document.close();
    ventana.focus();
    setTimeout(() => ventana.print(), 500);
}

// ── DICTADO POR VOZ ───────────────────────────────────────────────────
let recognition   = null;
let dictadoActivo = false;
let savedRange    = null;   // guarda posición del cursor al iniciar dictado
let micLang       = 'es-EC';

function setLang(lang) {
    if (dictadoActivo) return; // no cambiar mientras escucha
    if (lang === 'es') {
        micLang = 'es-EC';
        document.getElementById('langES').style.background = '#0264d6';
        document.getElementById('langES').classList.remove('bg-secondary');
        document.getElementById('langEN').style.background = '';
        document.getElementById('langEN').classList.add('bg-secondary');
    } else {
        micLang = 'en-US';
        document.getElementById('langEN').style.background = '#198754';
        document.getElementById('langEN').classList.remove('bg-secondary');
        document.getElementById('langES').style.background = '';
        document.getElementById('langES').classList.add('bg-secondary');
    }
}

function toggleDictado() {
    if (!dictadoActivo) {
        iniciarDictado();
    } else {
        detenerDictado();
    }
}

function iniciarDictado() {
    const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SR) {
        alert(ATT.noSpeech);
        return;
    }

    // Guardar posición actual del cursor en el editor antes de empezar
    var sel = window.getSelection();
    savedRange = (sel && sel.rangeCount > 0) ? sel.getRangeAt(0).cloneRange() : null;

    recognition = new SR();
    recognition.lang         = micLang;
    recognition.continuous   = true;       // sigue escuchando sin timeout
    recognition.interimResults = true;     // muestra texto parcial en tiempo real

    recognition.onresult = function(event) {
        let interim = '';
        let finalText = '';
        for (let i = event.resultIndex; i < event.results.length; i++) {
            if (event.results[i].isFinal) {
                finalText += event.results[i][0].transcript + ' ';
            } else {
                interim += event.results[i][0].transcript;
            }
        }
        // Mostrar texto parcial en vivo
        document.getElementById('interimText').textContent = interim;

        // Insertar texto final en el editor Summernote
        if (finalText) {
            var $editable = $('.note-editable').first();
            $editable[0].focus();

            // Restaurar posición guardada; si no hay, ir al final
            var sel2 = window.getSelection();
            sel2.removeAllRanges();
            if (savedRange) {
                try { sel2.addRange(savedRange); } catch(e) { savedRange = null; }
            }
            if (!savedRange || sel2.rangeCount === 0) {
                var range = document.createRange();
                range.selectNodeContents($editable[0]);
                range.collapse(false);
                sel2.removeAllRanges();
                sel2.addRange(range);
            }

            $('#editorInforme').summernote('insertText', finalText);

            // Actualizar savedRange a la nueva posición (después del texto insertado)
            var sel3 = window.getSelection();
            savedRange = (sel3 && sel3.rangeCount > 0) ? sel3.getRangeAt(0).cloneRange() : null;

            document.getElementById('interimText').textContent = '';
        }
    };

    recognition.onerror = function(event) {
        if (event.error === 'no-speech') return; // silencio temporal, ignorar
        detenerDictado();
        if (event.error === 'not-allowed') {
            alert(ATT.micDenied);
        } else {
            console.warn('Error de reconocimiento:', event.error);
        }
    };

    // Si el browser detiene el reconocimiento y sigue activo → reiniciar
    recognition.onend = function() {
        if (dictadoActivo) recognition.start();
    };

    recognition.start();
    dictadoActivo = true;

    // UI: estado activo
    document.getElementById('btnMic').innerHTML =
        '<i class="bi bi-mic-mute-fill"></i> ' + ATT.stopDictation;
    document.getElementById('btnMic').className =
        'btn btn-danger btn-sm d-flex align-items-center gap-1';
    document.getElementById('micStatus').classList.remove('d-none');
}

function detenerDictado() {
    dictadoActivo = false;
    if (recognition) { recognition.stop(); recognition = null; }

    // UI: estado inactivo
    document.getElementById('btnMic').innerHTML =
        '<i class="bi bi-mic-fill"></i> ' + ATT.startDictation;
    document.getElementById('btnMic').className =
        'btn btn-outline-danger btn-sm d-flex align-items-center gap-1';
    document.getElementById('micStatus').classList.add('d-none');
    document.getElementById('interimText').textContent = '';
}
</script>

<!-- ── MODAL: Instrucciones de uso del dictado por voz ─────────── -->
<div class="modal fade" id="modalDictadoAyuda" tabindex="-1" aria-labelledby="modalDictadoAyudaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#0e1f55,#1a3a8c);color:#fff;">
                <h5 class="modal-title d-flex align-items-center gap-2" id="modalDictadoAyudaLabel">
                    <i class="bi bi-mic-fill"></i> <?php te('att.help.title'); ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?php te('common.close'); ?>"></button>
            </div>
            <div class="modal-body">

                <!-- Requisitos -->
                <h6 class="fw-bold text-primary mb-2"><i class="bi bi-check2-circle me-1"></i><?php te('att.help.req.title'); ?></h6>
                <ul class="mb-3">
                    <li><?php te('att.help.req.browser'); ?></li>
                    <li><?php te('att.help.req.mic'); ?></li>
                    <li><?php te('att.help.req.https'); ?></li>
                </ul>

                <!-- Pasos -->
                <h6 class="fw-bold text-primary mb-2"><i class="bi bi-list-ol me-1"></i><?php te('att.help.steps.title'); ?></h6>
                <ol class="mb-3">
                    <li><?php te('att.help.steps.s1'); ?></li>
                    <li><?php te('att.help.steps.s2'); ?></li>
                    <li><?php te('att.help.steps.s3'); ?></li>
                    <li><?php te('att.help.steps.s4'); ?></li>
                    <li><?php te('att.help.steps.s5'); ?></li>
                </ol>

                <!-- Sugerencias generales -->
                <h6 class="fw-bold text-primary mb-2"><i class="bi bi-lightbulb me-1"></i><?php te('att.help.tips.title'); ?></h6>
                <ul class="mb-3">
                    <li><?php te('att.help.tips.t1'); ?></li>
                    <li><?php te('att.help.tips.t2'); ?></li>
                    <li><?php te('att.help.tips.t3'); ?></li>
                    <li><?php te('att.help.tips.t4'); ?></li>
                    <li><?php te('att.help.tips.t5'); ?></li>
                </ul>

                <!-- Uso desde el celular -->
                <div class="alert alert-info d-flex gap-2 align-items-start mb-3">
                    <i class="bi bi-phone fs-4 flex-shrink-0"></i>
                    <div>
                        <div class="fw-bold mb-1"><?php te('att.help.mobile.title'); ?></div>
                        <ul class="mb-0 ps-3">
                            <li><?php te('att.help.mobile.m1'); ?></li>
                            <li><?php te('att.help.mobile.m2'); ?></li>
                            <li><?php te('att.help.mobile.m3'); ?></li>
                            <li><?php te('att.help.mobile.m4'); ?></li>
                            <li><?php te('att.help.mobile.m5'); ?></li>
                        </ul>
                    </div>
                </div>

                <!-- Problemas comunes -->
                <h6 class="fw-bold text-primary mb-2"><i class="bi bi-exclamation-triangle me-1"></i><?php te('att.help.trouble.title'); ?></h6>
                <ul class="mb-0">
                    <li><strong><?php te('att.help.trouble.p1.title'); ?>:</strong> <?php te('att.help.trouble.p1.body'); ?></li>
                    <li><strong><?php te('att.help.trouble.p2.title'); ?>:</strong> <?php te('att.help.trouble.p2.body'); ?></li>
                    <li><strong><?php te('att.help.trouble.p3.title'); ?>:</strong> <?php te('att.help.trouble.p3.body'); ?></li>
                    <li><strong><?php te('att.help.trouble.p4.title'); ?>:</strong> <?php te('att.help.trouble.p4.body'); ?></li>
                </ul>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal"><?php te('common.close'); ?></button>
            </div>
        </div>
    </div>
</div>

</body>
</html>
