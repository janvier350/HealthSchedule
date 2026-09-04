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

// Filtros
$busqueda  = isset($_GET['q'])      ? $conexion->real_escape_string(trim($_GET['q'])) : '';
$fechaDesde = isset($_GET['desde']) ? $conexion->real_escape_string($_GET['desde'])   : '';
$fechaHasta = isset($_GET['hasta']) ? $conexion->real_escape_string($_GET['hasta'])   : '';
$idPaciente = isset($_GET['id'])    ? (int)$_GET['id'] : 0;

$where = "WHERE H.ESTADO = 'A'";
if($idPaciente) $where .= " AND P.IDPACIENTE = $idPaciente";
if($busqueda)   $where .= " AND (P.NOMBRES LIKE '%$busqueda%' OR P.APELLIDOS LIKE '%$busqueda%' OR P.CEDULA LIKE '%$busqueda%')";
if($fechaDesde) $where .= " AND C.FECHA_CITA >= '$fechaDesde'";
if($fechaHasta) $where .= " AND C.FECHA_CITA <= '$fechaHasta'";

$sql = "SELECT
            H.IDHISTORIAL, H.IDCITA, H.FECHA_REGISTRO, H.PESO, H.TALLA, H.IMC,
            H.CONTENIDO_INFORME,
            P.NOMBRES, P.APELLIDOS, P.CEDULA, P.TELEFONO,
            C.FECHA_CITA, C.HORA_INICIO,
            TC.NOMBRES AS TIPO_CONSULTA,
            D.NOMBRES AS DOC_NOMBRES, D.APELLIDOS AS DOC_APELLIDOS
        FROM AG_HISTORIAL H
        INNER JOIN AG_CITA C       ON H.IDCITA        = C.IDCITA
        INNER JOIN AG_PACIENTE P   ON C.IDPACIENTE    = P.IDPACIENTE
        LEFT  JOIN AG_TIPOCONSULTA TC ON C.IDTIPOCONSULTA = TC.IDTIPOCONSULTA
        LEFT  JOIN ADM_DOCTOR D    ON C.IDDOCTOR      = D.IDDOCTOR
        $where
        ORDER BY H.FECHA_REGISTRO DESC";

