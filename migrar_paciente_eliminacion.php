<?php
/**
 * migrar_paciente_eliminacion.php
 * Agrega columnas de auditoría de eliminación a AG_PACIENTE:
 *   MOTIVO_ELIMINACION VARCHAR(500), FECHA_ELIMINACION DATETIME, ELIMINADO_POR INT
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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Migración eliminación paciente</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Auditoría de eliminación de pacientes</h4>
<p class="text-muted">Agrega <code>MOTIVO_ELIMINACION</code>, <code>FECHA_ELIMINACION</code> y
<code>ELIMINADO_POR</code> a <code>AG_PACIENTE</code>. Idempotente.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar</button>
<a class="btn btn-link" href="pacientes_eliminados.php">Volver</a></form></div></body></html>
    <?php
    exit;
}
$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$col = function($c) use ($conexion,$dbName){
    return (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='".$conexion->real_escape_string($dbName)."' AND TABLE_NAME='AG_PACIENTE' AND COLUMN_NAME='".$conexion->real_escape_string($c)."'")->fetch_assoc()['c']>0;
};
$msgs=[];
$defs = [
    'MOTIVO_ELIMINACION' => "ALTER TABLE AG_PACIENTE ADD COLUMN MOTIVO_ELIMINACION VARCHAR(500) NULL",
    'FECHA_ELIMINACION'  => "ALTER TABLE AG_PACIENTE ADD COLUMN FECHA_ELIMINACION DATETIME NULL",
    'ELIMINADO_POR'      => "ALTER TABLE AG_PACIENTE ADD COLUMN ELIMINADO_POR INT NULL",
];
foreach ($defs as $c=>$sql) {
    if ($col($c)) { $msgs[]=['OK',"Ya existía: $c"]; continue; }
    $msgs[] = $conexion->query($sql) ? ['NEW',"Columna $c agregada"] : ['ERR',"$c: ".$conexion->error];
}
$cls=['NEW'=>'success','OK'=>'secondary','ERR'=>'danger'];
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Resultado</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="p-4"><div class="container" style="max-width:640px;"><h4>Resultado</h4><ul class="list-group">';
foreach ($msgs as $m) echo '<li class="list-group-item"><span class="badge bg-'.($cls[$m[0]]??'secondary').' me-2">'.htmlspecialchars($m[0]).'</span>'.htmlspecialchars($m[1]).'</li>';
echo '</ul><a class="btn btn-primary mt-3" href="pacientes_eliminados.php">Ir a Pacientes eliminados</a></div></body></html>';
