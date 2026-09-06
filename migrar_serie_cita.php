<?php
/**
 * migrar_serie_cita.php — Agrega la columna IDSERIE a AG_CITA.
 *
 * Sirve para agrupar las citas que pertenecen a una serie recurrente:
 * todas las citas de una misma serie comparten el mismo IDSERIE (que
 * apunta al IDCITA de la primera cita creada de esa serie).
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
     WHERE TABLE_SCHEMA='$dbName' AND TABLE_NAME='AG_CITA' AND COLUMN_NAME='IDSERIE'"
)->fetch_assoc()['c'] > 0;

$existeIdx = (int)$conexion->query(
    "SELECT COUNT(*) c FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA='$dbName' AND TABLE_NAME='AG_CITA' AND INDEX_NAME='IDX_AG_CITA_SERIE'"
)->fetch_assoc()['c'] > 0;

$ejecutar = isset($_POST['ejecutar']) && $_POST['ejecutar'] === '1';
$log = [];
$errores = 0;

if ($ejecutar) {
    if (!$existe) {
        if ($conexion->query("ALTER TABLE AG_CITA ADD COLUMN IDSERIE INT NULL AFTER IDAGENCIA")) {
            $log[] = ['ok', "✅ Columna IDSERIE agregada a AG_CITA."];
            $existe = true;
        } else {
            $log[] = ['err', "Error al agregar IDSERIE: " . $conexion->error];
            $errores++;
        }
    } else {
        $log[] = ['ok', "La columna IDSERIE ya existía — no se hizo ningún cambio."];
    }

    if ($existe && !$existeIdx) {
        if ($conexion->query("CREATE INDEX IDX_AG_CITA_SERIE ON AG_CITA (IDSERIE)")) {
            $log[] = ['ok', "✅ Índice IDX_AG_CITA_SERIE creado."];
        } else {
            $log[] = ['err', "Error al crear índice: " . $conexion->error];
            $errores++;
        }
    }

    $log[] = $errores === 0
        ? ['ok', "✅ Migración completada. Ya se pueden crear citas recurrentes y agrupar por serie."]
        : ['err', "⚠️ Migración con $errores error(es)."];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Migración: Citas recurrentes (IDSERIE)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>pre{background:#1e1e1e;color:#d4d4d4;padding:1rem;border-radius:6px;font-size:.8rem;}.log-ok{color:#4caf50;}.log-err{color:#f44336;}</style>
</head>
<body class="bg-light">
<div class="container py-4" style="max-width:760px;">
    <div class="card shadow">
        <div class="card-header text-white" style="background:#1a3a5c;">
            <h5 class="mb-0"><i class="bi bi-arrow-repeat me-2"></i>Migración: Citas recurrentes</h5>
        </div>
        <div class="card-body">
            <?php if (!$ejecutar): ?>
            <div class="alert alert-warning d-flex gap-2">
                <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0 mt-1"></i>
                <div><strong>Haz un backup antes de continuar.</strong> En phpMyAdmin → Exportar → Quick → Go.</div>
            </div>
            <div class="alert alert-info py-2">
                Esta migración agrega la columna <code>IDSERIE</code> (INT NULL) a <code>AG_CITA</code>
                y un índice sobre ella. Las citas existentes quedarán con IDSERIE nulo (no forman parte de ninguna serie).
            </div>
            <?php if ($existe && $existeIdx): ?>
            <div class="alert alert-success py-2 mb-0">La columna y el índice ya existen. Nada que migrar.</div>
            <?php endif; ?>
            <form method="POST" onsubmit="return confirm('¿Confirmas la migración? Asegúrate de tener backup.');">
                <input type="hidden" name="ejecutar" value="1">
                <button type="submit" class="btn btn-danger"><i class="bi bi-play-circle me-1"></i> Ejecutar migración</button>
                <a href="home.php" class="btn btn-outline-secondary ms-2">Cancelar</a>
            </form>
            <?php else: ?>
            <h6><i class="bi bi-terminal me-1"></i>Resultado</h6>
            <pre><?php foreach ($log as [$tipo, $msg]): ?>
<span class="log-<?php echo $tipo; ?>"><?php echo htmlspecialchars($msg); ?></span>
<?php endforeach; ?></pre>
            <a href="SCH_Calendar.php" class="btn btn-primary mt-2"><i class="bi bi-calendar me-1"></i> Ir al Calendario</a>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
