<?php
ob_start();
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

if (!isset($_SESSION["rol"], $_SESSION["iduser"])) {
    header("Location: break.php");
    exit();
}
if (isset($_SESSION['expire']) && time() > $_SESSION['expire']) {
    session_destroy();
    header("Location: expirada.php");
    exit();
}

$idUsuario = (int)$_SESSION['iduser'];

// ¿Existe la columna CORREO?
$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$tieneCorreo = (int)$conexion->query(
    "SELECT COUNT(*) c FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA='$dbName' AND TABLE_NAME='ADM_USUARIO' AND COLUMN_NAME='CORREO'"
)->fetch_assoc()['c'] > 0;

$cols = "A.NOMBRES, A.APELLIDOS, A.TELEFONO, A.USUARIO, B.CARGO" . ($tieneCorreo ? ", A.CORREO" : "");
$stmt = $conexion->prepare(
    "SELECT $cols FROM ADM_USUARIO A
     INNER JOIN ADM_ROL B ON A.IDADM_ROL = B.IDADM_ROL
     WHERE A.IDADM_USUARIO = ? LIMIT 1"
);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$u) { $u = []; }
$nombreCompleto = trim(($u['NOMBRES'] ?? '') . ' ' . ($u['APELLIDOS'] ?? ''));
$iniciales = strtoupper(substr($u['NOMBRES'] ?? '', 0, 1) . substr($u['APELLIDOS'] ?? '', 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi Perfil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./main.css" rel="stylesheet">
    <script src="js/jquery.min.js"></script>
    <style>
        .avatar-lg {
            width: 72px; height: 72px; border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff; font-weight: 700; font-size: 1.6rem;
            display: flex; align-items: center; justify-content: center;
        }
        .info-label { font-size:.72rem; text-transform:uppercase; color:#888; font-weight:600; }
    </style>
</head>
<body>
<div class="app-container app-theme-white body-tabs-shadow fixed-sidebar fixed-header">

    <div class="app-header header-shadow">
        <div class="app-header__logo">
            <div class="logo-src"></div>
            <div class="header__pane ml-auto">
                <button type="button" class="hamburger close-sidebar-btn hamburger--elastic" data-class="closed-sidebar">
                    <span class="hamburger-box"><span class="hamburger-inner"></span></span>
                </button>
            </div>
        </div>
        <div class="app-header__mobile-menu">
            <button type="button" class="hamburger hamburger--elastic mobile-toggle-nav">
                <span class="hamburger-box"><span class="hamburger-inner"></span></span>
            </button>
        </div>
        <div class="app-header__content">
            <div class="app-header-left"></div>
            <div class="app-header-right">
                <div class="header-btn-lg pr-0">
                    <div class="widget-content p-0">
                        <div class="widget-content-wrapper">
                            <div class="widget-content-left ml-3 header-user-info">
                                <div class="widget-heading"><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></div>
                                <div class="widget-subheading"><?php echo htmlspecialchars($_SESSION['rol'] ?? ''); ?></div>
                            </div>
                            <div class="widget-content-left ms-3">
                                <div class="btn-group">
                                    <a data-toggle="dropdown" class="p-0 btn" href="#">
                                        <i class="fa fa-angle-down ml-2 opacity-8"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a href="salir.php" class="dropdown-item">Cerrar Sesión</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-main">
        <div class="app-sidebar sidebar-shadow">
            <?php include("./menu/menu_adm.php"); ?>
        </div>

        <div class="app-main__outer">
            <div class="app-main__inner">

                <div class="app-page-title mb-3">
                    <div class="page-title-wrapper">
                        <div class="page-title-heading">
                            <div class="page-title-icon">
                                <i class="pe-7s-user icon-gradient bg-plum-plate"></i>
                            </div>
                            <div>
                                Mi Perfil
                                <div class="page-title-subheading">Consulta tus datos y cambia tu contraseña</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Datos del usuario -->
                    <div class="col-md-5 mb-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="avatar-lg"><?php echo htmlspecialchars($iniciales); ?></div>
                                    <div>
                                        <div class="fw-bold" style="font-size:1.1rem;"><?php echo htmlspecialchars($nombreCompleto); ?></div>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($u['CARGO'] ?? ($_SESSION['rol'] ?? '')); ?></span>
                                    </div>
                                </div>
                                <hr>
                                <div class="mb-2">
                                    <div class="info-label">Usuario</div>
                                    <div><?php echo htmlspecialchars($u['USUARIO'] ?? ($_SESSION['username'] ?? '')); ?></div>
                                </div>
                                <div class="mb-2">
                                    <div class="info-label">Teléfono</div>
                                    <div><?php echo htmlspecialchars($u['TELEFONO'] ?? '—') ?: '—'; ?></div>
                                </div>
                                <div class="mb-0">
                                    <div class="info-label">Correo</div>
                                    <div><?php echo htmlspecialchars(($tieneCorreo ? ($u['CORREO'] ?? '') : '') ?: '—'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cambiar contraseña -->
                    <div class="col-md-7 mb-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-header py-2">
                                <i class="bi bi-shield-lock me-1"></i> Cambiar contraseña
                            </div>
                            <div class="card-body">
                                <form id="formClave" onsubmit="return false;">
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Contraseña actual</label>
                                        <input type="password" id="claveActual" name="claveActual" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Nueva contraseña</label>
                                        <input type="password" id="claveNueva" name="claveNueva" class="form-control" minlength="6" required>
                                        <small class="text-muted">Mínimo 6 caracteres.</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Confirmar nueva contraseña</label>
                                        <input type="password" id="claveConfirmar" class="form-control" minlength="6" required>
                                    </div>
                                    <button type="button" class="btn btn-primary" id="btnGuardarClave" onclick="guardarClave()">
                                        <i class="bi bi-check-lg"></i> Guardar nueva contraseña
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script type="text/javascript" src="./assets/scripts/main.js"></script>
<script>
function guardarClave() {
    const actual     = document.getElementById('claveActual').value;
    const nueva       = document.getElementById('claveNueva').value;
    const confirmar  = document.getElementById('claveConfirmar').value;

    if (!actual || !nueva || !confirmar) {
        alert('Complete todos los campos.');
        return;
    }
    if (nueva.length < 6) {
        alert('La nueva contraseña debe tener al menos 6 caracteres.');
        return;
    }
    if (nueva !== confirmar) {
        alert('La nueva contraseña y su confirmación no coinciden.');
        return;
    }

    const btn = document.getElementById('btnGuardarClave');
    btn.disabled = true;

    $.post('cambiar_clave_perfil.php', { claveActual: actual, claveNueva: nueva }, function(res) {
        res = res.trim();
        if (res === 'OK') {
            alert('Contraseña actualizada correctamente.');
            document.getElementById('formClave').reset();
        } else if (res === 'CLAVE_ACTUAL_INCORRECTA') {
            alert('La contraseña actual es incorrecta.');
        } else if (res === 'CLAVE_CORTA') {
            alert('La nueva contraseña debe tener al menos 6 caracteres.');
        } else if (res === 'SIN_SESION') {
            alert('Tu sesión expiró. Vuelve a iniciar sesión.');
            window.location.href = 'index.php';
        } else {
            alert('Error al actualizar: ' + res);
        }
        btn.disabled = false;
    }).fail(function() {
        alert('Error de conexión. Intente de nuevo.');
        btn.disabled = false;
    });
}
</script>
</body>
</html>
