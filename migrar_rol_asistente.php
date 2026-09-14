<?php
/**
 * migrar_rol_asistente.php
 * Crea el rol "ASISTENTE" en ADM_ROL (si no existe) para poder asignarlo
 * al crear usuarios. Idempotente. SISTEMA-only, POST-driven.
 *
 * Permisos del rol (definidos en el menú y en los candados de cada página):
 *   - Calendario: crear/asignar citas a doctores + Colores de estados.
 *   - Pacientes: registrar y gestionar pacientes.
 *   - Facturación: crear facturas y ver Cuentas por Cobrar (sólo lectura de pagos).
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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Rol Asistente</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Crear rol "ASISTENTE"</h4>
<p class="text-muted">Agrega el rol <code>ASISTENTE</code> a <code>ADM_ROL</code> para poder
asignarlo al crear usuarios. Idempotente.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar</button>
<a class="btn btn-link" href="PNC_UsuarioCrear.php">Ir a crear usuarios</a></form></div></body></html>
    <?php
    exit;
}
$msgs = [];
$chk = $conexion->query("SELECT IDADM_ROL FROM ADM_ROL WHERE CARGO='ASISTENTE' LIMIT 1");
if ($chk && $chk->num_rows > 0) {
    $msgs[] = ['OK','El rol ASISTENTE ya existe.'];
} else {
    $ins = $conexion->query("INSERT INTO ADM_ROL (CARGO, ESTADO) VALUES ('ASISTENTE','A')");
    $msgs[] = $ins ? ['NEW','Rol ASISTENTE creado.'] : ['ERR','No se pudo crear: '.$conexion->error];
}
$cls=['NEW'=>'success','OK'=>'secondary','ERR'=>'danger'];
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Resultado</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="p-4"><div class="container" style="max-width:640px;"><h4>Resultado</h4><ul class="list-group">';
foreach ($msgs as $m) echo '<li class="list-group-item"><span class="badge bg-'.($cls[$m[0]]??'secondary').' me-2">'.htmlspecialchars($m[0]).'</span>'.htmlspecialchars($m[1]).'</li>';
echo '</ul><a class="btn btn-primary mt-3" href="PNC_UsuarioCrear.php">Ir a crear usuarios</a></div></body></html>';
