<?php
ob_start();
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/lang/i18n.php");
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
    die('<p style="color:red;font-family:sans-serif;padding:2rem;">'.htmlspecialchars(t('common.accessRestricted')).'</p>');
}

$mensaje = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar') {
        $id        = (int)($_POST['id'] ?? 0);
        $empresa   = trim($_POST['empresa']   ?? '');
        $telefono  = trim($_POST['telefono']  ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $notas     = trim($_POST['notas']     ?? '');
        $idTipo    = ($_POST['id_tipo_seguro'] ?? '') === '' ? null : (int)$_POST['id_tipo_seguro'];
        $idAgencia = ($_POST['id_agencia']     ?? '') === '' ? null : (int)$_POST['id_agencia'];

        if ($empresa === '') {
            $mensaje = ['err', t('seg.msg.companyRequired')];
        } elseif ($id > 0) {
            $stmt = $conexion->prepare(
                "UPDATE seguros
                    SET Empresa_seguro = ?, telefono = ?, direccion = ?,
                        id_tipo_seguro = ?, Notas_Seguro = ?, Id_agencia = ?
                  WHERE Id_seguro = ?"
            );
            $stmt->bind_param("sssisii", $empresa, $telefono, $direccion, $idTipo, $notas, $idAgencia, $id);
            $stmt->execute();
            $stmt->close();
            $mensaje = ['ok', t('seg.msg.updated')];
        } else {
            $stmt = $conexion->prepare(
                "INSERT INTO seguros
                    (Empresa_seguro, telefono, direccion, id_tipo_seguro, Notas_Seguro, Id_agencia, estado)
                 VALUES (?, ?, ?, ?, ?, ?, 1)"
            );
            $stmt->bind_param("sssisi", $empresa, $telefono, $direccion, $idTipo, $notas, $idAgencia);
            $stmt->execute();
            $stmt->close();
            $mensaje = ['ok', t('seg.msg.created')];
        }
    } elseif ($accion === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $conexion->query("UPDATE seguros SET estado = IF(estado = 1, 0, 1) WHERE Id_seguro = $id");
        $mensaje = ['ok', t('common.statusUpdated')];
    }
}

// Agencias activas para el selector
$agencias = [];
$resAg = $conexion->query("SELECT IDAGENCIA, DESCRIPCION FROM ADM_AGENCIA WHERE ESTADO = 1 ORDER BY DESCRIPCION");
while ($a = $resAg->fetch_assoc()) { $agencias[] = $a; }

// Tipos de seguro para el selector
$tiposSeguro = [];
$resTS = $conexion->query("SELECT id_tipo_seguro, Descripcion FROM tipo_seguro ORDER BY Descripcion");
while ($ts = $resTS->fetch_assoc()) { $tiposSeguro[] = $ts; }