$result = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <!-- Favicon de la app -->
    <link rel="icon" type="image/svg+xml" href="images/favicon.svg">
    <link rel="alternate icon" type="image/png" href="images/favicon.png">
    <link rel="apple-touch-icon" href="images/favicon.png">
    <title><?php te('hist.pageTitle'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="main.css" rel="stylesheet">
    <script src="js/jquery.min.js"></script>
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
                                <i class="pe-7s-notebook icon-gradient bg-tempting-azure"></i>
                            </div>
                            <div>
                                <?php te('hist.title'); ?>
                                <div class="page-title-subheading"><?php te('hist.subtitle'); ?></div>
                            </div>
                        </div>
                        <div class="page-title-actions">
                            <a href="SCH_Calendar.php" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-arrow-left"></i> <?php te('hist.backCalendar'); ?>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="main-card mb-3 card">
                    <div class="card-body">

            <!-- FILTROS -->
            <form method="GET" class="row g-2 mb-4 align-items-end">
                <div class="col-md-4">
                    <label class="form-label"><?php te('hist.searchLabel'); ?></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" class="form-control"
                               placeholder="<?php te('hist.searchPh'); ?>"
                               value="<?php echo htmlspecialchars($busqueda); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php te('hist.from'); ?></label>
                    <input type="date" name="desde" class="form-control"
                           value="<?php echo htmlspecialchars($fechaDesde); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php te('hist.to'); ?></label>
                    <input type="date" name="hasta" class="form-control"
                           value="<?php echo htmlspecialchars($fechaHasta); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i> <?php te('hist.filter'); ?>
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="historial_atenciones.php" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-x-circle"></i> <?php te('plist.clear'); ?>
                    </a>
                </div>
            </form>

            <!-- TABLA -->
            <?php if(!$result || $result->num_rows === 0): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i><?php te('hist.none'); ?>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th><?php te('plist.th.patient'); ?></th>
                            <th><?php te('pf.id'); ?></th>
                            <th><?php te('hist.th.appointmentDate'); ?></th>
                            <th><?php te('cal.consultType'); ?></th>
                            <th><?php te('cal.doctor'); ?></th>
                            <th class="text-center"><?php te('hist.th.weight'); ?></th>
                            <th class="text-center"><?php te('hist.th.height'); ?></th>
                            <th class="text-center"><?php te('common.bmi'); ?></th>
                            <th class="text-center"><?php te('hist.th.registerDate'); ?></th>
                            <th class="text-center"><?php te('pcreate.th.actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $n = 1;
                    while($h = $result->fetch_assoc()):
                        // Estado IMC
                        $imc = (float)$h['IMC'];
                        if     ($imc < 18.5) { $imcBadge = 'bg-info';    $imcLabel = t('imc.underweight'); }
                        elseif ($imc < 25)   { $imcBadge = 'bg-success'; $imcLabel = t('imc.normal'); }
                        elseif ($imc < 30)   { $imcBadge = 'bg-warning text-dark'; $imcLabel = t('imc.overweight'); }
                        else                 { $imcBadge = 'bg-danger';  $imcLabel = t('imc.obese'); }
                    ?>
                        <tr>
                            <td><?php echo $n++; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($h['NOMBRES'].' '.$h['APELLIDOS']); ?></strong><br>
                                <small class="text-muted"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($h['TELEFONO']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($h['CEDULA']); ?></td>
                            <td>
                                <?php echo date('d/m/Y', strtotime($h['FECHA_CITA'])); ?><br>
                                <small class="text-muted"><?php echo substr($h['HORA_INICIO'],0,5); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($h['TIPO_CONSULTA'] ?? '—'); ?></td>
                            <td>
                                <?php
                                $docN = trim($h['DOC_NOMBRES'].' '.$h['DOC_APELLIDOS']);
                                echo htmlspecialchars($docN ?: '—');
                                ?>
                            </td>
                            <td class="text-center"><?php echo $h['PESO'] ? number_format($h['PESO'],1) : '—'; ?></td>
                            <td class="text-center"><?php echo $h['TALLA'] ? number_format($h['TALLA'],1) : '—'; ?></td>
                            <td class="text-center">
                                <?php if($imc > 0): ?>
                                    <span class="badge <?php echo $imcBadge; ?>">
                                        <?php echo number_format($imc,2); ?><br>
                                        <small><?php echo $imcLabel; ?></small>
                                    </span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td class="text-center">
                                <small><?php echo date('d/m/Y H:i', strtotime($h['FECHA_REGISTRO'])); ?></small>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary"
                                        onclick="verInforme(<?php echo $h['IDHISTORIAL']; ?>)"
                                        title="<?php te('hist.viewReport'); ?>">
                                    <i class="bi bi-file-earmark-text"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary"
                                        onclick="imprimirInforme(<?php echo $h['IDHISTORIAL']; ?>)"
                                        title="<?php te('common.print'); ?>">
                                    <i class="bi bi-printer"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

                    </div><!-- card-body -->
                </div><!-- main-card -->

            </div><!-- app-main__inner -->
        </div><!-- app-main__outer -->
    </div><!-- app-main -->
</div><!-- app-container -->

<!-- MODAL VER INFORME -->
<div class="modal fade" id="modalInforme" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-file-earmark-text me-2"></i><?php te('plist.reportTitle'); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalInformeBody" style="min-height:400px;">
                <div class="text-center p-4"><div class="spinner-border text-primary"></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php te('common.close'); ?></button>
                <button type="button" class="btn btn-primary" id="btnImprimirModal" onclick="imprimirDesdeModal()">
                    <i class="bi bi-printer"></i> <?php te('common.print'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script type="text/javascript" src="./assets/scripts/main.js"></script>
<script>
const informesCache = {};

function verInforme(idHistorial){
    $('#modalInformeBody').html('<div class="text-center p-4"><div class="spinner-border text-primary"></div></div>');
    const modal = new bootstrap.Modal(document.getElementById('modalInforme'));
    modal.show();

    if(informesCache[idHistorial]){
        $('#modalInformeBody').html(informesCache[idHistorial]);
        return;
    }

    $.get('get_informe_html.php', { id: idHistorial }, function(html){
        informesCache[idHistorial] = html;
        $('#modalInformeBody').html(html);
    }).fail(function(){
        $('#modalInformeBody').html('<div class="alert alert-danger">' + <?php echo json_encode(t('hist.js.loadError')); ?> + '</div>');
    });
}

function imprimirDesdeModal(){
    const contenido = $('#modalInformeBody').html();
    const ventana = window.open('','_blank','height=800,width=900');
    ventana.document.write(`<html><head><title><?php echo addslashes(t('hist.js.reportWindow')); ?></title>
        <style>body{font-family:Arial,sans-serif;padding:40px;color:#333;}</style>
        </head><body>${contenido}</body></html>`);
    ventana.document.close();
    setTimeout(()=>ventana.print(), 500);
}

function imprimirInforme(idHistorial){
    $.get('get_informe_html.php', { id: idHistorial }, function(html){
        const ventana = window.open('','_blank','height=800,width=900');
        ventana.document.write(`<html><head><title><?php echo addslashes(t('hist.js.reportWindow')); ?></title>
            <style>body{font-family:Arial,sans-serif;padding:40px;color:#333;}</style>
            </head><body>${html}</body></html>`);
        ventana.document.close();
        setTimeout(()=>ventana.print(), 500);
    });
}
</script>
</body>
</html>
