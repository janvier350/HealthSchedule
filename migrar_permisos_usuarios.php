<?php
/**
 * migrar_permisos_usuarios.php
 * Crea la tabla usuario_permisos (permisos por usuario que sobreescriben el rol).
 * Idempotente. SISTEMA-only, POST-driven.
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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Permisos por usuario</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Permisos por usuario</h4>
<p class="text-muted">Crea <code>usuario_permisos</code> (permitir/bloquear módulos por persona,
sobreescribiendo el rol). Idempotente.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar</button>
<a class="btn btn-link" href="permisos_usuarios.php">Ir al panel</a></form></div></body></html>
    <?php
    exit;
}
$ok = $conexion->query("CREATE TABLE IF NOT EXISTS usuario_permisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    IDADM_USUARIO INT NOT NULL,
    modulo VARCHAR(60) NOT NULL,
    permitido TINYINT(1) NOT NULL DEFAULT 1,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_by INT NULL,
    UNIQUE KEY uq_user_mod (IDADM_USUARIO, modulo),
    INDEX idx_user (IDADM_USUARIO)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$cls = $ok ? 'success' : 'danger';
$msg = $ok ? 'Tabla usuario_permisos lista.' : ('Error: '.$conexion->error);
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Resultado</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="p-4"><div class="container" style="max-width:640px;"><h4>Resultado</h4><div class="alert alert-'.$cls.'">'.htmlspecialchars($msg).'</div><a class="btn btn-primary" href="permisos_usuarios.php">Ir al panel de permisos</a></div></body></html>';
