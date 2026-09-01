<?php
/**
 * migrar_idioma_paciente.php — Agrega la columna IDIOMA a AG_PACIENTE
 *
 * Guarda el idioma preferido de cada paciente ('es' | 'en') para enviar
 * los correos de cita en su idioma. Por defecto 'es'.
 *
 * IMPORTANTE: Haz un backup antes de ejecutar. Solo se ejecuta una vez.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'SISTEMA') {
    die('<p style="color:red;font-family:sans-serif;padding:2rem;">Acceso restringido — solo SISTEMA.</p>');
}

$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$existe = (int)$conexion->query(
    "SELECT COUNT(*) c FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA='$dbName' AND TABLE_NAME='AG_PACIENTE' AND COLUMN_NAME='IDIOMA'"
)->fetch_assoc()['c'] > 0;

$ejecutar = isset($_POST['ejecutar']) && $_POST['ejecutar'] === '1';
$log = [];
$errores = 0;

if ($ejecutar && !$existe) {
    if ($conexion->query("ALTER TABLE AG_PACIENTE ADD COLUMN IDIOMA VARCHAR(2) NOT NULL DEFAULT 'es' AFTER GENDER")) {
        $log[] = ['ok', "✅ Columna IDIOMA agregada a AG_PACIENTE (valor por defecto: 'es')."];
    } else {
        $log[] = ['err', "Error en ALTER TABLE: " . $conexion->error];
        $errores++;
    }
    $log[] = $errores === 0
        ? ['ok', "✅ Migración completada. Ya puedes elegir el idioma de cada paciente y los correos se enviarán en ese idioma."]
        : ['err', "⚠️ Migración con $errores error(es)."];
} elseif ($ejecutar && $existe) {
    $log[] = ['ok', "La columna IDIOMA ya existía — no se hizo ningún cambio."];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Migración: Idioma del Paciente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>pre{background:#1e1e1e;color:#d4d4d4;padding:1rem;border-radius:6px;font-size:.8rem;}.log-ok{color:#4caf50;}.log-err{color:#f44336;}</style>
</head>
<body class="bg-light">
<div class="container py-4" style="max-width:760px;">
    <div class="card shadow">
        <div class="card-header text-white" style="background:#1a3a5c;">
            <h5 class="mb-0"><i class="bi bi-translate me-2"></i>Migración: Idioma del Paciente</h5>
        </div>
        <div class="card-body">
            <?php if (!$ejecutar): ?>
            <div class="alert alert-warning d-flex gap-2">
                <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0 mt-1"></i>
                <div><strong>Haz un backup antes de continuar.</strong> En phpMyAdmin → Exportar → Quick → Go.</div>
            </div>
            <?php if ($existe): ?>
            <div class="alert alert-success py-2 mb-0">
                La columna <code>IDIOMA</code> ya existe en <code>AG_PACIENTE</code>. No hay nada que migrar.
            </div>
            <?php else: ?>
            <div class="alert alert-info py-2">
                Se agregará la columna <code>IDIOMA</code> (VARCHAR(2), por defecto <code>'es'</code>) a
                <code>AG_PACIENTE</code>. Los pacientes existentes quedarán en español; podrás cambiarlos a
                inglés uno por uno desde la ficha del paciente.
            </div>
            <form method="POST" onsubmit="return confirm('¿Confirmas la migración? Asegúrate de tener backup.');">
                <input type="hidden" name="ejecutar" value="1">
                <button type="submit" class="btn btn-danger"><i class="bi bi-play-circle me-1"></i> Ejecutar migración</button>
                <a href="home.php" class="btn btn-outline-secondary ms-2">Cancelar</a>
            </form>
            <?php endif; ?>
            <?php else: ?>
            <h6><i class="bi bi-terminal me-1"></i>Resultado</h6>
            <pre><?php foreach ($log as [$tipo, $msg]): ?>
<span class="log-<?php echo $tipo; ?>"><?php echo htmlspecialchars($msg); ?></span>
<?php endforeach; ?></pre>
            <a href="listado_pacientes.php" class="btn btn-primary mt-2"><i class="bi bi-people me-1"></i> Ir a Listado de Pacientes</a>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
