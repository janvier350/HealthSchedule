<?php
/**
 * crear_factura.php
 * Crea una factura nueva (hacia adelante): paciente + líneas de servicio
 * (desde el catálogo Bill Items) + vencimiento. Guarda facturas + detalle.
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

// ── POST: guardar factura ─────────────────────────────────────────────
$okId = 0; $err = '';
if ($tablaOk && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $idPac  = (int)($_POST['idpaciente'] ?? 0);
    $fecha  = trim($_POST['fecha'] ?? '') ?: date('Y-m-d');
    $venc   = trim($_POST['vencimiento'] ?? '');
    $status = trim($_POST['status'] ?? 'Billing Client');
    $notas  = trim($_POST['notas'] ?? '');
    $codes  = $_POST['d_code']  ?? [];
    $descs  = $_POST['d_desc']  ?? [];
    $prices = $_POST['d_price'] ?? [];
    $qtys   = $_POST['d_qty']   ?? [];

    if ($idPac <= 0) { $err = 'Selecciona un paciente.'; }
    else {
        // nombre del paciente para el snapshot
        $cn=''; $kc='';
        $sp = $conexion->prepare("SELECT NOMBRES, APELLIDOS, KALIX_ID FROM AG_PACIENTE WHERE IDPACIENTE=? LIMIT 1");
        if ($sp) { $sp->bind_param('i',$idPac); $sp->execute(); if($rp=$sp->get_result()->fetch_assoc()){ $cn=trim($rp['APELLIDOS'].', '.$rp['NOMBRES']); $kc=$rp['KALIX_ID']??''; } $sp->close(); }

        // calcular total desde las líneas
        $lineas=[]; $total=0;
        foreach ($codes as $i=>$c) {
            $desc = trim($descs[$i] ?? '');
            $price= (float)($prices[$i] ?? 0);
            $qty  = (int)($qtys[$i] ?? 0);
            if ($desc==='' && $price<=0 && $qty<=0) continue;
            $imp = $price*$qty;
            $total += $imp;
            $lineas[] = [trim($c), $desc, $price, $qty, $imp];
        }
        if (!$lineas) { $err = 'Agrega al menos una línea de servicio.'; }
        else {
            $vencParam = $venc!=='' ? $venc : null;
            $descResumen = $lineas[0][1];
            $ins = $conexion->prepare("INSERT INTO facturas (IDPACIENTE,kalix_client_id,client_name,fecha,fecha_vencimiento,status,descripcion,total,pagado,saldo,origen,creado_por) VALUES (?,?,?,?,?,?,?,?,0,?,'app',?)");
            $creadoPor = (int)($_SESSION['iduser'] ?? 0);
            $ins->bind_param('issssssddi',$idPac,$kc,$cn,$fecha,$vencParam,$status,$descResumen,$total,$total,$creadoPor);
            if ($ins->execute()) {
                $okId = (int)$conexion->insert_id;
                $ins->close();
                // billing_number legible para facturas de la app
                $bn = 'F'.str_pad((string)$okId,6,'0',STR_PAD_LEFT);
                $conexion->query("UPDATE facturas SET billing_number='".$conexion->real_escape_string($bn)."' WHERE id=".$okId);
                // detalle
                $ind = $conexion->prepare("INSERT INTO factura_detalle (id_factura,billing_number,code,description,unit_price,quantity,importe) VALUES (?,?,?,?,?,?,?)");
                foreach ($lineas as $l) {
                    $ind->bind_param('isssdid',$okId,$bn,$l[0],$l[1],$l[2],$l[3],$l[4]);
                    $ind->execute();
                }
                $ind->close();
                header("Location: factura_ver.php?id=".$okId."&nueva=1"); exit;
            } else { $err = 'Error al guardar: '.$ins->error; $ins->close(); }
        }
    }
}

// Datos para selects
$pacientes = [];
$rp = $conexion->query("SELECT IDPACIENTE, NOMBRES, APELLIDOS, CEDULA FROM AG_PACIENTE WHERE ESTADO='A' ORDER BY APELLIDOS, NOMBRES");
if ($rp) while ($x=$rp->fetch_assoc()) $pacientes[]=$x;

$billItems = [];
if (($conexion->query("SHOW TABLES LIKE 'bill_items'")->num_rows ?? 0) > 0) {
    $rb = $conexion->query("SELECT id, code, pos, description, unit_price, default_units FROM bill_items WHERE estado=1 ORDER BY code, pos");
    if ($rb) while ($x=$rb->fetch_assoc()) $billItems[]=$x;
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="images/favicon.svg">
    <title>Crear factura</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./main.css" rel="stylesheet">
    <script src="js/jquery.min.js"></script>
    <style>.select2-container{width:100%!important}.select2-container .select2-selection--single{height:calc(2.375rem + 2px);display:flex;align-items:center}</style>
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
                <div class="page-title-icon"><i class="pe-7s-note2 icon-gradient bg-plum-plate"></i></div>
                <div>Crear factura <div class="page-title-subheading">Nueva factura para un paciente.</div></div>
            </div>
            <div class="page-title-actions"><a href="cuentas_por_cobrar.php" class="btn btn-outline-secondary btn-sm">Cuentas por Cobrar</a></div>
            </div></div>

            <?php if (!$tablaOk): ?>
                <div class="alert alert-warning">Falta el esquema de facturación. <a href="migrar_facturas_schema.php" class="alert-link">Créalo aquí</a>.</div>
            <?php else: ?>
                <?php if ($err): ?><div class="alert alert-danger"><?php echo h($err); ?></div><?php endif; ?>
                <form method="POST">
                    <div class="card shadow-sm mb-3"><div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Paciente *</label>
                                <select name="idpaciente" id="selPaciente" class="form-select" required>
                                    <option value="">— Selecciona —</option>
                                    <?php foreach ($pacientes as $p): ?>
                                        <option value="<?php echo (int)$p['IDPACIENTE']; ?>"><?php echo h(trim($p['APELLIDOS'].', '.$p['NOMBRES']).($p['CEDULA']?' · '.$p['CEDULA']:'')); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2"><label class="form-label">Fecha</label><input type="date" name="fecha" class="form-control" value="<?php echo date('Y-m-d'); ?>"></div>
                            <div class="col-md-2"><label class="form-label">Vencimiento</label><input type="date" name="vencimiento" class="form-control"></div>
                            <div class="col-md-2"><label class="form-label">Facturar a</label>
                                <select name="status" class="form-select">
                                    <option value="Billing Client">Cliente</option>
                                    <option value="Billing Primary Insurance">Seguro primario</option>
                                    <option value="Ready to Batch">Listo para batch</option>
                                </select>
                            </div>
                        </div>
                    </div></div>

                    <div class="card shadow-sm mb-3"><div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0"><i class="bi bi-list-ul"></i> Líneas de servicio</h6>
                            <button type="button" class="btn btn-sm btn-success" onclick="addLinea()"><i class="bi bi-plus-lg"></i> Agregar línea</button>
                        </div>
                        <div class="table-responsive"><table class="table table-sm align-middle">
                            <thead class="table-light"><tr>
                                <th style="width:34%;">Servicio (Bill Item)</th><th>Código</th><th class="text-end">Precio</th>
                                <th class="text-center" style="width:90px;">Cant.</th><th class="text-end">Importe</th><th></th>
                            </tr></thead>
                            <tbody id="lineasBody"></tbody>
                            <tfoot><tr><th colspan="4" class="text-end">Total</th><th class="text-end" id="granTotal">$0.00</th><th></th></tr></tfoot>
                        </table></div>
                        <div class="mt-2"><label class="form-label">Notas</label><input type="text" name="notas" class="form-control"></div>
                    </div></div>

                    <div class="mb-4">
                        <button class="btn btn-primary btn-lg"><i class="bi bi-check-lg"></i> Guardar factura</button>
                        <a href="cuentas_por_cobrar.php" class="btn btn-link">Cancelar</a>
                    </div>
                </form>
            <?php endif; ?>
        </div></div>
    </div>
</div>

<template id="tplLinea">
    <tr data-linea>
        <td>
            <select class="form-select form-select-sm bill-item" onchange="fillFromItem(this)">
                <option value="">— Elegir servicio o escribir manual —</option>
                <?php foreach ($billItems as $bi): ?>
                    <option value="<?php echo (int)$bi['id']; ?>"
                        data-code="<?php echo h($bi['code']); ?>"
                        data-desc="<?php echo h($bi['description']); ?>"
                        data-price="<?php echo h($bi['unit_price']); ?>"
                        data-units="<?php echo h($bi['default_units']); ?>">
                        <?php echo h($bi['code'].' (POS '.$bi['pos'].') — $'.number_format($bi['unit_price'],2)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="d_desc[]" class="form-control form-control-sm mt-1 d-desc" placeholder="Descripción">
        </td>
        <td><input type="text" name="d_code[]" class="form-control form-control-sm d-code" style="width:90px;"></td>
        <td><input type="number" step="0.01" name="d_price[]" class="form-control form-control-sm text-end d-price" oninput="recalc()"></td>
        <td><input type="number" name="d_qty[]" class="form-control form-control-sm text-center d-qty" value="1" oninput="recalc()"></td>
        <td class="text-end d-importe">$0.00</td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="delLinea(this)"><i class="bi bi-trash"></i></button></td>
    </tr>
</template>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function(){ $('#selPaciente').select2({width:'100%',placeholder:'Buscar paciente…'}); });
function addLinea(){
    const t=document.getElementById('tplLinea');
    document.getElementById('lineasBody').appendChild(t.content.cloneNode(true));
    recalc();
}
function delLinea(btn){ btn.closest('[data-linea]').remove(); recalc(); }
function fillFromItem(sel){
    const o=sel.options[sel.selectedIndex]; if(!o.value) return;
    const row=sel.closest('[data-linea]');
    row.querySelector('.d-code').value  = o.dataset.code||'';
    row.querySelector('.d-desc').value  = o.dataset.desc||'';
    row.querySelector('.d-price').value = o.dataset.price||'';
    row.querySelector('.d-qty').value   = o.dataset.units||1;
    recalc();
}
function recalc(){
    let tot=0;
    document.querySelectorAll('#lineasBody [data-linea]').forEach(function(r){
        const p=parseFloat(r.querySelector('.d-price').value)||0;
        const q=parseInt(r.querySelector('.d-qty').value)||0;
        const imp=p*q; tot+=imp;
        r.querySelector('.d-importe').textContent='$'+imp.toFixed(2);
    });
    document.getElementById('granTotal').textContent='$'+tot.toFixed(2);
}
document.addEventListener('DOMContentLoaded', addLinea);
</script>
</body>
</html>
