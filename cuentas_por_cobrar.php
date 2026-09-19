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
// Acceso por permisos por usuario (default: SISTEMA, DOCTOR, ASISTENTE).
require_once("class/permisos.php");
requerir('fact.cuentas');
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$tablaOk = ($conexion->query("SHOW TABLES LIKE 'facturas'")->num_rows ?? 0) > 0;

$q      = trim($_GET['q'] ?? '');
// Filtro de estado: consaldo (default) | pendientes | parciales | pagadas | todas
$filtro = $_GET['f'] ?? 'consaldo';
$filtrosValidos = ['consaldo','pendientes','parciales','pagadas','todas'];
if (!in_array($filtro,$filtrosValidos,true)) $filtro='consaldo';

$rows = [];
$bk = ['pend'=>['c'=>0,'s'=>0],'parc'=>['c'=>0,'s'=>0,'p'=>0],'pag'=>['c'=>0,'p'=>0],'tot'=>['c'=>0,'t'=>0,'p'=>0,'s'=>0]];
if ($tablaOk) {
    // Desglose por estado (pendiente / parcial / pagada)
    $rb = $conexion->query("SELECT
            SUM(CASE WHEN saldo>0.001 AND pagado<=0.001 THEN 1 ELSE 0 END) pend_c,
            SUM(CASE WHEN saldo>0.001 AND pagado<=0.001 THEN saldo ELSE 0 END) pend_s,
            SUM(CASE WHEN saldo>0.001 AND pagado>0.001 THEN 1 ELSE 0 END) parc_c,
            SUM(CASE WHEN saldo>0.001 AND pagado>0.001 THEN saldo ELSE 0 END) parc_s,
            SUM(CASE WHEN saldo>0.001 AND pagado>0.001 THEN pagado ELSE 0 END) parc_p,
            SUM(CASE WHEN saldo<=0.001 THEN 1 ELSE 0 END) pag_c,
            SUM(CASE WHEN saldo<=0.001 THEN pagado ELSE 0 END) pag_p,
            COUNT(*) tot_c, COALESCE(SUM(total),0) tot_t, COALESCE(SUM(pagado),0) tot_p, COALESCE(SUM(saldo),0) tot_s
        FROM facturas WHERE estado=1")->fetch_assoc();
    $bk['pend']=['c'=>(int)$rb['pend_c'],'s'=>(float)$rb['pend_s']];
    $bk['parc']=['c'=>(int)$rb['parc_c'],'s'=>(float)$rb['parc_s'],'p'=>(float)$rb['parc_p']];
    $bk['pag'] =['c'=>(int)$rb['pag_c'],'p'=>(float)$rb['pag_p']];
    $bk['tot'] =['c'=>(int)$rb['tot_c'],'t'=>(float)$rb['tot_t'],'p'=>(float)$rb['tot_p'],'s'=>(float)$rb['tot_s']];

    $qEsc = $conexion->real_escape_string($q);
    $where = "WHERE f.estado=1";
    if     ($filtro==='consaldo')   $where .= " AND f.saldo > 0.001";
    elseif ($filtro==='pendientes') $where .= " AND f.saldo > 0.001 AND f.pagado <= 0.001";
    elseif ($filtro==='parciales')  $where .= " AND f.saldo > 0.001 AND f.pagado > 0.001";
    elseif ($filtro==='pagadas')    $where .= " AND f.saldo <= 0.001";
    // 'todas' => sin filtro adicional
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
            </div>
            <div class="page-title-actions">
                <button type="button" class="btn btn-outline-info btn-sm me-2" data-bs-toggle="modal" data-bs-target="#modalAyudaFactura"><i class="bi bi-question-circle me-1"></i><?php te('help.howItWorks'); ?></button>
                <a href="crear_factura.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> <?php te('menu.createInvoice'); ?></a>
            </div>
            </div></div>

            <!-- ── MODAL: ¿Cómo funciona la facturación? ──────────────── -->
            <div class="modal fade" id="modalAyudaFactura" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                  <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="bi bi-receipt me-2"></i><?php te('help.billingTitle'); ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <?php if (current_lang() === 'en'): ?>
                      <h6 class="fw-bold text-info"><i class="bi bi-plus-circle me-1"></i>Create an invoice</h6>
                      <ol style="line-height:1.9;">
                        <li>Pick the <b>patient</b> and the <b>date</b>.</li>
                        <li>Add <b>line items</b> (service / bill item), quantity and price — the <b>total updates automatically</b>.</li>
                        <li><b>Save</b> the invoice. It then appears in <b>Accounts Receivable</b>.</li>
                      </ol>
                      <h6 class="fw-bold text-info"><i class="bi bi-cash-coin me-1"></i>Accounts Receivable &amp; payments</h6>
                      <ul style="line-height:1.9;">
                        <li>This screen lists each invoice's status (paid / pending / due date).</li>
                        <li>Open an invoice to <b>register a payment</b> (full or partial); the balance updates. You can also <b>print</b> it.</li>
                      </ul>
                      <div class="alert alert-info py-2 mb-0"><b>Note:</b> the catalog of services/prices is in <b>Bill Items</b>. Insurers are managed under <b>Insurance</b>.</div>
                    <?php else: ?>
                      <h6 class="fw-bold text-info"><i class="bi bi-plus-circle me-1"></i>Crear una factura</h6>
                      <ol style="line-height:1.9;">
                        <li>Elige el <b>paciente</b> y la <b>fecha</b>.</li>
                        <li>Agrega los <b>renglones</b> (servicio / bill item), cantidad y precio — el <b>total se calcula solo</b>.</li>
                        <li><b>Guarda</b> la factura. Luego aparece en <b>Cuentas por Cobrar</b>.</li>
                      </ol>
                      <h6 class="fw-bold text-info"><i class="bi bi-cash-coin me-1"></i>Cuentas por Cobrar y pagos</h6>
                      <ul style="line-height:1.9;">
                        <li>Esta pantalla lista el estado de cada factura (pagada / pendiente / vencimiento).</li>
                        <li>Abre una factura para <b>registrar un pago</b> (total o parcial); el saldo se actualiza. También puedes <b>imprimirla</b>.</li>
                      </ul>
                      <div class="alert alert-info py-2 mb-0"><b>Nota:</b> el catálogo de servicios/precios está en <b>Bill Items</b>. Las aseguradoras se gestionan en <b>Seguros</b>.</div>
                    <?php endif; ?>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php te('common.close'); ?></button>
                  </div>
                </div>
              </div>
            </div>
            <script>(function(){var m=document.getElementById('modalAyudaFactura');if(m&&m.parentNode!==document.body)document.body.appendChild(m);})();</script>

            <?php if (!$tablaOk): ?>
                <div class="alert alert-warning">Falta el esquema de facturación.
                    <a href="migrar_facturas_schema.php" class="alert-link">Créalo aquí</a> y luego
                    <a href="importar_facturas_kalix.php" class="alert-link">importa las facturas de Kalix</a>.</div>
            <?php else: ?>

                <!-- Overview general -->
                <div class="row g-2 mb-2">
                    <div class="col"><div class="card shadow-sm text-center"><div class="card-body py-2">
                        <div class="text-muted small">Total facturas</div><div class="h5 mb-0"><?php echo number_format($bk['tot']['c']); ?></div></div></div></div>
                    <div class="col"><div class="card shadow-sm text-center"><div class="card-body py-2">
                        <div class="text-muted small">Total facturado</div><div class="h5 mb-0">$<?php echo number_format($bk['tot']['t'],2); ?></div></div></div></div>
                    <div class="col"><div class="card shadow-sm text-center"><div class="card-body py-2">
                        <div class="text-muted small">Total pagado</div><div class="h5 mb-0 text-success">$<?php echo number_format($bk['tot']['p'],2); ?></div></div></div></div>
                    <div class="col"><div class="card shadow-sm text-center"><div class="card-body py-2">
                        <div class="text-muted small">Saldo por cobrar</div><div class="h5 mb-0 text-danger">$<?php echo number_format($bk['tot']['s'],2); ?></div></div></div></div>
                </div>

                <!-- Desglose por estado (clicable = filtro) -->
                <div class="row g-2 mb-3">
                    <?php
                    $qs = $q!=='' ? '&q='.urlencode($q) : '';
                    $tarjetas = [
                        ['pendientes','⏳ Pendientes','secondary', $bk['pend']['c'], $bk['pend']['s'], 'saldo'],
                        ['parciales','◐ Abonados (parcial)','warning', $bk['parc']['c'], $bk['parc']['s'], 'saldo'],
                        ['pagadas','✅ Pagadas','success', $bk['pag']['c'], $bk['pag']['p'], 'pagado'],
                    ];
                    foreach ($tarjetas as $t): $activo = ($filtro===$t[0]); ?>
                        <div class="col-md-4">
                            <a href="?f=<?php echo $t[0].$qs; ?>" class="text-decoration-none">
                                <div class="card shadow-sm <?php echo $activo?'border-2 border-'.$t[2]:''; ?>">
                                    <div class="card-body py-2 d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-semibold text-<?php echo $t[2]==='warning'?'dark':$t[2]; ?>"><?php echo $t[1]; ?></div>
                                            <div class="text-muted small"><?php echo number_format($t[3]); ?> factura(s)</div>
                                        </div>
                                        <div class="h6 mb-0 <?php echo $t[0]==='pagadas'?'text-success':'text-danger'; ?>">
                                            $<?php echo number_format($t[4],2); ?>
                                            <div class="text-muted small text-end"><?php echo $t[5]==='pagado'?'pagado':'saldo'; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="card shadow-sm mb-3"><div class="card-body py-2">
                    <form method="GET" class="row g-2 align-items-center">
                        <input type="hidden" name="f" value="<?php echo h($filtro); ?>">
                        <div class="col"><div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="q" class="form-control border-start-0" placeholder="Buscar por paciente o № de factura" value="<?php echo h($q); ?>">
                        </div></div>
                        <div class="col-auto">
                            <select name="f" class="form-select" onchange="this.form.submit()">
                                <option value="consaldo"   <?php echo $filtro==='consaldo'?'selected':''; ?>>Con saldo (pendientes + abonados)</option>
                                <option value="pendientes" <?php echo $filtro==='pendientes'?'selected':''; ?>>Solo pendientes</option>
                                <option value="parciales"  <?php echo $filtro==='parciales'?'selected':''; ?>>Solo abonados (parcial)</option>
                                <option value="pagadas"    <?php echo $filtro==='pagadas'?'selected':''; ?>>Solo pagadas</option>
                                <option value="todas"      <?php echo $filtro==='todas'?'selected':''; ?>>Todas</option>
                            </select>
                        </div>
                        <div class="col-auto"><button class="btn btn-primary">Buscar</button>
                            <?php if($q!==''): ?><a href="?f=<?php echo h($filtro); ?>" class="btn btn-outline-secondary">Limpiar</a><?php endif; ?>
                        </div>
                    </form>
                </div></div>

                <div class="card shadow-sm"><div class="card-body p-0"><div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr>
                            <th>Fecha</th><th>№ Factura</th><th>Paciente</th><th>Descripción</th>
                            <th>Vence</th><th class="text-end">Total</th><th class="text-end">Pagado</th>
                            <th class="text-end">Saldo</th><th class="text-center">Estado</th><th></th>
                        </tr></thead>
                        <tbody>
                        <?php if ($rows): foreach ($rows as $f):
                            $nombre = trim(($f['APELLIDOS']??'').' '.($f['NOMBRES']??''));
                            if ($nombre==='') $nombre = $f['client_name'] ?: '—';
                            $venc=''; $overdue=0;
                            if (!empty($f['fecha_vencimiento'])) {
                                $venc = date('m/d/Y', strtotime($f['fecha_vencimiento']));
                                $d = (new DateTime($f['fecha_vencimiento']))->diff($hoy);
                                $overdue = ($hoy > new DateTime($f['fecha_vencimiento'])) ? $d->days : 0;
                            }
                        ?>
                            <tr>
                                <td><small><?php echo $f['fecha']?date('m/d/Y',strtotime($f['fecha'])):'—'; ?></small></td>
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
                                <td class="text-end pe-3">
                                    <a href="factura_ver.php?id=<?php echo (int)$f['id']; ?>" class="btn btn-sm btn-outline-primary py-0 px-2">
                                        <i class="bi bi-eye"></i> <?php echo $f['saldo']>0.001?'Ver / Pagar':'Ver'; ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="10" class="text-center py-5 text-muted"><i class="bi bi-check2-circle fs-2 d-block mb-2"></i>Sin facturas pendientes.</td></tr>
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