// Listado de seguros
$seguros = [];
$res = $conexion->query(
    "SELECT S.Id_seguro, S.Empresa_seguro, S.telefono, S.direccion,
            S.id_tipo_seguro, S.Notas_Seguro, S.Id_agencia, S.estado,
            A.DESCRIPCION AS AGENCIA,
            T.Descripcion AS TIPO_DESC
       FROM seguros S
       LEFT JOIN ADM_AGENCIA A ON A.IDAGENCIA      = S.Id_agencia
       LEFT JOIN tipo_seguro  T ON T.id_tipo_seguro = S.id_tipo_seguro
   ORDER BY S.Empresa_seguro"
);
while ($r = $res->fetch_assoc()) { $seguros[] = $r; }
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php te('menu.insurance'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./main.css" rel="stylesheet">
    <script src="js/jquery.min.js"></script>
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
                                        <a href="salir.php" class="dropdown-item"><?php te('menu.logout'); ?></a>
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
                                <i class="pe-7s-shield icon-gradient bg-plum-plate"></i>
                            </div>
                            <div>
                                <?php te('menu.insurance'); ?>
                                <div class="page-title-subheading">
                                    <?php te('seg.subtitle'); ?>
                                </div>
                            </div>
                        </div>
                        <div class="page-title-actions">
                            <button type="button" class="btn btn-primary btn-sm" onclick="nuevoSeguro()">
                                <i class="bi bi-plus-circle me-1"></i> <?php te('seg.newBtn'); ?>
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
                            <i class="bi bi-shield-fill-check me-1"></i> <?php echo count($seguros); ?> <?php te('seg.countSuffix'); ?>
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?php te('seg.th.company'); ?></th>
                                        <th><?php te('pf.phone'); ?></th>
                                        <th><?php te('pf.address'); ?></th>
                                        <th><?php te('seg.th.type'); ?></th>
                                        <th><?php te('cal.location'); ?></th>
                                        <th class="text-center"><?php te('common.status'); ?></th>
                                        <th class="text-end"><?php te('common.actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($seguros)): ?>
                                    <tr><td colspan="7" class="text-center text-muted py-4"><?php te('seg.none'); ?></td></tr>
                                <?php else: foreach ($seguros as $s): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($s['Empresa_seguro']); ?></td>
                                        <td><?php echo htmlspecialchars($s['telefono'] ?: '—'); ?></td>
                                        <td><?php echo htmlspecialchars($s['direccion'] ?: '—'); ?></td>
                                        <td><?php echo htmlspecialchars($s['TIPO_DESC'] ?: '—'); ?></td>
                                        <td><?php echo htmlspecialchars($s['AGENCIA'] ?: '—'); ?></td>
                                        <td class="text-center">
                                            <?php if ((int)$s['estado'] === 1): ?>
                                                <span class="badge bg-success"><?php te('common.active'); ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?php te('common.inactive'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-outline-warning btn-sm"
                                                onclick='editarSeguro(<?php echo json_encode($s, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" class="d-inline" onsubmit="return confirm(<?php echo json_encode(t('seg.confirmToggle')); ?>);">
                                                <input type="hidden" name="accion" value="toggle">
                                                <input type="hidden" name="id" value="<?php echo (int)$s['Id_seguro']; ?>">
                                                <button type="submit" class="btn btn-outline-secondary btn-sm">
                                                    <i class="bi bi-<?php echo (int)$s['estado'] === 1 ? 'slash-circle' : 'check-circle'; ?>"></i>
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
<div class="modal fade" id="modalSeguro" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalSeguroTitulo"><?php te('seg.modalNew'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php te('common.close'); ?>"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="guardar">
                    <input type="hidden" name="id" id="fsId" value="0">

                    <div class="mb-3">
                        <label class="form-label"><?php te('seg.company'); ?></label>
                        <input type="text" class="form-control" name="empresa" id="fsEmpresa" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php te('pf.phone'); ?></label>
                            <input type="text" class="form-control" name="telefono" id="fsTelefono" maxlength="30">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php te('seg.typeLabel'); ?></label>
                            <select class="form-select" name="id_tipo_seguro" id="fsTipo">
                                <option value=""><?php te('pcreate.selectDash'); ?></option>
                                <?php foreach ($tiposSeguro as $ts): ?>
                                    <option value="<?php echo (int)$ts['id_tipo_seguro']; ?>">
                                        <?php echo htmlspecialchars($ts['Descripcion']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php te('pf.address'); ?></label>
                        <input type="text" class="form-control" name="direccion" id="fsDireccion" maxlength="255">
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php te('seg.locationAgency'); ?></label>
                        <select class="form-select" name="id_agencia" id="fsAgencia">
                            <option value=""><?php te('pcreate.selectDash'); ?></option>
                            <?php foreach ($agencias as $a): ?>
                                <option value="<?php echo (int)$a['IDAGENCIA']; ?>">
                                    <?php echo htmlspecialchars($a['DESCRIPCION']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php te('pf.notes'); ?></label>
                        <textarea class="form-control" name="notas" id="fsNotas" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php te('common.cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><?php te('common.save'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script type="text/javascript" src="./assets/scripts/main.js"></script>
<script>
const modalSeguro = new bootstrap.Modal(document.getElementById('modalSeguro'));

function nuevoSeguro() {
    document.getElementById('modalSeguroTitulo').textContent = <?php echo json_encode(t('seg.modalNew')); ?>;
    document.getElementById('fsId').value        = '0';
    document.getElementById('fsEmpresa').value   = '';
    document.getElementById('fsTelefono').value  = '';
    document.getElementById('fsTipo').value      = '';
    document.getElementById('fsDireccion').value = '';
    document.getElementById('fsAgencia').value   = '';
    document.getElementById('fsNotas').value     = '';
    modalSeguro.show();
}

function editarSeguro(s) {
    document.getElementById('modalSeguroTitulo').textContent = <?php echo json_encode(t('seg.modalEdit')); ?>;
    document.getElementById('fsId').value        = s.Id_seguro;
    document.getElementById('fsEmpresa').value   = s.Empresa_seguro || '';
    document.getElementById('fsTelefono').value  = s.telefono || '';
    document.getElementById('fsTipo').value      = s.id_tipo_seguro || '';
    document.getElementById('fsDireccion').value = s.direccion || '';
    document.getElementById('fsAgencia').value   = s.Id_agencia || '';
    document.getElementById('fsNotas').value     = s.Notas_Seguro || '';
    modalSeguro.show();
}
</script>
</body>
</html>
