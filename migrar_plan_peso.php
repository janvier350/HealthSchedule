<?php
/**
 * migrar_plan_peso.php — Crea la tabla AG_PLAN_PESO
 *
 * Guarda en la ficha del paciente los planes generados con el
 * Planificador de Peso Corporal (modelo NIH).
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
$tablaExiste = (int)$conexion->query(
    "SELECT COUNT(*) c FROM information_schema.TABLES
     WHERE TABLE_SCHEMA='$dbName' AND TABLE_NAME='AG_PLAN_PESO'"
)->fetch_assoc()['c'] > 0;

$ejecutar = isset($_POST['ejecutar']) && $_POST['ejecutar'] === '1';
$log = [];
$errores = 0;

if ($ejecutar && !$tablaExiste) {
    $sql = "CREATE TABLE AG_PLAN_PESO (
        IDPLAN INT AUTO_INCREMENT PRIMARY KEY,
        IDPACIENTE INT NOT NULL,
        IDADM_USUARIO INT NULL,
        UNIDADES VARCHAR(10) NULL,
        SEXO TINYINT NULL,
        EDAD INT NULL,
        ESTATURA_M DECIMAL(4,2) NULL,
        PESO_INICIAL DECIMAL(6,2) NULL,
        PAL_INICIAL DECIMAL(4,2) NULL,
        PESO_META DECIMAL(6,2) NULL,
        FECHA_META DATE NULL,
        PAL_META DECIMAL(4,2) NULL,
        CAL_MANTENER_ACTUAL INT NULL,
        CAL_ALCANZAR INT NULL,
        CAL_MANTENER_META INT NULL,
        FECHA_REGISTRO DATETIME DEFAULT CURRENT_TIMESTAMP,
        ESTADO CHAR(1) DEFAULT 'A',
        INDEX (IDPACIENTE)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    if ($conexion->query($sql)) {
        $log[] = ['ok', "✅ Tabla AG_PLAN_PESO creada correctamente."];
    } else {
        $log[] = ['err', "Error al crear la tabla: " . $conexion->error];
        $errores++;
    }
    $log[] = $errores === 0
        ? ['ok', "✅ Migración completada. Ya puedes guardar planes de peso en la ficha del paciente."]
        : ['err', "⚠️ Migración con $errores error(es)."];
} elseif ($ejecutar && $tablaExiste) {
    $log[] = ['ok', "La tabla AG_PLAN_PESO ya existía — no se hizo ningún cambio."];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Migración: Planes de Peso</title>
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
            <h5 class="mb-0"><i class="bi bi-graph-down-arrow me-2"></i>Migración: Planes de Peso</h5>
        </div>
        <div class="card-body">

            <?php if (!$ejecutar): ?>
            <div class="alert alert-warning d-flex gap-2">
                <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0 mt-1"></i>
                <div><strong>Haz un backup antes de continuar.</strong> En phpMyAdmin → Exportar → Quick → Go.</div>
            </div>

            <?php if ($tablaExiste): ?>
            <div class="alert alert-success py-2 mb-0">
                La tabla <code>AG_PLAN_PESO</code> ya existe. No hay nada que migrar —
                puedes ir directo al <a href="body_weight_planner.php">Planificador de Peso</a>.
            </div>
            <?php else: ?>
            <div class="alert alert-info py-2">
                Se creará la tabla <code>AG_PLAN_PESO</code> para guardar los planes generados con el
                Planificador de Peso Corporal en la ficha de cada paciente.
            </div>
            <form method="POST" onsubmit="return confirm('¿Confirmas la migración? Asegúrate de tener backup.');">
                <input type="hidden" name="ejecutar" value="1">
                <button type="submit" class="btn btn-danger"><i class="bi bi-play-circle me-1"></i> Ejecutar migración</button>
                <a href="home.php" class="btn btn-outline-secondary ms-2">Cancelar</a>
            </form>
            <?php endif; ?>

            <?php else: ?>
            <h6><i class="bi bi-terminal me-1"></i>Resultado de la migración</h6>
            <pre><?php foreach ($log as [$tipo, $msg]): ?>
<span class="log-<?php echo $tipo; ?>"><?php echo htmlspecialchars($msg); ?></span>
<?php endforeach; ?></pre>
            <a href="body_weight_planner.php" class="btn btn-primary mt-2">
                <i class="bi bi-graph-down-arrow me-1"></i> Ir al Planificador de Peso
            </a>
            <?php endif; ?>

        </div>
    </div>
</div>
</body>
</html>
