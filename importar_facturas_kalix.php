<?php
/**
 * importar_facturas_kalix.php
 * Importa el historial de facturación de Kalix desde 2 CSV:
 *   - Billing.csv    (export "Bills")      -> facturas (cabecera + total/pagado/saldo)
 *   - Bill_Items.csv (export "Bill Items") -> factura_detalle (líneas)
 *
 * Modo dry-run ACTIVADO por defecto. Idempotente por billing_number.
 * Resuelve el paciente por KalixClientId -> AG_PACIENTE.KALIX_ID.
 * SISTEMA-only, POST-driven.
 */
@set_time_limit(600);
@ini_set('memory_limit', '512M');
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
if (!isset($_SESSION["rol"]) || strtoupper($_SESSION["rol"]) !== 'SISTEMA') {
    http_response_code(403); exit('Acceso restringido: sólo SISTEMA.');
}
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// ¿Existe el esquema?
$tablaOk = ($conexion->query("SHOW TABLES LIKE 'facturas'")->num_rows ?? 0) > 0;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Importar facturas (Kalix)</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:720px;">
<h4>Importar facturación desde Kalix</h4>
<?php if (!$tablaOk): ?>
<div class="alert alert-warning">Primero crea el esquema:
<a href="migrar_facturas_schema.php" class="alert-link">migrar_facturas_schema.php</a>.</div>
<?php endif; ?>
<p class="text-muted">Sube los 2 CSV de Kalix (Billing → Export): <b>Bills</b> y <b>Bill Items</b>.
Idempotente por número de factura. Resuelve el paciente por su Kalix Id.</p>
<form method="POST" enctype="multipart/form-data" class="card card-body">
    <div class="mb-3">
        <label class="form-label"><b>Bills</b> CSV (cabeceras de factura)</label>
        <input type="file" name="bills" accept=".csv" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label"><b>Bill Items</b> CSV (líneas) <span class="text-muted">— opcional</span></label>
        <input type="file" name="items" accept=".csv" class="form-control">
    </div>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="dryrun" id="dryrun" value="1" checked>
        <label class="form-check-label" for="dryrun"><b>Solo previsualizar</b> (no escribe nada)</label>
    </div>
    <button class="btn btn-primary" <?php echo $tablaOk?'':'disabled'; ?>>Procesar</button>
    <a class="btn btn-link" href="cuentas_por_cobrar.php">Volver</a>
</form>
</div></body></html>
    <?php
    exit;
}

if (!$tablaOk) { exit('Falta el esquema. Corre migrar_facturas_schema.php primero.'); }

$dryRun = isset($_POST['dryrun']);
if (!isset($_FILES['bills']) || $_FILES['bills']['error'] !== UPLOAD_ERR_OK) {
    exit('Error al subir Bills CSV.');
}

// Mapa KalixClientId -> IDPACIENTE (si existe la columna)
$mapPac = [];
$tieneKalix = ($conexion->query("SHOW COLUMNS FROM AG_PACIENTE LIKE 'KALIX_ID'")->num_rows ?? 0) > 0;
if ($tieneKalix) {
    $r = $conexion->query("SELECT IDPACIENTE, KALIX_ID FROM AG_PACIENTE WHERE KALIX_ID IS NOT NULL AND KALIX_ID<>''");
    if ($r) while ($x = $r->fetch_assoc()) $mapPac[$x['KALIX_ID']] = (int)$x['IDPACIENTE'];
}

function abrirCsv($tmp, &$idx) {
    $fh = fopen($tmp, 'r');
    if (!$fh) return null;
    $header = fgetcsv($fh);
    if (!$header) { fclose($fh); return null; }
    $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
    $idx = array_flip($header);
    return $fh;
}
$colf = function($row,$idx,$name){ return isset($idx[$name])&&isset($row[$idx[$name]]) ? trim($row[$idx[$name]]) : ''; };
$num  = function($v){ $v=str_replace([',','$'],'',(string)$v); return $v===''?0:(float)$v; };
$fecha= function($v){ $v=trim($v); return ($v===''||$v==='0000-00-00')?null:substr($v,0,10); };

// ── 1) FACTURAS (Bills) ───────────────────────────────────────────────
$idxB = [];
$fhB = abrirCsv($_FILES['bills']['tmp_name'], $idxB);
if (!$fhB) exit('No se pudo leer Bills CSV.');

