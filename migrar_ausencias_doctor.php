<?php
/**
 * migrar_ausencias_doctor.php
 * Crea la tabla de ausencias/no disponibilidad del doctor:
 *   - tipo 'vacacion': rango de días completos.
 *   - tipo 'bloqueo' : una fecha con rango de horas no disponibles.
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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Ausencias del doctor</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Módulo de vacaciones / no disponibilidad</h4>
<p class="text-muted">Crea la tabla <code>ausencias_doctor</code> (vacaciones por rango de días y bloqueos por horas). Idempotente.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar</button>
<a class="btn btn-link" href="ausencias_doctor.php">Ir al módulo</a></form></div></body></html>
    <?php
    exit;
}
$ok = $conexion->query("CREATE TABLE IF NOT EXISTS ausencias_doctor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    IDDOCTOR INT NOT NULL,
    tipo VARCHAR(20) NOT NULL DEFAULT 'vacacion',
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    hora_inicio TIME NULL,
    hora_fin TIME NULL,
    motivo VARCHAR(255) NULL,
    creado_por INT NULL,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    INDEX idx_doc (IDDOCTOR),
    INDEX idx_fechas (fecha_inicio, fecha_fin),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$cls = $ok ? 'success' : 'danger';
$msg = $ok ? 'Tabla ausencias_doctor lista.' : ('Error: '.$conexion->error);
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Resultado</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="p-4"><div class="container" style="max-width:640px;"><h4>Resultado</h4><div class="alert alert-'.$cls.'">'.htmlspecialchars($msg).'</div><a class="btn btn-primary" href="ausencias_doctor.php">Ir al módulo de vacaciones</a></div></body></html>';
