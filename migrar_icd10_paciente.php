<?php
/**
 * migrar_icd10_paciente.php
 * Añade la columna AG_PACIENTE.IDICD10 (INT NULL) que referencia
 * ENFE_DIAG_COD.ID_ENFE_DIAG_COD (código ICD-10 principal del paciente).
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
<head><meta charset="UTF-8"><title>Migración ICD-10 paciente</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4">
<div class="container" style="max-width:640px;">
    <h4>Migrar ICD-10 del paciente</h4>
    <p class="text-muted">Agrega la columna <code>AG_PACIENTE.IDICD10</code> (INT NULL) que referencia <code>ENFE_DIAG_COD.ID_ENFE_DIAG_COD</code>.</p>
    <form method="POST">
        <button class="btn btn-primary" type="submit">Ejecutar migración</button>
        <a class="btn btn-link" href="home.php">Volver</a>
    </form>
</div></body></html>
<?php
    exit;
}

$conexion = conectarse();
if (!$conexion) { exit('No hay conexión a la base.'); }

$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$existe = (int)$conexion->query(
    "SELECT COUNT(*) c FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA='$dbName' AND TABLE_NAME='AG_PACIENTE' AND COLUMN_NAME='IDICD10'"
)->fetch_assoc()['c'];

$msgs = [];
if ($existe === 0) {
    if ($conexion->query("ALTER TABLE AG_PACIENTE ADD COLUMN IDICD10 INT NULL DEFAULT NULL COMMENT 'ID del código ICD-10 (ENFE_DIAG_COD.ID_ENFE_DIAG_COD)'")) {
        $msgs[] = 'OK — columna IDICD10 creada.';
    } else {
        $msgs[] = 'ERROR creando IDICD10: ' . $conexion->error;
    }
} else {
    $msgs[] = 'La columna IDICD10 ya existe. No se hizo nada.';
}

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Migración</title>'
   . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>'
   . '<body class="p-4"><div class="container" style="max-width:640px;"><h4>Resultado</h4><ul class="list-group">';
foreach ($msgs as $m) echo '<li class="list-group-item">' . htmlspecialchars($m) . '</li>';
echo '</ul><a class="btn btn-secondary mt-3" href="home.php">Volver</a></div></body></html>';
