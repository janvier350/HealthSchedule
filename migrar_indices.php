<?php
/**
 * migrar_indices.php  (v2 — un índice por request, con timeouts fuertes)
 *
 * Estrategia:
 *  - Se muestra un dashboard con todos los índices y su estado
 *    (creado / pendiente / columna faltante / tabla no existe).
 *  - Cada botón "Crear" dispara UN ÚNICO índice, en un POST propio.
 *  - Antes del CREATE INDEX se aplican timeouts cortos para que si
 *    hay un meta-lock la operación falle en segundos, no cuelgue.
 *
 * Con este diseño:
 *  - Ningún request dura > 30 s (set_time_limit),
 *  - Ninguna sentencia bloquea > 5 s (SET SESSION lock_wait_timeout),
 *  - Puedes cancelar en cualquier momento sin dejar a medias más de
 *    un solo índice.
 *
 * Idempotente: si el índice ya existe, se reporta OK y no se recrea.
 *
 * SISTEMA-only.
 */
@ini_set('display_errors', '1');
error_reporting(E_ALL);
@set_time_limit(30);

session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

if (!isset($_SESSION["rol"]) || strtoupper($_SESSION["rol"]) !== 'SISTEMA') {
    http_response_code(403); exit('Acceso restringido: sólo SISTEMA.');
}

if ($conexion) {
    @$conexion->query("SET SESSION lock_wait_timeout = 5");
    @$conexion->query("SET SESSION innodb_lock_wait_timeout = 5");
}

/* ────────────────────────────────────────────────────────────────
 * Plan de índices (mismo del script anterior)
 * ──────────────────────────────────────────────────────────────── */
$plan = [
    'AG_CITA' => [
        ['name' => 'idx_fecha',            'cols' => ['FECHA_CITA']],
        ['name' => 'idx_fecha_estado',     'cols' => ['FECHA_CITA','ESTADO']],
        ['name' => 'idx_estado_cita',      'cols' => ['ESTADO_CITA','ESTADO','FECHA_CITA']],
        ['name' => 'idx_paciente_fecha',   'cols' => ['IDPACIENTE','FECHA_CITA']],
        ['name' => 'idx_doctor_fecha',     'cols' => ['IDDOCTOR','FECHA_CITA']],
        ['name' => 'idx_serie',            'cols' => ['IDSERIE']],
    ],
    'AG_HISTORIAL' => [
        ['name' => 'idx_cita',             'cols' => ['IDCITA']],
        // Nota: AG_HISTORIAL no tiene IDPACIENTE (se resuelve vía JOIN con AG_CITA).
    ],
    'AG_PACIENTE' => [
        ['name' => 'idx_estado',           'cols' => ['ESTADO']],
        ['name' => 'idx_estado_ape_nom',   'cols' => ['ESTADO','APELLIDOS','NOMBRES']],
    ],
    'paciente_seguro' => [
        ['name' => 'idx_paciente',         'cols' => ['IDPACIENTE']],
    ],
    'documento_envio' => [
        ['name' => 'idx_token',            'cols' => ['token']],
        ['name' => 'idx_paciente_estado',  'cols' => ['IDPACIENTE','estado']],
    ],
    'documentos' => [
        ['name' => 'idx_titulo',           'cols' => ['titulo']],
        ['name' => 'idx_estado',           'cols' => ['estado']],
    ],
    'cat_plantillas_nutricion' => [
        ['name' => 'idx_categoria',        'cols' => ['categoria']],
        ['name' => 'idx_nombre',           'cols' => ['nombre_plantilla']],
    ],
];

/* Aplanamos el plan en una lista numerada [id => item] */
$flat = [];
$id = 0;
foreach ($plan as $tabla => $indices) {
    foreach ($indices as $idx) {
        $flat[++$id] = ['tabla' => $tabla, 'name' => $idx['name'], 'cols' => $idx['cols']];
    }
}

/* Helpers */
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function tableExists($conn, $tabla) {
    $safe = str_replace('`', '', $tabla);
    $like = $conn->real_escape_string($safe);
    $r = @$conn->query("SHOW TABLES LIKE '{$like}'");
    return $r && $r->num_rows > 0;
}
function tableColumns($conn, $tabla) {
    $safe = str_replace('`', '', $tabla);
    $r = @$conn->query("SHOW COLUMNS FROM `{$safe}`");
    $cols = [];
    if ($r) { while ($row = $r->fetch_assoc()) { $cols[strtolower($row['Field'])] = $row['Field']; } }
    return $cols;
}
function tableIndexes($conn, $tabla) {
    $safe = str_replace('`', '', $tabla);
    $r = @$conn->query("SHOW INDEX FROM `{$safe}`");
    $idx = [];
    if ($r) { while ($row = $r->fetch_assoc()) { $idx[strtolower($row['Key_name'])] = true; } }
    return $idx;
}

