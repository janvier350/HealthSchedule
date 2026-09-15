<?php
/**
 * migrar_paciente_zip.php
 * Agrega ZIP (código postal) a AG_PACIENTE. Idempotente. SISTEMA-only, POST-driven.
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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>ZIP del paciente</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Código postal (ZIP) del paciente</h4>
<p class="text-muted">Agrega <code>ZIP</code> a <code>AG_PACIENTE</code>. Idempotente.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar</button>
<a class="btn btn-link" href="pacientes_crud.php">Volver</a></form></div></body></html>
    <?php
    exit;
}
$dbEsc = $conexion->real_escape_string($conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db']);
$existe = (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$dbEsc' AND TABLE_NAME='AG_PACIENTE' AND COLUMN_NAME='ZIP'")->fetch_assoc()['c']>0;
if ($existe) { $cls='secondary'; $msg='La columna ZIP ya existe.'; }
else { $ok=$conexion->query("ALTER TABLE AG_PACIENTE ADD COLUMN ZIP VARCHAR(15) NULL");
       $cls=$ok?'success':'danger'; $msg=$ok?'Columna ZIP agregada.':('Error: '.$conexion->error); }
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Resultado</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="p-4"><div class="container" style="max-width:640px;"><h4>Resultado</h4><div class="alert alert-'.$cls.'">'.htmlspecialchars($msg).'</div><a class="btn btn-primary" href="pacientes_crud.php">Volver</a></div></body></html>';
