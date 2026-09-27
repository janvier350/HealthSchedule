<?php
/**
 * migrar_auditoria.php
 * Crea la tabla `auditoria` (bitácora de cambios). Idempotente. SISTEMA-only.
 * También la crea sola el helper class/auditoria.php la primera vez que se usa,
 * pero esta migración permite crearla explícitamente.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
if (!isset($_SESSION["rol"]) || strtoupper($_SESSION["rol"]) !== 'SISTEMA') {
    http_response_code(403); exit('Acceso restringido: sólo SISTEMA.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Auditoría</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Bitácora de auditoría</h4>
<p class="text-muted">Crea la tabla <code>auditoria</code> para registrar quién crea, edita o elimina información y en qué módulo. Idempotente.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar</button>
<a class="btn btn-link" href="auditoria.php">Ir a la bitácora</a></form></div></body></html>
    <?php
    exit;
}
$ok = $conexion->query("CREATE TABLE IF NOT EXISTS auditoria (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_usuario INT NULL,
    usuario VARCHAR(100) NULL,
    nombre VARCHAR(160) NULL,
    rol VARCHAR(40) NULL,
    modulo VARCHAR(60) NOT NULL,
    accion VARCHAR(30) NOT NULL,
    entidad VARCHAR(60) NULL,
    entidad_id VARCHAR(40) NULL,
    detalle VARCHAR(500) NULL,
    ip VARCHAR(45) NULL,
    INDEX idx_fecha (fecha),
    INDEX idx_user (id_usuario),
    INDEX idx_modulo (modulo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$cls = $ok ? 'success' : 'danger';
$msg = $ok ? 'Tabla auditoria lista.' : ('Error: '.$conexion->error);
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Resultado</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="p-4"><div class="container" style="max-width:640px;"><h4>Resultado</h4><div class="alert alert-'.$cls.'">'.htmlspecialchars($msg).'</div><a class="btn btn-primary" href="auditoria.php">Ir a la bitácora</a></div></body></html>';
