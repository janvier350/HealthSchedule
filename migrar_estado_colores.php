<?php
/**
 * migrar_estado_colores.php
 * Crea la tabla estado_cita_colores (colores editables de los estados del
 * calendario) y siembra los valores por defecto. El "Pendiente" se siembra
 * con un gris azulado suave (antes negro puro, muy fuerte para la vista).
 * Idempotente (upsert por clave). SISTEMA-only, POST-driven.
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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Colores de estados</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Crear colores de estados del calendario</h4>
<p class="text-muted">Crea <code>estado_cita_colores</code> y siembra los colores por defecto
(el "Pendiente" queda en un gris azulado suave en lugar de negro).</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar</button>
<a class="btn btn-link" href="gestionar_colores_estado.php">Volver</a></form></div></body></html>
    <?php
    exit;
}

$msgs = [];
$conexion->query("CREATE TABLE IF NOT EXISTS estado_cita_colores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(40) NOT NULL,
    etiqueta VARCHAR(80) NOT NULL,
    color VARCHAR(7) NOT NULL,
    orden INT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4")
    ? $msgs[]=['OK','Tabla estado_cita_colores lista'] : $msgs[]=['ERR',$conexion->error];

// clave, etiqueta, color por defecto, orden
$defaults = [
    ['pendiente',              'Pendiente / Reagendada',      '#3b4252', 1],
    ['confirmada',             'Confirmada',                  '#6f42c1', 2],
    ['atendida',               'Atendida',                    '#28a745', 3],
    ['cancelada',              'Cancelada',                   '#fd7e14', 4],
    ['cancelacion_tardia',     'Cancelación tardía',          '#ffc107', 5],
    ['cancelado_profesional',  'Cancelado por profesional',   '#ff8a80', 6],
    ['no_asistio',             'No asistió',                  '#dc3545', 7],
    ['default',                'Otro / sin estado',           '#007bff', 8],
];
$chk = $conexion->prepare("SELECT id FROM estado_cita_colores WHERE clave=? LIMIT 1");
foreach ($defaults as $d) {
    [$clave,$etq,$color,$ord] = $d;
    $chk->bind_param('s',$clave); $chk->execute();
    if ($chk->get_result()->fetch_assoc()) { $msgs[]=['OK',"Ya existía: $clave"]; continue; }
    $ins = $conexion->prepare("INSERT INTO estado_cita_colores (clave,etiqueta,color,orden) VALUES (?,?,?,?)");
    $ins->bind_param('sssi',$clave,$etq,$color,$ord);
    $msgs[] = $ins->execute() ? ['NEW',"Creado: $clave ($color)"] : ['ERR',"$clave: ".$ins->error];
    $ins->close();
}
$chk->close();

$cls=['NEW'=>'success','OK'=>'secondary','ERR'=>'danger'];
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Resultado</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="p-4"><div class="container" style="max-width:640px;"><h4>Resultado</h4><ul class="list-group">';
foreach ($msgs as $m) echo '<li class="list-group-item"><span class="badge bg-'.($cls[$m[0]]??'secondary').' me-2">'.htmlspecialchars($m[0]).'</span>'.htmlspecialchars($m[1]).'</li>';
echo '</ul><a class="btn btn-primary mt-3" href="gestionar_colores_estado.php">Ir a Colores de Estados</a></div></body></html>';