function itemStatus($conn, $item) {
    if (!tableExists($conn, $item['tabla']))         return ['SKIP', 'Tabla no existe'];
    $cols = tableColumns($conn, $item['tabla']);
    foreach ($item['cols'] as $c) {
        if (!isset($cols[strtolower($c)]))            return ['SKIP', "Falta columna: {$c}"];
    }
    $idx  = tableIndexes($conn, $item['tabla']);
    if (isset($idx[strtolower($item['name'])]))       return ['OK',   'Ya existe'];
    return ['NEW', 'Pendiente'];
}

/* ────────────────────────────────────────────────────────────────
 * POST: crear UN índice
 * ──────────────────────────────────────────────────────────────── */
$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_id'])) {
    $cid = (int) $_POST['create_id'];
    if (isset($flat[$cid])) {
        $item = $flat[$cid];
        [$state, $why] = itemStatus($conexion, $item);
        if ($state === 'OK') {
            $msg = ['secondary', "{$item['tabla']}.{$item['name']} ya existía."];
        } elseif ($state !== 'NEW') {
            $msg = ['warning', "{$item['tabla']}.{$item['name']}: {$why}"];
        } else {
            $safeTbl  = str_replace('`', '', $item['tabla']);
            $safeName = str_replace('`', '', $item['name']);
            $colList  = implode(', ', array_map(function ($c) { return '`'.str_replace('`', '', $c).'`'; }, $item['cols']));
            $sql = "CREATE INDEX `{$safeName}` ON `{$safeTbl}` ({$colList})";
            $t0 = microtime(true);
            $ok = @$conexion->query($sql);
            $ms = round((microtime(true) - $t0) * 1000);
            if ($ok) {
                $msg = ['success', "✅ Creado {$item['tabla']}.{$item['name']} en {$ms} ms."];
            } else {
                $msg = ['danger', "❌ Fallo {$item['tabla']}.{$item['name']} (".$conexion->errno."): ".$conexion->error." — {$ms} ms"];
            }
        }
    }
}

/* ────────────────────────────────────────────────────────────────
 * Render: dashboard
 * ──────────────────────────────────────────────────────────────── */
$rows = [];
$counts = ['NEW' => 0, 'OK' => 0, 'SKIP' => 0];
foreach ($flat as $cid => $item) {
    [$state, $why] = itemStatus($conexion, $item);
    $counts[$state] = ($counts[$state] ?? 0) + 1;
    $rows[] = compact('cid', 'item', 'state', 'why');
}
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Índices — v2</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:1000px;">
<h4>Migración de índices <small class="text-muted">v2 — uno por vez</small></h4>
<p class="text-muted small">
Cada botón crea un solo índice. Timeouts: <code>lock_wait_timeout=5s</code>, <code>set_time_limit=30s</code>.
No puede colgar la app: si algún índice tarda, ese request falla solo y el resto sigue funcionando.
</p>

<?php if ($msg): ?>
    <div class="alert alert-<?= h($msg[0]) ?> mt-2"><?= h($msg[1]) ?></div>
<?php endif; ?>

<div class="d-flex gap-2 mb-2 small">
    <span class="badge bg-success">Creados: <?= (int)$counts['OK'] ?></span>
    <span class="badge bg-primary">Pendientes: <?= (int)$counts['NEW'] ?></span>
    <span class="badge bg-warning text-dark">Skips: <?= (int)$counts['SKIP'] ?></span>
</div>

<table class="table table-sm table-bordered align-middle">
<thead class="table-light">
<tr><th style="width:36px;">#</th><th>Tabla</th><th>Índice</th><th>Columnas</th><th>Estado</th><th style="width:140px;">Acción</th></tr>
</thead>
<tbody>
<?php foreach ($rows as $r):
    $cid = $r['cid']; $item = $r['item']; $state = $r['state']; $why = $r['why'];
    $badge = ['NEW' => 'primary', 'OK' => 'success', 'SKIP' => 'warning'][$state] ?? 'secondary';
?>
<tr>
    <td class="text-muted"><?= (int)$cid ?></td>
    <td><code><?= h($item['tabla']) ?></code></td>
    <td><code><?= h($item['name']) ?></code></td>
    <td class="small"><?= h(implode(', ', $item['cols'])) ?></td>
    <td><span class="badge bg-<?= h($badge) ?>"><?= h($state === 'OK' ? '✅ Ya existe' : ($state === 'SKIP' ? '⚠ '.$why : 'Pendiente')) ?></span></td>
    <td>
        <?php if ($state === 'NEW'): ?>
            <form method="POST" style="margin:0;">
                <input type="hidden" name="create_id" value="<?= (int)$cid ?>">
                <button type="submit" class="btn btn-sm btn-primary">Crear</button>
            </form>
        <?php else: ?>
            <span class="text-muted small">—</span>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<a class="btn btn-secondary" href="Home.php">Volver</a>
</div></body></html>