$nFac=0;$nFacNew=0;$nFacUpd=0;$nConPac=0;$nSinPac=0;$sumTotal=0;$sumPagado=0;$sumSaldo=0;$errores=[];
$idPorBilling = []; // billing_number -> id_factura (para el detalle)

$selFac = $conexion->prepare("SELECT id FROM facturas WHERE billing_number=? LIMIT 1");

while (($row = fgetcsv($fhB)) !== false) {
    if (count($row)===1 && trim($row[0])==='') continue;
    $billing = $colf($row,$idxB,'Billing Number');
    if ($billing==='') continue;
    $nFac++;

    $kcid   = $colf($row,$idxB,'KalixClientId');
    $idPac  = $kcid!=='' && isset($mapPac[$kcid]) ? $mapPac[$kcid] : null;
    if ($idPac) $nConPac++; else $nSinPac++;
    $cname  = $colf($row,$idxB,'ClientName');
    $apptId = $colf($row,$idxB,'AppointmentId');
    $fe     = $fecha($colf($row,$idxB,'Date'));
    $due    = $fecha($colf($row,$idxB,'DueDate'));
    $status = $colf($row,$idxB,'Status');
    $desc   = $colf($row,$idxB,'Description');
    $total  = $num($colf($row,$idxB,'Total'));
    $contract=$num($colf($row,$idxB,'Contract'));
    $paid   = $num($colf($row,$idxB,'Paid'));
    $tax    = $num($colf($row,$idxB,'Tax'));
    $exp    = $colf($row,$idxB,'Expected');
    $saldo  = $exp!=='' ? $num($exp) : ($total - $paid);
    $notas  = $colf($row,$idxB,'Notes');

    $sumTotal+=$total; $sumPagado+=$paid; $sumSaldo+=$saldo;

    if ($dryRun) { $nFacNew++; continue; }

    // upsert por billing_number
    $selFac->bind_param('s',$billing);
    $selFac->execute();
    $ex = $selFac->get_result()->fetch_assoc();
    if ($ex) {
        $idFac = (int)$ex['id'];
        $up = $conexion->prepare("UPDATE facturas SET IDPACIENTE=?,kalix_client_id=?,client_name=?,kalix_appointment_id=?,fecha=?,fecha_vencimiento=?,status=?,descripcion=?,total=?,contract=?,pagado=?,impuesto=?,saldo=?,notas=?,origen='kalix' WHERE id=?");
        $up->bind_param('issssssssddddsi',$idPac,$kcid,$cname,$apptId,$fe,$due,$status,$desc,$total,$contract,$paid,$tax,$saldo,$notas,$idFac);
        $up->execute(); $up->close();
        $nFacUpd++;
    } else {
        $ins = $conexion->prepare("INSERT INTO facturas (billing_number,IDPACIENTE,kalix_client_id,client_name,kalix_appointment_id,fecha,fecha_vencimiento,status,descripcion,total,contract,pagado,impuesto,saldo,notas,origen) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'kalix')");
        $ins->bind_param('sisssssssddddss',$billing,$idPac,$kcid,$cname,$apptId,$fe,$due,$status,$desc,$total,$contract,$paid,$tax,$saldo,$notas);
        if ($ins->execute()) { $idFac=(int)$conexion->insert_id; $nFacNew++; }
        else { $errores[]="Factura $billing: ".$ins->error; $ins->close(); continue; }
        $ins->close();
    }
    $idPorBilling[$billing] = $idFac;

    // Registrar el pago histórico agregado (para conservar el saldo)
    if ($paid > 0) {
        $conexion->query("DELETE FROM factura_pagos WHERE id_factura=".$idFac." AND metodo='Importado (Kalix)'");
        $pg = $conexion->prepare("INSERT INTO factura_pagos (id_factura,fecha,monto,metodo,referencia) VALUES (?,?,?,'Importado (Kalix)','Migración')");
        $pg->bind_param('isd',$idFac,$fe,$paid);
        $pg->execute(); $pg->close();
    }
}
fclose($fhB);
$selFac->close();

