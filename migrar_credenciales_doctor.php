<?php
/**
 * migrar_credenciales_doctor.php — Agrega columnas NPI y LICENSE_ID a ADM_USUARIO
 *
 * Guarda las credenciales profesionales (NPI y número de licencia) de cada
 * doctor para que aparezcan en la firma de los informes de atención.
 *
 * También precarga los valores conocidos para Silvia Ross.
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

function columnaExiste($conexion, $dbName, $col) {
    return (int)$conexion->query(
        "SELECT COUNT(*) c FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA='$dbName' AND TABLE_NAME='ADM_USUARIO' AND COLUMN_NAME='$col'"
    )->fetch_assoc()['c'] > 0;
}

$existeNpi     = columnaExiste($conexion, $dbName, 'NPI');
$existeLicense = columnaExiste($conexion, $dbName, 'LICENSE_ID');

$ejecutar = isset($_POST['ejecutar']) && $_POST['ejecutar'] === '1';
$log = [];
$errores = 0;

if ($ejecutar) {
    if (!$existeNpi) {
        if ($conexion->query("ALTER TABLE ADM_USUARIO ADD COLUMN NPI VARCHAR(20) NULL AFTER CONTRASENA")) {
            $log[] = ['ok', "✅ Columna NPI agregada a ADM_USUARIO."];
            $existeNpi = true;
        } else {
            $log[] = ['err', "Error al agregar NPI: " . $conexion->error];
            $errores++;
        }
    } else {
        $log[] = ['ok', "La columna NPI ya existía — no se hizo ningún cambio."];
    }

    if (!$existeLicense) {
        if ($conexion->query("ALTER TABLE ADM_USUARIO ADD COLUMN LICENSE_ID VARCHAR(60) NULL AFTER NPI")) {
            $log[] = ['ok', "✅ Columna LICENSE_ID agregada a ADM_USUARIO."];
            $existeLicense = true;
        } else {
            $log[] = ['err', "Error al agregar LICENSE_ID: " . $conexion->error];
            $errores++;
        }
    } else {
        $log[] = ['ok', "La columna LICENSE_ID ya existía — no se hizo ningún cambio."];
    }

    // Precarga: Silvia Ross
    if ($existeNpi && $existeLicense) {
        $stmt = $conexion->prepare(
            "UPDATE ADM_USUARIO
             SET NPI = ?, LICENSE_ID = ?
             WHERE (USUARIO = 'silvia.ross'
                    OR (LOWER(NOMBRES) LIKE '%silvia%' AND LOWER(APELLIDOS) LIKE '%ross%'))
               AND ESTADO = 'A'"
        );
        $npi = '1114420973';
        $lic = '008982-1ok';
        $stmt->bind_param('ss', $npi, $lic);
        if ($stmt->execute()) {
            $afectados = $stmt->affected_rows;
            $log[] = ['ok', "✅ Credenciales de Silvia Ross precargadas (usuarios actualizados: $afectados)."];
        } else {
            $log[] = ['err', "Error al precargar Silvia Ross: " . $stmt->error];
            $errores++;
        }
        $stmt->close();
    }

    $log[] = $errores === 0
        ? ['ok', "✅ Migración completada."]
        : ['err', "⚠️ Migración con $errores error(es)."];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Migración: Credenciales del doctor (NPI, License)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>pre{background:#1e1e1e;color:#d4d4d4;padding:1rem;border-radius:6px;font-size:.8rem;}.log-ok{color:#4caf50;}.log-err{color:#f44336;}</style>
</head>
<body class="bg-light">
<div class="container py-4" style="max-width:760px;">
    <div class="card shadow">
        <div class="card-header text-white" style="background:#1a3a5c;">
            <h5 class="mb-0"><i class="bi bi-patch-check me-2"></i>Migración: Credenciales del doctor</h5>
        </div>
        <div class="card-body">
            <?php if (!$ejecutar): ?>
            <div class="alert alert-warning d-flex gap-2">
                <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0 mt-1"></i>
                <div><strong>Haz un backup antes de continuar.</strong> En phpMyAdmin → Exportar → Quick → Go.</div>
            </div>
            <div class="alert alert-info py-2">
                Esta migración:
                <ul class="mb-0">
                    <li>Agrega la columna <code>NPI</code> (VARCHAR(20)) a <code>ADM_USUARIO</code> si no existe.</li>
                    <li>Agrega la columna <code>LICENSE_ID</code> (VARCHAR(60)) a <code>ADM_USUARIO</code> si no existe.</li>
                    <li>Precarga las credenciales de <strong>Silvia Ross</strong> (NPI <code>1114420973</code>, License <code>008982-1ok</code>).</li>
                </ul>
            </div>
            <?php if ($existeNpi && $existeLicense): ?>
            <div class="alert alert-success py-2">Las columnas ya existen. Al ejecutar solo se refrescarán las credenciales de Silvia Ross.</div>
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
            <a href="PNC_UsuarioCrear.php" class="btn btn-primary mt-2"><i class="bi bi-people me-1"></i> Ir a Usuarios</a>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
