<?php
ob_start();
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

if (!isset($_SESSION["rol"])) {
    header("Location: break.php");
    exit();
}
if (isset($_SESSION['expire']) && time() > $_SESSION['expire']) {
    session_destroy();
    header("Location: expirada.php");
    exit();
}
if ($_SESSION['rol'] !== 'SISTEMA') {
    die('<p style="color:red;font-family:sans-serif;padding:2rem;">Acceso restringido — solo SISTEMA.</p>');
}

$mensaje = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar') {
        $id        = (int)($_POST['id'] ?? 0);
        $titulo    = trim($_POST['titulo'] ?? '');
        $contenido = $_POST['contenido'] ?? '';

        if ($titulo === '') {
            $mensaje = ['err', 'El título es obligatorio.'];
        } elseif ($id > 0) {
            $stmt = $conexion->prepare("UPDATE documentos SET titulo = ?, contenido = ? WHERE id_documento = ?");
            $stmt->bind_param("ssi", $titulo, $contenido, $id);
            $stmt->execute();
            $stmt->close();
            $mensaje = ['ok', 'Documento actualizado.'];
        } else {
            $stmt = $conexion->prepare("INSERT INTO documentos (titulo, contenido, estado) VALUES (?, ?, 1)");
            $stmt->bind_param("ss", $titulo, $contenido);
            $stmt->execute();
            $stmt->close();
            $mensaje = ['ok', 'Documento creado.'];
        }
    } elseif ($accion === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $conexion->query("UPDATE documentos SET estado = IF(estado = 1, 0, 1) WHERE id_documento = $id");
        $mensaje = ['ok', 'Estado actualizado.'];
    }
}

$documentos = [];
$res = $conexion->query("SELECT id_documento, titulo, contenido, estado FROM documentos ORDER BY titulo");
while ($r = $res->fetch_assoc()) { $documentos[] = $r; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Documentos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./main.css" rel="stylesheet">
    <script src="js/jquery.min.js"></script>
    <style>
        #dContenido { font-family: ui-monospace, Menlo, Consolas, monospace; font-size: .85rem; }
        .doc-preview { border:1px solid #e3e6ef; border-radius:6px; padding:12px; background:#fff; min-height:120px; max-height:300px; overflow:auto; }
    </style>
</head>
<body>
<div class="app-container app-theme-white body-tabs-shadow fixed-sidebar fixed-header">

    <!-- HEADER -->
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
        <!-- SIDEBAR -->
        <div class="app-sidebar sidebar-shadow">
            <?php include("./menu/menu_adm.php"); ?>
        </div>

        <div class="app-main__outer">
            <div class="app-main__inner">

                <!-- TÍTULO -->
                <div class="app-page-title mb-3">
                    <div class="page-title-wrapper">
                        <div class="page-title-heading">
                            <div class="page-title-icon">
                                <i class="pe-7s-note2 icon-gradient bg-plum-plate"></i>
                            </div>
                            <div>
                                Documentos
                                <div class="page-title-subheading">
                                    Plantillas de documentos y políticas que se envían a los pacientes para firmar
                                </div>
                            </div>
                        </div>
                        <div class="page-title-actions">
                            <button type="button" class="btn btn-primary btn-sm" onclick="nuevoDoc()">
                                <i class="bi bi-plus-circle me-1"></i> Nuevo Documento
                            </button>
                        </div>
                    </div>
                </div>

                <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $mensaje[0] === 'ok' ? 'success' : 'danger'; ?> py-2">
                    <?php echo htmlspecialchars($mensaje[1]); ?>
                </div>
                <?php endif; ?>

                <!-- TABLA -->
                <div class="card shadow-sm">
                    <div class="card-header py-2">
                        <span style="font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;color:#6c757d;font-weight:600;">
                            <i class="bi bi-file-earmark-text me-1"></i> <?php echo count($documentos); ?> documento(s)
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Título</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($documentos)): ?>
                                    <tr><td colspan="3" class="text-center text-muted py-4">No hay documentos registrados.</td></tr>
                                <?php else: foreach ($documentos as $d): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($d['titulo']); ?></td>
                                        <td class="text-center">
                                            <?php if ((int)$d['estado'] === 1): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-outline-warning btn-sm"
                                                onclick='editarDoc(<?php echo json_encode($d, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP); ?>)'>
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('¿Cambiar el estado de este documento?');">
                                                <input type="hidden" name="accion" value="toggle">
                                                <input type="hidden" name="id" value="<?php echo (int)$d['id_documento']; ?>">
                                                <button type="submit" class="btn btn-outline-secondary btn-sm">
                                                    <i class="bi bi-<?php echo (int)$d['estado'] === 1 ? 'slash-circle' : 'check-circle'; ?>"></i>
                                                </button>
                                            </form>
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

<!-- MODAL CREAR/EDITAR -->
<div class="modal fade" id="modalDoc" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDocTitulo">Nuevo Documento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="guardar">
                    <input type="hidden" name="id" id="dId" value="0">

                    <div class="mb-3">
                        <label class="form-label">Título</label>
                        <input type="text" class="form-control" name="titulo" id="dTitulo" maxlength="180" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Contenido</label>
                        <textarea class="form-control" name="contenido" id="dContenido" rows="10"
                                  oninput="actualizarPreview()"></textarea>
                        <div class="form-text">Puedes usar HTML para dar formato (negritas, listas, etc.). Lo que escribas se mostrará al paciente.</div>
                    </div>

                    <label class="form-label">Vista previa</label>
                    <div class="doc-preview" id="dPreview"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script type="text/javascript" src="./assets/scripts/main.js"></script>
<script>
const modalDoc = new bootstrap.Modal(document.getElementById('modalDoc'));

function actualizarPreview() {
    document.getElementById('dPreview').innerHTML = document.getElementById('dContenido').value;
}

function nuevoDoc() {
    document.getElementById('modalDocTitulo').textContent = 'Nuevo Documento';
    document.getElementById('dId').value = '0';
    document.getElementById('dTitulo').value = '';
    document.getElementById('dContenido').value = '';
    actualizarPreview();
    modalDoc.show();
}

function editarDoc(d) {
    document.getElementById('modalDocTitulo').textContent = 'Editar Documento';
    document.getElementById('dId').value = d.id_documento;
    document.getElementById('dTitulo').value = d.titulo || '';
    document.getElementById('dContenido').value = d.contenido || '';
    actualizarPreview();
    modalDoc.show();
}
</script>
</body>
</html>
