<?php
/**
 * migrar_indices.php
 * Añade índices en las tablas más consultadas para acelerar
 * calendario, listados de citas, historial, envíos y plantillas.
 *
 * Idempotente: cada índice se crea sólo si no existe (se comprueba
 * en information_schema.STATISTICS). Seguro de ejecutar en vivo:
 * las tablas son pequeñas (~decenas de MB), MySQL InnoDB añade
 * índices sin bloquear SELECTs.
 *
 * SISTEMA-only, POST-driven.
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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Índices de rendimiento</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:720px;">
<h4>Añadir índices de rendimiento</h4>
<p class="text-muted">Crea índices en <code>AG_CITA</code>, <code>AG_HISTORIAL</code>, <code>AG_PACIENTE</code>,
<code>paciente_seguro</code>, <code>documento_envio</code>, <code>documentos</code> y <code>cat_plantillas_nutricion</code>.
Idempotente: no duplica índices que ya existan.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar</button>
<a class="btn btn-link" href="Home.php">Volver</a></form></div></body></html>
    <?php
    exit;
}

// [ ['tabla', 'nombre_indice', 'expresion CREATE INDEX ... ON tabla (cols)'], ... ]
$indices = [
    // AG_CITA — calendario, home, agenda, reagendar
    ['AG_CITA',      'idx_fecha',              'CREATE INDEX idx_fecha ON AG_CITA (FECHA_CITA)'],
    ['AG_CITA',      'idx_fecha_estado',       'CREATE INDEX idx_fecha_estado ON AG_CITA (FECHA_CITA, ESTADO)'],
    ['AG_CITA',      'idx_estado_cita',        'CREATE INDEX idx_estado_cita ON AG_CITA (ESTADO_CITA, ESTADO, FECHA_CITA)'],
    ['AG_CITA',      'idx_paciente_fecha',     'CREATE INDEX idx_paciente_fecha ON AG_CITA (IDPACIENTE, FECHA_CITA)'],
    ['AG_CITA',      'idx_medico_fecha',       'CREATE INDEX idx_medico_fecha ON AG_CITA (IDMEDICO, FECHA_CITA)'],
    ['AG_CITA',      'idx_serie',              'CREATE INDEX idx_serie ON AG_CITA (IDSERIE)'],

    // AG_HISTORIAL — atenciones y guardar_atencion (SELECT ... WHERE IDCITA = ?)
    ['AG_HISTORIAL', 'idx_cita',               'CREATE INDEX idx_cita ON AG_HISTORIAL (IDCITA)'],
    ['AG_HISTORIAL', 'idx_paciente',           'CREATE INDEX idx_paciente ON AG_HISTORIAL (IDPACIENTE)'],

    // AG_PACIENTE — buscar_pacientes, listados
    ['AG_PACIENTE',  'idx_estado',             'CREATE INDEX idx_estado ON AG_PACIENTE (ESTADO)'],
    ['AG_PACIENTE',  'idx_estado_ape_nom',     'CREATE INDEX idx_estado_ape_nom ON AG_PACIENTE (ESTADO, APELLIDOS, NOMBRES)'],

    // paciente_seguro
    ['paciente_seguro', 'idx_paciente',        'CREATE INDEX idx_paciente ON paciente_seguro (id_paciente)'],

    // documento_envio — firmar_guardar, documentos_enviados
    ['documento_envio', 'idx_token',           'CREATE INDEX idx_token ON documento_envio (token)'],
    ['documento_envio', 'idx_paciente_estado', 'CREATE INDEX idx_paciente_estado ON documento_envio (id_paciente, estado)'],

    // documentos
    ['documentos',      'idx_titulo',          'CREATE INDEX idx_titulo ON documentos (titulo)'],
    ['documentos',      'idx_estado',          'CREATE INDEX idx_estado ON documentos (estado)'],

    // cat_plantillas_nutricion — filtro por categoría
    ['cat_plantillas_nutricion', 'idx_categoria', 'CREATE INDEX idx_categoria ON cat_plantillas_nutricion (categoria)'],
    ['cat_plantillas_nutricion', 'idx_nombre',    'CREATE INDEX idx_nombre    ON cat_plantillas_nutricion (nombre_plantilla)'],
];

$msgs = [];

$chkTable = $conexion->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1");
$chkIdx   = $conexion->prepare("SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1");

foreach ($indices as $row) {
    [$tabla, $nombreIdx, $sql] = $row;

    // ¿existe la tabla?
    $chkTable->bind_param('s', $tabla);
    $chkTable->execute();
    $existTbl = (bool) $chkTable->get_result()->fetch_row();
    if (!$existTbl) {
        $msgs[] = ['SKIP', "Tabla no existe: {$tabla} — se salta {$nombreIdx}"];
        continue;
    }

    // ¿ya existe el índice?
    $chkIdx->bind_param('ss', $tabla, $nombreIdx);
    $chkIdx->execute();
    $existIdx = (bool) $chkIdx->get_result()->fetch_row();
    if ($existIdx) {
        $msgs[] = ['OK',   "Ya existía: {$tabla}.{$nombreIdx}"];
        continue;
    }

    // crear
    if ($conexion->query($sql)) {
        $msgs[] = ['NEW',  "Creado: {$tabla}.{$nombreIdx}"];
    } else {
        $msgs[] = ['ERR',  "ERROR {$tabla}.{$nombreIdx}: ".$conexion->error];
    }
}
$chkTable->close();
$chkIdx->close();

$cls = ['NEW' => 'success', 'OK' => 'secondary', 'SKIP' => 'warning', 'ERR' => 'danger'];
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Índices</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:820px;">
<h4>Resultado — Índices de rendimiento</h4>
<ul class="list-group">
<?php foreach ($msgs as $m): [$tag, $txt] = $m; ?>
    <li class="list-group-item">
        <span class="badge bg-<?= $cls[$tag] ?? 'secondary' ?> me-2"><?= htmlspecialchars($tag) ?></span>
        <?= htmlspecialchars($txt) ?>
    </li>
<?php endforeach; ?>
</ul>
<a class="btn btn-primary mt-3" href="Home.php">Volver al inicio</a>
</div></body></html>
