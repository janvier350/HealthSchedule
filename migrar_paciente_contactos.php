<?php
/**
 * migrar_paciente_contactos.php
 * Permite guardar TELÉFONOS y CORREOS adicionales por paciente
 * (además del principal en AG_PACIENTE). Crea la tabla paciente_contactos.
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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Contactos adicionales</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Contactos adicionales del paciente</h4>
<p class="text-muted">Crea <code>paciente_contactos</code> para guardar teléfonos y correos
adicionales por paciente. Idempotente.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar</button>
<a class="btn btn-link" href="pacientes_crud.php">Volver</a></form></div></body></html>
    <?php
    exit;
}
$ok = $conexion->query("CREATE TABLE IF NOT EXISTS paciente_contactos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    IDPACIENTE INT NOT NULL,
    tipo VARCHAR(10) NOT NULL,            -- 'telefono' | 'email'
    valor VARCHAR(160) NOT NULL,
    etiqueta VARCHAR(60) NULL,            -- ej: Casa, Trabajo, Familiar
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_usuario INT NULL,
    INDEX idx_pac (IDPACIENTE)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$cls = $ok ? 'success' : 'danger';
$msg = $ok ? 'Tabla paciente_contactos lista.' : ('Error: '.$conexion->error);
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Resultado</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="p-4"><div class="container" style="max-width:640px;"><h4>Resultado</h4><div class="alert alert-'.$cls.'">'.htmlspecialchars($msg).'</div><a class="btn btn-primary" href="pacientes_crud.php">Ir a Gestionar Pacientes</a></div></body></html>';
