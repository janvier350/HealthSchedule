<?php
/**
 * pacientes_eliminados.php
 * Lista de pacientes eliminados (ESTADO='I') con motivo, fecha y quién,
 * y botón para recuperar. SISTEMA-only.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/lang/i18n.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
if (!isset($_SESSION["rol"]) || strtoupper($_SESSION["rol"]) !== 'SISTEMA') { header("Location: break.php"); exit(); }
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$tieneAudit = (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='".$conexion->real_escape_string($dbName)."' AND TABLE_NAME='AG_PACIENTE' AND COLUMN_NAME='MOTIVO_ELIMINACION'")->fetch_assoc()['c']>0;

$q = trim($_GET['q'] ?? '');
$qEsc = $conexion->real_escape_string($q);
$sel = "P.IDPACIENTE, P.NOMBRES, P.APELLIDOS, P.CEDULA, P.TELEFONO";
if ($tieneAudit) $sel .= ", P.MOTIVO_ELIMINACION, P.FECHA_ELIMINACION, CONCAT(U.NOMBRES,' ',U.APELLIDOS) AS ELIMINADO_POR_NOMBRE";
$join = $tieneAudit ? "LEFT JOIN ADM_USUARIO U ON U.IDADM_USUARIO = P.ELIMINADO_POR" : "";
$where = "WHERE P.ESTADO='I'";
if ($q!=='') $where .= " AND (P.NOMBRES LIKE '%$qEsc%' OR P.APELLIDOS LIKE '%$qEsc%' OR P.CEDULA LIKE '%$qEsc%' OR P.TELEFONO LIKE '%$qEsc%')";
$order = $tieneAudit ? "ORDER BY P.FECHA_ELIMINACION DESC" : "ORDER BY P.IDPACIENTE DESC";
$rows=[]; $r=$conexion->query("SELECT $sel FROM AG_PACIENTE P $join $where $order LIMIT 500");
if ($r) while($x=$r->fetch_assoc()) $rows[]=$x;
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="images/favicon.svg">
    <title>Pacientes eliminados</title>
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
                <div class="page-title-icon"><i class="pe-7s-trash icon-gradient bg-plum-plate"></i></div>
                <div>Pacientes eliminados <div class="page-title-subheading">Motivo, fecha y quién los eliminó. Puedes recuperarlos.</div></div>
            </div>
            <div class="page-title-actions"><a href="pacientes_crud.php" class="btn btn-outline-secondary btn-sm">Gestionar Pacientes</a></div>
            </div></div>

            <?php if (!$tieneAudit): ?>
                <div class="alert alert-warning">Falta la auditoría de eliminación. <a href="migrar_paciente_eliminacion.php" class="alert-link">Créala aquí</a> para ver motivos.</div>
            <?php endif; ?>

            <div class="card shadow-sm mb-3"><div class="card-body py-2">
                <form method="GET" class="row g-2 align-items-center">
                    <div class="col"><div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="q" class="form-control border-start-0" placeholder="Buscar por nombre, cédula o teléfono" value="<?php echo h($q); ?>">
                    </div></div>
                    <div class="col-auto"><button class="btn btn-primary">Buscar</button>
                        <?php if($q!==''): ?><a href="pacientes_eliminados.php" class="btn btn-outline-secondary">Limpiar</a><?php endif; ?>
                    </div>
                </form>
            </div></div>

            <div class="card shadow-sm"><div class="card-body p-0"><div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr>
                        <th>Paciente</th><th>Cédula</th><th>Teléfono</th>
                        <?php if($tieneAudit): ?><th>Motivo</th><th>Eliminado</th><th>Por</th><?php endif; ?>
                        <th class="text-end pe-3">Acción</th>
                    </tr></thead>
                    <tbody>
                    <?php if($rows): foreach($rows as $p): ?>
                        <tr id="drow-<?php echo (int)$p['IDPACIENTE']; ?>">
                            <td class="fw-semibold"><?php echo h(trim($p['APELLIDOS'].', '.$p['NOMBRES'])); ?></td>
                            <td><small class="text-muted"><?php echo h($p['CEDULA']?:'—'); ?></small></td>
                            <td><small><?php echo h($p['TELEFONO']?:'—'); ?></small></td>
                            <?php if($tieneAudit): ?>
                                <td style="max-width:320px;"><small><?php echo h($p['MOTIVO_ELIMINACION']?:'—'); ?></small></td>
                                <td><small><?php echo !empty($p['FECHA_ELIMINACION'])?date('d/m/Y H:i',strtotime($p['FECHA_ELIMINACION'])):'—'; ?></small></td>
                                <td><small class="text-muted"><?php echo h($p['ELIMINADO_POR_NOMBRE']?:'—'); ?></small></td>
                            <?php endif; ?>
                            <td class="text-end pe-3">
                                <button class="btn btn-sm btn-outline-success py-0 px-2" onclick="recuperar(<?php echo (int)$p['IDPACIENTE']; ?>)">
                                    <i class="bi bi-arrow-counterclockwise"></i> Recuperar
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="<?php echo $tieneAudit?7:4; ?>" class="text-center py-5 text-muted"><i class="bi bi-trash fs-2 d-block mb-2"></i>No hay pacientes eliminados.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div></div></div>

        </div></div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function recuperar(id){
    if(!confirm('¿Recuperar este paciente? Volverá a la lista activa.')) return;
    $.post('paciente_recuperar.php', { idPaciente: id }, function(res){
        res=(res||'').trim();
        if(res==='OK'||res==='NO_CAMBIO'){ var r=document.getElementById('drow-'+id); if(r) r.remove(); }
        else alert('No se pudo recuperar: '+res);
    }).fail(function(){ alert('Error de conexión.'); });
}
</script>
</body>
</html>
