<?php
ob_start();
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

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

        // PDF adjunto (opcional)
        $pdfPath = null; $tienePdf = false; $errPdf = '';
        if (!empty($_FILES['archivo_pdf']['name']) && ($_FILES['archivo_pdf']['error'] ?? 1) === UPLOAD_ERR_OK) {
            $f   = $_FILES['archivo_pdf'];
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if ($ext !== 'pdf') {
                $errPdf = 'El archivo adjunto debe ser PDF.';
            } elseif ($f['size'] > 10 * 1024 * 1024) {
                $errPdf = 'El PDF supera el límite de 10MB.';
            } else {
                $dir = __DIR__ . '/uploads/documentos';
                if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
                $nombre = 'doc_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $f['name']);
                if (move_uploaded_file($f['tmp_name'], $dir . '/' . $nombre)) {
                    $pdfPath = 'uploads/documentos/' . $nombre; $tienePdf = true;
                } else {
                    $errPdf = 'No se pudo guardar el PDF.';
                }
            }
        }

        if ($titulo === '') {
            $mensaje = ['err', 'El título es obligatorio.'];
        } elseif ($errPdf !== '') {
            $mensaje = ['err', $errPdf];
        } elseif ($id > 0) {
            if ($tienePdf) {
                $stmt = $conexion->prepare("UPDATE documentos SET titulo = ?, contenido = ?, archivo_pdf = ? WHERE id_documento = ?");
                $stmt->bind_param("sssi", $titulo, $contenido, $pdfPath, $id);
            } else {
                $stmt = $conexion->prepare("UPDATE documentos SET titulo = ?, contenido = ? WHERE id_documento = ?");
                $stmt->bind_param("ssi", $titulo, $contenido, $id);
            }
            $stmt->execute();
            $stmt->close();
            $mensaje = ['ok', 'Documento actualizado.'];
        } else {
            $stmt = $conexion->prepare("INSERT INTO documentos (titulo, contenido, archivo_pdf, estado) VALUES (?, ?, ?, 1)");
            $stmt->bind_param("sss", $titulo, $contenido, $pdfPath);
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
$res = $conexion->query("SELECT id_documento, titulo, contenido, archivo_pdf, estado FROM documentos ORDER BY titulo");
while ($r = $res->fetch_assoc()) { $documentos[] = $r; }

// Pacientes activos con correo (para enviar documentos)
$pacientesEnvio = [];
$resP = $conexion->query("SELECT IDPACIENTE, NOMBRES, APELLIDOS, EMAIL FROM AG_PACIENTE WHERE ESTADO = 'A' AND EMAIL IS NOT NULL AND EMAIL <> '' ORDER BY NOMBRES, APELLIDOS");
while ($resP && $p = $resP->fetch_assoc()) { $pacientesEnvio[] = $p; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Documentos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
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
                                            <button type="button" class="btn btn-outline-primary btn-sm"
                                                onclick='abrirEnviar(<?php echo (int)$d['id_documento']; ?>, <?php echo json_encode($d['titulo'], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                                <?php echo (int)$d['estado'] !== 1 ? 'disabled' : ''; ?> title="Enviar a paciente">
                                                <i class="bi bi-send"></i>
                                            </button>
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
            <form method="POST" enctype="multipart/form-data" onsubmit="return sincronizarContenido();">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="guardar">
                    <input type="hidden" name="id" id="dId" value="0">

                    <div class="mb-3">
                        <label class="form-label">Título</label>
                        <input type="text" class="form-control" name="titulo" id="dTitulo" maxlength="180" required>
                    </div>

                    <label class="form-label">Contenido</label>
                    <div class="btn-toolbar gap-1 mb-1" role="toolbar">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" onmousedown="event.preventDefault()" onclick="fmt('bold')" title="Negrita"><b>B</b></button>
                            <button type="button" class="btn btn-outline-secondary" onmousedown="event.preventDefault()" onclick="fmt('italic')" title="Cursiva"><i>I</i></button>
                            <button type="button" class="btn btn-outline-secondary" onmousedown="event.preventDefault()" onclick="fmt('underline')" title="Subrayado"><u>U</u></button>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" onmousedown="event.preventDefault()" onclick="fmtBlock('H2')" title="Título">Título</button>
                            <button type="button" class="btn btn-outline-secondary" onmousedown="event.preventDefault()" onclick="fmtBlock('H3')" title="Subtítulo">Subtítulo</button>
                            <button type="button" class="btn btn-outline-secondary" onmousedown="event.preventDefault()" onclick="fmtBlock('P')" title="Texto normal">Normal</button>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" onmousedown="event.preventDefault()" onclick="fmt('insertUnorderedList')" title="Lista">&bull; Lista</button>
                            <button type="button" class="btn btn-outline-secondary" onmousedown="event.preventDefault()" onclick="fmt('insertOrderedList')" title="Lista numerada">1. Lista</button>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" onmousedown="event.preventDefault()" title="Insertar dato del paciente">Insertar dato</button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="insertarCampo('{{paciente}}');return false;">Nombre del paciente</a></li>
                                <li><a class="dropdown-item" href="#" onclick="insertarCampo('{{cedula}}');return false;">Cédula / ID</a></li>
                                <li><a class="dropdown-item" href="#" onclick="insertarCampo('{{fecha_nacimiento}}');return false;">Fecha de nacimiento</a></li>
                                <li><a class="dropdown-item" href="#" onclick="insertarCampo('{{fecha}}');return false;">Fecha (del documento)</a></li>
                            </ul>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" onmousedown="event.preventDefault()" onclick="insertarTabla()" title="Insertar tabla">&#8862; Tabla</button>
                            <button type="button" class="btn btn-outline-secondary" id="btnHtml" onmousedown="event.preventDefault()" onclick="toggleHtml()" title="Editar el HTML (para pegar tablas o contenido avanzado)">&lt;/&gt; HTML</button>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-warning" onmousedown="event.preventDefault()" onclick="insertarCampoLlenar('texto')" title="Campo en blanco que el paciente completará al firmar">&#9998; Campo</button>
                            <button type="button" class="btn btn-outline-warning" onmousedown="event.preventDefault()" onclick="insertarCampoLlenar('checkbox')" title="Casilla que el paciente marcará al firmar">&#9745; Casilla</button>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onmousedown="event.preventDefault()" onclick="fmt('removeFormat')" title="Quitar formato">Limpiar</button>
                    </div>
                    <div id="dEditor" contenteditable="true" class="form-control" style="min-height:240px;max-height:50vh;overflow:auto;"></div>
                    <textarea id="dHtml" class="form-control" style="display:none;min-height:240px;max-height:50vh;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:.85rem;"></textarea>
                    <input type="hidden" name="contenido" id="dContenido">
                    <div class="form-text">Da formato con los botones. <strong>"Insertar dato"</strong> agrega campos que se completan solos. <strong>"Campo"/"Casilla"</strong> agregan espacios en blanco que el <u>paciente</u> llena al firmar (ej. número de tarjeta, nombre del titular). <strong>"HTML"</strong> te deja pegar tablas/contenido avanzado.</div>

                    <div class="mt-3">
                        <label class="form-label">Adjuntar PDF (opcional)</label>
                        <input type="file" name="archivo_pdf" id="dPdf" accept="application/pdf" class="form-control form-control-sm">
                        <div class="form-text" id="dPdfActual"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL ENVIAR A PACIENTE -->
<div class="modal fade" id="modalEnviar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enviar documento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="envDocId">
                <p class="mb-2">Documento: <strong id="envDocTitulo"></strong></p>
                <div class="mb-2">
                    <label class="form-label">Paciente (con correo registrado)</label>
                    <select class="form-select" id="envPaciente">
                        <option value="">— Seleccione —</option>
                        <?php foreach ($pacientesEnvio as $p): ?>
                            <option value="<?php echo (int)$p['IDPACIENTE']; ?>">
                                <?php echo htmlspecialchars(trim($p['NOMBRES'].' '.$p['APELLIDOS'])); ?> — <?php echo htmlspecialchars($p['EMAIL']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Se enviará un correo con un link y un código de acceso.</div>
                </div>
                <div id="envResultado" class="mt-2"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="envBtn" onclick="enviarDocumento()">
                    <i class="bi bi-send"></i> Enviar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script type="text/javascript" src="./assets/scripts/main.js"></script>
<script>
const modalDoc = new bootstrap.Modal(document.getElementById('modalDoc'));
const modalEnviar = new bootstrap.Modal(document.getElementById('modalEnviar'));

// Buscador de pacientes (Select2)
$(function () {
    $('#envPaciente').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#modalEnviar'),
        width: '100%',
        placeholder: 'Escribe el nombre del paciente…'
    });
});

function abrirEnviar(idDoc, titulo) {
    document.getElementById('envDocId').value = idDoc;
    document.getElementById('envDocTitulo').textContent = titulo;
    $('#envPaciente').val(null).trigger('change');
    document.getElementById('envResultado').innerHTML = '';
    modalEnviar.show();
}

function enviarDocumento() {
    const idDoc = document.getElementById('envDocId').value;
    const idPac = document.getElementById('envPaciente').value;
    const res   = document.getElementById('envResultado');
    const btn   = document.getElementById('envBtn');
    if (!idPac) { alert('Seleccione un paciente.'); return; }
    btn.disabled = true;
    res.innerHTML = '<div class="text-muted small"><span class="spinner-border spinner-border-sm me-1"></span> Enviando…</div>';
    fetch('enviar_documento_guardar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ id_documento: idDoc, id_paciente: idPac })
    })
    .then(r => r.json())
    .then(d => {
        btn.disabled = false;
        if (d.ok) {
            let html = '<div class="alert alert-' + (d.correo_fallo ? 'warning' : 'success') + ' py-2 small mb-0">' + d.msg + '</div>';
            if (d.link) {
                html += '<div class="mt-2 small border rounded p-2">'
                      + '<strong>Link:</strong> <a href="' + d.link + '" target="_blank">' + d.link + '</a><br>'
                      + '<strong>Código:</strong> <span style="font-size:1.1rem;letter-spacing:2px;">' + d.codigo + '</span></div>';
            }
            res.innerHTML = html;
        } else {
            res.innerHTML = '<div class="alert alert-danger py-2 small mb-0">' + (d.msg || 'Error al enviar.') + '</div>';
        }
    })
    .catch(() => { btn.disabled = false; res.innerHTML = '<div class="alert alert-danger py-2 small mb-0">Error de conexión.</div>'; });
}

