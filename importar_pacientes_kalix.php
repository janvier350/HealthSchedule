<?php
/**
 * importar_pacientes_kalix.php
 * Importa pacientes (y sus seguros) desde el export CSV de Kalix
 * ("clients" export). SISTEMA-only, POST-driven.
 *
 * Seguridad de datos:
 *  - Modo "solo previsualizar" (dry-run) ACTIVADO por defecto: muestra qué
 *    haría sin escribir nada. La importación real solo ocurre al desmarcarlo.
 *  - Idempotente: usa la columna AG_PACIENTE.KALIX_ID (se crea si no existe)
 *    para no duplicar pacientes al re-ejecutar.
 *  - Aseguradoras: se resuelven por nombre (case-insensitive) contra
 *    `seguros`; las que no existan se crean.
 */
@set_time_limit(300);
@ini_set('memory_limit', '512M');
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/lang/i18n.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

if (!isset($_SESSION["rol"]) || strtoupper($_SESSION["rol"]) !== 'SISTEMA') {
    http_response_code(403); exit('Acceso restringido: sólo SISTEMA.');
}

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

/* ── Formulario (GET) ─────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Importar pacientes (Kalix)</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:720px;">
<h4>Importar pacientes desde Kalix (CSV)</h4>
<p class="text-muted">Sube el archivo <code>clients.csv</code> exportado de Kalix. Se importan datos
demográficos + seguros. Idempotente por <code>KALIX_ID</code>.</p>
<form method="POST" enctype="multipart/form-data" class="card card-body">
    <div class="mb-3">
        <label class="form-label">Archivo CSV de clientes (Kalix)</label>
        <input type="file" name="csv" accept=".csv,text/csv" class="form-control" required>
    </div>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="dryrun" id="dryrun" value="1" checked>
        <label class="form-check-label" for="dryrun">
            <b>Solo previsualizar</b> (no escribe nada; muestra qué se importaría)
        </label>
    </div>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="conSeguros" id="conSeguros" value="1" checked>
        <label class="form-check-label" for="conSeguros">Importar también los seguros (primario/secundario)</label>
    </div>
    <button class="btn btn-primary" type="submit">Procesar</button>
    <a class="btn btn-link" href="pacientes_crud.php">Volver</a>
</form>
</div></body></html>
    <?php
    exit;
}

/* ── Procesamiento (POST) ─────────────────────────────────────────── */
$dryRun     = isset($_POST['dryrun']);
$conSeguros = isset($_POST['conSeguros']);

if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
    exit('Error al subir el CSV (' . (int)($_FILES['csv']['error'] ?? -1) . ').');
}
$fh = fopen($_FILES['csv']['tmp_name'], 'r');
if (!$fh) { exit('No se pudo abrir el CSV.'); }

// Cabecera (quitar BOM del primer campo)
$header = fgetcsv($fh);
if (!$header) { exit('CSV vacío.'); }
$header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
$idx = array_flip($header);
$col = function($row, $name) use ($idx) {
    return isset($idx[$name]) && isset($row[$idx[$name]]) ? trim($row[$idx[$name]]) : '';
};

// Asegurar columna KALIX_ID (solo en importación real)
$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$tieneKalix = (int)$conexion->query(
    "SELECT COUNT(*) c FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA='" . $conexion->real_escape_string($dbName) . "'
       AND TABLE_NAME='AG_PACIENTE' AND COLUMN_NAME='KALIX_ID'"
)->fetch_assoc()['c'] > 0;
if (!$tieneKalix && !$dryRun) {
    @$conexion->query("ALTER TABLE AG_PACIENTE ADD COLUMN KALIX_ID VARCHAR(40) NULL, ADD INDEX idx_kalix (KALIX_ID)");
    $tieneKalix = (int)$conexion->query(
        "SELECT COUNT(*) c FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA='" . $conexion->real_escape_string($dbName) . "'
           AND TABLE_NAME='AG_PACIENTE' AND COLUMN_NAME='KALIX_ID'"
    )->fetch_assoc()['c'] > 0;
}

