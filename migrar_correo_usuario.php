<?php
/**
 * migrar_correo_usuario.php — Agrega la columna CORREO a ADM_USUARIO
 *
 * Necesaria para guardar el correo del usuario y poder enviarle el
 * correo de bienvenida con el enlace de la aplicación al crearlo.
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
     WHERE TABLE_SCHEMA = '$dbName' AND TABLE_NAME = 'ADM_USUARIO' AND COLUMN_NAME = 'CORREO'"
);
$columnaExiste = (int)$colRes->fetch_assoc()['c'] > 0;

$ejecutar = isset($_POST['ejecutar']) && $_POST['ejecutar'] === '1';
$log      = [];
$errores  = 0;

if ($ejecutar && !$columnaExiste) {
    if ($conexion->query("ALTER TABLE ADM_USUARIO ADD COLUMN CORREO VARCHAR(150) NULL AFTER TELEFONO")) {
        $log[] = ['ok', "✅ Columna CORREO agregada a ADM_USUARIO."];
    } else {
        $log[] = ['err', "Error en ALTER TABLE: " . $conexion->error];
        $errores++;
    }
    $log[] = $errores === 0
        ? ['ok', "✅ Migración completada. Al crear un usuario ahora se podrá enviar el correo de bienvenida."]
        : ['err', "⚠️ Migración con $errores error(es)."];
} elseif ($ejecutar && $columnaExiste) {
    $log[] = ['ok', "La columna CORREO ya existía — no se hizo ningún cambio."];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Migración: Correo de Usuario</title>
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
            <h5 class="mb-0"><i class="bi bi-envelope-at me-2"></i>Migración: Correo de Usuario</h5>
        </div>
        <div class="card-body">

            <?php if (!$ejecutar): ?>

            <div class="alert alert-warning d-flex gap-2">
                <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0 mt-1"></i>
                <div><strong>Haz un backup antes de continuar.</strong> En phpMyAdmin → Exportar → Quick → Go.</div>
            </div>

            <?php if ($columnaExiste): ?>
            <div class="alert alert-success py-2 mb-0">
                La columna <code>CORREO</code> ya existe en <code>ADM_USUARIO</code>. No hay nada que migrar —
                puedes ir directo a <a href="PNC_UsuarioCrear.php">Crear Usuario</a>.
            </div>
            <?php else: ?>
            <div class="alert alert-info py-2">
                Se agregará la columna <code>CORREO</code> (VARCHAR(150)) a <code>ADM_USUARIO</code> para
                guardar el correo del usuario y enviarle el correo de bienvenida con el enlace de la aplicación.
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

            <a href="PNC_UsuarioCrear.php" class="btn btn-primary mt-2">
                <i class="bi bi-person-plus me-1"></i> Ir a Crear Usuario
            </a>

            <?php endif; ?>

        </div>
    </div>
</div>
</body>
</html>