// ── 2) DETALLE (Bill Items) ───────────────────────────────────────────
$nDet=0;$nDetNew=0;
if (!$dryRun && isset($_FILES['items']) && $_FILES['items']['error']===UPLOAD_ERR_OK) {
    $idxI=[];
    $fhI = abrirCsv($_FILES['items']['tmp_name'],$idxI);
    if ($fhI) {
        // Resolver id_factura para billing_numbers no cacheados
        $selF2 = $conexion->prepare("SELECT id FROM facturas WHERE billing_number=? LIMIT 1");
        // Limpiar detalle existente de las facturas que aparezcan (idempotente)
        $limpiadas = [];
        while (($row=fgetcsv($fhI))!==false) {
            if (count($row)===1 && trim($row[0])==='') continue;
            $billing=$colf($row,$idxI,'Billing Number');
            if ($billing==='') continue;
            $nDet++;
            $idFac = $idPorBilling[$billing] ?? null;
            if ($idFac===null) {
                $selF2->bind_param('s',$billing); $selF2->execute();
                $rf=$selF2->get_result()->fetch_assoc();
                if ($rf){ $idFac=(int)$rf['id']; $idPorBilling[$billing]=$idFac; }
            }
            if (!$idFac) continue;
            if (!isset($limpiadas[$idFac])) { $conexion->query("DELETE FROM factura_detalle WHERE id_factura=".$idFac); $limpiadas[$idFac]=1; }
            $desc=$colf($row,$idxI,'Description');
            $code=strtok($desc,':'); // "97802" de "97802: Medical..."
            $up=$num($colf($row,$idxI,'UnitPrice'));
            $qty=(int)$num($colf($row,$idxI,'Quantity'));
            $imp=$num($colf($row,$idxI,'Total'));
            $ins=$conexion->prepare("INSERT INTO factura_detalle (id_factura,billing_number,code,description,unit_price,quantity,importe) VALUES (?,?,?,?,?,?,?)");
            $ins->bind_param('isssdid',$idFac,$billing,$code,$desc,$up,$qty,$imp);
            if ($ins->execute()) $nDetNew++;
            $ins->close();
        }
        $selF2->close();
        fclose($fhI);
    }
}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Resultado facturas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:820px;">
<h4>Resultado — Importación de facturas
<?php echo $dryRun?'<span class="badge bg-warning text-dark">PREVISUALIZACIÓN</span>':'<span class="badge bg-success">IMPORTADO</span>'; ?></h4>
<ul class="list-group my-3">
    <li class="list-group-item d-flex justify-content-between">Facturas leídas <span class="badge bg-secondary"><?php echo $nFac; ?></span></li>
    <li class="list-group-item d-flex justify-content-between"><?php echo $dryRun?'Se crearían':'Creadas'; ?> <span class="badge bg-primary"><?php echo $nFacNew; ?></span></li>
    <?php if(!$dryRun): ?><li class="list-group-item d-flex justify-content-between">Actualizadas <span class="badge bg-info text-dark"><?php echo $nFacUpd; ?></span></li><?php endif; ?>
    <li class="list-group-item d-flex justify-content-between">Ligadas a un paciente <span class="badge bg-success"><?php echo $nConPac; ?></span></li>
    <li class="list-group-item d-flex justify-content-between">Sin paciente (solo nombre) <span class="badge bg-warning text-dark"><?php echo $nSinPac; ?></span></li>
    <li class="list-group-item d-flex justify-content-between">Líneas de detalle <?php echo $dryRun?'(no procesadas en preview)':'importadas'; ?> <span class="badge bg-dark"><?php echo $nDetNew; ?></span></li>
</ul>
<table class="table table-sm w-auto">
    <tr><th class="text-end">Total facturado</th><td class="text-end">$<?php echo number_format($sumTotal,2); ?></td></tr>
    <tr><th class="text-end">Total pagado</th><td class="text-end">$<?php echo number_format($sumPagado,2); ?></td></tr>
    <tr><th class="text-end">Saldo pendiente (por cobrar)</th><td class="text-end text-danger fw-bold">$<?php echo number_format($sumSaldo,2); ?></td></tr>
</table>
<?php if ($errores): ?><div class="alert alert-danger"><b>Errores (<?php echo count($errores); ?>):</b><ul class="mb-0 small"><?php foreach(array_slice($errores,0,20) as $e) echo '<li>'.h($e).'</li>'; ?></ul></div><?php endif; ?>
<?php if ($dryRun): ?><div class="alert alert-warning">Previsualización. Para importar, vuelve y <b>desmarca "Solo previsualizar"</b> (y adjunta también el Bill Items CSV).</div><?php endif; ?>
<a class="btn btn-primary mt-2" href="importar_facturas_kalix.php">Volver</a>
<a class="btn btn-outline-secondary mt-2" href="cuentas_por_cobrar.php">Ir a Cuentas por Cobrar</a>
</div></body></html>
