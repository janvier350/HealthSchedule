<?php
/**
 * auditoria.php — Bitácora de cambios (quién crea/edita/elimina y en qué módulo).
 * Visible para SISTEMA y DOCTOR (Dra. Silvia) por defecto; a otros usuarios se
 * les puede dar acceso con el permiso 'panel.auditoria' desde el panel de usuarios.
 */
ob_start();
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/lang/i18n.php");
require_once(__DIR__ . "/class/permisos.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

if (!isset($_SESSION["rol"])) { header("Location: break.php"); exit(); }
if (isset($_SESSION['expire']) && time() > $_SESSION['expire']) { session_destroy(); header("Location: expirada.php"); exit(); }
requerir('panel.auditoria');

$en = (current_lang() === 'en');

// ¿Existe la tabla? (la crea el helper al primer cambio o migrar_auditoria.php)
$dbEsc  = $conexion->real_escape_string($conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db']);
$existe = (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.TABLES WHERE TABLE_SCHEMA='$dbEsc' AND TABLE_NAME='auditoria'")->fetch_assoc()['c'] > 0;

// Filtros
$fModulo = trim($_GET['modulo']  ?? '');
$fAccion = trim($_GET['accion']  ?? '');
$fUser   = (int)($_GET['usuario'] ?? 0);
$fDesde  = trim($_GET['desde']   ?? '');
$fHasta  = trim($_GET['hasta']   ?? '');
$fQ      = trim($_GET['q']       ?? '');

$modulos = []; $acciones = []; $usuarios = []; $rows = []; $total = 0;
if ($existe) {
    // Opciones de filtro
    if ($r = $conexion->query("SELECT DISTINCT modulo FROM auditoria ORDER BY modulo")) while ($x=$r->fetch_assoc()) $modulos[]=$x['modulo'];
    if ($r = $conexion->query("SELECT DISTINCT accion FROM auditoria ORDER BY accion")) while ($x=$r->fetch_assoc()) $acciones[]=$x['accion'];
    if ($r = $conexion->query("SELECT id_usuario, MAX(nombre) nombre, MAX(usuario) usuario FROM auditoria WHERE id_usuario IS NOT NULL GROUP BY id_usuario ORDER BY nombre")) while ($x=$r->fetch_assoc()) $usuarios[]=$x;

    // Consulta con filtros (prepared)
    $where = "WHERE 1=1"; $tipos=''; $vals=[];
    if ($fModulo !== '') { $where.=" AND modulo=?";     $tipos.='s'; $vals[]=$fModulo; }
    if ($fAccion !== '') { $where.=" AND accion=?";     $tipos.='s'; $vals[]=$fAccion; }
    if ($fUser   >  0)   { $where.=" AND id_usuario=?"; $tipos.='i'; $vals[]=$fUser; }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/',$fDesde)) { $where.=" AND fecha>=?"; $tipos.='s'; $vals[]=$fDesde.' 00:00:00'; }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/',$fHasta)) { $where.=" AND fecha<=?"; $tipos.='s'; $vals[]=$fHasta.' 23:59:59'; }
    if ($fQ !== '') { $where.=" AND (nombre LIKE ? OR usuario LIKE ? OR detalle LIKE ? OR entidad_id LIKE ?)"; $tipos.='ssss'; $like='%'.$fQ.'%'; array_push($vals,$like,$like,$like,$like); }

    $sql = "SELECT fecha, id_usuario, usuario, nombre, rol, modulo, accion, entidad, entidad_id, detalle, ip
            FROM auditoria $where ORDER BY fecha DESC, id DESC LIMIT 500";
    if ($stmt = $conexion->prepare($sql)) {
        if ($tipos !== '') $stmt->bind_param($tipos, ...$vals);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($x = $res->fetch_assoc()) $rows[] = $x;
        $stmt->close();
    }
    $total = (int)$conexion->query("SELECT COUNT(*) c FROM auditoria")->fetch_assoc()['c'];
}

// Etiquetas de acción (bilingüe, con color)
function accionBadge($a, $en) {
    $map = [
        'crear'     => ['success', $en?'Created':'Creó'],
        'editar'    => ['primary', $en?'Edited':'Editó'],
        'eliminar'  => ['danger',  $en?'Deleted':'Eliminó'],
        'cancelar'  => ['warning', $en?'Cancelled':'Canceló'],
        'restaurar' => ['info',    $en?'Restored':'Restauró'],
        'reagendar' => ['secondary',$en?'Rescheduled':'Reagendó'],
        'atender'   => ['success', $en?'Attended':'Atendió'],
        'estado'    => ['secondary',$en?'Status':'Estado'],
    ];
    $m = $map[$a] ?? ['secondary', htmlspecialchars($a)];
    return '<span class="badge bg-'.$m[0].'">'.htmlspecialchars($m[1]).'</span>';
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="images/favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $en?'Change log':'Bitácora de cambios'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./main.css" rel="stylesheet">
    <script src="js/jquery.min.js"></script>
</head>
<body>
<div class="app-container app-theme-white body-tabs-shadow fixed-sidebar fixed-header">
    <div class="app-header header-shadow">
        <div class="app-header__logo"><div class="logo-src"></div>
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
        <div class="app-header__content"><div class="app-header-left"></div>
            <div class="app-header-right"><div class="header-btn-lg pr-0"><div class="widget-content p-0"><div class="widget-content-wrapper">
                <div class="widget-content-left ml-3 header-user-info">
                    <div class="widget-heading"><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></div>
                    <div class="widget-subheading"><?php echo htmlspecialchars($_SESSION['rol'] ?? ''); ?></div>
                </div>
                <div class="widget-content-left ms-3"><a href="salir.php" class="btn btn-sm btn-outline-secondary"><?php te('common.close'); ?></a></div>
            </div></div></div></div>
        </div>
    </div>
    <div class="app-main">
        <div class="app-sidebar sidebar-shadow"><?php include("./menu/menu_adm.php"); ?></div>
        <div class="app-main__outer"><div class="app-main__inner">

            <div class="app-page-title mb-3"><div class="page-title-wrapper"><div class="page-title-heading">
                <div class="page-title-icon"><i class="pe-7s-note2 icon-gradient bg-happy-itmeo"></i></div>
                <div><?php echo $en?'Change log (audit)':'Bitácora de cambios (auditoría)'; ?>
                    <div class="page-title-subheading"><?php echo $en?'Who creates, edits or deletes information and in which module.':'Quién crea, edita o elimina información y en qué módulo.'; ?></div>
                </div>
            </div></div></div>

            <?php if (!$existe): ?>
                <div class="alert alert-info">
                    <?php echo $en?'No changes recorded yet. The log starts filling as soon as users make changes.':'Aún no hay cambios registrados. La bitácora se empieza a llenar en cuanto los usuarios realicen cambios.'; ?>
                </div>
            <?php else: ?>

            <div class="card shadow-sm mb-3"><div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small mb-1"><?php echo $en?'User':'Usuario'; ?></label>
                        <select name="usuario" class="form-select form-select-sm">
                            <option value="0"><?php echo $en?'All':'Todos'; ?></option>
                            <?php foreach ($usuarios as $u): ?>
                            <option value="<?php echo (int)$u['id_usuario']; ?>" <?php echo $fUser===(int)$u['id_usuario']?'selected':''; ?>>
                                <?php echo htmlspecialchars(trim($u['nombre']) ?: $u['usuario']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1"><?php echo $en?'Module':'Módulo'; ?></label>
                        <select name="modulo" class="form-select form-select-sm">
                            <option value=""><?php echo $en?'All':'Todos'; ?></option>
                            <?php foreach ($modulos as $m): ?>
                            <option value="<?php echo htmlspecialchars($m); ?>" <?php echo $fModulo===$m?'selected':''; ?>><?php echo htmlspecialchars($m); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1"><?php echo $en?'Action':'Acción'; ?></label>
                        <select name="accion" class="form-select form-select-sm">
                            <option value=""><?php echo $en?'All':'Todas'; ?></option>
                            <?php foreach ($acciones as $a): ?>
                            <option value="<?php echo htmlspecialchars($a); ?>" <?php echo $fAccion===$a?'selected':''; ?>><?php echo htmlspecialchars($a); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1"><?php echo $en?'From':'Desde'; ?></label>
                        <input type="date" name="desde" class="form-control form-control-sm" value="<?php echo htmlspecialchars($fDesde); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1"><?php echo $en?'To':'Hasta'; ?></label>
                        <input type="date" name="hasta" class="form-control form-control-sm" value="<?php echo htmlspecialchars($fHasta); ?>">
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="q" class="form-control form-control-sm" placeholder="<?php echo $en?'Search in name / detail…':'Buscar en nombre / detalle…'; ?>" value="<?php echo htmlspecialchars($fQ); ?>">
                    </div>
                    <div class="col-md-6 text-md-end">
                        <a href="auditoria.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle"></i> <?php echo $en?'Clear':'Limpiar'; ?></a>
                    </div>
                </form>
            </div></div>

            <div class="card shadow-sm"><div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small"><?php echo ($en?'Showing ':'Mostrando ').count($rows).($en?' of ':' de ').$total.($en?' records (latest 500).':' registros (últimos 500).'); ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th><?php echo $en?'Date/time':'Fecha/hora'; ?></th>
                                <th><?php echo $en?'User':'Usuario'; ?></th>
                                <th><?php echo $en?'Role':'Rol'; ?></th>
                                <th><?php echo $en?'Module':'Módulo'; ?></th>
                                <th><?php echo $en?'Action':'Acción'; ?></th>
                                <th><?php echo $en?'Detail':'Detalle'; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$rows): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4"><?php echo $en?'No records for these filters.':'No hay registros para estos filtros.'; ?></td></tr>
                        <?php else: foreach ($rows as $r): ?>
                            <tr>
                                <td class="small text-nowrap"><?php echo htmlspecialchars(date('m/d/Y H:i', strtotime($r['fecha']))); ?></td>
                                <td class="small"><?php echo htmlspecialchars(trim($r['nombre']) ?: ($r['usuario'] ?: '—')); ?></td>
                                <td class="small"><?php echo htmlspecialchars($r['rol'] ?: '—'); ?></td>
                                <td class="small"><?php echo htmlspecialchars($r['modulo']); ?></td>
                                <td><?php echo accionBadge($r['accion'], $en); ?></td>
                                <td class="small"><?php echo htmlspecialchars($r['detalle'] ?: '—'); ?><?php echo $r['entidad_id']?' <span class="text-muted">(#'.htmlspecialchars($r['entidad_id']).')</span>':''; ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div></div>

            <?php endif; ?>

        </div></div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>