// ── Editor con formato ───────────────────────────────────────────────
var modoHtml = false;

function fmt(cmd) {
    document.execCommand(cmd, false, null);
    document.getElementById('dEditor').focus();
}
function fmtBlock(tag) {
    document.execCommand('formatBlock', false, tag);
    document.getElementById('dEditor').focus();
}
function insertarCampo(texto) {
    document.getElementById('dEditor').focus();
    document.execCommand('insertText', false, texto);
}
function insertarTabla() {
    var filas = parseInt(prompt('¿Cuántas filas?', '3'), 10) || 0;
    var cols  = parseInt(prompt('¿Cuántas columnas?', '2'), 10) || 0;
    if (filas < 1 || cols < 1) return;
    var html = '<table border="1" cellpadding="6" style="border-collapse:collapse;width:100%;">';
    for (var i = 0; i < filas; i++) { html += '<tr>'; for (var j = 0; j < cols; j++) html += '<td>&nbsp;</td>'; html += '</tr>'; }
    html += '</table><p>&nbsp;</p>';
    document.getElementById('dEditor').focus();
    document.execCommand('insertHTML', false, html);
}
function insertarCampoLlenar(tipo) {
    document.getElementById('dEditor').focus();
    var html;
    if (tipo === 'checkbox') {
        html = '<input type="checkbox" class="campo-llenar" style="width:16px;height:16px;vertical-align:middle;margin:0 4px;">';
    } else {
        var ancho = parseInt(prompt('Ancho aproximado del campo (en caracteres)', '20'), 10) || 20;
        var px = Math.max(60, ancho * 8);
        html = '<input type="text" class="campo-llenar" style="border:none;border-bottom:1px solid #333;background:#fff8e1;padding:1px 4px;min-width:' + px + 'px;">';
    }
    document.execCommand('insertHTML', false, html);
}
function toggleHtml() {
    var ed = document.getElementById('dEditor'), ta = document.getElementById('dHtml');
    if (!modoHtml) {                       // visual -> HTML
        ta.value = ed.innerHTML;
        ed.style.display = 'none'; ta.style.display = 'block'; modoHtml = true;
        document.getElementById('btnHtml').classList.add('active');
    } else {                               // HTML -> visual
        ed.innerHTML = ta.value;
        ta.style.display = 'none'; ed.style.display = 'block'; modoHtml = false;
        document.getElementById('btnHtml').classList.remove('active');
    }
}
function sincronizarContenido() {
    var html = modoHtml ? document.getElementById('dHtml').value : document.getElementById('dEditor').innerHTML;
    document.getElementById('dContenido').value = html;
    return true;
}
function resetEditor(html) {
    modoHtml = false;
    document.getElementById('dHtml').style.display = 'none';
    document.getElementById('btnHtml').classList.remove('active');
    var ed = document.getElementById('dEditor');
    ed.style.display = 'block';
    ed.innerHTML = html || '';
}

function nuevoDoc() {
    document.getElementById('modalDocTitulo').textContent = 'Nuevo Documento';
    document.getElementById('dId').value = '0';
    document.getElementById('dTitulo').value = '';
    resetEditor('');
    document.getElementById('dPdf').value = '';
    document.getElementById('dPdfActual').innerHTML = '';
    modalDoc.show();
}

function editarDoc(d) {
    document.getElementById('modalDocTitulo').textContent = 'Editar Documento';
    document.getElementById('dId').value = d.id_documento;
    document.getElementById('dTitulo').value = d.titulo || '';
    resetEditor(d.contenido || '');
    document.getElementById('dPdf').value = '';
    document.getElementById('dPdfActual').innerHTML = d.archivo_pdf
        ? 'PDF actual: <a href="' + d.archivo_pdf + '" target="_blank">ver</a> — sube otro para reemplazarlo.'
        : '';
    modalDoc.show();
}
</script>
</body>
</html>
