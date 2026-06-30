<?php
ob_start();
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

if (!isset($_SESSION["rol"])) { header("Location: break.php"); exit(); }
if (isset($_SESSION['expire']) && time() > $_SESSION['expire']) { session_destroy(); header("Location: expirada.php"); exit(); }
if ($_SESSION['rol'] !== 'SISTEMA') {
    die('<p style="color:red;font-family:sans-serif;padding:2rem;">Acceso restringido — solo SISTEMA.</p>');
}

$envios = [];
$res = $conexion->query(
    "SELECT e.id_envio, e.estado, e.fecha_envio, e.fecha_firma, e.firmado_por,
            d.titulo,
            CONCAT(p.NOMBRES,' ',p.APELLIDOS) AS paciente
       FROM documento_envio e
       INNER JOIN documentos  d ON d.id_documento = e.id_documento
       INNER JOIN AG_PACIENTE p ON p.IDPACIENTE   = e.IDPACIENTE
   ORDER BY e.fecha_envio DESC"
);
while ($res && $r = $res->fetch_assoc()) { $envios[] = $r; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Documentos Enviados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./main.css" rel="stylesheet">
    <script src="js/jquery.min.js"></script>
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
        <div class="app-header__menu">
            <button type="button" class="btn-icon btn-icon-only btn btn-primary btn-sm mobile-toggle-header-nav">
                <span class="btn-icon-wrapper"><i class="fa fa-ellipsis-v fa-w-6"></i></span>
            </button>
        </div>
        <div class="app-header__content">
            <div class="app-header-left"></div>
            <div class="app-header-right">
                <div class="header-btn-lg pr-0">
                    <div class="widget-content p-0">
                        <div class="widget-content-wrapper">
                            <div class="widget-content-left ml-3 header-user-info">
                                <div class="widget-heading"><?php echo htmlspecialchars($_SESSION['nombres'] ?? ''); ?></div>
                                <div class="widget-subheading"><?php echo htmlspecialchars($_SESSION['rol'] ?? ''); ?></div>
                            </div>
                            <div class="widget-content-left ms-3">
                                <div class="btn-group">
                                    <a data-toggle="dropdown" class="p-0 btn" href="#"><i class="fa fa-angle-down ml-2 opacity-8"></i></a>
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
                                <i class="bi bi-send-check icon-gradient bg-plum-plate"></i>
                            </div>
                            <div>
                                Documentos Enviados
                                <div class="page-title-subheading">Documentos enviados a pacientes y su estado de firma</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header py-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <span style="font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;color:#6c757d;font-weight:600;">
                            <i class="bi bi-send me-1"></i> <span id="contadorEnvios"><?php echo count($envios); ?></span> envío(s)
                        </span>
                        <div style="max-width:300px;width:100%;">
                            <input type="text" id="buscarEnvio" class="form-control form-control-sm"
                                   placeholder="Buscar por paciente, documento o estado...">
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="tablaEnvios">
                                <thead class="table-light">
                                    <tr>
                                        <th>Paciente</th>
                                        <th>Documento</th>
                                        <th class="text-center">Estado</th>
                                        <th>Enviado</th>
                                        <th>Firmado</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($envios)): ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">No hay documentos enviados.</td></tr>
                                <?php else: foreach ($envios as $e): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($e['paciente']); ?></td>
                                        <td><?php echo htmlspecialchars($e['titulo']); ?></td>
                                        <td class="text-center">
                                            <?php if ($e['estado'] === 'Firmado'): ?>
                                                <span class="badge bg-success">Firmado</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Pendiente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small"><?php echo $e['fecha_envio'] ? date('d/m/Y H:i', strtotime($e['fecha_envio'])) : '—'; ?></td>
                                        <td class="small"><?php echo $e['fecha_firma'] ? date('d/m/Y H:i', strtotime($e['fecha_firma'])) : '—'; ?></td>
                                        <td class="text-end">
                                            <a href="ver_documento_firmado.php?id=<?php echo (int)$e['id_envio']; ?>" target="_blank"
                                               class="btn btn-outline-primary btn-sm" title="Ver">
                                                <i class="bi bi-eye"></i> Ver
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
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
(function () {
    var input    = document.getElementById('buscarEnvio');
    var filas    = document.querySelectorAll('#tablaEnvios tbody tr');
    var contador = document.getElementById('contadorEnvios');
    if (!input) return;

    input.addEventListener('input', function () {
        var q = input.value.trim().toLowerCase();
        var visibles = 0;
        filas.forEach(function (fila) {
            var coincide = fila.textContent.toLowerCase().indexOf(q) !== -1;
            fila.style.display = coincide ? '' : 'none';
            if (coincide) visibles++;
        });
        contador.textContent = visibles;
    });
})();
</script>
</body>
</html>
