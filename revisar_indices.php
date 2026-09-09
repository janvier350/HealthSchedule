<?php
/**
 * revisar_indices.php
 * SÓLO LECTURA. Lista los índices existentes de las tablas críticas.
 * No modifica nada — seguro de abrir en producción, incluso durante
 * una incidencia, sin riesgo de bloquear ni de disparar deadlocks.
 *
 * Se usa para saber si migrar_indices.php ya alcanzó a crear algunos
 * índices antes de un timeout/cuelgue, y decidir cuáles quedan.
 *
 * SISTEMA-only.
 */
@ini_set('display_errors', '1');
error_reporting(E_ALL);

session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

if (!isset($_SESSION["rol"]) || strtoupper($_SESSION["rol"]) !== 'SISTEMA') {
    http_response_code(403); exit('Acceso restringido: sólo SISTEMA.');
}

// Timeouts ultra cortos: si hay lock, no bloqueamos, sólo mostramos aviso.
@$conexion->query("SET SESSION lock_wait_timeout = 3");
@$conexion->query("SET SESSION innodb_lock_wait_timeout = 3");

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$tablas = [
    'AG_CITA',
    'AG_HISTORIAL',
    'AG_PACIENTE',
    'paciente_seguro',
    'documento_envio',
    'documentos',
    'cat_plantillas_nutricion',
];

$expected = [
    'AG_CITA'                   => ['idx_fecha','idx_fecha_estado','idx_estado_cita','idx_paciente_fecha','idx_medico_fecha','idx_serie'],
    'AG_HISTORIAL'              => ['idx_cita','idx_paciente'],
    'AG_PACIENTE'               => ['idx_estado','idx_estado_ape_nom'],
    'paciente_seguro'           => ['idx_paciente'],
    'documento_envio'           => ['idx_token','idx_paciente_estado'],
    'documentos'                => ['idx_titulo','idx_estado'],
    'cat_plantillas_nutricion'  => ['idx_categoria','idx_nombre'],
];
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Revisión de índices</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:960px;">
<h4>Índices existentes por tabla <small class="text-muted">(sólo lectura)</small></h4>
<p class="text-muted small">Verde ✅ = ya creado (no volver a intentar). Rojo ❌ = pendiente. La lista muestra sólo los índices que este proyecto necesita; los primarios y otros índices del sistema se omiten.</p>

<?php foreach ($tablas as $t):
    $safe = str_replace('`', '', $t);
    $r = @$conexion->query("SHOW TABLES LIKE '".$conexion->real_escape_string($safe)."'");
    if (!$r || $r->num_rows === 0) {
        echo '<div class="alert alert-warning">Tabla no existe: <code>'.h($t).'</code></div>';
        continue;
    }
    $ri = @$conexion->query("SHOW INDEX FROM `{$safe}`");
    $have = [];
    if ($ri) {
        while ($row = $ri->fetch_assoc()) { $have[strtolower($row['Key_name'])] = true; }
    }
    $exp = $expected[$t] ?? [];
?>
<div class="card mb-3"><div class="card-body p-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0"><code><?= h($t) ?></code></h6>
        <span class="badge bg-secondary"><?= count($have) ?> índice(s) totales</span>
    </div>
    <table class="table table-sm table-bordered mb-0" style="font-size:13px;">
        <thead class="table-light">
            <tr><th style="width:60%;">Índice esperado</th><th>Estado</th></tr>
        </thead>
        <tbody>
        <?php foreach ($exp as $idx): $ok = isset($have[strtolower($idx)]); ?>
            <tr>
                <td><code><?= h($idx) ?></code></td>
                <td>
                    <?php if ($ok): ?>
                        <span class="badge bg-success">✅ Creado</span>
                    <?php else: ?>
                        <span class="badge bg-danger">❌ Pendiente</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div></div>
<?php endforeach; ?>

<p class="text-muted small mt-4">
Este archivo NO crea ni borra índices. Para crear los que faltan, usar <code>migrar_indices.php</code>.
</p>
<a class="btn btn-secondary" href="Home.php">Volver</a>
</div></body></html>
