<?php
ob_start();
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/lang/i18n.php");
$conexion = conectarse();

if(!isset($_SESSION["rol"])){
    header("Location: break.php");
    exit();
}

$now = time();
if(isset($_SESSION['expire']) && $now > $_SESSION['expire']){
    session_destroy();
    header("Location: expirada.php");
    exit();
}

$colorTipoDefault = '#3788d8';

$query = "SELECT
            A.IDCITA,
            A.IDPACIENTE,
            A.IDTIPOCONSULTA,
            A.IDDOCTOR,
            A.IDAGENCIA,
            CONCAT(B.NOMBRES, ' ', B.APELLIDOS) AS PACIENTE,
            B.TELEFONO,
            C.NOMBRES AS TIPO_CONSULTA,
            C.COLOR AS TIPO_COLOR,
            A.FECHA_CITA,
            A.HORA_INICIO,
            A.HORA_FIN,
            A.ESTADO_CITA,
            A.COMENTARIO,
            CONCAT(D.NOMBRES, ' ', D.APELLIDOS) AS DOCTOR,
            E.DESCRIPCION AS AGENCIA
          FROM AG_CITA A
          INNER JOIN AG_PACIENTE B     ON A.IDPACIENTE      = B.IDPACIENTE
          INNER JOIN AG_TIPOCONSULTA C ON A.IDTIPOCONSULTA  = C.IDTIPOCONSULTA
          INNER JOIN ADM_USUARIO D     ON A.IDDOCTOR        = D.IDADM_USUARIO
          LEFT  JOIN ADM_AGENCIA E     ON A.IDAGENCIA       = E.IDAGENCIA
          WHERE A.ESTADO = 'A'";

$resultado = $conexion->query($query);
$eventos   = array();

while ($row = $resultado->fetch_assoc()) {
    switch($row['ESTADO_CITA']) {
        case 'Pendiente':
        case 'Reagendada':              $colorEstado = '#212529'; break; // Negro  - Pendiente / Reagendada
        case 'Confirmada':              $colorEstado = '#6f42c1'; break; // Morado - Confirmada
        case 'A':                       $colorEstado = '#28a745'; break; // Verde  - Atendida
        case 'Cancelada':
        case 'Cancelado':               $colorEstado = '#fd7e14'; break; // Naranja - Cancelada
        case 'Atrasado':
        case 'Cancelación Tardía':      $colorEstado = '#ffc107'; break; // Ámbar  - Cancelación tardía
        case 'Cancelado por Profesional': $colorEstado = '#ff8a80'; break; // Salmón - Cancelado por el profesional
        case 'No Asistió':              $colorEstado = '#dc3545'; break; // Rojo   - No asistió
        default:                        $colorEstado = '#007bff'; break; // Azul
    }

    $colorTipo = !empty($row['TIPO_COLOR']) ? $row['TIPO_COLOR'] : $colorTipoDefault;

    $eventos[] = array(
        'id'              => $row['IDCITA'],
        'title'           => $row['PACIENTE'],
        'start'           => $row['FECHA_CITA'] . 'T' . $row['HORA_INICIO'],
        'end'             => $row['FECHA_CITA'] . 'T' . $row['HORA_FIN'],
        'backgroundColor' => $colorEstado,
        'borderColor'     => $colorTipo,
        'textColor'       => '#ffffff',
        'extendedProps'   => array(
            'cita'      => $row['ESTADO_CITA'],
            'medico'    => $row['DOCTOR'],
            'consulta'  => $row['TIPO_CONSULTA'],
            'telefono'  => $row['TELEFONO'],
            'comentario'=> $row['COMENTARIO'],
            'agencia'   => $row['AGENCIA'],
            'idpaciente'=> $row['IDPACIENTE'],
            'idtipoconsulta' => $row['IDTIPOCONSULTA'],
            'iddoctor'       => $row['IDDOCTOR'],
            'idagencia'      => $row['IDAGENCIA'],
        )
    );
}

$tiposConsultaActivos = array();
$resTipos = $conexion->query("SELECT IDTIPOCONSULTA, NOMBRES, COLOR FROM AG_TIPOCONSULTA WHERE ESTADO = 'A' ORDER BY NOMBRES");
while ($t = $resTipos->fetch_assoc()) {
    $tiposConsultaActivos[] = array(
        'id'     => $t['IDTIPOCONSULTA'],
        'nombre' => $t['NOMBRES'],
        'color'  => !empty($t['COLOR']) ? $t['COLOR'] : $colorTipoDefault,
    );
}

$sqlDoctores = "SELECT U.IDADM_USUARIO, U.NOMBRES, U.APELLIDOS
                FROM ADM_USUARIO U
                INNER JOIN ADM_ROL R ON U.IDADM_ROL = R.IDADM_ROL
                WHERE R.CARGO = 'DOCTOR'
                  AND U.ESTADO = 'A'
                ORDER BY U.NOMBRES";
$resultDoctores  = $conexion->query($sqlDoctores);
$doctoresActivos = array();
while ($d = $resultDoctores->fetch_assoc()) {
    $doctoresActivos[] = array(
        'id'     => $d['IDADM_USUARIO'],
        'nombre' => $d['NOMBRES'] . ' ' . $d['APELLIDOS'],
    );
}

