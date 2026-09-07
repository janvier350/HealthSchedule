<?php
/**
 * migrar_firma_doctor.php
 * Añade la columna ADM_USUARIO.FIRMA_IMG (LONGTEXT) para guardar la firma
 * escaneada / dibujada del profesional como data URI base64 (image/png|jpeg).
 * Ejecutar una sola vez desde el rol SISTEMA con POST desde el navegador.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");

if (!isset($_SESSION["rol"]) || strtoupper($_SESSION["rol"]) !== 'SISTEMA') {
    http_response_code(403);
    exit('Acceso restringido: sólo SISTEMA puede correr esta migración.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Migración firma doctor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container" style="max-width:640px;">
    <h4>Migrar firma del profesional</h4>
    <p class="text-muted">Agrega la columna <code>ADM_USUARIO.FIRMA_IMG</code> (LONGTEXT) si aún no existe.</p>
    <form method="POST">
        <button class="btn btn-primary" type="submit">Ejecutar migración</button>
        <a class="btn btn-link" href="home.php">Volver</a>
    </form>
</div>
</body>
</html>
<?php
    exit;
}

$conexion = conectarse();
if (!$conexion) { exit('No hay conexión a la base.'); }

$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$existe = (int)$conexion->query(
    "SELECT COUNT(*) c FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA='$dbName' AND TABLE_NAME='ADM_USUARIO' AND COLUMN_NAME='FIRMA_IMG'"
)->fetch_assoc()['c'];

$msgs = [];
if ($existe === 0) {
    if ($conexion->query("ALTER TABLE ADM_USUARIO ADD COLUMN FIRMA_IMG LONGTEXT NULL COMMENT 'Firma del profesional (data URI base64)'")) {
        $msgs[] = 'OK — columna FIRMA_IMG creada.';
    } else {
        $msgs[] = 'ERROR creando FIRMA_IMG: ' . $conexion->error;
    }
} else {
    $msgs[] = 'La columna FIRMA_IMG ya existe. No se hizo nada.';
}

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Migración</title>'
    . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>'
    . '<body class="p-4"><div class="container" style="max-width:640px;">'
    . '<h4>Resultado</h4><ul class="list-group">';
foreach ($msgs as $m) echo '<li class="list-group-item">' . htmlspecialchars($m) . '</li>';
echo '</ul><a class="btn btn-secondary mt-3" href="home.php">Volver</a></div></body></html>';
