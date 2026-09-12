<?php
/**
 * factura_ver.php
 * Detalle de una factura: cabecera, líneas, historial de pagos, y formulario
 * para registrar un pago/abono. Permite anular pagos. SISTEMA-only.
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

// Eliminar un pago (recalcula)
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion']??'')==='del_pago') {
    $idp=(int)($_POST['id_pago']??0); $idf=(int)($_POST['id_factura']??0);
    if ($idp>0 && $idf>0) {
        $conexion->query("DELETE FROM factura_pagos WHERE id=".$idp." AND id_factura=".$idf);
        $sum=$conexion->query("SELECT COALESCE(SUM(monto),0) p FROM factura_pagos WHERE id_factura=".$idf)->fetch_assoc();
        $tot=$conexion->query("SELECT total FROM facturas WHERE id=".$idf)->fetch_assoc();
        $pag=(float)$sum['p']; $sal=(float)$tot['total']-$pag;
        $u=$conexion->prepare("UPDATE facturas SET pagado=?,saldo=? WHERE id=?"); $u->bind_param('ddi',$pag,$sal,$idf); $u->execute(); $u->close();
    }
    header("Location: factura_ver.php?id=".$idf); exit;
}

$id = (int)($_GET['id'] ?? 0);
$f = null;
$fq = $conexion->prepare("SELECT f.*, P.NOMBRES, P.APELLIDOS, P.CEDULA FROM facturas f LEFT JOIN AG_PACIENTE P ON P.IDPACIENTE=f.IDPACIENTE WHERE f.id=? LIMIT 1");
if ($fq) { $fq->bind_param('i',$id); $fq->execute(); $f=$fq->get_result()->fetch_assoc(); $fq->close(); }
if (!$f) { header("Location: cuentas_por_cobrar.php"); exit; }

$det=[]; $rd=$conexion->query("SELECT * FROM factura_detalle WHERE id_factura=".$id." ORDER BY id");
if ($rd) while($x=$rd->fetch_assoc()) $det[]=$x;
$pagos=[]; $rp=$conexion->query("SELECT * FROM factura_pagos WHERE id_factura=".$id." ORDER BY fecha, id");
if ($rp) while($x=$rp->fetch_assoc()) $pagos[]=$x;

$nombre = trim(($f['APELLIDOS']??'').' '.($f['NOMBRES']??'')); if($nombre==='') $nombre=$f['client_name']?:'—';
$saldo = (float)$f['saldo'];
$estado = $saldo<=0.001 ? ['success','Pagada'] : ((float)$f['pagado']>0.001 ? ['warning','Parcial (abonada)'] : ['secondary','Pendiente']);
$metodos = ['Cash'=>'Efectivo','Credit Card'=>'Tarjeta de crédito','Debit'=>'Débito','Check'=>'Cheque','Electronic Transfer'=>'Transferencia','Insurance'=>'Seguro','Adjustment'=>'Ajuste'];
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="images/favicon.svg">
    <title>Factura <?php echo h($f['billing_number']?:('#'.$f['id'])); ?></title>
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

            <?php if (isset($_GET['nueva'])): ?><div class="alert alert-success">Factura creada correctamente.</div><?php endif; ?>
            <?php if (isset($_GET['pago'])):  ?><div class="alert alert-success">Pago registrado.</div><?php endif; ?>
            <?php if (isset($_GET['err'])):   ?><div class="alert alert-danger">No se pudo registrar el pago (revisa monto y método).</div><?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Factura <?php echo h($f['billing_number']?:('#'.$f['id'])); ?>
                    <span class="badge bg-<?php echo $estado[0]; ?> <?php echo $estado[0]==='warning'?'text-dark':''; ?> ms-2"><?php echo $estado[1]; ?></span>
                </h4>
                <a href="cuentas_por_cobrar.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Cuentas por Cobrar</a>
            </div>

            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="card shadow-sm mb-3"><div class="card-body">
                        <div class="row">
                            <div class="col-6"><small class="text-muted">Paciente</small><div class="fw-semibold"><?php echo h($nombre); ?></div></div>
                            <div class="col-3"><small class="text-muted">Fecha</small><div><?php echo $f['fecha']?date('d/m/Y',strtotime($f['fecha'])):'—'; ?></div></div>
                            <div class="col-3"><small class="text-muted">Vence</small><div><?php echo $f['fecha_vencimiento']?date('d/m/Y',strtotime($f['fecha_vencimiento'])):'—'; ?></div></div>
                            <div class="col-6 mt-2"><small class="text-muted">Facturar a</small><div><?php echo h($f['status']?:'—'); ?></div></div>
                            <div class="col-6 mt-2"><small class="text-muted">Origen</small><div><?php echo h($f['origen']); ?></div></div>
                        </div>
                    </div></div>

                    <div class="card shadow-sm mb-3"><div class="card-body">
                        <h6><i class="bi bi-list-ul"></i> Líneas</h6>
                        <table class="table table-sm">
                            <thead class="table-light"><tr><th>Código</th><th>Descripción</th><th class="text-end">Precio</th><th class="text-center">Cant.</th><th class="text-end">Importe</th></tr></thead>
                            <tbody>
                            <?php if($det): foreach($det as $d): ?>
                                <tr><td><?php echo h($d['code']); ?></td><td><small><?php echo h(mb_strimwidth((string)$d['description'],0,70,'…')); ?></small></td>
                                <td class="text-end">$<?php echo number_format($d['unit_price'],2); ?></td>
                                <td class="text-center"><?php echo (int)$d['quantity']; ?></td>
                                <td class="text-end">$<?php echo number_format($d['importe'],2); ?></td></tr>
                            <?php endforeach; else: ?><tr><td colspan="5" class="text-muted text-center">Sin líneas.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div></div>
                </div>

                <div class="col-lg-5">
                    <div class="card shadow-sm mb-3"><div class="card-body">
                        <div class="d-flex justify-content-between"><span>Total</span><span class="fw-semibold">$<?php echo number_format($f['total'],2); ?></span></div>
                        <div class="d-flex justify-content-between text-success"><span>Pagado</span><span>$<?php echo number_format($f['pagado'],2); ?></span></div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between h5"><span>Saldo</span><span class="<?php echo $saldo>0.001?'text-danger':'text-success'; ?>">$<?php echo number_format($saldo,2); ?></span></div>
                    </div></div>

                    <?php if ($saldo > 0.001): ?>
                    <div class="card shadow-sm mb-3"><div class="card-body">
                        <h6><i class="bi bi-cash-coin"></i> Registrar pago</h6>
                        <form method="POST" action="factura_pago_guardar.php">
                            <input type="hidden" name="id_factura" value="<?php echo (int)$f['id']; ?>">
                            <div class="row g-2">
                                <div class="col-6"><label class="form-label small">Monto</label>
                                    <input type="number" step="0.01" name="monto" class="form-control" value="<?php echo number_format($saldo,2,'.',''); ?>" required></div>
                                <div class="col-6"><label class="form-label small">Fecha</label>
                                    <input type="date" name="fecha" class="form-control" value="<?php echo date('Y-m-d'); ?>"></div>
                                <div class="col-6"><label class="form-label small">Método</label>
                                    <select name="metodo" class="form-select">
                                        <?php foreach ($metodos as $k=>$v): ?><option value="<?php echo h($k); ?>"><?php echo h($v); ?></option><?php endforeach; ?>
                                    </select></div>
                                <div class="col-6"><label class="form-label small">Referencia</label>
                                    <input type="text" name="referencia" class="form-control" placeholder="Nº operación, cheque…"></div>
                            </div>
                            <button class="btn btn-success w-100 mt-3"><i class="bi bi-check-lg"></i> Registrar pago</button>
                        </form>
                    </div></div>
                    <?php endif; ?>

                    <div class="card shadow-sm"><div class="card-body">
                        <h6><i class="bi bi-clock-history"></i> Pagos</h6>
                        <?php if($pagos): ?>
                        <table class="table table-sm">
                            <thead class="table-light"><tr><th>Fecha</th><th>Método</th><th class="text-end">Monto</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach($pagos as $pg): ?>
                                <tr>
                                    <td><small><?php echo $pg['fecha']?date('d/m/Y',strtotime($pg['fecha'])):'—'; ?></small></td>
                                    <td><small><?php echo h($metodos[$pg['metodo']]??$pg['metodo']); ?><?php echo $pg['referencia']?' · '.h($pg['referencia']):''; ?></small></td>
                                    <td class="text-end">$<?php echo number_format($pg['monto'],2); ?></td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('¿Eliminar este pago?');" style="display:inline;">
                                            <input type="hidden" name="accion" value="del_pago">
                                            <input type="hidden" name="id_pago" value="<?php echo (int)$pg['id']; ?>">
                                            <input type="hidden" name="id_factura" value="<?php echo (int)$f['id']; ?>">
                                            <button class="btn btn-sm btn-outline-danger py-0 px-1" title="Eliminar"><i class="bi bi-x"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?><p class="text-muted small mb-0">Aún no hay pagos registrados.</p><?php endif; ?>
                    </div></div>
                </div>
            </div>

        </div></div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