$agenciasActivas = array();
$resAgencias = $conexion->query("SELECT IDAGENCIA, DESCRIPCION FROM ADM_AGENCIA WHERE ESTADO = 1 ORDER BY DESCRIPCION");
while ($a = $resAgencias->fetch_assoc()) {
    $agenciasActivas[] = array(
        'id'     => $a['IDAGENCIA'],
        'nombre' => $a['DESCRIPCION'],
    );
}
?>
<!doctype html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="utf-8">
    <!-- Favicon de la app -->
    <link rel="icon" type="image/svg+xml" href="images/favicon.svg">
    <link rel="alternate icon" type="image/png" href="images/favicon.png">
    <link rel="apple-touch-icon" href="images/favicon.png">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, shrink-to-fit=no">
    <title><?php te('cal.pageTitle'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/css/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <link href="./fullcalendar/main.css" rel="stylesheet">
    <link href="./main.css" rel="stylesheet">
    <script src="js/jquery.min.js"></script>
    <style>
        /* ── Modal Agendar Cita: campos claros y con borde (independiente del CDN) ── */
        #editModal .modal-body .form-label { font-weight: 600; color: #33475b; margin-bottom: .3rem; }
        #editModal .form-control,
        #editModal .form-select { min-height: 46px; border: 1px solid #ced4da; border-radius: .5rem; }

        /* Select2: garantizar apariencia aunque el tema del CDN no cargue */
        #editModal .select2-container { width: 100% !important; }
        #editModal .select2-container .select2-selection--single {
            height: 46px;
            display: flex;
            align-items: center;
            border: 1px solid #ced4da;
            border-radius: .5rem;
            background-color: #fff;
            padding: 0 .75rem;
        }
        #editModal .select2-container--open .select2-selection--single {
            border-color: #86b7fe;
            box-shadow: 0 0 0 .2rem rgba(13,110,253,.15);
        }
        #editModal .select2-selection--single .select2-selection__rendered {
            line-height: 44px !important;
            padding: 0 !important;
            color: #212529;
            font-size: 1rem;
        }
        #editModal .select2-selection__placeholder { color: #6c757d; }
        #editModal .select2-selection--single .select2-selection__arrow { height: 44px !important; right: 10px; }
        /* Dropdown de búsqueda */
        .select2-dropdown { border: 1px solid #ced4da; border-radius: .5rem; box-shadow: 0 4px 14px rgba(0,0,0,.12); }
        .select2-search__field { border: 1px solid #ced4da !important; border-radius: .375rem; padding: .4rem .6rem; }
        .select2-results__option { padding: .5rem .75rem; }
        .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: #1976d2; }

        .fc-event { cursor: pointer; font-size: 0.85em; padding: 2px 5px; }
        #eventModal .btn { transition: all 0.3s ease; white-space: nowrap; }
        #eventModal .btn:hover { transform: translateY(-2px); box-shadow: 0 3px 10px rgba(0,0,0,0.1); }
        /* Franja izquierda = tipo de consulta (el fondo del evento sigue indicando el estado) */
        .fc-daygrid-event, .fc-timegrid-event, .fc-list-event-dot {
            border-width: 0 0 0 5px !important;
            border-style: solid !important;
            border-radius: 3px;
        }
        .fc-list-event-dot { border-radius: 50%; border-width: 5px !important; }
        /* Leyenda de colores */
        .leyenda-calendario { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 6px; font-size: 0.8rem; }
        .leyenda-item { display: flex; align-items: center; gap: 5px; }
        .leyenda-dot { width: 12px; height: 12px; border-radius: 50%; display: inline-block; }
        .leyenda-dot-borde { width: 12px; height: 12px; border-radius: 50%; display: inline-block; background: #fff; border: 3px solid #999; box-sizing: border-box; }
        .leyenda-franja { width: 16px; height: 12px; display: inline-block; background: #fff; border: 1px solid #ddd; border-left: 5px solid #999; box-sizing: border-box; border-radius: 2px; }
        .leyenda-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: .04em; color: #888; font-weight: 600; margin-right: 4px; }

        /* ── Vista por Doctor ─────────────────────────────────────── */
        .cv-scroll { overflow-x: auto; border: 1px solid #dee2e6; border-radius: 4px; }
        .cv-grid { position: relative; }
        .cv-col-head {
            position: absolute; box-sizing: border-box;
            border-right: 1px solid #dee2e6; border-bottom: 1px solid #dee2e6;
            background: #f8f9fa; font-size: 0.78rem; text-align: center;
            display: flex; align-items: center; justify-content: center; overflow: hidden;
        }
        .cv-col-head.cv-day-head { font-weight: 600; background: #eef1f5; }
        .cv-col-head.cv-day-head.cv-today { background: #fff3cd; }
        .cv-time-label {
            position: absolute; width: 64px; box-sizing: border-box;
            font-size: 0.72rem; color: #6c757d; text-align: right; padding-right: 6px;
            border-right: 1px solid #dee2e6; border-bottom: 1px solid #f1f1f1;
        }
        .cv-cell {
            position: absolute; box-sizing: border-box;
            border-right: 1px solid #f1f1f1; border-bottom: 1px solid #f1f1f1;
        }
        .cv-cell.cv-day-end { border-right: 1px solid #dee2e6; }
        .cv-event {
            position: absolute; box-sizing: border-box; border-radius: 4px;
            color: #fff; font-size: 0.72rem; padding: 2px 4px; overflow: hidden;
            cursor: pointer; line-height: 1.2; box-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        .cv-event b { display: block; }
        .cv-doctores { display: flex; flex-wrap: wrap; gap: 10px 18px; align-items: center; font-size: 0.85rem; }
        .cv-doctores label { display: flex; align-items: center; gap: 5px; margin: 0; cursor: pointer; }

        /* ════════ Mejoras responsive móvil + citas legibles ════════ */
        .fc-event-main-custom { padding: 2px 4px; line-height: 1.25; overflow: hidden; }
        .fc-event-time-custom { font-weight: 700; font-size: 0.78em; }
        .fc-event-title-custom { font-weight: 600; font-size: 0.82em; white-space: normal; word-break: break-word; }
        .fc-event-sub-custom { font-size: 0.72em; opacity: 0.95; white-space: normal; }

        /* Vista de Mes: mostrar las citas como bloques legibles (no líneas) */
        .fc-daygrid-event { white-space: normal !important; align-items: stretch; }
        .fc-daygrid-event .fc-event-main-custom { padding: 3px 5px; }
        .fc-daygrid-event-harness { margin-bottom: 2px; }
        .fc-daygrid-day-events { min-height: 2em; }

        /* Vista Día/Semana: filas de hora más altas para aprovechar la pantalla (estilo agenda) */
        .fc .fc-timegrid-slot { height: 2.8em; }
        .fc .fc-timegrid-slot-label { font-size: 0.8rem; }

        /* Lista de consultas anteriores en el modal de cita (estilo tarjetas) */
        .cita-hist-lista { max-height: 280px; overflow-y: auto; border: 1px solid #eee; border-radius: 6px; }
        .cita-hist-card { display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-bottom: 1px solid #f1f1f1; }
        .cita-hist-card:last-child { border-bottom: none; }
        .cita-hist-main { flex: 1 1 auto; min-width: 0; }
        .cita-hist-fecha { font-weight: 600; font-size: 0.82rem; color: #343a40; }
        .cita-hist-sub { font-size: 0.74rem; color: #777; white-space: normal; }
        .cita-hist-estado { flex: 0 0 auto; }

        /* Lista de citas para móvil (Vista por Doctor) */
        .cv-list { display: none; }
        .cv-list-day { font-weight: 700; font-size: 0.9rem; margin: 14px 0 6px; padding-bottom: 4px; border-bottom: 2px solid #dee2e6; color: #343a40; }
        .cv-list-card { display: flex; border-radius: 6px; overflow: hidden; margin-bottom: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.12); cursor: pointer; }
        .cv-list-color { width: 6px; flex: 0 0 6px; }
        .cv-list-body { flex: 1; padding: 8px 10px; color: #fff; }
        .cv-list-body .hora { font-weight: 700; font-size: 0.82rem; }
        .cv-list-body .pac { font-weight: 600; font-size: 0.9rem; }
        .cv-list-body .sub { font-size: 0.76rem; opacity: 0.95; }

        @media (max-width: 768px) {
            .app-main__inner { padding: 10px !important; }
            .card-body { padding: 8px !important; }

            /* Cabecera y botones que no se monten */
            .page-title-heading > div { flex-wrap: wrap; }

            /* Leyenda más compacta para ganar espacio arriba */
            .leyenda-calendario { gap: 6px 10px; font-size: 0.68rem; margin-bottom: 4px; }
            .leyenda-label { font-size: 0.62rem; width: 100%; margin-bottom: 2px; }

            /* Barra del calendario en columna para que quepan todos los botones */
            .fc .fc-toolbar.fc-header-toolbar { flex-direction: column; gap: 8px; align-items: stretch; }
            .fc .fc-toolbar-title { font-size: 1.1rem; text-align: center; }
            .fc .fc-button { padding: 4px 8px; font-size: 0.8rem; }
            .fc .fc-toolbar-chunk { display: flex; justify-content: center; }

            /* En móvil: ocultar la cuadrícula horizontal de Vista por Doctor y mostrar la lista */
            .cv-scroll { display: none; }
            .cv-list { display: block; }
        }

        /* ── Buscador de pacientes (estilo Kalix) ── */
        .cal-patient-search { min-width: 280px; }
        @media (max-width: 575px){ .cal-patient-search { min-width: 100%; width: 100%; } }
        .cal-search-results {
            position: absolute; top: 100%; left: 0; right: 0; z-index: 1055;
            background: #fff; border: 1px solid #e6e9f0; border-radius: 10px;
            max-height: 360px; overflow-y: auto; margin-top: 4px;
        }
        .cal-search-results .csr-item { padding: 9px 12px; cursor: pointer; border-bottom: 1px solid #f1f3f8; }
        .cal-search-results .csr-item:last-child { border-bottom: 0; }
        .cal-search-results .csr-item:hover,
        .cal-search-results .csr-item.active { background: #f0f4ff; }
        .cal-search-results .csr-name { font-weight: 600; color: #23324d; }
        .cal-search-results .csr-meta { font-size: .78rem; color: #6c757d; }
        .cal-search-results .csr-empty { padding: 10px 12px; color: #6c757d; font-size: .85rem; }
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
            <div class="app-header-left">
                <ul class="header-menu nav">
                    <li class="nav-item">
                        <a href="javascript:void(0);" class="nav-link">
                            <i class="nav-link-icon fa fa-database"></i> Estadistica
                        </a>
                    </li>
                    <li class="dropdown nav-item">
                        <a href="javascript:void(0);" class="nav-link">
                            <i class="nav-link-icon fa fa-cog"></i> Configuracion
                        </a>
                    </li>
                </ul>
            </div>
            <div class="app-header-right">
                <div class="header-btn-lg pr-0">
                    <div class="widget-content p-0">
                        <div class="widget-content-wrapper">
                            <div class="widget-content-left">
                                <div class="btn-group">
                                    <a data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="p-0 btn">
                                        <img width="42" class="rounded-circle">
                                        <i class="fa fa-angle-down ml-2 opacity-8"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a href="perfil.php" class="dropdown-item"><?php te('hdr.userProfile'); ?></a>
                                        <button type="button" class="dropdown-item"><?php te('hdr.settings'); ?></button>
                                        <div class="dropdown-divider"></div>
                                        <a href="salir.php" class="dropdown-item"><?php te('menu.logout'); ?></a>
                                    </div>
                                </div>
                            </div>
                            <div class="widget-content-left ml-3 header-user-info">
                                <div class="widget-heading">
                                    <?php echo htmlspecialchars($_SESSION["username"] ?? ''); ?>
                                </div>
                                <div class="widget-subheading">
                                    <?php echo htmlspecialchars($_SESSION["rol"] ?? 'Usuario'); ?> — <?php echo date('d/m/Y'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
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
                <div class="app-page-title">
                    <div class="page-title-wrapper">
                        <div class="page-title-heading">
                            <div class="page-title-icon">
                                <i class="pe-7s-date icon-gradient bg-warm-flame"></i>
                            </div>
                            <div>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#editModal">
                                    <i class="bi bi-calendar-plus"></i> <?php te('cal.newAppointment'); ?>
                                </button>
                                <div class="btn-group ms-2" role="group">
                                    <button type="button" id="btnVistaCalendario" class="btn btn-outline-primary active">
                                        <i class="bi bi-calendar3"></i> <?php te('cal.viewCalendar'); ?>
                                    </button>
                                    <button type="button" id="btnVistaDoctor" class="btn btn-outline-primary">
                                        <i class="bi bi-people"></i> <?php te('cal.viewByDoctor'); ?>
                                    </button>
                                </div>
                                <div class="page-title-subheading"><?php te('cal.subheading'); ?></div>
                            </div>
                        </div>
                        <div class="page-title-actions">
                            <div class="cal-patient-search position-relative">
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                    <input type="text" id="calPacienteBuscar" class="form-control"
                                           placeholder="<?php te('cal.searchPatientTop'); ?>" autocomplete="off">
                                    <button type="button" class="btn btn-outline-secondary d-none" id="calPacienteClear" title="<?php te('plist.clear'); ?>">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                                <div id="calPacienteResultados" class="cal-search-results shadow d-none"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="main-card mb-3 card" id="viewFullCalendar">
                    <div class="card-body">
                        <!-- Leyenda de colores (colapsable, para ganar espacio arriba) -->
                        <button class="btn btn-sm btn-outline-secondary mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#leyendaColores1" aria-expanded="false">
                            <i class="bi bi-palette"></i> <?php te('cal.legendToggle'); ?>
                        </button>
                        <div class="collapse" id="leyendaColores1">
                            <div class="leyenda-calendario">
                                <span class="leyenda-label"><?php te('cal.legendStatus'); ?></span>
                                <span class="leyenda-item"><span class="leyenda-dot" style="background:#212529"></span> <?php te('cal.legendPendingResched'); ?></span>
                                <span class="leyenda-item"><span class="leyenda-dot" style="background:#6f42c1"></span> <?php echo estado_label('Confirmada'); ?></span>
                                <span class="leyenda-item"><span class="leyenda-dot" style="background:#28a745"></span> <?php echo estado_label('Atendida'); ?></span>
                                <span class="leyenda-item"><span class="leyenda-dot" style="background:#fd7e14"></span> <?php echo estado_label('Cancelada'); ?></span>
                                <span class="leyenda-item"><span class="leyenda-dot" style="background:#ffc107"></span> <?php echo estado_label('Cancelación Tardía'); ?></span>
                                <span class="leyenda-item"><span class="leyenda-dot" style="background:#ff8a80"></span> <?php echo estado_label('Cancelado por Profesional'); ?></span>
                                <span class="leyenda-item"><span class="leyenda-dot" style="background:#dc3545"></span> <?php echo estado_label('No Asistió'); ?></span>
                            </div>
                            <div class="leyenda-calendario">
                                <span class="leyenda-label"><?php te('cal.legendType'); ?></span>
                                <?php foreach ($tiposConsultaActivos as $tc): ?>
                                    <span class="leyenda-item"><span class="leyenda-franja" style="border-left-color:<?php echo htmlspecialchars($tc['color']); ?>"></span> <?php echo htmlspecialchars($tc['nombre']); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div id="calendar1"></div>
                    </div>
                </div>

                <div class="main-card mb-3 card d-none" id="viewPorDoctor">
                    <div class="card-body">
                        <!-- Leyenda de colores (colapsable, para ganar espacio arriba) -->
                        <button class="btn btn-sm btn-outline-secondary mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#leyendaColores2" aria-expanded="false">
                            <i class="bi bi-palette"></i> <?php te('cal.legendToggle'); ?>
                        </button>
                        <div class="collapse" id="leyendaColores2">
                            <div class="leyenda-calendario">
                                <span class="leyenda-label"><?php te('cal.legendStatus'); ?></span>
                                <span class="leyenda-item"><span class="leyenda-dot" style="background:#212529"></span> <?php te('cal.legendPendingResched'); ?></span>
                                <span class="leyenda-item"><span class="leyenda-dot" style="background:#6f42c1"></span> <?php echo estado_label('Confirmada'); ?></span>
                                <span class="leyenda-item"><span class="leyenda-dot" style="background:#28a745"></span> <?php echo estado_label('Atendida'); ?></span>
                                <span class="leyenda-item"><span class="leyenda-dot" style="background:#fd7e14"></span> <?php echo estado_label('Cancelada'); ?></span>
                                <span class="leyenda-item"><span class="leyenda-dot" style="background:#ffc107"></span> <?php echo estado_label('Cancelación Tardía'); ?></span>
                                <span class="leyenda-item"><span class="leyenda-dot" style="background:#ff8a80"></span> <?php echo estado_label('Cancelado por Profesional'); ?></span>
                                <span class="leyenda-item"><span class="leyenda-dot" style="background:#dc3545"></span> <?php echo estado_label('No Asistió'); ?></span>
                            </div>
                            <div class="leyenda-calendario">
                                <span class="leyenda-label"><?php te('cal.legendType'); ?></span>
                                <?php foreach ($tiposConsultaActivos as $tc): ?>
                                    <span class="leyenda-item"><span class="leyenda-franja" style="border-left-color:<?php echo htmlspecialchars($tc['color']); ?>"></span> <?php echo htmlspecialchars($tc['nombre']); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="cv-toolbar d-flex align-items-center gap-2 mb-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="cvToday"><?php te('cal.today'); ?></button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="cvPrev"><i class="bi bi-chevron-left"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="cvNext"><i class="bi bi-chevron-right"></i></button>
                            <strong id="cvRangeLabel" class="ms-2"></strong>
                        </div>
                        <div class="cv-scroll">
                            <div id="cvGrid" class="cv-grid"></div>
                        </div>
                        <div class="cv-list" id="cvList"></div>
                        <div class="cv-doctores mt-3" id="cvDoctorFiltros">
                            <span class="fw-semibold me-2"><?php te('cal.doctors'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── MODAL AGENDAR CITA ─────────────────────────────────────────── -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php te('cal.newAppointment'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="insertCita" method="POST" action="class/Insert_cita.php">
                    <div class="mb-3">
                        <label class="form-label"><?php te('cal.appDate'); ?></label>
                        <input type="date" class="form-control" name="fechafactura" id="fechafactura">
                        <script>document.getElementById('fechafactura').value = new Date().toISOString().substring(0, 10);</script>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php te('cal.patient'); ?></label>
                        <select class="form-select select-busqueda" name="IdPaciente" required>
                            <option value=""><?php te('cal.searchPatient'); ?></option>
                            <?php
                            $queryP = $conexion->query("SELECT IDPACIENTE, NOMBRES, APELLIDOS FROM AG_PACIENTE WHERE ESTADO = 'A' ORDER BY NOMBRES");
                            while ($v = $queryP->fetch_assoc()):
                            ?>
                            <option value="<?php echo $v['IDPACIENTE']; ?>"><?php echo htmlspecialchars($v['NOMBRES'].' '.$v['APELLIDOS']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php te('cal.startTime'); ?></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-clock"></i></span>
                            <select class="form-select select-busqueda" name="timeIni" id="timeIni" required>
                                <option value=""><?php te('cal.selectTime'); ?></option>
                                <?php
                                $inicio    = new DateTime('07:00');
                                $fin       = new DateTime('22:30');
                                $intervalo = new DateInterval('PT30M');
                                foreach (new DatePeriod($inicio, $intervalo, $fin) as $hora):
                                    $v = $hora->format('H:i');
                                ?>
                                <option value="<?php echo $v; ?>"><?php echo $hora->format('h:i A'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php te('cal.consultType'); ?></label>
                        <select class="form-select select-busqueda" name="Idconsulta" required>
                            <option value=""><?php te('cal.searchType'); ?></option>
                            <?php
                            $queryC = $conexion->query("SELECT IDTIPOCONSULTA, NOMBRES FROM AG_TIPOCONSULTA WHERE ESTADO = 'A' ORDER BY NOMBRES");
                            while ($v = $queryC->fetch_assoc()):
                            ?>
                            <option value="<?php echo $v['IDTIPOCONSULTA']; ?>"><?php echo htmlspecialchars($v['NOMBRES']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php te('cal.doctor'); ?></label>
                        <select class="form-select select-busqueda" name="IdDoctor" required>
                            <option value=""><?php te('cal.searchDoctor'); ?></option>
                            <?php foreach ($doctoresActivos as $v): ?>
                            <option value="<?php echo $v['id']; ?>"><?php echo htmlspecialchars($v['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php te('cal.location'); ?></label>
                        <select class="form-select select-busqueda" name="IdAgencia" required>
                            <option value=""><?php te('cal.selectLocation'); ?></option>
                            <?php
                            $queryAg = $conexion->query("SELECT IDAGENCIA, DESCRIPCION FROM ADM_AGENCIA WHERE ESTADO = 1 ORDER BY DESCRIPCION");
                            while ($v = $queryAg->fetch_assoc()):
                            ?>
                            <option value="<?php echo $v['IDAGENCIA']; ?>"><?php echo htmlspecialchars($v['DESCRIPCION']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-calendar-check"></i> <?php te('cal.scheduleBtn'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ── MODAL GESTIÓN CITA ─────────────────────────────────────────── -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php te('cal.manageTitle'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEstado" method="POST" action="class/actualizar_estado.php" onsubmit="return validarFormulario();">
                <div class="modal-body">
                    <div id="eventDetails" class="mb-3"></div>

                    <!-- Datos del paciente (edad, estatura, IMC, etc.) cargados por AJAX -->
                    <div id="citaPacienteInfo" class="mb-2"></div>

                    <!-- Panel Reagendar (oculto por defecto) -->
                    <div id="reagendarSection" class="d-none border rounded p-3 bg-light mt-2">
                        <h6 class="mb-3"><i class="bi bi-calendar2-event"></i> <?php te('cal.newDateTime'); ?></h6>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold"><?php te('cal.date'); ?></label>
                                <input type="date" id="nuevaFecha" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold"><?php te('cal.time'); ?></label>
                                <select id="nuevaHora" class="form-select"></select>
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <button type="button" class="btn btn-warning flex-grow-1" onclick="guardarReagenda()">
                                <i class="bi bi-calendar-check"></i> <?php te('cal.saveNewDate'); ?>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="cerrarReagendar()">
                                <?php te('common.cancel'); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Panel Editar Cita (oculto por defecto) -->
                    <div id="editarSection" class="d-none border rounded p-3 bg-light mt-2">
                        <h6 class="mb-1"><i class="bi bi-pencil-square"></i> <?php te('cal.editAppt'); ?></h6>
                        <p class="text-muted small mb-3"><?php te('cal.editApptHelp'); ?></p>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold"><?php te('cal.date'); ?></label>
                                <input type="date" id="editFecha" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold"><?php te('cal.time'); ?></label>
                                <input type="time" id="editHora" class="form-control" min="07:00" max="22:30">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold"><?php te('cal.consultTypeShort'); ?></label>
                                <select id="editTipoConsulta" class="form-select">
                                    <?php foreach ($tiposConsultaActivos as $tc): ?>
                                    <option value="<?php echo $tc['id']; ?>"><?php echo htmlspecialchars($tc['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold"><?php te('cal.doctor'); ?></label>
                                <select id="editDoctor" class="form-select">
                                    <?php foreach ($doctoresActivos as $d): ?>
                                    <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold"><?php te('cal.location'); ?></label>
                                <select id="editAgencia" class="form-select">
                                    <option value="0"><?php te('cal.unassigned'); ?></option>
                                    <?php foreach ($agenciasActivas as $a): ?>
                                    <option value="<?php echo $a['id']; ?>"><?php echo htmlspecialchars($a['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <button type="button" class="btn btn-info text-white flex-grow-1" onclick="guardarEdicion()">
                                <i class="bi bi-check2-square"></i> <?php te('common.saveChanges'); ?>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="cerrarEditar()">
                                <?php te('common.cancel'); ?>
                            </button>
                        </div>
                    </div>

                    <input type="hidden" id="idCita"     name="id">
                    <input type="hidden" id="estadoCita" name="estado">
                </div>
                <div class="modal-footer" id="modalFooterBtns">
                    <button id="btnAtender" class="btn btn-primary" type="button" onclick="irAConsulta()">
                        <i class="bi bi-person-check-fill"></i> <?php te('cal.attendPatient'); ?>
                    </button>
                    <a id="btnHistorial" href="#" class="btn btn-purple d-none"
                       style="background:#6f42c1;color:#fff;">
                        <i class="bi bi-clipboard2-pulse"></i> <?php te('cal.viewHistory'); ?>
                    </a>
                    <button id="btnEditarPaciente" class="btn btn-outline-primary" type="button" onclick="editarPacienteDesdeCita()">
                        <i class="bi bi-person-vcard"></i> <?php te('cal.editPatient'); ?>
                    </button>
                    <button id="btnEditar" class="btn btn-info text-white" type="button" onclick="toggleEditar()">
                        <i class="bi bi-pencil-square"></i> <?php te('common.edit'); ?>
                    </button>
                    <button id="btnReagendar" class="btn btn-warning" type="button" onclick="toggleReagendar()">
                        <i class="bi bi-calendar2-event"></i> <?php te('cal.reschedule'); ?>
                    </button>
                    <button id="btnConfirmar" class="btn btn-success" type="submit" onclick="setEstado('Confirmada')">
                        <i class="bi bi-check-circle"></i> <?php te('cal.confirm'); ?>
                    </button>
                    <button id="btnCancelar" class="btn btn-danger" type="submit" onclick="setEstado('Cancelada')">
                        <i class="bi bi-x-circle"></i> <?php te('common.cancel'); ?>
                    </button>
                    <div id="btnMasEstados" class="btn-group">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            <?php te('cal.otherStatus'); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><button class="dropdown-item" type="submit" onclick="setEstado('Reagendada')"><?php echo estado_label('Reagendada'); ?></button></li>
                            <li><button class="dropdown-item" type="submit" onclick="setEstado('Cancelación Tardía')"><?php echo estado_label('Cancelación Tardía'); ?></button></li>
                            <li><button class="dropdown-item" type="submit" onclick="setEstado('Cancelado por Profesional')"><?php echo estado_label('Cancelado por Profesional'); ?></button></li>
                            <li><button class="dropdown-item" type="submit" onclick="setEstado('No Asistió')"><?php echo estado_label('No Asistió'); ?></button></li>
                        </ul>
                    </div>
                    <button id="btnEliminar" class="btn btn-outline-danger" type="button" onclick="eliminarCita()">
                        <i class="bi bi-trash"></i> <?php te('cal.delete'); ?>
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php te('common.close'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── MODAL EDITAR PACIENTE ──────────────────────────────────────── -->
<div class="modal fade" id="modalEditarPaciente" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2" style="background:#5a2d82;">
                <h6 class="modal-title text-white mb-0">
                    <i class="bi bi-pencil-square me-2"></i><?php te('plist.editTitleModal'); ?>
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarPaciente">
                    <input type="hidden" id="epId" name="idPaciente">

                    <h6 class="text-muted mb-2"><i class="bi bi-person-vcard"></i> <?php te('pf.patientData'); ?></h6>
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
                            <input type="text" id="epSex" name="sex" class="form-control" placeholder="M / F">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold"><?php te('pf.gender'); ?></label>
                            <input type="text" id="epGender" name="gender" class="form-control">
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
                    </div>

                    <h6 class="text-muted mb-2"><i class="bi bi-journal-text"></i> <?php te('pf.notes'); ?></h6>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-danger">
                            <i class="bi bi-exclamation-triangle-fill"></i> <?php te('pf.alert'); ?>
                        </label>
                        <textarea id="epAlerta" name="alerta" class="form-control" rows="2"
                                  placeholder="<?php te('pf.alertPh'); ?>"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold"><?php te('pf.importantNotes'); ?></label>
                        <textarea id="epNotes" name="notes" class="form-control" rows="3"
                                  placeholder="<?php te('pf.importantPh'); ?>"></textarea>
                    </div>
                    <div class="mb-1">
                        <label class="form-label small fw-semibold"><?php te('pf.billingNotes'); ?></label>
                        <textarea id="epAddNotes" name="addNotes" class="form-control" rows="2"
                                  placeholder="<?php te('pf.billingPh'); ?>"></textarea>
                    </div>
                    <div id="epAlertaAviso" class="small text-muted d-none mt-1">
                        <i class="bi bi-info-circle"></i> <?php te('plist.alertNotSavedPre'); ?> <strong><?php te('pf.alert'); ?></strong> <?php te('plist.alertNotSavedMid'); ?>
                        <a href="migrar_notas_paciente.php" target="_blank"><?php te('plist.notesMigration'); ?></a>.
                    </div>
                </form>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?php te('common.cancel'); ?></button>
                <button type="button" class="btn btn-primary btn-sm" id="epGuardar" onclick="guardarPacienteCita()">
                    <i class="bi bi-check-lg"></i> <?php te('common.saveChanges'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── MODAL VER INFORME ──────────────────────────────────────────── -->
<div class="modal fade" id="modalInforme" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title"><i class="bi bi-file-earmark-text me-2"></i><?php te('plist.reportTitle'); ?></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="cuerpoInforme">
                <div class="text-center py-4"><div class="spinner-border spinner-border-sm"></div></div>
            </div>
            <div class="modal-footer py-2">
                <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-printer"></i> <?php te('common.print'); ?>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?php te('common.close'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts — cargados UNA sola vez, en orden correcto -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="./fullcalendar/main.js"></script>
<script src="./fullcalendar/locales/es.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script type="text/javascript" src="./assets/scripts/main.js"></script>

<script>
// ── i18n (inyectado desde PHP) ───────────────────────────────────────
const CAL_LANG = <?php echo json_encode(current_lang()); ?>;
const CAL_DIAS  = <?php echo json_encode(current_lang() === 'es'
    ? ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado']
    : ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday']); ?>;
const CAL_MESES = <?php echo json_encode(current_lang() === 'es'
    ? ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre']
    : ['January','February','March','April','May','June','July','August','September','October','November','December']); ?>;
const ESTADO_LABELS = {
    'Pendiente':                   <?php echo json_encode(estado_label('Pendiente')); ?>,
    'Reagendada':                  <?php echo json_encode(estado_label('Reagendada')); ?>,
    'Confirmada':                  <?php echo json_encode(estado_label('Confirmada')); ?>,
    'A':                           <?php echo json_encode(estado_label('Atendida')); ?>,
    'Cancelada':                   <?php echo json_encode(estado_label('Cancelada')); ?>,
    'Cancelado':                   <?php echo json_encode(estado_label('Cancelado')); ?>,
    'Atrasado':                    <?php echo json_encode(estado_label('Atrasado')); ?>,
    'Cancelación Tardía':          <?php echo json_encode(estado_label('Cancelación Tardía')); ?>,
    'Cancelado por Profesional':   <?php echo json_encode(estado_label('Cancelado por Profesional')); ?>,
    'No Asistió':                  <?php echo json_encode(estado_label('No Asistió')); ?>,
};
const TC = <?php echo json_encode(array(
    'lblPatient'      => t('cal.js.lblPatient'),
    'lblPhone'        => t('cal.js.lblPhone'),
    'lblStart'        => t('cal.js.lblStart'),
    'lblDoctor'       => t('cal.js.lblDoctor'),
    'lblLocation'     => t('cal.js.lblLocation'),
    'lblStatus'       => t('cal.js.lblStatus'),
    'lblType'         => t('cal.js.lblType'),
    'lblComment'      => t('cal.js.lblComment'),
    'notRegisteredM'  => t('cal.js.notRegisteredM'),
    'notRegisteredF'  => t('cal.js.notRegisteredF'),
    'confirmWhatsapp' => t('cal.js.confirmWhatsapp'),
    'noWhatsapp'      => t('cal.js.noWhatsapp'),
    'loadingPatient'  => t('cal.js.loadingPatient'),
    'couldNotLoadPat' => t('cal.js.couldNotLoadPat'),
    'consultations'   => t('cal.js.consultations'),
    'noApptsWeek'     => t('cal.js.noApptsWeek'),
    'unassigned'      => t('cal.unassigned'),
    'waPre'           => t('cal.js.waPre'),
    'waMid'           => t('cal.js.waMid'),
    'waAt'            => t('cal.js.waAt'),
    'waEnd'           => t('cal.js.waEnd'),
    'selectDateTime'  => t('cal.js.selectDateTime'),
    'confirmReschedPre'=> t('cal.js.confirmReschedPre'),
    'at'              => t('cal.js.at'),
    'reschedOk'       => t('cal.js.reschedOk'),
    'reschedNoEmail'  => t('cal.js.reschedNoEmail'),
    'reschedError'    => t('cal.js.reschedError'),
    'confirmSavePre'  => t('cal.js.confirmSavePre'),
    'updatedOk'       => t('cal.js.updatedOk'),
    'updatedEmail'    => t('cal.js.updatedEmail'),
    'updatedNoEmail'  => t('cal.js.updatedNoEmail'),
    'editError'       => t('cal.js.editError'),
    'confirmDelete'   => t('cal.js.confirmDelete'),
    'deletedOk'       => t('cal.js.deletedOk'),
    'deletedNoEmail'  => t('cal.js.deletedNoEmail'),
    'deleteError'     => t('cal.js.deleteError'),
    'deleteConnError' => t('cal.js.deleteConnError'),
    'noPatientId'     => t('cal.js.noPatientId'),
    'patientUpdatedOk'=> t('cal.js.patientUpdatedOk'),
    'formIncomplete'  => t('cal.js.formIncomplete'),
    'completeFields'  => t('cal.js.completeFields'),
    'timeRange'       => t('cal.js.timeRange'),
    'apptGone'        => t('cal.js.apptGone'),
    'slotTaken'       => t('cal.js.slotTaken'),
    'apptGonePrev'    => t('cal.js.apptGonePrev'),
    'connError'       => t('common.js.connError'),
    'loadError'       => t('plist.js.loadError'),
    'loadHttp'        => t('plist.js.loadHttp'),
    'nameRequired'    => t('plist.js.nameRequired'),
    'updatedNoAlert'  => t('plist.js.updatedNoAlert'),
    'incomplete'      => t('plist.js.incomplete'),
    'saveError'       => t('plist.js.saveError'),
)); ?>;

// ── Helpers ──────────────────────────────────────────────────────────
function estadoBadge(estado) {
    const map = {
        'Pendiente':                   '#212529',
        'Reagendada':                  '#212529',
        'Confirmada':                  '#6f42c1',
        'A':                           '#28a745',
        'Cancelada':                   '#fd7e14',
        'Cancelado':                   '#fd7e14',
        'Atrasado':                    '#ffc107',
        'Cancelación Tardía':          '#ffc107',
        'Cancelado por Profesional':   '#ff8a80',
        'No Asistió':                  '#dc3545',
    };
    const color = map[estado] || '#6c757d';
    const label = ESTADO_LABELS[estado] || estado;
    return `<span class="badge" style="background:${color}">${label}</span>`;
}

function setEstado(estado) {
    document.getElementById('estadoCita').value = estado;
}

function irAConsulta() {
    const id = document.getElementById('idCita').value;
    window.location.href = `atencion_paciente.php?idCita=${id}`;
}

// ── Reagendar ────────────────────────────────────────────────────────
function generarHoras(selectId) {
    const sel = document.getElementById(selectId);
    if (sel.options.length > 0) return; // ya generadas
    let h = 7, m = 0;
    while (h < 22 || (h === 22 && m === 0)) {
        const val  = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}`;
        const ampm = h < 12 ? 'AM' : 'PM';
        const h12  = h > 12 ? h - 12 : (h === 0 ? 12 : h);
        const lbl  = `${String(h12).padStart(2,'0')}:${String(m).padStart(2,'0')} ${ampm}`;
        sel.add(new Option(lbl, val));
        m += 30;
        if (m >= 60) { m = 0; h++; }
    }
}

function toggleReagendar() {
    const section = document.getElementById('reagendarSection');
    const hidden  = section.classList.contains('d-none');
    cerrarEditar();
    section.classList.toggle('d-none', !hidden);
    if (hidden) {
        generarHoras('nuevaHora');
        document.getElementById('nuevaFecha').value = new Date().toISOString().substring(0, 10);
    }
}

function cerrarReagendar() {
    document.getElementById('reagendarSection').classList.add('d-none');
}

function guardarReagenda() {
    const id    = document.getElementById('idCita').value;
    const fecha = document.getElementById('nuevaFecha').value;
    const hora  = document.getElementById('nuevaHora').value;

    if (!fecha || !hora) {
        alert(TC.selectDateTime);
        return;
    }

    // Formatear fecha para confirmar
    const [y, mo, d] = fecha.split('-');
    if (!confirm(`${TC.confirmReschedPre} ${d}/${mo}/${y} ${TC.at} ${hora}?`)) return;

    $.post('reagendar_cita.php', { idCita: id, fecha: fecha, hora: hora }, function(res) {
        res = res.trim();
        if (res === 'OK') {
            alert(TC.reschedOk);
            location.reload();
        } else if (res === 'OK_SIN_CORREO') {
            alert(TC.reschedNoEmail);
            location.reload();
        } else if (res === 'NO_ENCONTRADA') {
            alert(TC.apptGone);
            location.reload();
        } else {
            alert(TC.reschedError + res);
        }
    }).fail(function() {
        alert(TC.connError);
    });
}

// ── Editar cita (corregir datos sin cambiar el estado) ───────────────
function toggleEditar() {
    const section = document.getElementById('editarSection');
    const hidden  = section.classList.contains('d-none');
    cerrarReagendar();
    section.classList.toggle('d-none', !hidden);
    if (hidden && citaActual) {
        document.getElementById('editFecha').value        = citaActual.fecha;
        document.getElementById('editHora').value         = citaActual.hora;
        document.getElementById('editTipoConsulta').value = citaActual.idtipoconsulta || '';
        document.getElementById('editDoctor').value       = citaActual.iddoctor || '';
        document.getElementById('editAgencia').value      = citaActual.idagencia || '0';
    }
}

function cerrarEditar() {
    document.getElementById('editarSection').classList.add('d-none');
}

function guardarEdicion() {
    const id      = document.getElementById('idCita').value;
    const fecha   = document.getElementById('editFecha').value;
    const hora    = document.getElementById('editHora').value;
    const tipo    = document.getElementById('editTipoConsulta').value;
    const doctor  = document.getElementById('editDoctor').value;
    const agencia = document.getElementById('editAgencia').value;

    if (!fecha || !hora || !tipo || !doctor) {
        alert(TC.completeFields);
        return;
    }

    if (hora < '07:00' || hora > '22:30') {
        alert(TC.timeRange);
        return;
    }

    const [y, mo, d] = fecha.split('-');
    if (!confirm(`${TC.confirmSavePre} ${d}/${mo}/${y} ${TC.at} ${hora}.`)) return;

    $.post('editar_cita.php', {
        idCita: id,
        fecha: fecha,
        hora: hora,
        idTipoConsulta: tipo,
        idDoctor: doctor,
        idAgencia: agencia
    }, function(res) {
        res = res.trim();
        if (res === 'OK') {
            alert(TC.updatedOk);
            location.reload();
        } else if (res === 'OK_MODIFICADA') {
            alert(TC.updatedEmail);
            location.reload();
        } else if (res === 'OK_SIN_CORREO') {
            alert(TC.updatedNoEmail);
            location.reload();
        } else if (res === 'HORARIO_OCUPADO') {
            alert(TC.slotTaken);
        } else if (res === 'HORA_FUERA_RANGO') {
            alert(TC.timeRange);
        } else if (res === 'NO_ENCONTRADA') {
            alert(TC.apptGone);
            location.reload();
        } else {
            alert(TC.editError + res);
        }
    }).fail(function() {
        alert(TC.connError);
    });
}

// ── Eliminar cita (borra del calendario y notifica al paciente) ──────
function eliminarCita() {
    const id = document.getElementById('idCita').value;
    if (!id) return;

    if (!confirm(TC.confirmDelete)) return;

    $.post('eliminar_cita.php', { idCita: id }, function(res) {
        res = res.trim();
        if (res === 'OK') {
            alert(TC.deletedOk);
            location.reload();
        } else if (res === 'OK_SIN_CORREO') {
            alert(TC.deletedNoEmail);
            location.reload();
        } else if (res === 'NO_ENCONTRADA') {
            alert(TC.apptGonePrev);
            location.reload();
        } else {
            alert(TC.deleteError + res);
        }
    }).fail(function() {
        alert(TC.deleteConnError);
    });
}

// ── Editar Paciente desde el modal de Gestión de Cita ────────────────
let epModal = null;

function editarPacienteDesdeCita() {
    const idPaciente = citaActual ? citaActual.idpaciente : null;
    if (!idPaciente) { alert(TC.noPatientId); return; }

    if (!epModal) epModal = new bootstrap.Modal(document.getElementById('modalEditarPaciente'));

    document.getElementById('formEditarPaciente').reset();
    document.getElementById('epId').value = idPaciente;

    $.getJSON('get_paciente.php', { id: idPaciente })
        .done(function(p) {
            if (p.error) { alert(TC.loadError + p.error); return; }
            document.getElementById('epNombres').value   = p.NOMBRES   || '';
            document.getElementById('epApellidos').value = p.APELLIDOS || '';
            document.getElementById('epCedula').value    = p.CEDULA    || '';
            document.getElementById('epTelefono').value  = p.TELEFONO  || '';
            document.getElementById('epFecNac').value    = p.FECHANACIMIENTO || '';
            document.getElementById('epEmail').value     = p.EMAIL     || '';
            document.getElementById('epSex').value       = p.SEX       || '';
            document.getElementById('epGender').value    = p.GENDER    || '';
            document.getElementById('epAddress').value   = p.ADDRESS   || '';
            document.getElementById('epIdioma').value    = (p.IDIOMA === 'en') ? 'en' : 'es';
            document.getElementById('epAlerta').value    = p.ALERTA    || '';
            document.getElementById('epNotes').value     = p.NOTES     || '';
            document.getElementById('epAddNotes').value  = p.ADDNOTES  || '';

            document.getElementById('epAlertaAviso').classList.toggle('d-none', p._tieneAlerta !== false);

            // Ocultar el modal de cita para que no se monten los backdrops
            const evtModalEl = document.getElementById('eventModal');
            const evtModal   = bootstrap.Modal.getInstance(evtModalEl);
            if (evtModal) evtModal.hide();

            epModal.show();
        })
        .fail(function(xhr) {
            alert(TC.loadHttp + xhr.status + ').');
        });
}

function guardarPacienteCita() {
    const nombres   = document.getElementById('epNombres').value.trim();
    const apellidos = document.getElementById('epApellidos').value.trim();
    if (!nombres || !apellidos) {
        alert(TC.nameRequired);
        return;
    }

    const btn = document.getElementById('epGuardar');
    btn.disabled = true;

    $.post('editar_paciente.php', $('#formEditarPaciente').serialize(), function(res) {
        res = res.trim();
        if (res === 'OK' || res === 'OK_SIN_ALERTA') {
            if (res === 'OK_SIN_ALERTA') {
                alert(TC.updatedNoAlert);
            } else {
                alert(TC.patientUpdatedOk);
            }
            location.reload();
        } else if (res === 'DATOS_INCOMPLETOS') {
            alert(TC.incomplete);
            btn.disabled = false;
        } else {
            alert(TC.saveError + res);
            btn.disabled = false;
        }
    }).fail(function() {
        alert(TC.connError);
        btn.disabled = false;
    });
}

function validarFormulario() {
    const id     = document.getElementById('idCita').value;
    const estado = document.getElementById('estadoCita').value;
    if (!id || !estado) {
        alert(TC.formIncomplete);
        return false;
    }
    return true;
}

// ── Datos compartidos (FullCalendar + Vista por Doctor) ───────────────
const eventosAll      = <?php echo json_encode($eventos); ?>;
const doctoresActivos = <?php echo json_encode($doctoresActivos); ?>;
// Doctor con la sesión iniciada (si el usuario logueado es DOCTOR): su columna se muestra por defecto
const loginDoctorId   = <?php echo json_encode((strtoupper($_SESSION['rol'] ?? '') === 'DOCTOR') ? (string)($_SESSION['iduser'] ?? '') : ''); ?>;

// ── Modal de gestión de cita (usado por ambas vistas) ──────────────────
const ESTADOS_CERRADOS = ['A', 'Cancelada', 'Cancelado', 'Cancelación Tardía', 'Cancelado por Profesional', 'No Asistió'];

let citaActual = null; // datos de la cita abierta en el modal (para prellenar el panel de edición)

function abrirModalCita(id, title, startDate, p) {
    const est      = p.cita || 'Pendiente';
    const atendida = est === 'A';
    const cerrada  = ESTADOS_CERRADOS.includes(est);

    // Limpiar y formatear teléfono para WhatsApp
    let tel = p.telefono ? p.telefono.replace(/\D/g, '') : '';
    if (tel.length === 9 && tel.startsWith('9'))        tel = '593' + tel;
    else if (tel.length === 10 && tel.startsWith('09')) tel = '593' + tel.substring(1);

    const msg = encodeURIComponent(
        `${TC.waPre} ${p.consulta} ${TC.waMid} ` +
        `${startDate.toLocaleDateString()} ${TC.waAt} ` +
        `${startDate.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}. ${TC.waEnd}`
    );

    document.getElementById('eventDetails').innerHTML = `
        <div class="row">
            <div class="col-md-8">
                <p><strong>${TC.lblPatient}</strong> ${title}</p>
                <p><strong>${TC.lblPhone}</strong> ${p.telefono || TC.notRegisteredM}</p>
                <p><strong>${TC.lblStart}</strong> ${startDate.toLocaleString()}</p>
                <p><strong>${TC.lblDoctor}</strong> ${p.medico}</p>
                <p><strong>${TC.lblLocation}</strong> ${p.agencia || TC.notRegisteredF}</p>
                <p><strong>${TC.lblStatus}</strong> ${estadoBadge(est)}</p>
            </div>
            <div class="col-md-4 text-center">
                ${tel
                    ? `<a href="https://wa.me/${tel}?text=${msg}" target="_blank"
                        class="btn btn-outline-success btn-lg mb-2">
                        <i class="bi bi-whatsapp"></i> ${TC.confirmWhatsapp}
                       </a>`
                    : `<p class="text-danger small">${TC.noWhatsapp}</p>`
                }
            </div>
        </div>
        <hr>
        <p><strong>${TC.lblType}</strong> ${p.consulta}</p>
        ${p.comentario ? `<p><strong>${TC.lblComment}</strong> ${p.comentario}</p>` : ''}
    `;

    // Controlar visibilidad de botones según estado
    document.getElementById('idCita').value     = id;
    document.getElementById('estadoCita').value = est;

    // Guardar datos actuales de la cita para el panel de edición
    citaActual = {
        id: id,
        fecha: `${startDate.getFullYear()}-${String(startDate.getMonth() + 1).padStart(2, '0')}-${String(startDate.getDate()).padStart(2, '0')}`,
        hora: `${String(startDate.getHours()).padStart(2, '0')}:${String(startDate.getMinutes()).padStart(2, '0')}`,
        idtipoconsulta: p.idtipoconsulta,
        iddoctor: p.iddoctor,
        idagencia: p.idagencia,
        idpaciente: p.idpaciente
    };

    const btnAtender     = document.getElementById('btnAtender');
    const btnHistorial   = document.getElementById('btnHistorial');
    const btnEditar      = document.getElementById('btnEditar');
    const btnReagendar    = document.getElementById('btnReagendar');
    const btnConfirmar   = document.getElementById('btnConfirmar');
    const btnCancelar    = document.getElementById('btnCancelar');
    const btnMasEstados  = document.getElementById('btnMasEstados');

    // Cerrar paneles reagendar/editar al abrir una nueva cita
    cerrarReagendar();
    cerrarEditar();

    if (cerrada) {
        btnAtender.classList.add('d-none');
        btnEditar.classList.add('d-none');
        btnReagendar.classList.add('d-none');
        btnConfirmar.classList.add('d-none');
        btnCancelar.classList.add('d-none');
        btnMasEstados.classList.add('d-none');
    } else {
        btnAtender.classList.remove('d-none');
        btnEditar.classList.remove('d-none');
        btnReagendar.classList.remove('d-none');
        btnConfirmar.classList.remove('d-none');
        btnCancelar.classList.remove('d-none');
        btnMasEstados.classList.remove('d-none');
    }

    if (atendida) {
        btnHistorial.href = `historial_atenciones.php?q=${encodeURIComponent(title)}`;
        btnHistorial.classList.remove('d-none');
    } else {
        btnHistorial.classList.add('d-none');
    }

    // Cargar datos del paciente (edad, estatura, IMC, estadísticas) reusando el endpoint de informes
    cargarInfoPacienteCita(p.idpaciente);

    new bootstrap.Modal(document.getElementById('eventModal')).show();
}

// Trae el bloque de datos del paciente desde get_historial_paciente.php (igual al de informes)
function cargarInfoPacienteCita(idPaciente) {
    const cont = document.getElementById('citaPacienteInfo');
    if (!cont) return;
    if (!idPaciente) { cont.innerHTML = ''; return; }
    cont.innerHTML = '<div class="text-muted small py-2"><span class="spinner-border spinner-border-sm me-1"></span> ' + TC.loadingPatient + '</div>';
    fetch('get_historial_paciente.php?id=' + encodeURIComponent(idPaciente))
        .then(function (r) { return r.text(); })
        .then(function (html) {
            var tmp = document.createElement('div');
            tmp.innerHTML = html;
            var style = tmp.querySelector('style');           // estilos (.info-label, .badge-atendida, etc.)
            var info  = tmp.querySelector('.border-bottom');   // bloque superior: datos + estadísticas
            var filas = tmp.querySelectorAll('table tbody tr'); // consultas del paciente

            var out = '';
            if (info) {
                out += '<div class="border rounded overflow-hidden">' + (style ? style.outerHTML : '') + info.outerHTML + '</div>';
            }

            // Consultas anteriores como tarjetas (estilo agenda)
            if (filas.length) {
                out += '<div class="mt-3">' +
                       '<div class="fw-semibold mb-2"><i class="bi bi-clock-history"></i> ' + TC.consultations + ' (' + filas.length + ')</div>' +
                       '<div class="cita-hist-lista">';
                filas.forEach(function (tr) {
                    var td = tr.querySelectorAll('td');
                    if (td.length < 5) return;
                    var fecha  = td[0].textContent.trim();
                    var hora   = td[1].textContent.trim().replace(/\s+/g, ' ');
                    var tipo   = td[2].textContent.trim();
                    var doctor = td[3].textContent.trim();
                    var badge  = td[4].innerHTML.trim();   // conserva el estado con su color
                    var sub    = tipo + (doctor && doctor !== '—' ? ' — ' + doctor : '');
                    // Botón "Ver informe" si la cita tiene informe registrado
                    var informeHtml = '';
                    if (td.length > 6) {
                        var btn = td[6].querySelector('button');
                        if (btn) {
                            // Importante: forzar type="button" para que NO envíe el formulario de la cita
                            btn.setAttribute('type', 'button');
                            informeHtml = btn.outerHTML;
                        }
                    }
                    out += '<div class="cita-hist-card">' +
                               '<div class="cita-hist-main">' +
                                   '<div class="cita-hist-fecha">' + fecha + ' · ' + hora + '</div>' +
                                   '<div class="cita-hist-sub">' + sub + '</div>' +
                               '</div>' +
                               '<div class="cita-hist-estado d-flex flex-column align-items-end gap-1">' +
                                   badge + informeHtml +
                               '</div>' +
                           '</div>';
                });
                out += '</div></div>';
            }

            cont.innerHTML = out;
        })
        .catch(function () {
            cont.innerHTML = '<p class="text-muted small mb-0">' + TC.couldNotLoadPat + '</p>';
        });
}

// Ver informe de una atención (mismo endpoint que el listado de pacientes)
function verInforme(idHistorial) {
    document.getElementById('cuerpoInforme').innerHTML =
        '<div class="text-center py-4"><div class="spinner-border spinner-border-sm"></div></div>';
    new bootstrap.Modal(document.getElementById('modalInforme')).show();
    $.get('get_informe_html.php', { id: idHistorial })
        .done(function (html) {
            document.getElementById('cuerpoInforme').innerHTML = html;
        })
        .fail(function (xhr) {
            document.getElementById('cuerpoInforme').innerHTML =
                '<div class="alert alert-danger m-3">No se pudo cargar el informe (HTTP ' + xhr.status + ').</div>';
        });
}

// ── FullCalendar ─────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {

    // En celular arranca en vista de Día (más legible que el mes apretado)
    var esMovilInit = window.innerWidth <= 768;

    var calendarEl = document.getElementById('calendar1');
    var calendar   = new FullCalendar.Calendar(calendarEl, {
        locale: CAL_LANG,
        headerToolbar: {
            left:   'prev,next today',
            center: 'title',
            right:  'dayGridMonth,timeGridWeek,timeGridDay'
        },
        initialView: esMovilInit ? 'timeGridDay' : 'dayGridMonth',
        eventDisplay: 'block',
        eventMinHeight: 70,
        height: 'auto',
        slotMinTime: '06:00:00',
        slotMaxTime: '22:00:00',
        expandRows: true,
        nowIndicator: true,
        events: eventosAll,

        // Render personalizado en TODAS las vistas (mes, semana y día):
        // hora inicio-fin, paciente, doctor y location sin recortar
        eventContent: function (arg) {
            var p = arg.event.extendedProps;
            var esc = function (s) {
                return (s == null ? '' : String(s))
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            };
            var fmt = function (d) {
                return d ? d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';
            };
            var rango = fmt(arg.event.start) + (arg.event.end ? ' - ' + fmt(arg.event.end) : '');
            var div = document.createElement('div');
            div.className = 'fc-event-main-custom';
            div.innerHTML =
                '<div class="fc-event-time-custom">' + rango + '</div>' +
                '<div class="fc-event-title-custom">' + esc(arg.event.title) + '</div>' +
                '<div class="fc-event-sub-custom">Dr. ' + esc(p.medico || '') + '</div>' +
                '<div class="fc-event-sub-custom">L: ' + esc(p.agencia || TC.unassigned) + '</div>';
            return { domNodes: [div] };
        },

        eventClick: function (info) {
            abrirModalCita(info.event.id, info.event.title, info.event.start, info.event.extendedProps);
        }
    });

    calendar.render();

    inicializarVistaPorDoctor();

    document.getElementById('btnVistaCalendario').addEventListener('click', function () {
        document.getElementById('viewFullCalendar').classList.remove('d-none');
        document.getElementById('viewPorDoctor').classList.add('d-none');
        this.classList.add('active');
        document.getElementById('btnVistaDoctor').classList.remove('active');
        calendar.updateSize();
    });

    document.getElementById('btnVistaDoctor').addEventListener('click', function () {
        document.getElementById('viewFullCalendar').classList.add('d-none');
        document.getElementById('viewPorDoctor').classList.remove('d-none');
        this.classList.add('active');
        document.getElementById('btnVistaCalendario').classList.remove('active');
        renderVistaPorDoctor();
    });
});

// ── Vista por Doctor (semana en columnas por doctor) ───────────────────
const CV_START_HOUR    = 6;
const CV_END_HOUR      = 21;
const CV_SLOT_MINUTES  = 30;
const CV_SLOT_HEIGHT   = 32;
const CV_COL_WIDTH     = 140;
const CV_TIME_COL_W    = 64;
const CV_DAY_HEAD_H    = 30;
const CV_DOC_HEAD_H    = 28;
const CV_HEADER_H      = CV_DAY_HEAD_H + CV_DOC_HEAD_H;
const CV_DIAS          = CAL_DIAS;
const CV_MESES         = CAL_MESES;

let cvWeekStart      = startOfWeek(new Date());
const cvDoctoresOcultos = new Set();

function startOfWeek(d) {
    const r = new Date(d.getFullYear(), d.getMonth(), d.getDate());
    r.setDate(r.getDate() - r.getDay());
    return r;
}

function cvEscapeHtml(s) {
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
}

function mismaFecha(a, b) {
    return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
}

function cvRangeLabel(weekStart) {
    const end = new Date(weekStart);
    end.setDate(end.getDate() + 6);
    const sd = weekStart.getDate(), ed = end.getDate(), yr = end.getFullYear();
    const sm = CV_MESES[weekStart.getMonth()], em = CV_MESES[end.getMonth()];
    if (CAL_LANG === 'es') {
        if (weekStart.getMonth() === end.getMonth()) return `${sd} - ${ed} de ${em} ${yr}`;
        return `${sd} de ${sm} - ${ed} de ${em} ${yr}`;
    }
    if (weekStart.getMonth() === end.getMonth()) return `${em} ${sd} - ${ed}, ${yr}`;
    return `${sm} ${sd} - ${em} ${ed}, ${yr}`;
}

function inicializarVistaPorDoctor() {
    const cont = document.getElementById('cvDoctorFiltros');

    // Si quien inicia sesión es un doctor y está en la lista, se muestra
    // solo su columna por defecto (las demás quedan ocultas).
    const soloMiColumna = loginDoctorId &&
        doctoresActivos.some(function (d) { return String(d.id) === String(loginDoctorId); });

    doctoresActivos.forEach(function (doc) {
        const id = 'cvDoc_' + doc.id;
        const esMio = String(doc.id) === String(loginDoctorId);
        const marcado = soloMiColumna ? esMio : true;
        if (soloMiColumna && !esMio) cvDoctoresOcultos.add(doc.nombre);

        const label = document.createElement('label');
        label.innerHTML = `<input type="checkbox" id="${id}"${marcado ? ' checked' : ''}> ${doc.nombre}`;
        if (esMio) {
            label.classList.add('fw-semibold');
            label.title = 'Doctor con la sesión iniciada';
        }
        cont.appendChild(label);
        label.querySelector('input').addEventListener('change', function (e) {
            if (e.target.checked) cvDoctoresOcultos.delete(doc.nombre);
            else cvDoctoresOcultos.add(doc.nombre);
            renderVistaPorDoctor();
        });
    });

    document.getElementById('cvToday').addEventListener('click', function () {
        cvWeekStart = startOfWeek(new Date());
        renderVistaPorDoctor();
    });
    document.getElementById('cvPrev').addEventListener('click', function () {
        cvWeekStart.setDate(cvWeekStart.getDate() - 7);
        renderVistaPorDoctor();
    });
    document.getElementById('cvNext').addEventListener('click', function () {
        cvWeekStart.setDate(cvWeekStart.getDate() + 7);
        renderVistaPorDoctor();
    });
}

function renderVistaPorDoctor() {
    const doctores = doctoresActivos.filter(d => !cvDoctoresOcultos.has(d.nombre));
    const grid      = document.getElementById('cvGrid');
    const nDoc      = Math.max(doctores.length, 1);
    const nSlots    = ((CV_END_HOUR - CV_START_HOUR) * 60) / CV_SLOT_MINUTES;
    const totalCols = 7 * nDoc;
    const gridW     = CV_TIME_COL_W + totalCols * CV_COL_WIDTH;
    const gridH      = CV_HEADER_H + nSlots * CV_SLOT_HEIGHT;

    document.getElementById('cvRangeLabel').textContent = cvRangeLabel(cvWeekStart);

    grid.innerHTML = '';
    grid.style.width  = gridW + 'px';
    grid.style.height = gridH + 'px';

    const hoy = new Date();

    // Cabeceras de día y doctor
    for (let dia = 0; dia < 7; dia++) {
        const fecha = new Date(cvWeekStart);
        fecha.setDate(fecha.getDate() + dia);
        const left      = CV_TIME_COL_W + dia * nDoc * CV_COL_WIDTH;
        const width     = nDoc * CV_COL_WIDTH;
        const esHoy     = mismaFecha(fecha, hoy);

        const dayHead = document.createElement('div');
        dayHead.className = 'cv-col-head cv-day-head' + (esHoy ? ' cv-today' : '');
        dayHead.style.left = left + 'px'; dayHead.style.top = '0px';
        dayHead.style.width = width + 'px'; dayHead.style.height = CV_DAY_HEAD_H + 'px';
        dayHead.textContent = `${CV_DIAS[fecha.getDay()]} ${fecha.getDate()}`;
        grid.appendChild(dayHead);

        doctores.forEach(function (doc, i) {
            const docHead = document.createElement('div');
            docHead.className = 'cv-col-head';
            docHead.style.left = (left + i * CV_COL_WIDTH) + 'px';
            docHead.style.top  = CV_DAY_HEAD_H + 'px';
            docHead.style.width = CV_COL_WIDTH + 'px';
            docHead.style.height = CV_DOC_HEAD_H + 'px';
            docHead.textContent = doc.nombre;
            grid.appendChild(docHead);
        });
    }

    // Etiquetas de hora + líneas de fondo
    for (let s = 0; s < nSlots; s++) {
        const minutos = CV_START_HOUR * 60 + s * CV_SLOT_MINUTES;
        const h = Math.floor(minutos / 60), m = minutos % 60;
        const ampm = h < 12 ? 'AM' : 'PM';
        const h12  = (h % 12) === 0 ? 12 : h % 12;
        const top  = CV_HEADER_H + s * CV_SLOT_HEIGHT;

        const lbl = document.createElement('div');
        lbl.className = 'cv-time-label';
        lbl.style.left = '0px'; lbl.style.top = top + 'px';
        lbl.style.height = CV_SLOT_HEIGHT + 'px';
        lbl.textContent = `${h12}:${String(m).padStart(2,'0')} ${ampm}`;
        grid.appendChild(lbl);

        for (let c = 0; c < totalCols; c++) {
            const cell = document.createElement('div');
            const esFinDia = (c + 1) % nDoc === 0;
            cell.className = 'cv-cell' + (esFinDia ? ' cv-day-end' : '');
            cell.style.left = (CV_TIME_COL_W + c * CV_COL_WIDTH) + 'px';
            cell.style.top  = top + 'px';
            cell.style.width = CV_COL_WIDTH + 'px';
            cell.style.height = CV_SLOT_HEIGHT + 'px';
            grid.appendChild(cell);
        }
    }

    // Eventos de la semana visible
    const weekEnd = new Date(cvWeekStart);
    weekEnd.setDate(weekEnd.getDate() + 7);

    eventosAll.forEach(function (ev) {
        const inicio = new Date(ev.start);
        const fin    = new Date(ev.end);
        if (inicio < cvWeekStart || inicio >= weekEnd) return;

        const docIdx = doctores.findIndex(d => d.nombre === ev.extendedProps.medico);
        if (docIdx === -1) return;

        const diaIdx = Math.floor((inicio - cvWeekStart) / 86400000);
        const minutosInicio = inicio.getHours() * 60 + inicio.getMinutes() - CV_START_HOUR * 60;
        const duracionMin   = Math.max((fin - inicio) / 60000, CV_SLOT_MINUTES / 2);

        const left   = CV_TIME_COL_W + (diaIdx * nDoc + docIdx) * CV_COL_WIDTH + 2;
        const top    = CV_HEADER_H + (minutosInicio / CV_SLOT_MINUTES) * CV_SLOT_HEIGHT;
        const height = Math.max((duracionMin / CV_SLOT_MINUTES) * CV_SLOT_HEIGHT - 2, 18);

        const div = document.createElement('div');
        div.className = 'cv-event';
        div.style.left   = left + 'px';
        div.style.top    = top + 'px';
        div.style.width  = (CV_COL_WIDTH - 4) + 'px';
        div.style.height = height + 'px';
        div.style.background  = ev.backgroundColor;
        div.style.borderLeft  = '5px solid ' + (ev.borderColor || '#333');
        div.innerHTML = `<b>${inicio.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</b>${cvEscapeHtml(ev.title)}`;
        div.addEventListener('click', function () {
            abrirModalCita(ev.id, ev.title, inicio, ev.extendedProps);
        });
        grid.appendChild(div);
    });

    // ── Lista para móvil (misma semana, agrupada por día) ──────────────
    renderVistaPorDoctorLista(doctores, weekEnd);
}

// Lista de citas para celular: tarjetas agrupadas por día
function renderVistaPorDoctorLista(doctores, weekEnd) {
    const cont = document.getElementById('cvList');
    if (!cont) return;
    cont.innerHTML = '';

    const fmt = (d) => d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    for (let dia = 0; dia < 7; dia++) {
        const fecha = new Date(cvWeekStart);
        fecha.setDate(fecha.getDate() + dia);
        const finDia = new Date(fecha);
        finDia.setDate(finDia.getDate() + 1);

        // Citas de este día con doctor visible, ordenadas por hora
        const citasDia = eventosAll.filter(function (ev) {
            const inicio = new Date(ev.start);
            if (inicio < fecha || inicio >= finDia) return false;
            return doctores.some(d => d.nombre === ev.extendedProps.medico);
        }).sort((a, b) => new Date(a.start) - new Date(b.start));

        if (citasDia.length === 0) continue;

        const titulo = document.createElement('div');
        titulo.className = 'cv-list-day';
        titulo.textContent = CAL_LANG === 'es'
            ? `${CV_DIAS[fecha.getDay()]} ${fecha.getDate()} de ${CV_MESES[fecha.getMonth()]}`
            : `${CV_DIAS[fecha.getDay()]}, ${CV_MESES[fecha.getMonth()]} ${fecha.getDate()}`;
        cont.appendChild(titulo);

        citasDia.forEach(function (ev) {
            const inicio = new Date(ev.start);
            const fin    = new Date(ev.end);
            const card = document.createElement('div');
            card.className = 'cv-list-card';
            card.innerHTML =
                `<div class="cv-list-color" style="background:${ev.borderColor || '#333'}"></div>` +
                `<div class="cv-list-body" style="background:${ev.backgroundColor}">` +
                    `<div class="hora">${fmt(inicio)} - ${fmt(fin)}</div>` +
                    `<div class="pac">${cvEscapeHtml(ev.title)}</div>` +
                    `<div class="sub">${cvEscapeHtml(ev.extendedProps.consulta || '')} — Dr. ${cvEscapeHtml(ev.extendedProps.medico || '')}</div>` +
                `</div>`;
            card.addEventListener('click', function () {
                abrirModalCita(ev.id, ev.title, inicio, ev.extendedProps);
            });
            cont.appendChild(card);
        });
    }

    if (!cont.children.length) {
        cont.innerHTML = '<p class="text-muted text-center my-3">' + TC.noApptsWeek + '</p>';
    }
}

// ── Select2 en modal Agendar ─────────────────────────────────────────
$(document).ready(function () {
    $('#editModal').on('shown.bs.modal', function () {
        $('.select-busqueda').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#editModal'),
            width: '100%'
        });
    });
});
</script>

<!-- ── Buscador de pacientes (estilo Kalix) → abre el Historial de atenciones ── -->
<script>
(function(){
    const input = document.getElementById('calPacienteBuscar');
    const box   = document.getElementById('calPacienteResultados');
    const clear = document.getElementById('calPacienteClear');
    if(!input) return;

    const CAL_SEARCH_NONE = <?php echo json_encode(t('cal.searchNone')); ?>;
    let timer = null, items = [], activeIdx = -1;

    function hide(){ box.classList.add('d-none'); box.innerHTML=''; items=[]; activeIdx=-1; }
    function irA(id){ window.location.href = 'historial_atenciones.php?id=' + encodeURIComponent(id); }
    function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];}); }

    function render(data){
        items = Array.isArray(data) ? data : [];
        if(!items.length){
            box.innerHTML = '<div class="csr-empty">' + escapeHtml(CAL_SEARCH_NONE) + '</div>';
            box.classList.remove('d-none');
            return;
        }
        box.innerHTML = items.map(function(p,i){
            var meta = [p.cedula, p.telefono].filter(Boolean).join('  ·  ');
            return '<div class="csr-item" data-id="'+p.id+'" data-i="'+i+'">'
                 + '<div class="csr-name">' + escapeHtml(p.nombre) + '</div>'
                 + (meta ? '<div class="csr-meta">' + escapeHtml(meta) + '</div>' : '')
                 + '</div>';
        }).join('');
        box.classList.remove('d-none');
        Array.prototype.forEach.call(box.querySelectorAll('.csr-item'), function(el){
            el.addEventListener('mousedown', function(e){ e.preventDefault(); irA(el.getAttribute('data-id')); });
        });
        activeIdx = -1;
    }

    input.addEventListener('input', function(){
        var q = input.value.trim();
        clear.classList.toggle('d-none', q.length === 0);
        if(timer) clearTimeout(timer);
        if(q.length < 2){ hide(); return; }
        timer = setTimeout(function(){
            fetch('buscar_pacientes.php?q=' + encodeURIComponent(q))
                .then(function(r){ return r.json(); })
                .then(render)
                .catch(hide);
        }, 220);
    });

    input.addEventListener('keydown', function(e){
        if(box.classList.contains('d-none')) return;
        var els = box.querySelectorAll('.csr-item');
        if(!els.length) return;
        if(e.key === 'ArrowDown'){ e.preventDefault(); activeIdx = Math.min(activeIdx+1, els.length-1); }
        else if(e.key === 'ArrowUp'){ e.preventDefault(); activeIdx = Math.max(activeIdx-1, 0); }
        else if(e.key === 'Enter'){ if(activeIdx>=0 && items[activeIdx]){ e.preventDefault(); irA(items[activeIdx].id); } return; }
        else if(e.key === 'Escape'){ hide(); return; }
        else return;
        Array.prototype.forEach.call(els, function(el,i){ el.classList.toggle('active', i===activeIdx); });
        if(els[activeIdx]) els[activeIdx].scrollIntoView({block:'nearest'});
    });

    clear.addEventListener('click', function(){ input.value=''; clear.classList.add('d-none'); hide(); input.focus(); });
    document.addEventListener('click', function(e){ if(!e.target.closest('.cal-patient-search')) hide(); });
})();
</script>

</body>
</html>
