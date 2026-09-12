<?php
/**
 * bill_items_admin.php
 * CRUD del catálogo de servicios facturables (Bill Items, tipo Kalix).
 * SISTEMA-only. POST-driven para guardar / activar-desactivar.
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

// ¿Existe la tabla?
$tablaOk = (bool)($conexion->query("SHOW TABLES LIKE 'bill_items'")->num_rows ?? 0);

$mensaje = null;
if ($tablaOk && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'guardar') {
        $id     = (int)($_POST['id'] ?? 0);
        $code   = trim($_POST['code'] ?? '');
        $pos    = trim($_POST['pos'] ?? '');
        $desc   = trim($_POST['description'] ?? '');
        $price  = (float)($_POST['unit_price'] ?? 0);
        $units  = (int)($_POST['default_units'] ?? 1);
        $taxOn  = isset($_POST['add_tax']) ? 1 : 0;
        $taxR   = ($_POST['tax_rate'] ?? '') === '' ? null : (float)$_POST['tax_rate'];
        $contract = ($_POST['contract_amount'] ?? '') === '' ? null : (float)$_POST['contract_amount'];
        $mods   = trim($_POST['modifiers'] ?? '');
        if ($desc === '') {
            $mensaje = ['danger','La descripción es obligatoria.'];
        } elseif ($id > 0) {
            $st = $conexion->prepare("UPDATE bill_items SET code=?,pos=?,description=?,unit_price=?,default_units=?,add_tax=?,tax_rate=?,contract_amount=?,modifiers=? WHERE id=?");
            $st->bind_param('sssdiiddsi',$code,$pos,$desc,$price,$units,$taxOn,$taxR,$contract,$mods,$id);
            $st->execute(); $st->close();
            $mensaje = ['success','Servicio actualizado.'];
        } else {
            $st = $conexion->prepare("INSERT INTO bill_items (code,pos,description,unit_price,default_units,add_tax,tax_rate,contract_amount,modifiers,estado) VALUES (?,?,?,?,?,?,?,?,?,1)");
            $st->bind_param('sssdiidds',$code,$pos,$desc,$price,$units,$taxOn,$taxR,$contract,$mods);
            $st->execute(); $st->close();
            $mensaje = ['success','Servicio creado.'];
        }
    } elseif ($accion === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $st = $conexion->prepare("UPDATE bill_items SET estado = IF(estado=1,0,1) WHERE id=?");
        $st->bind_param('i',$id); $st->execute(); $st->close();
        $mensaje = ['success','Estado actualizado.'];
    }
}

$items = [];
if ($tablaOk) {
    $q = trim($_GET['q'] ?? '');
    $qEsc = $conexion->real_escape_string($q);
    $where = $q !== '' ? "WHERE code LIKE '%$qEsc%' OR description LIKE '%$qEsc%'" : '';
    $r = $conexion->query("SELECT * FROM bill_items $where ORDER BY code, pos");
    if ($r) while ($x = $r->fetch_assoc()) $items[] = $x;
}
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="images/favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bill Items</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./main.css" rel="stylesheet">
    <script src="js/jquery.min.js"></script>
</head>
<body>
<div class="app-container app-theme-white body-tabs-shadow fixed-sidebar fixed-header">
    <div class="app-header header-shadow">
        <div class="app-header__logo"><div class="logo-src"></div>
            <div class="header__pane ml-auto"><button type="button" class="hamburger close-sidebar-btn hamburger--elastic" data-class="closed-sidebar"><span class="hamburger-box"><span class="hamburger-inner"></span></span></button></div>
        </div>
        <div class="app-header__mobile-menu"><button type="button" class="hamburger hamburger--elastic mobile-toggle-nav"><span class="hamburger-box"><span class="hamburger-inner"></span></span></button></div>
        <div class="app-header__menu"><button type="button" class="btn-icon btn-icon-only btn btn-primary btn-sm mobile-toggle-header-nav"><span class="btn-icon-wrapper"><i class="fa fa-ellipsis-v"></i></span></button></div>
        <div class="app-header__content"><div class="app-header-left"></div>
            <div class="app-header-right"><div class="header-btn-lg pr-0"><div class="widget-content p-0"><div class="widget-content-wrapper">
                <div class="widget-content-left ml-3 header-user-info">
                    <div class="widget-heading"><?php echo h($_SESSION['nombres'] ?? ''); ?></div>
                    <div class="widget-subheading"><?php echo h($_SESSION['rol'] ?? ''); ?></div>
                </div>
            </div></div></div></div>
        </div>
    </div>
    <div class="app-main">
        <div class="app-sidebar sidebar-shadow"><?php include("./menu/menu_adm.php"); ?></div>
        <div class="app-main__outer"><div class="app-main__inner">

            <div class="app-page-title mb-3"><div class="page-title-wrapper"><div class="page-title-heading">
                <div class="page-title-icon"><i class="pe-7s-cash icon-gradient bg-plum-plate"></i></div>
                <div>Bill Items <div class="page-title-subheading">Servicios facturables (CPT/HCPC) — precio, unidades, POS.</div></div>
            </div>
            <div class="page-title-actions">
                <button class="btn btn-primary btn-sm" onclick="nuevoItem()"><i class="bi bi-plus-lg"></i> Nuevo servicio</button>
            </div>
            </div></div>

            <?php if (!$tablaOk): ?>
                <div class="alert alert-warning">
                    La tabla <code>bill_items</code> aún no existe.
                    <a href="migrar_bill_items.php" class="alert-link">Ejecuta la migración</a> para crearla y sembrar los 6 códigos MNT.
                </div>
            <?php else: ?>
                <?php if ($mensaje): ?><div class="alert alert-<?php echo h($mensaje[0]); ?>"><?php echo h($mensaje[1]); ?></div><?php endif; ?>

                <div class="card shadow-sm mb-3"><div class="card-body py-2">
                    <form method="GET" class="row g-2 align-items-center">
                        <div class="col"><div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="q" class="form-control border-start-0" placeholder="Buscar por código o descripción" value="<?php echo h($_GET['q'] ?? ''); ?>">
                        </div></div>
                        <div class="col-auto"><button class="btn btn-primary">Buscar</button></div>
                    </form>
                </div></div>

                <div class="card shadow-sm"><div class="card-body p-0"><div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr>
                            <th>Code</th><th>POS</th><th>Descripción</th>
                            <th class="text-end">Unit&nbsp;Price</th><th class="text-center">Units</th>
                            <th class="text-end">Total</th><th class="text-center">Estado</th><th class="text-end pe-3">Acciones</th>
                        </tr></thead>
                        <tbody>
                        <?php if ($items): foreach ($items as $it):
                            $total = (float)$it['unit_price'] * (int)$it['default_units'];
                        ?>
                            <tr class="<?php echo $it['estado']==1?'':'text-muted'; ?>">
                                <td><strong><?php echo h($it['code']); ?></strong></td>
                                <td><span class="badge bg-secondary"><?php echo h($it['pos']); ?></span></td>
                                <td style="max-width:420px;"><small><?php echo h($it['description']); ?></small></td>
                                <td class="text-end">$<?php echo number_format($it['unit_price'],2); ?></td>
                                <td class="text-center"><?php echo (int)$it['default_units']; ?></td>
                                <td class="text-end">$<?php echo number_format($total,2); ?></td>
                                <td class="text-center">
                                    <?php if ($it['estado']==1): ?><span class="badge bg-success">Activo</span>
                                    <?php else: ?><span class="badge bg-secondary">Inactivo</span><?php endif; ?>
                                </td>
                                <td class="text-end pe-3" style="white-space:nowrap;">
                                    <button class="btn btn-sm btn-outline-secondary py-0 px-2" onclick='editarItem(<?php echo json_encode($it, JSON_HEX_APOS|JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil-square"></i></button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="accion" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo (int)$it['id']; ?>">
                                        <button class="btn btn-sm btn-outline-<?php echo $it['estado']==1?'warning':'success'; ?> py-0 px-2" title="Activar/Desactivar">
                                            <i class="bi bi-<?php echo $it['estado']==1?'pause':'play'; ?>-fill"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="8" class="text-center py-5 text-muted"><i class="bi bi-cash-stack fs-2 d-block mb-2"></i>Sin servicios. Crea uno o corre la migración.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div></div></div>
            <?php endif; ?>

        </div></div>
    </div>
</div>

<!-- Modal crear/editar -->
<div class="modal fade" id="modalItem" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="POST">
        <div class="modal-header py-2" style="background:#5a2d82;">
            <h6 class="modal-title text-white mb-0"><i class="bi bi-cash-coin me-2"></i><span id="miTitulo">Nuevo servicio</span></h6>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="id" id="mi_id">
            <div class="row g-2">
                <div class="col-md-3"><label class="form-label small fw-semibold">Code (CPT/HCPC)</label><input type="text" name="code" id="mi_code" class="form-control"></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Place of Service</label>
                    <select name="pos" id="mi_pos" class="form-select">
                        <option value="11">11 — Office</option>
                        <option value="12">12 — Home / Telehealth</option>
                        <option value="02">02 — Telehealth</option>
                        <option value="">—</option>
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Modifiers (opcional)</label><input type="text" name="modifiers" id="mi_mods" class="form-control" placeholder="ej: GT, 95"></div>
                <div class="col-12"><label class="form-label small fw-semibold">Descripción *</label><textarea name="description" id="mi_desc" class="form-control" rows="2" required></textarea></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Unit Price ($)</label><input type="number" step="0.01" name="unit_price" id="mi_price" class="form-control" oninput="calcTotal()"></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Units</label><input type="number" name="default_units" id="mi_units" class="form-control" value="1" oninput="calcTotal()"></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Total</label><input type="text" id="mi_total" class="form-control" readonly></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Contract Amount (opcional)</label><input type="number" step="0.01" name="contract_amount" id="mi_contract" class="form-control"></div>
                <div class="col-md-3 d-flex align-items-center"><div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="add_tax" id="mi_tax" value="1">
                    <label class="form-check-label small" for="mi_tax">Add Tax</label>
                </div></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Tax Rate %</label><input type="number" step="0.01" name="tax_rate" id="mi_taxr" class="form-control"></div>
            </div>
        </div>
        <div class="modal-footer py-2">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg"></i> Guardar</button>
        </div>
    </form>
</div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let miModal = null;
function _m(){ if(!miModal) miModal = new bootstrap.Modal(document.getElementById('modalItem')); return miModal; }
function calcTotal(){
    const p = parseFloat(document.getElementById('mi_price').value)||0;
    const u = parseInt(document.getElementById('mi_units').value)||0;
    document.getElementById('mi_total').value = '$' + (p*u).toFixed(2);
}
function nuevoItem(){
    document.getElementById('miTitulo').textContent = 'Nuevo servicio';
    document.getElementById('mi_id').value=''; document.getElementById('mi_code').value='';
    document.getElementById('mi_pos').value='11'; document.getElementById('mi_mods').value='';
    document.getElementById('mi_desc').value=''; document.getElementById('mi_price').value='';
    document.getElementById('mi_units').value='1'; document.getElementById('mi_contract').value='';
    document.getElementById('mi_tax').checked=false; document.getElementById('mi_taxr').value='';
    calcTotal(); _m().show();
}
function editarItem(it){
    document.getElementById('miTitulo').textContent = 'Editar servicio';
    document.getElementById('mi_id').value=it.id; document.getElementById('mi_code').value=it.code||'';
    document.getElementById('mi_pos').value=it.pos||''; document.getElementById('mi_mods').value=it.modifiers||'';
    document.getElementById('mi_desc').value=it.description||''; document.getElementById('mi_price').value=it.unit_price||'';
    document.getElementById('mi_units').value=it.default_units||1; document.getElementById('mi_contract').value=it.contract_amount||'';
    document.getElementById('mi_tax').checked = String(it.add_tax)==='1'; document.getElementById('mi_taxr').value=it.tax_rate||'';
    calcTotal(); _m().show();
}
</script>
</body>
</html>
