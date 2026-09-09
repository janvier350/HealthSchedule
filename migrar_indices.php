<?php
/**
 * migrar_indices.php
 * Añade índices en las tablas más consultadas para acelerar
 * calendario, listados de citas, historial, envíos y plantillas.
 *
 * Idempotente: por cada tabla se lista SHOW INDEX y sólo se crea
 * lo que no existe. Sin dependencia de information_schema
 * (algunos hostings compartidos lo bloquean).
 *
 * Resiliente: cualquier error de MySQL se captura y se muestra,
 * el script no aborta con fatal. Los mensajes se envían al vuelo
 * (flush) para ver hasta dónde llegó si algo falla.
 *
 * SISTEMA-only, POST-driven.
 */

// Errores visibles SÓLO durante este script (sin cambiar php.ini global)
@ini_set('display_errors', '1');
error_reporting(E_ALL);

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
<body class="p-4"><div class="container" style="max-width:820px;">
<h4>Añadir índices de rendimiento</h4>
<p class="text-muted">Crea índices en <code>AG_CITA</code>, <code>AG_HISTORIAL</code>, <code>AG_PACIENTE</code>,
<code>paciente_seguro</code>, <code>documento_envio</code>, <code>documentos</code> y <code>cat_plantillas_nutricion</code>.
Idempotente: no duplica índices existentes.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar</button>
<a class="btn btn-link" href="Home.php">Volver</a></form></div></body></html>
    <?php
    exit;
}

// Salida incremental para ver progreso aunque algo falle a mitad
@ini_set('output_buffering', '0');
@ini_set('zlib.output_compression', '0');
while (ob_get_level() > 0) { @ob_end_flush(); }

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Índices</title>'
   . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>'
   . '<body class="p-4"><div class="container" style="max-width:900px;">'
   . '<h4>Resultado — Índices de rendimiento</h4>'
   . '<ul class="list-group" id="log">';
@ob_flush(); @flush();

function say($tag, $msg) {
    $cls = [
        'NEW'  => 'success',
        'OK'   => 'secondary',
        'SKIP' => 'warning',
        'ERR'  => 'danger',
        'INFO' => 'info',
    ][$tag] ?? 'secondary';
    echo '<li class="list-group-item"><span class="badge bg-'.$cls.' me-2">'
       . htmlspecialchars($tag).'</span>'.htmlspecialchars($msg).'</li>';
    @ob_flush(); @flush();
}

if (!$conexion) {
    say('ERR', 'No hay conexión a la BD.');
    echo '</ul></div></body></html>';
    exit;
}

/* Definición: por cada tabla, lista de índices a asegurar.
   'name' = nombre lógico del índice
   'cols' = columnas (ya escapadas con backticks al ejecutar) */
$plan = [
    'AG_CITA' => [
        ['name' => 'idx_fecha',            'cols' => ['FECHA_CITA']],
        ['name' => 'idx_fecha_estado',     'cols' => ['FECHA_CITA','ESTADO']],
        ['name' => 'idx_estado_cita',      'cols' => ['ESTADO_CITA','ESTADO','FECHA_CITA']],
        ['name' => 'idx_paciente_fecha',   'cols' => ['IDPACIENTE','FECHA_CITA']],
        ['name' => 'idx_medico_fecha',     'cols' => ['IDMEDICO','FECHA_CITA']],
        ['name' => 'idx_serie',            'cols' => ['IDSERIE']],
    ],
    'AG_HISTORIAL' => [
        ['name' => 'idx_cita',             'cols' => ['IDCITA']],
        ['name' => 'idx_paciente',         'cols' => ['IDPACIENTE']],
    ],
    'AG_PACIENTE' => [
        ['name' => 'idx_estado',           'cols' => ['ESTADO']],
        ['name' => 'idx_estado_ape_nom',   'cols' => ['ESTADO','APELLIDOS','NOMBRES']],
    ],
    'paciente_seguro' => [
        ['name' => 'idx_paciente',         'cols' => ['id_paciente']],
    ],
    'documento_envio' => [
        ['name' => 'idx_token',            'cols' => ['token']],
        ['name' => 'idx_paciente_estado',  'cols' => ['id_paciente','estado']],
    ],
    'documentos' => [
        ['name' => 'idx_titulo',           'cols' => ['titulo']],
        ['name' => 'idx_estado',           'cols' => ['estado']],
    ],
    'cat_plantillas_nutricion' => [
        ['name' => 'idx_categoria',        'cols' => ['categoria']],
        ['name' => 'idx_nombre',           'cols' => ['nombre_plantilla']],
    ],
];

foreach ($plan as $tabla => $indices) {
    // ¿existe la tabla? — SHOW TABLES LIKE (siempre permitido)
    $safeTbl = str_replace('`', '', $tabla);
    $like    = $conexion->real_escape_string($safeTbl);
    $r = @$conexion->query("SHOW TABLES LIKE '{$like}'");
    if (!$r || $r->num_rows === 0) {
        say('SKIP', "Tabla no existe: {$tabla}");
        continue;
    }

    // ¿qué columnas y qué índices ya tiene?
    $existingCols = [];
    $rc = @$conexion->query("SHOW COLUMNS FROM `{$safeTbl}`");
    if ($rc) { while ($row = $rc->fetch_assoc()) { $existingCols[strtolower($row['Field'])] = $row['Field']; } }

    $existingIdx = [];
    $ri = @$conexion->query("SHOW INDEX FROM `{$safeTbl}`");
    if ($ri) { while ($row = $ri->fetch_assoc()) { $existingIdx[strtolower($row['Key_name'])] = true; } }

    foreach ($indices as $idx) {
        $name = $idx['name'];
        $cols = $idx['cols'];

        // Todas las columnas deben existir
        $missing = [];
        foreach ($cols as $c) {
            if (!isset($existingCols[strtolower($c)])) { $missing[] = $c; }
        }
        if ($missing) {
            say('SKIP', "{$tabla}.{$name}: falta(n) columna(s) ".implode(', ', $missing));
            continue;
        }

        if (isset($existingIdx[strtolower($name)])) {
            say('OK', "Ya existía: {$tabla}.{$name}");
            continue;
        }

        // Construir SQL con backticks en columnas
        $colList = implode(', ', array_map(function ($c) { return '`'.str_replace('`', '', $c).'`'; }, $cols));
        $safeName = '`'.str_replace('`', '', $name).'`';
        $sql = "CREATE INDEX {$safeName} ON `{$safeTbl}` ({$colList})";

        if (@$conexion->query($sql)) {
            say('NEW', "Creado: {$tabla}.{$name} ({$colList})");
        } else {
            say('ERR', "Fallo {$tabla}.{$name}: (".$conexion->errno.") ".$conexion->error);
        }
    }
}

say('INFO', 'Terminado. Puedes volver a ejecutar sin problema (idempotente).');

echo '</ul><a class="btn btn-primary mt-3" href="Home.php">Volver al inicio</a>'
   . '</div></body></html>';
