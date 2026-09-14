<?php
/**
 * diag_paciente_reciente.php
 * SÓLO LECTURA. Diagnóstico de registro de pacientes:
 *  - Lista los pacientes registrados en una fecha dada (con hora).
 *  - Muestra los últimos N pacientes por ID (creados recientemente).
 *  - Distingue importados de Kalix (KALIX_ID) vs. altas manuales.
 *  - Detecta posibles rechazos por teléfono duplicado.
 * SISTEMA-only. No modifica nada.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
if (!isset($_SESSION["rol"]) || strtoupper($_SESSION["rol"]) !== 'SISTEMA') {
    http_response_code(403); exit('Acceso restringido: sólo SISTEMA.');
}
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$tieneFecha = (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='".$conexion->real_escape_string($dbName)."' AND TABLE_NAME='AG_PACIENTE' AND COLUMN_NAME='FECHA_REGISTRO'")->fetch_assoc()['c']>0;
$tieneKalix = (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='".$conexion->real_escape_string($dbName)."' AND TABLE_NAME='AG_PACIENTE' AND COLUMN_NAME='KALIX_ID'")->fetch_assoc()['c']>0;

$fecha = trim($_GET['fecha'] ?? '');
if ($fecha==='' || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$fecha)) {
    // Sábado más reciente por defecto
    $d = new DateTime('today');
    while ((int)$d->format('N') !== 6) { $d->modify('-1 day'); }
    $fecha = $d->format('Y-m-d');
}
$fEsc = $conexion->real_escape_string($fecha);
$kalixSel = $tieneKalix ? ', KALIX_ID' : '';

// Registros de esa fecha
$delDia = [];
if ($tieneFecha) {
    $r = $conexion->query("SELECT IDPACIENTE, NOMBRES, APELLIDOS, CEDULA, TELEFONO, EMAIL, ESTADO, FECHA_REGISTRO$kalixSel
                           FROM AG_PACIENTE WHERE DATE(FECHA_REGISTRO)='$fEsc' ORDER BY FECHA_REGISTRO");
    if ($r) while ($x=$r->fetch_assoc()) $delDia[]=$x;
}
// Últimos por ID (creados recientemente, tabla incluye inactivos)
$ult = [];
$r2 = $conexion->query("SELECT IDPACIENTE, NOMBRES, APELLIDOS, CEDULA, TELEFONO, EMAIL, ESTADO".($tieneFecha?', FECHA_REGISTRO':'')."$kalixSel
                        FROM AG_PACIENTE ORDER BY IDPACIENTE DESC LIMIT 30");
if ($r2) while ($x=$r2->fetch_assoc()) $ult[]=$x;
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Diagnóstico registro pacientes</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:1000px;">
<h4>Diagnóstico — registro de pacientes <small class="text-muted">(sólo lectura)</small></h4>

<?php if (!$tieneFecha): ?>
<div class="alert alert-warning">La columna <code>FECHA_REGISTRO</code> no existe. Solo se puede ver por orden de creación (ID).
Puedes crearla con <a href="migrar_fecha_registro_paciente.php" class="alert-link">migrar_fecha_registro_paciente.php</a>.</div>
<?php endif; ?>

<form method="GET" class="row g-2 align-items-end mb-3">
    <div class="col-auto"><label class="form-label small">Fecha a revisar</label>
        <input type="date" name="fecha" value="<?php echo h($fecha); ?>" class="form-control"></div>
    <div class="col-auto"><button class="btn btn-primary">Ver</button></div>
</form>

<h5>Registrados el <?php echo h($fecha); ?> <span class="badge bg-secondary"><?php echo count($delDia); ?></span></h5>
<?php if ($delDia): ?>
<div class="table-responsive"><table class="table table-sm table-bordered">
<thead class="table-light"><tr><th>Hora</th><th>ID</th><th>Paciente</th><th>Cédula</th><th>Teléfono</th><th>Email</th><th>Estado</th><th>Origen</th></tr></thead>
<tbody>
<?php foreach ($delDia as $p):
    $hora = !empty($p['FECHA_REGISTRO']) ? date('H:i:s', strtotime($p['FECHA_REGISTRO'])) : '—';
    $origen = ($tieneKalix && !empty($p['KALIX_ID'])) ? 'Kalix' : 'Manual';
?>
<tr class="<?php echo $p['ESTADO']==='A'?'':'table-warning'; ?>">
    <td><b><?php echo h($hora); ?></b></td>
    <td><?php echo (int)$p['IDPACIENTE']; ?></td>
    <td><?php echo h(trim($p['APELLIDOS'].', '.$p['NOMBRES'])); ?></td>
    <td><?php echo h($p['CEDULA']?:'—'); ?></td>
    <td><?php echo h($p['TELEFONO']?:'—'); ?></td>
    <td><small><?php echo h($p['EMAIL']?:'—'); ?></small></td>
    <td><?php echo $p['ESTADO']==='A'?'<span class="badge bg-success">Activo</span>':'<span class="badge bg-warning text-dark">Inactivo</span>'; ?></td>
    <td><span class="badge bg-<?php echo $origen==='Kalix'?'info text-dark':'primary'; ?>"><?php echo $origen; ?></span></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php else: ?>
<div class="alert alert-info">No hay pacientes con <code>FECHA_REGISTRO</code> en <?php echo h($fecha); ?>.
Si la Dra. dice que creó uno, es muy probable que <b>el alta fue rechazada por teléfono duplicado</b>
(el número ya existe en otro paciente, p. ej. importado de Kalix).</div>
<?php endif; ?>

<h5 class="mt-4">Últimos 30 pacientes creados (por ID)</h5>
<div class="table-responsive"><table class="table table-sm table-bordered">
<thead class="table-light"><tr><th>ID</th><th>Paciente</th><th>Teléfono</th><th><?php echo $tieneFecha?'Registro':''; ?></th><th>Estado</th><th>Origen</th></tr></thead>
<tbody>
<?php foreach ($ult as $p):
    $origen = ($tieneKalix && !empty($p['KALIX_ID'])) ? 'Kalix' : 'Manual';
?>
<tr class="<?php echo $p['ESTADO']==='A'?'':'table-warning'; ?>">
    <td><?php echo (int)$p['IDPACIENTE']; ?></td>
    <td><?php echo h(trim($p['APELLIDOS'].', '.$p['NOMBRES'])); ?></td>
    <td><?php echo h($p['TELEFONO']?:'—'); ?></td>
    <td><small><?php echo $tieneFecha && !empty($p['FECHA_REGISTRO']) ? h(date('Y-m-d H:i',strtotime($p['FECHA_REGISTRO']))) : '—'; ?></small></td>
    <td><?php echo $p['ESTADO']==='A'?'<span class="badge bg-success">Activo</span>':'<span class="badge bg-warning text-dark">Inactivo</span>'; ?></td>
    <td><span class="badge bg-<?php echo $origen==='Kalix'?'info text-dark':'primary'; ?>"><?php echo $origen; ?></span></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>

<p class="text-muted small mt-3">Este archivo no modifica nada. Bórralo del servidor cuando termines el diagnóstico.</p>
</div></body></html>