// ¿Existen columnas opcionales?
$tieneIdioma = (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='".$conexion->real_escape_string($dbName)."' AND TABLE_NAME='AG_PACIENTE' AND COLUMN_NAME='IDIOMA'")->fetch_assoc()['c'] > 0;

// Cache de aseguradoras por nombre (lower)
$segCache = [];
$rSeg = $conexion->query("SELECT Id_seguro, Empresa_seguro FROM seguros");
if ($rSeg) { while ($s = $rSeg->fetch_assoc()) { $segCache[mb_strtolower(trim($s['Empresa_seguro']))] = (int)$s['Id_seguro']; } }
// Tipo de seguro por defecto (menor id existente) para aseguradoras nuevas
$defTipo = null;
$rt = $conexion->query("SELECT MIN(id_tipo_seguro) m FROM tipo_seguro");
if ($rt && ($rr = $rt->fetch_assoc()) && $rr['m'] !== null) { $defTipo = (int)$rr['m']; }

function resolverSeguro($conexion, &$segCache, $defTipo, $nombre, $dryRun, &$segCreados) {
    $key = mb_strtolower(trim($nombre));
    if ($key === '') return 0;
    if (isset($segCache[$key])) return $segCache[$key];
    // No existe → crear (salvo dry-run)
    $segCreados[] = $nombre;
    if ($dryRun) { $segCache[$key] = -1; return -1; } // marcador
    if ($defTipo === null) {
        $ins = $conexion->prepare("INSERT INTO seguros (Empresa_seguro, estado) VALUES (?, 1)");
        $ins->bind_param('s', $nombre);
    } else {
        $ins = $conexion->prepare("INSERT INTO seguros (Empresa_seguro, id_tipo_seguro, estado) VALUES (?, ?, 1)");
        $ins->bind_param('si', $nombre, $defTipo);
    }
    if ($ins && $ins->execute()) {
        $nid = (int)$conexion->insert_id;
        $ins->close();
        $segCache[$key] = $nid;
        return $nid;
    }
    if ($ins) $ins->close();
    return 0;
}

// Prepared statements para importación real
$stmtBuscaKalix = $tieneKalix ? $conexion->prepare("SELECT IDPACIENTE FROM AG_PACIENTE WHERE KALIX_ID = ? LIMIT 1") : null;

$nTotal=0; $nNuevos=0; $nExistentes=0; $nSinDatos=0; $nSegAsignados=0;
$segCreados=[]; $ejemplos=[]; $errores=[];

while (($row = fgetcsv($fh)) !== false) {
    if (count($row) === 1 && trim($row[0]) === '') continue; // línea vacía
    $nTotal++;

    $kalixId = $col($row, 'Id');
    if ($kalixId === '') $kalixId = $col($row, 'ExternalId');
    $nombres   = $col($row, 'Name GivenName');
    $apellidos = $col($row, 'Name LastName');
    if ($nombres === '' && $apellidos === '') { $nSinDatos++; continue; }

    $title   = $col($row, 'Name Title');
    $dob     = $col($row, 'DateOfBirth');            // YYYY-MM-DD o ''
    $email   = $col($row, 'Email');
    $tel     = $col($row, 'BestNumber');
    if ($tel === '') $tel = $col($row, 'Numbers[0] Number');
    $sex     = $col($row, 'Gender');                  // Female/Male/''
    if ($sex === '') $sex = 'N/A';
    $gender  = $col($row, 'GenderIdentity');
    if ($gender === '') $gender = $col($row, 'Gender');
    if ($gender === '') $gender = 'Default Select';
    $addrParts = array_filter([
        $col($row, 'PostalAddress Street1'),
        $col($row, 'PostalAddress Suburb'),
        $col($row, 'PostalAddress State'),
    ], fn($x)=>$x!=='');
    $address = implode(', ', $addrParts);
    $notes   = $col($row, 'Notes');
    $addNotes= $col($row, 'BillNotes');
    $dobParam= ($dob === '') ? null : $dob;

    // ¿Ya existe por KALIX_ID?
    $idExistente = 0;
    if ($tieneKalix && $kalixId !== '' && $stmtBuscaKalix) {
        $stmtBuscaKalix->bind_param('s', $kalixId);
        $stmtBuscaKalix->execute();
        $r = $stmtBuscaKalix->get_result()->fetch_assoc();
        if ($r) $idExistente = (int)$r['IDPACIENTE'];
    }

    if ($idExistente > 0) {
        $nExistentes++;
        continue; // ya importado; no se toca
    }

    $nNuevos++;
    if (count($ejemplos) < 10) {
        $ejemplos[] = trim($nombres.' '.$apellidos) . ' · ' . ($dob ?: 's/f') . ' · ' . ($email ?: 's/e');
    }

    if ($dryRun) {
        // Contar seguros que se asignarían
        if ($conSeguros) {
            foreach ([0,1] as $i) {
                if ($col($row, "Insurers[$i] Name") !== '') { $nSegAsignados++; resolverSeguro($conexion,$segCache,$defTipo,$col($row,"Insurers[$i] Name"),true,$segCreados); }
            }
        }
        continue;
    }

    // ── INSERT real ──
    $cols = ['NOMBRES','APELLIDOS','EMAIL','FECHANACIMIENTO','TELEFONO','TITLE','SEX','GENDER','ESTADO','ADDRESS','NOTES','ADDNOTES'];
    $vals = [$nombres,$apellidos,$email,$dobParam,$tel,$title,$sex,$gender,'A',$address,$notes,$addNotes];
    $types= 'ssssssssssss';
    if ($tieneKalix) { $cols[]='KALIX_ID'; $vals[]=($kalixId?:null); $types.='s'; }
    if ($tieneIdioma){ $cols[]='IDIOMA';   $vals[]='es';            $types.='s'; }
    $ph = implode(',', array_fill(0, count($cols), '?'));
    $stmt = $conexion->prepare("INSERT INTO AG_PACIENTE (".implode(',',$cols).") VALUES ($ph)");
    if (!$stmt) { $errores[] = "INSERT prepare: ".$conexion->error; continue; }
    $stmt->bind_param($types, ...$vals);
    if (!$stmt->execute()) { $errores[] = "INSERT ".$nombres." ".$apellidos.": ".$stmt->error; $stmt->close(); continue; }
    $idPac = (int)$conexion->insert_id;
    $stmt->close();

    // Seguros
    if ($conSeguros) {
        foreach ([0,1] as $i) {
            $segNombre = $col($row, "Insurers[$i] Name");
            if ($segNombre === '') continue;
            $idSeg = resolverSeguro($conexion, $segCache, $defTipo, $segNombre, false, $segCreados);
            if ($idSeg <= 0) continue;
            $poliza = $col($row, "Insurers[$i] InsuredNumber");
            $prio   = $i === 0 ? 'Primario' : 'Secundario';
            $is = $conexion->prepare("INSERT INTO paciente_seguro (IDPACIENTE, Id_seguro, num_poliza, prioridad, estado) VALUES (?, ?, ?, ?, 1)");
            if ($is) { $is->bind_param('iiss', $idPac, $idSeg, $poliza, $prio); if ($is->execute()) $nSegAsignados++; $is->close(); }
        }
    }
}
fclose($fh);
if ($stmtBuscaKalix) $stmtBuscaKalix->close();

$segCreadosUnq = array_values(array_unique($segCreados));
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Resultado importación</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:820px;">
<h4>Resultado — Importación de pacientes Kalix
    <?php if ($dryRun): ?><span class="badge bg-warning text-dark">PREVISUALIZACIÓN (no se escribió nada)</span>
    <?php else: ?><span class="badge bg-success">IMPORTACIÓN REALIZADA</span><?php endif; ?>
</h4>
<ul class="list-group my-3">
    <li class="list-group-item d-flex justify-content-between">Filas leídas <span class="badge bg-secondary"><?php echo $nTotal; ?></span></li>
    <li class="list-group-item d-flex justify-content-between"><?php echo $dryRun?'Se crearían':'Creados'; ?> <span class="badge bg-primary"><?php echo $nNuevos; ?></span></li>
    <li class="list-group-item d-flex justify-content-between">Ya existían (KALIX_ID) <span class="badge bg-info text-dark"><?php echo $nExistentes; ?></span></li>
    <li class="list-group-item d-flex justify-content-between">Sin nombre (omitidos) <span class="badge bg-warning text-dark"><?php echo $nSinDatos; ?></span></li>
    <li class="list-group-item d-flex justify-content-between">Seguros <?php echo $dryRun?'a asignar':'asignados'; ?> <span class="badge bg-success"><?php echo $nSegAsignados; ?></span></li>
    <li class="list-group-item d-flex justify-content-between">Aseguradoras nuevas <?php echo $dryRun?'a crear':'creadas'; ?> <span class="badge bg-dark"><?php echo count($segCreadosUnq); ?></span></li>
</ul>

<?php if ($ejemplos): ?>
<h6>Ejemplos (primeros <?php echo count($ejemplos); ?> nuevos):</h6>
<ul class="small"><?php foreach ($ejemplos as $e) echo '<li>'.h($e).'</li>'; ?></ul>
<?php endif; ?>

<?php if ($segCreadosUnq): ?>
<h6>Aseguradoras <?php echo $dryRun?'que se crearían':'creadas'; ?> (<?php echo count($segCreadosUnq); ?>):</h6>
<div class="small text-muted"><?php echo h(implode(' · ', array_slice($segCreadosUnq,0,60))); ?></div>
<?php endif; ?>

<?php if ($errores): ?>
<div class="alert alert-danger mt-3"><b>Errores (<?php echo count($errores); ?>):</b><ul class="mb-0 small">
<?php foreach (array_slice($errores,0,30) as $e) echo '<li>'.h($e).'</li>'; ?></ul></div>
<?php endif; ?>

<?php if ($dryRun): ?>
<div class="alert alert-warning mt-3">Esto fue una <b>previsualización</b>. Para importar de verdad, vuelve y <b>desmarca "Solo previsualizar"</b>.</div>
<?php endif; ?>

<a class="btn btn-primary mt-2" href="importar_pacientes_kalix.php">Volver</a>
<a class="btn btn-outline-secondary mt-2" href="pacientes_crud.php">Ir a Gestionar Pacientes</a>
</div></body></html>
