<?php
/**
 * migrar_paciente_ciudad_estado.php
 * Agrega CITY y STATE a AG_PACIENTE para una dirección completa
 * (ciudad y estado, ej. New York). Idempotente. SISTEMA-only, POST-driven.
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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Ciudad y Estado</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Ciudad y Estado del paciente</h4>
<p class="text-muted">Agrega <code>CITY</code> y <code>STATE</code> a <code>AG_PACIENTE</code>. Idempotente.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar</button>
<a class="btn btn-link" href="pacientes_crud.php">Volver</a></form></div></body></html>
    <?php
    exit;
}
$dbEsc = $conexion->real_escape_string($conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db']);
$col = function($c) use ($conexion,$dbEsc){
    return (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$dbEsc' AND TABLE_NAME='AG_PACIENTE' AND COLUMN_NAME='".$conexion->real_escape_string($c)."'")->fetch_assoc()['c']>0;
};
$msgs=[];
foreach (['CITY'=>"ALTER TABLE AG_PACIENTE ADD COLUMN CITY VARCHAR(120) NULL",
          'STATE'=>"ALTER TABLE AG_PACIENTE ADD COLUMN STATE VARCHAR(60) NULL"] as $c=>$sql) {
    if ($col($c)) { $msgs[]=['OK',"Ya existía: $c"]; continue; }
    $msgs[] = $conexion->query($sql) ? ['NEW',"Columna $c agregada"] : ['ERR',"$c: ".$conexion->error];
}
$cls=['NEW'=>'success','OK'=>'secondary','ERR'=>'danger'];
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Resultado</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="p-4"><div class="container" style="max-width:640px;"><h4>Resultado</h4><ul class="list-group">';
foreach ($msgs as $m) echo '<li class="list-group-item"><span class="badge bg-'.($cls[$m[0]]??'secondary').' me-2">'.htmlspecialchars($m[0]).'</span>'.htmlspecialchars($m[1]).'</li>';
echo '</ul><a class="btn btn-primary mt-3" href="pacientes_crud.php">Volver</a></div></body></html>';
