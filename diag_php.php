<?php
/**
 * diag_php.php
 * Diagnóstico de configuración PHP / OPcache / MySQL — SISTEMA-only.
 * Muestra sólo lo indispensable para decidir si hay que subir memory_limit,
 * activar OPcache o subir su tamaño. NO expone rutas, credenciales ni secretos.
 *
 * Uso: abrirlo una vez logueado como SISTEMA, pasar captura al soporte y BORRAR
 * el archivo del hosting cuando termine el diagnóstico.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");

if (!isset($_SESSION["rol"]) || strtoupper($_SESSION["rol"]) !== 'SISTEMA') {
    http_response_code(403); exit('Acceso restringido: sólo SISTEMA.');
}

$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$phpVer      = PHP_VERSION;
$memoryLimit = ini_get('memory_limit');
$maxExec     = ini_get('max_execution_time');
$postMax     = ini_get('post_max_size');
$uploadMax   = ini_get('upload_max_filesize');
$sessGc      = ini_get('session.gc_maxlifetime');
$defCharset  = ini_get('default_charset');
$dispErrors  = ini_get('display_errors');
$errorLog    = ini_get('log_errors');

$opcacheOn   = function_exists('opcache_get_status');
$opcacheStat = $opcacheOn ? @opcache_get_status(false) : null;
$opcacheCfg  = function_exists('opcache_get_configuration') ? @opcache_get_configuration() : null;

$mysqlVersion = $mysqlUptime = $slowQueries = $bufferPool = '—';
if ($conexion) {
    $r = $conexion->query("SELECT VERSION() v");
    if ($r && $row = $r->fetch_assoc()) { $mysqlVersion = $row['v']; }
    $r = $conexion->query("SHOW GLOBAL STATUS WHERE Variable_name IN ('Uptime','Slow_queries')");
    if ($r) { while ($row = $r->fetch_assoc()) {
        if ($row['Variable_name'] === 'Uptime')      $mysqlUptime = $row['Value'];
        if ($row['Variable_name'] === 'Slow_queries') $slowQueries = $row['Value'];
    } }
    $r = $conexion->query("SHOW GLOBAL VARIABLES WHERE Variable_name IN ('innodb_buffer_pool_size','max_connections','query_cache_size')");
    $vars = [];
    if ($r) { while ($row = $r->fetch_assoc()) { $vars[$row['Variable_name']] = $row['Value']; } }
    $bufferPool = isset($vars['innodb_buffer_pool_size']) ? number_format($vars['innodb_buffer_pool_size']/1048576, 1).' MB' : '—';
    $maxConn    = $vars['max_connections']    ?? '—';
    $qCache     = isset($vars['query_cache_size']) ? number_format($vars['query_cache_size']/1048576, 1).' MB' : '—';
}
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Diagnóstico PHP / MySQL</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:840px;">
<h4>Diagnóstico PHP / OPcache / MySQL</h4>
<p class="text-muted small">Ejecutado <?= h(date('Y-m-d H:i:s')) ?>. Borrar este archivo del servidor cuando termine el diagnóstico.</p>

<h5 class="mt-3">PHP</h5>
<table class="table table-sm table-bordered">
<tr><th style="width:260px;">PHP version</th><td><?= h($phpVer) ?></td></tr>
<tr><th>memory_limit</th><td><?= h($memoryLimit) ?> <span class="text-muted">(recomendado ≥ 256M)</span></td></tr>
<tr><th>max_execution_time</th><td><?= h($maxExec) ?> s <span class="text-muted">(≥ 60)</span></td></tr>
<tr><th>post_max_size</th><td><?= h($postMax) ?></td></tr>
<tr><th>upload_max_filesize</th><td><?= h($uploadMax) ?></td></tr>
<tr><th>session.gc_maxlifetime</th><td><?= h($sessGc) ?> s</td></tr>
<tr><th>default_charset</th><td><?= h($defCharset) ?></td></tr>
<tr><th>display_errors</th><td><?= h($dispErrors) ?> <span class="text-muted">(debería estar Off en producción)</span></td></tr>
<tr><th>log_errors</th><td><?= h($errorLog) ?></td></tr>
</table>

<h5 class="mt-3">OPcache</h5>
<?php if (!$opcacheOn): ?>
<div class="alert alert-danger">OPcache NO está disponible. Impacto ALTO — cada request recompila el PHP.</div>
<?php else: ?>
<?php $enabled = !empty($opcacheStat['opcache_enabled']); ?>
<div class="alert alert-<?= $enabled ? 'success' : 'danger' ?>">
OPcache <?= $enabled ? 'HABILITADO ✅' : 'DESHABILITADO ❌ — activar en el hosting' ?>
</div>
<?php if ($opcacheStat): ?>
<table class="table table-sm table-bordered">
<tr><th style="width:260px;">Memoria usada</th>
    <td><?= number_format(($opcacheStat['memory_usage']['used_memory'] ?? 0)/1048576, 1) ?> MB
        / <?= number_format((($opcacheStat['memory_usage']['used_memory'] ?? 0)+($opcacheStat['memory_usage']['free_memory'] ?? 0))/1048576, 1) ?> MB</td></tr>
<tr><th>Hit rate</th>
    <td><?= isset($opcacheStat['opcache_statistics']['opcache_hit_rate'])
        ? number_format($opcacheStat['opcache_statistics']['opcache_hit_rate'], 2).' %' : '—' ?>
        <span class="text-muted">(objetivo ≥ 99 %)</span></td></tr>
<tr><th>Scripts cacheados</th>
    <td><?= (int)($opcacheStat['opcache_statistics']['num_cached_scripts'] ?? 0) ?>
        / max_accelerated_files <?= (int)($opcacheCfg['directives']['opcache.max_accelerated_files'] ?? 0) ?></td></tr>
<tr><th>Restarts (OOM + hash + manual)</th>
    <td>OOM=<?= (int)($opcacheStat['opcache_statistics']['oom_restarts'] ?? 0) ?>
        &nbsp; hash=<?= (int)($opcacheStat['opcache_statistics']['hash_restarts'] ?? 0) ?>
        &nbsp; manual=<?= (int)($opcacheStat['opcache_statistics']['manual_restarts'] ?? 0) ?>
        <span class="text-muted">(deberían ser 0)</span></td></tr>
<tr><th>opcache.memory_consumption</th>
    <td><?= h($opcacheCfg['directives']['opcache.memory_consumption'] ?? '—') ?> bytes
        <span class="text-muted">(≥ 128M)</span></td></tr>
<tr><th>opcache.validate_timestamps</th>
    <td><?= var_export($opcacheCfg['directives']['opcache.validate_timestamps'] ?? null, true) ?></td></tr>
</table>
<?php endif; ?>
<?php endif; ?>

<h5 class="mt-3">MySQL</h5>
<table class="table table-sm table-bordered">
<tr><th style="width:260px;">Version</th><td><?= h($mysqlVersion) ?></td></tr>
<tr><th>Uptime</th><td><?= h($mysqlUptime) ?> s</td></tr>
<tr><th>Slow queries</th><td><?= h($slowQueries) ?> <span class="text-muted">(objetivo bajo)</span></td></tr>
<tr><th>innodb_buffer_pool_size</th><td><?= h($bufferPool) ?> <span class="text-muted">(≥ 128 MB)</span></td></tr>
<tr><th>max_connections</th><td><?= h($maxConn ?? '—') ?></td></tr>
<tr><th>query_cache_size</th><td><?= h($qCache ?? '—') ?></td></tr>
</table>

<p class="mt-3"><a class="btn btn-secondary" href="Home.php">Volver</a></p>

<p class="text-muted small">Cuando termine el diagnóstico, elimina <code>diag_php.php</code> del servidor.</p>
</div></body></html>
