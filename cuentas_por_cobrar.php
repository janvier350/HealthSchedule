<?php
/**
 * cuentas_por_cobrar.php
 * Reporte de facturas pendientes de cobro (Accounts Receivable).
 * Lista facturas con saldo > 0, con overview y antigüedad (días vencidos).
 * SISTEMA-only.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/lang/i18n.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
if (!isset($_SESSION["rol"]) || strtoupper($_SESSION["rol"]) !== 'SISTEMA') {
    header("Location: break.php"); exit();
}
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$tablaOk = ($conexion->query("SHOW TABLES LIKE 'facturas'")->num_rows ?? 0) > 0;

$q      = trim($_GET['q'] ?? '');
$verTodo= isset($_GET['todo']);   // mostrar todas o solo pendientes
$rows = []; $ovTotal=0;$ovPaid=0;$ovSaldo=0;$ovCount=0;
if ($tablaOk) {
    // Overview global de pendientes
    $ov = $conexion->query("SELECT COUNT(*) c, COALESCE(SUM(total),0) t, COALESCE(SUM(pagado),0) p, COALESCE(SUM(saldo),0) s FROM facturas WHERE estado=1 AND saldo>0.001")->fetch_assoc();
    $ovCount=(int)$ov['c']; $ovTotal=(float)$ov['t']; $ovPaid=(float)$ov['p']; $ovSaldo=(float)$ov['s'];

    $qEsc = $conexion->real_escape_string($q);
    $where = "WHERE f.estado=1";
    if (!$verTodo) $where .= " AND f.saldo > 0.001";
    if ($q!=='') $where .= " AND (f.billing_number LIKE '%$qEsc%' OR f.client_name LIKE '%$qEsc%' OR CONCAT(P.APELLIDOS,' ',P.NOMBRES) LIKE '%$qEsc%')";
    $sql = "SELECT f.*, P.NOMBRES, P.APELLIDOS
            FROM facturas f LEFT JOIN AG_PACIENTE P ON P.IDPACIENTE=f.IDPACIENTE
            $where ORDER BY f.fecha_vencimiento IS NULL, f.fecha_vencimiento ASC, f.fecha ASC
            LIMIT 500";
    $r = $conexion->query($sql);
    if ($r) while ($x=$r->fetch_assoc()) $rows[]=$x;
}
$hoy = new DateTime('today');
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="images/favicon.svg">
    <title>Cuentas por Cobrar</title>
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
                <div class="widget-content-left ml-3 header-user-info">
                    <div class="widget-heading"><?php echo h($_SESSION['nombres'] ?? ''); ?></div>
                    <div class="widget-subheading"><?php echo h($_SESSION['rol'] ?? ''); ?></div>
                </div></div></div></div></div>
        </div>
    </div>
    <div class="app-main">
        <div class="app-sidebar sidebar-shadow"><?php include("./menu/menu_adm.php"); ?></div>
        <div class="app-main__outer"><div class="app-main__inner">

            <div class="app-page-title mb-3"><div class="page-title-wrapper"><div class="page-title-heading">
                <div class="page-title-icon"><i class="pe-7s-cash icon-gradient bg-plum-plate"></i></div>
                <div>Cuentas por Cobrar <div class="page-title-subheading">Facturas abiertas con saldo pendiente.</div></div>
            </div></div></div>

            <?php if (!$tablaOk): ?>
                <div class="alert alert-warning">Falta el esquema de facturación.
                    <a href="migrar_facturas_schema.php" class="alert-link">Créalo aquí</a> y luego
                    <a href="importar_facturas_kalix.php" class="alert-link">importa las facturas de Kalix</a>.</div>
            <?php else: ?>

                <!-- Overview -->
                <div class="row g-2 mb-3">
                    <div class="col"><div class="card shadow-sm text-center"><div class="card-body py-2">
                        <div class="text-muted small">Facturas pendientes</div><div class="h5 mb-0"><?php echo number_format($ovCount); ?></div></div></div></div>
                    <div class="col"><div class="card shadow-sm text-center"><div class="card-body py-2">
                        <div class="text-muted small">Total facturado</div><div class="h5 mb-0">$<?php echo number_format($ovTotal,2); ?></div></div></div></div>
                    <div class="col"><div class="card shadow-sm text-center"><div class="card-body py-2">
                        <div class="text-muted small">Pagado</div><div class="h5 mb-0 text-success">$<?php echo number_format($ovPaid,2); ?></div></div></div></div>
                    <div class="col"><div class="card shadow-sm text-center"><div class="card-body py-2">
                        <div class="text-muted small">Saldo por cobrar</div><div class="h5 mb-0 text-danger">$<?php echo number_format($ovSaldo,2); ?></div></div></div></div>
                </div>

                <div class="card shadow-sm mb-3"><div class="card-body py-2">
                    <form method="GET" class="row g-2 align-items-center">
                        <div class="col"><div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="q" class="form-control border-start-0" placeholder="Buscar por paciente o № de factura" value="<?php echo h($q); ?>">
                        </div></div>
                        <div class="col-auto"><div class="form-check">
                            <input class="form-check-input" type="checkbox" name="todo" id="todo" value="1" <?php echo $verTodo?'checked':''; ?> onchange="this.form.submit()">
                            <label class="form-check-label small" for="todo">Ver todas (incl. pagadas)</label>
                        </div></div>
                        <div class="col-auto"><button class="btn btn-primary">Buscar</button>
                            <?php if($q!==''): ?><a href="cuentas_por_cobrar.php<?php echo $verTodo?'?todo=1':''; ?>" class="btn btn-outline-secondary">Limpiar</a><?php endif; ?>
                        </div>
                    </form>
                </div></div>

                <div class="card shadow-sm"><div class="card-body p-0"><div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr>
                            <th>Fecha</th><th>№ Factura</th><th>Paciente</th><th>Descripción</th>
                            <th>Vence</th><th class="text-end">Total</th><th class="text-end">Pagado</th>
                            <th class="text-end">Saldo</th><th class="text-center">Estado</th>
                        </tr></thead>
                        <tbody>
                        <?php if ($rows): foreach ($rows as $f):
                            $nombre = trim(($f['APELLIDOS']??'').' '.($f['NOMBRES']??''));
                            if ($nombre==='') $nombre = $f['client_name'] ?: '—';
                            $venc=''; $overdue=0;
                            if (!empty($f['fecha_vencimiento'])) {
                                $venc = date('d/m/Y', strtotime($f['fecha_vencimiento']));
                                $d = (new DateTime($f['fecha_vencimiento']))->diff($hoy);
                                $overdue = ($hoy > new DateTime($f['fecha_vencimiento'])) ? $d->days : 0;
                            }
                        ?>
                            <tr>
                                <td><small><?php echo $f['fecha']?date('d/m/Y',strtotime($f['fecha'])):'—'; ?></small></td>
                                <td><small class="fw-semibold"><?php echo h($f['billing_number']?:('#'.$f['id'])); ?></small></td>
                                <td><small><?php echo h($nombre); ?></small></td>
                                <td style="max-width:340px;"><small class="text-muted"><?php echo h(mb_strimwidth((string)$f['descripcion'],0,60,'…')); ?></small></td>
                                <td><small><?php echo $venc?:'—'; ?><?php if($overdue>0): ?><br><span class="badge bg-danger"><?php echo $overdue; ?>d vencida</span><?php endif; ?></small></td>
                                <td class="text-end">$<?php echo number_format($f['total'],2); ?></td>
                                <td class="text-end text-success">$<?php echo number_format($f['pagado'],2); ?></td>
                                <td class="text-end fw-bold <?php echo $f['saldo']>0.001?'text-danger':'text-success'; ?>">$<?php echo number_format($f['saldo'],2); ?></td>
                                <td class="text-center">
                                    <?php
                                    $st = (float)$f['saldo']<=0.001 ? ['success','Pagada'] : ((float)$f['pagado']>0.001 ? ['warning','Parcial'] : ['secondary','Pendiente']);
                                    ?>
                                    <span class="badge bg-<?php echo $st[0]; ?> <?php echo $st[0]==='warning'?'text-dark':''; ?>"><?php echo $st[1]; ?></span>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="9" class="text-center py-5 text-muted"><i class="bi bi-check2-circle fs-2 d-block mb-2"></i>Sin facturas pendientes.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div></div></div>
                <?php if (count($rows)>=500): ?><p class="text-muted small mt-2">Mostrando las primeras 500. Usa el buscador para acotar.</p><?php endif; ?>

            <?php endif; ?>
        </div></div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
