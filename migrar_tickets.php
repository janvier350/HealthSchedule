<?php
/**
 * migrar_tickets.php — Crea las tablas del módulo de Solicitudes/Tickets.
 * Idempotente. SISTEMA-only. (El módulo también se auto-crea al primer uso,
 * pero esta página permite prepararlo y confirmar.)
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/class/tickets.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
if (!isset($_SESSION["rol"]) || strtoupper($_SESSION["rol"]) !== 'SISTEMA') {
    http_response_code(403); exit('Acceso restringido: sólo SISTEMA.');
}

$ok = false; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ok = tickets_ensure_tablas($conexion);
    if (!$ok) $err = $conexion->error ?: 'No se pudieron crear las tablas.';
}
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Preparar Tickets</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Módulo de Solicitudes / Tickets</h4>
<?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <?php if ($ok): ?>
        <div class="alert alert-success">Tablas listas: <code>tickets</code>, <code>ticket_comentarios</code>, <code>ticket_adjuntos</code>.</div>
        <a class="btn btn-primary" href="tickets.php">Ir a Solicitudes</a>
    <?php else: ?>
        <div class="alert alert-danger">Error: <?php echo htmlspecialchars($err); ?></div>
    <?php endif; ?>
<?php else: ?>
    <p class="text-muted">Crea las tablas necesarias para el módulo de solicitudes. Idempotente.</p>
    <form method="POST"><button class="btn btn-primary" type="submit">Preparar módulo</button></form>
<?php endif; ?>
</div></body></html>
