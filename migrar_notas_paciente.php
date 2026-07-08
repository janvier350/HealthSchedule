<?php
/**
 * migrar_notas_paciente.php — Agrega la columna ALERTA a AG_PACIENTE
 *
 * Habilita las notas estilo Kalix por paciente:
 *   - Alerta              → columna ALERTA  (nueva, se crea aquí)
 *   - Notas Importantes   → columna NOTES    (ya existe)
 *   - Notas de Facturación→ columna ADDNOTES (ya existe)
 *
 * IMPORTANTE: Haz un backup completo antes de ejecutar.
 * Solo debe ejecutarse una vez.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'SISTEMA') {
    die('<p style="color:red;font-family:sans-serif;padding:2rem;">Acceso restringido — solo SISTEMA.</p>');
}

$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];

$colRes = $conexion->query(
    "SELECT COUNT(*) c FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = '$dbName' AND TABLE_NAME = 'AG_PACIENTE' AND COLUMN_NAME = 'ALERTA'"
);
$columnaExiste = (int)$colRes->fetch_assoc()['c'] > 0;

$ejecutar = isset($_POST['ejecutar']) && $_POST['ejecutar'] === '1';
$log      = [];
$errores  = 0;

if ($ejecutar && !$columnaExiste) {
    if ($conexion->query("ALTER TABLE AG_PACIENTE ADD COLUMN ALERTA TEXT NULL AFTER ADDNOTES")) {
        $log[] = ['ok', "✅ Columna ALERTA agregada a AG_PACIENTE."];
    } else {
        $log[] = ['err', "Error en ALTER TABLE: " . $conexion->error];
        $errores++;
    }
    $log[] = $errores === 0
        ? ['ok', "✅ Migración completada. Ya puedes usar las notas del paciente en el Listado de Pacientes."]
        : ['err', "⚠️ Migración con $errores error(es)."];
} elseif ($ejecutar && $columnaExiste) {
    $log[] = ['ok', "La columna ALERTA ya existía — no se hizo ningún cambio."];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Migración: Notas del Paciente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        pre { background:#1e1e1e; color:#d4d4d4; padding:1rem; border-radius:6px; font-size:.8rem; }
        .log-ok  { color:#4caf50; }
        .log-err { color:#f44336; }
    </style>
</head>
<body class="bg-light">
<div class="container py-4" style="max-width:760px;">
    <div class="card shadow">
        <div class="card-header text-white" style="background:#1a3a5c;">
            <h5 class="mb-0"><i class="bi bi-journal-text me-2"></i>Migración: Notas del Paciente</h5>
        </div>
        <div class="card-body">

            <?php if (!$ejecutar): ?>

            <div class="alert alert-warning d-flex gap-2">
                <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0 mt-1"></i>
                <div><strong>Haz un backup antes de continuar.</strong> En phpMyAdmin → Exportar → Quick → Go.</div>
            </div>

            <?php if ($columnaExiste): ?>
            <div class="alert alert-success py-2 mb-0">
                La columna <code>ALERTA</code> ya existe en <code>AG_PACIENTE</code>. No hay nada que migrar —
                puedes ir directo a <a href="listado_pacientes.php">Listado de Pacientes</a>.
            </div>
            <?php else: ?>
            <div class="alert alert-info py-2">
                Se agregará la columna <code>ALERTA</code> (TEXT) a <code>AG_PACIENTE</code> para las notas de
                tipo <strong>Alerta</strong>. Las <strong>Notas Importantes</strong> y de
                <strong>Facturación</strong> usan las columnas existentes <code>NOTES</code> y <code>ADDNOTES</code>.
            </div>
            <form method="POST" onsubmit="return confirm('¿Confirmas la migración? Asegúrate de tener backup.');">
                <input type="hidden" name="ejecutar" value="1">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-play-circle me-1"></i> Ejecutar migración
                </button>
                <a href="home.php" class="btn btn-outline-secondary ms-2">Cancelar</a>
            </form>
            <?php endif; ?>

            <?php else: ?>

            <h6><i class="bi bi-terminal me-1"></i>Resultado de la migración</h6>
            <pre><?php foreach ($log as [$tipo, $msg]): ?>
<span class="log-<?php echo $tipo; ?>"><?php echo htmlspecialchars($msg); ?></span>
<?php endforeach; ?></pre>

            <a href="listado_pacientes.php" class="btn btn-primary mt-2">
                <i class="bi bi-people me-1"></i> Ir a Listado de Pacientes
            </a>

            <?php endif; ?>

        </div>
    </div>
</div>
</body>
</html>
