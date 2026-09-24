<?php
/**
 * migrar_paciente_direccion_telefono.php
 * Amplía las columnas ADDRESS y TELEFONO de AG_PACIENTE para que quepan
 * direcciones completas y números de teléfono más largos (con lada,
 * extensión, etc.). Antes eran demasiado cortas y MySQL rechazaba el
 * guardado ("Data too long"), por eso la dirección "se borraba" y el
 * teléfono no aceptaba más dígitos.
 *
 * ADDRESS  -> VARCHAR(255)
 * TELEFONO -> VARCHAR(30)
 *
 * Sólo amplía (nunca reduce). Idempotente. SISTEMA-only, POST-driven.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
if (!isset($_SESSION["rol"]) || strtoupper($_SESSION["rol"]) !== 'SISTEMA') {
    http_response_code(403); exit('Acceso restringido: sólo SISTEMA.');
}

$dbEsc = $conexion->real_escape_string($conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db']);
$infoCol = function($c) use ($conexion, $dbEsc) {
    $c = $conexion->real_escape_string($c);
    $r = $conexion->query(
        "SELECT DATA_TYPE, CHARACTER_MAXIMUM_LENGTH AS len
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA='$dbEsc' AND TABLE_NAME='AG_PACIENTE' AND COLUMN_NAME='$c' LIMIT 1"
    );
    return $r ? $r->fetch_assoc() : null;
};

// Definición objetivo: columna => [tipo destino, longitud mínima requerida]
$objetivos = [
    'ADDRESS'  => ['VARCHAR(255)', 255],
    'TELEFONO' => ['VARCHAR(30)',   30],
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Dirección / Teléfono</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Ampliar Dirección y Teléfono del paciente</h4>
<p class="text-muted">Amplía <code>ADDRESS</code> a VARCHAR(255) y <code>TELEFONO</code> a VARCHAR(30)
en <code>AG_PACIENTE</code> para que se guarden direcciones completas y teléfonos más largos.
Sólo amplía, nunca reduce. Idempotente.</p>
<ul class="list-group mb-3">
<?php
foreach ($objetivos as $col => $obj) {
    $info = $infoCol($col);
    $actual = $info ? ($info['DATA_TYPE'].($info['len'] !== null ? '('.$info['len'].')' : '')) : 'no existe';
    echo '<li class="list-group-item d-flex justify-content-between"><span><code>'.htmlspecialchars($col).'</code></span>'
       . '<span>actual: <b>'.htmlspecialchars($actual).'</b> &rarr; '.htmlspecialchars($obj[0]).'</span></li>';
}
?>
</ul>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar</button>
<a class="btn btn-link" href="pacientes_crud.php">Volver</a></form></div></body></html>
    <?php
    exit;
}

$msgs = [];
foreach ($objetivos as $col => $obj) {
    list($tipoDestino, $lenMin) = $obj;
    $info = $infoCol($col);
    if (!$info) {
        $ok = $conexion->query("ALTER TABLE AG_PACIENTE ADD COLUMN $col $tipoDestino NULL");
        $msgs[] = $ok ? ['NEW', "Columna $col creada como $tipoDestino."]
                      : ['ERR', "$col: ".$conexion->error];
        continue;
    }
    $len = ($info['len'] === null) ? 0 : (int)$info['len'];
    $esTexto = in_array(strtolower($info['DATA_TYPE']), ['varchar','char','text','tinytext','mediumtext','longtext'], true);
    // Ya es texto y suficientemente amplia: no tocar.
    if ($esTexto && $len >= $lenMin) {
        $msgs[] = ['OK', "$col ya es amplia (".$info['DATA_TYPE']."($len)); sin cambios."];
        continue;
    }
    $ok = $conexion->query("ALTER TABLE AG_PACIENTE MODIFY COLUMN $col $tipoDestino NULL");
    $antes = $info['DATA_TYPE'].($info['len'] !== null ? '('.$len.')' : '');
    $msgs[] = $ok ? ['NEW', "$col ampliada de $antes a $tipoDestino."]
                  : ['ERR', "$col: ".$conexion->error];
}

$cls = ['NEW'=>'success','OK'=>'secondary','ERR'=>'danger'];
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Resultado</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="p-4"><div class="container" style="max-width:640px;"><h4>Resultado</h4><ul class="list-group">';
foreach ($msgs as $m) echo '<li class="list-group-item"><span class="badge bg-'.($cls[$m[0]]??'secondary').' me-2">'.htmlspecialchars($m[0]).'</span>'.htmlspecialchars($m[1]).'</li>';
echo '</ul><a class="btn btn-primary mt-3" href="pacientes_crud.php">Volver</a></div></body></html>';
