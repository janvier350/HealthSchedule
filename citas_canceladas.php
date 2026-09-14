<?php
/**
 * citas_canceladas.php
 * Lista de citas canceladas (cualquier estado de cancelación) con su
 * comentario/motivo de cancelación, quién la canceló y cuándo.
 * Accesible a cualquier usuario con sesión. Los DOCTOR ven sólo las suyas.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/lang/i18n.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
if (!isset($_SESSION["rol"])) { header("Location: break.php"); exit(); }
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$rol       = strtoupper($_SESSION['rol'] ?? '');
$esDoctor  = ($rol === 'DOCTOR');
$idUser    = (int)($_SESSION['iduser'] ?? 0);

$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$dbEsc  = $conexion->real_escape_string($dbName);
$tieneAudit = (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$dbEsc' AND TABLE_NAME='AG_CITA' AND COLUMN_NAME='MOTIVO_CANCELACION'")->fetch_assoc()['c']>0;

// Estados de cancelación
$estadosCancel = ['Cancelada','Cancelado','Cancelación Tardía','Cancelado por Profesional','No Asistió'];
$inList = "'" . implode("','", array_map([$conexion,'real_escape_string'], $estadosCancel)) . "'";

// Filtros
$q      = trim($_GET['q'] ?? '');
$estadoF= trim($_GET['estado'] ?? '');
$desde  = trim($_GET['desde'] ?? '');
$hasta  = trim($_GET['hasta'] ?? '');
$qEsc   = $conexion->real_escape_string($q);

$where = "WHERE A.ESTADO_CITA IN ($inList)";
if ($esDoctor) $where .= " AND A.IDDOCTOR = " . $idUser;
if ($estadoF !== '' && in_array($estadoF, $estadosCancel, true)) {
    $where .= " AND A.ESTADO_CITA = '" . $conexion->real_escape_string($estadoF) . "'";
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) $where .= " AND A.FECHA_CITA >= '$desde'";
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) $where .= " AND A.FECHA_CITA <= '$hasta'";
if ($q !== '') {
    $where .= " AND (P.NOMBRES LIKE '%$qEsc%' OR P.APELLIDOS LIKE '%$qEsc%'
                 OR CONCAT(P.NOMBRES,' ',P.APELLIDOS) LIKE '%$qEsc%'
                 OR CONCAT(D.NOMBRES,' ',D.APELLIDOS) LIKE '%$qEsc%')";
}

$colAudit = $tieneAudit
    ? ", A.MOTIVO_CANCELACION, A.FECHA_CANCELACION, CONCAT(U.NOMBRES,' ',U.APELLIDOS) AS CANCELADO_POR_NOMBRE"
    : "";
$joinAudit = $tieneAudit ? "LEFT JOIN ADM_USUARIO U ON U.IDADM_USUARIO = A.CANCELADO_POR" : "";

$sql = "SELECT A.IDCITA, A.FECHA_CITA, A.HORA_INICIO, A.HORA_FIN, A.ESTADO_CITA, A.COMENTARIO,
               CONCAT(P.APELLIDOS,', ',P.NOMBRES) AS PACIENTE, P.TELEFONO,
               CONCAT(D.NOMBRES,' ',D.APELLIDOS) AS DOCTOR,
               TC.NOMBRES AS TIPO_CONSULTA $colAudit
        FROM AG_CITA A
        INNER JOIN AG_PACIENTE P      ON A.IDPACIENTE     = P.IDPACIENTE
        LEFT  JOIN ADM_USUARIO D      ON A.IDDOCTOR       = D.IDADM_USUARIO
        LEFT  JOIN AG_TIPOCONSULTA TC ON A.IDTIPOCONSULTA = TC.IDTIPOCONSULTA
        $joinAudit
        $where
        ORDER BY " . ($tieneAudit ? "A.FECHA_CANCELACION DESC, " : "") . "A.FECHA_CITA DESC, A.HORA_INICIO DESC
        LIMIT 800";
$rows=[]; $r=$conexion->query($sql);
if ($r) while($x=$r->fetch_assoc()) $rows[]=$x;

// Colores de badge por estado
$badge = [
    'Cancelada'                 => '#fd7e14',
    'Cancelado'                 => '#fd7e14',
    'Cancelación Tardía'        => '#e0a800',
    'Cancelado por Profesional' => '#d6336c',
    'No Asistió'                => '#6c757d',
];
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="images/favicon.svg">
    <title><?php te('cc.title'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./main.css" rel="stylesheet">
    <script src="js/jquery.min.js"></script>
</head>
<body>
<div class="app-container app-theme-white body-tabs-shadow fixed-sidebar fixed-header">
    <div class="app-header header-shadow">
        <div class="app-header__logo"><div class="logo-src"></div>
            <div class="header__pane ml-auto"><button type="button" class="hamburger close-sidebar-btn hamburger--elastic" data-class="closed-sidebar"><span class="hamburger-box"><span class="hamburger-inner"></span></span></button></div></div>
        <div class="app-header__mobile-menu"><button type="button" class="hamburger hamburger--elastic mobile-toggle-nav"><span class="hamburger-box"><span class="hamburger-inner"></span></span></button></div>
        <div class="app-header__menu"><button type="button" class="btn-icon btn-icon-only btn btn-primary btn-sm mobile-toggle-header-nav"><span class="btn-icon-wrapper"><i class="fa fa-ellipsis-v"></i></span></button></div>
        <div class="app-header__content"><div class="app-header-left"></div>
            <div class="app-header-right"><div class="header-btn-lg pr-0"><div class="widget-content p-0"><div class="widget-content-wrapper">
                <div class="widget-content-left ml-3 header-user-info"><div class="widget-heading"><?php echo h($_SESSION['nombres']??''); ?></div><div class="widget-subheading"><?php echo h($_SESSION['rol']??''); ?></div></div>
            </div></div></div></div></div>
    </div>
    <div class="app-main">
        <div class="app-sidebar sidebar-shadow"><?php include("./menu/menu_adm.php"); ?></div>
        <div class="app-main__outer"><div class="app-main__inner">

            <div class="app-page-title mb-3"><div class="page-title-wrapper"><div class="page-title-heading">
                <div class="page-title-icon"><i class="pe-7s-close-circle icon-gradient bg-plum-plate"></i></div>
                <div><?php te('cc.title'); ?> <div class="page-title-subheading"><?php te('cc.subtitle'); ?></div></div>
            </div>
            <div class="page-title-actions"><a href="SCH_Calendar.php" class="btn btn-outline-secondary btn-sm"><?php te('menu.calendar'); ?></a></div>
            </div></div>

            <?php if (!$tieneAudit): ?>
                <div class="alert alert-warning"><?php te('cc.needMigration'); ?>
                    <a href="migrar_cita_cancelacion.php" class="alert-link"><?php te('cc.createHere'); ?></a>.
                    <?php te('cc.fallbackNote'); ?>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm mb-3"><div class="card-body py-2">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4"><label class="form-label small mb-1"><?php te('cc.search'); ?></label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="q" class="form-control border-start-0" placeholder="<?php te('cc.searchPh'); ?>" value="<?php echo h($q); ?>">
                        </div>
                    </div>
                    <div class="col-md-3"><label class="form-label small mb-1"><?php te('cc.status'); ?></label>
                        <select name="estado" class="form-select">
                            <option value=""><?php te('cc.allStatuses'); ?></option>
                            <?php foreach ($estadosCancel as $e): ?>
                                <option value="<?php echo h($e); ?>" <?php echo $estadoF===$e?'selected':''; ?>><?php echo h(estado_label($e)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2"><label class="form-label small mb-1"><?php te('cc.from'); ?></label>
                        <input type="date" name="desde" class="form-control" value="<?php echo h($desde); ?>"></div>
                    <div class="col-md-2"><label class="form-label small mb-1"><?php te('cc.to'); ?></label>
                        <input type="date" name="hasta" class="form-control" value="<?php echo h($hasta); ?>"></div>
                    <div class="col-md-1 d-grid"><button class="btn btn-primary"><?php te('cc.filter'); ?></button></div>
                </form>
                <?php if($q!==''||$estadoF!==''||$desde!==''||$hasta!==''): ?>
                    <div class="mt-2"><a href="citas_canceladas.php" class="btn btn-sm btn-outline-secondary"><?php te('cc.clear'); ?></a></div>
                <?php endif; ?>
            </div></div>

            <div class="mb-2 text-muted small"><?php echo count($rows); ?> <?php te('cc.results'); ?></div>

            <div class="card shadow-sm"><div class="card-body p-0"><div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr>
                        <th><?php te('cc.date'); ?></th>
                        <th><?php te('cc.patient'); ?></th>
                        <th><?php te('cc.doctor'); ?></th>
                        <th><?php te('cc.type'); ?></th>
                        <th><?php te('cc.status'); ?></th>
                        <th><?php te('cc.reason'); ?></th>
                        <?php if($tieneAudit): ?><th><?php te('cc.cancelledBy'); ?></th><?php endif; ?>
                    </tr></thead>
                    <tbody>
                    <?php if($rows): foreach($rows as $c):
                        $col = $badge[$c['ESTADO_CITA']] ?? '#6c757d';
                        $motivo = $tieneAudit ? ($c['MOTIVO_CANCELACION'] ?? '') : '';
                        if ($motivo === '' || $motivo === null) $motivo = $c['COMENTARIO'] ?? '';
                    ?>
                        <tr>
                            <td style="white-space:nowrap;">
                                <?php echo h(date('d/m/Y', strtotime($c['FECHA_CITA']))); ?>
                                <div class="text-muted small"><?php echo h(substr($c['HORA_INICIO'],0,5)); ?><?php echo $c['HORA_FIN']?'–'.h(substr($c['HORA_FIN'],0,5)):''; ?></div>
                            </td>
                            <td class="fw-semibold"><?php echo h($c['PACIENTE']); ?>
                                <?php if(!empty($c['TELEFONO'])): ?><div class="text-muted small"><?php echo h($c['TELEFONO']); ?></div><?php endif; ?>
                            </td>
                            <td><small><?php echo h($c['DOCTOR']?:'—'); ?></small></td>
                            <td><small><?php echo h($c['TIPO_CONSULTA']?:'—'); ?></small></td>
                            <td><span class="badge" style="background:<?php echo $col; ?>;color:#fff;"><?php echo h(estado_label($c['ESTADO_CITA'])); ?></span></td>
                            <td style="max-width:340px;"><small><?php echo $motivo!==''?h($motivo):'<span class="text-muted">—</span>'; ?></small></td>
                            <?php if($tieneAudit): ?>
                                <td><small class="text-muted"><?php echo h($c['CANCELADO_POR_NOMBRE']?:'—'); ?></small>
                                    <?php if(!empty($c['FECHA_CANCELACION'])): ?><div class="text-muted small"><?php echo h(date('d/m/Y H:i', strtotime($c['FECHA_CANCELACION']))); ?></div><?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="<?php echo $tieneAudit?7:6; ?>" class="text-center py-5 text-muted"><i class="bi bi-calendar-x fs-2 d-block mb-2"></i><?php te('cc.empty'); ?></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div></div></div>

        </div></div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
