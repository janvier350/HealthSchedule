<?php
/**
 * migrar_paciente_archivos.php
 * Crea la tabla `paciente_archivos` para guardar documentos (PDF/imagen) de
 * cada paciente. Los archivos se guardan en una carpeta protegida y sólo se
 * sirven por un visor con sesión + permiso (paciente_archivo_ver.php).
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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Documentos de pacientes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Documentos por paciente</h4>
<p class="text-muted">Crea la tabla <code>paciente_archivos</code> para adjuntar PDF/imágenes a cada paciente. Idempotente.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar</button>
<a class="btn btn-link" href="pacientes_crud.php">Volver</a></form></div></body></html>
    <?php
    exit;
}
$ok = $conexion->query("CREATE TABLE IF NOT EXISTS paciente_archivos (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    IDPACIENTE INT NOT NULL,
    titulo VARCHAR(255) NULL,
    nombre_original VARCHAR(255) NOT NULL,
    archivo VARCHAR(255) NOT NULL,
    mime VARCHAR(100) NULL,
    tamano INT NULL,
    id_usuario INT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado CHAR(1) NOT NULL DEFAULT 'A',
    INDEX idx_pac (IDPACIENTE, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$cls = $ok ? 'success' : 'danger';
$msg = $ok ? 'Tabla paciente_archivos lista.' : ('Error: '.$conexion->error);
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Resultado</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="p-4"><div class="container" style="max-width:640px;"><h4>Resultado</h4><div class="alert alert-'.$cls.'">'.htmlspecialchars($msg).'</div><a class="btn btn-primary" href="pacientes_crud.php">Volver</a></div></body></html>';
