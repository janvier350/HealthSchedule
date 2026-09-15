<?php
/**
 * ncp_diagnosticos_admin.php
 * Administración del catálogo de diagnósticos NCP/PES (bilingüe).
 * Crear, editar, activar/desactivar y eliminar. SISTEMA-only.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/lang/i18n.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
if (!isset($_SESSION["rol"]) || strtoupper($_SESSION["rol"]) !== 'SISTEMA') { header("Location: break.php"); exit(); }
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$tabla = (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.TABLES WHERE TABLE_SCHEMA='".$conexion->real_escape_string($dbName)."' AND TABLE_NAME='ncp_diagnosticos'")->fetch_assoc()['c']>0;

$msg = null;
if ($tabla && $_SERVER['REQUEST_METHOD']==='POST') {
    $accion = $_POST['accion'] ?? '';
    if ($accion==='guardar') {
        $id = (int)($_POST['id'] ?? 0);
        $f = fn($k)=>trim($_POST[$k] ?? '');
        $campos = ['enfermedad_es','enfermedad_en','codigo','problema_es','problema_en','etiologia_es','etiologia_en','signos_es','signos_en','intervencion_es','intervencion_en','monitoreo_es','monitoreo_en'];
        if ($f('enfermedad_es')==='' || $f('enfermedad_en')==='') {
            $msg=['danger','El nombre de la enfermedad (ES y EN) es obligatorio.'];
        } else {
            if ($id>0) {
                $sets = implode(',', array_map(fn($c)=>"$c=?", $campos));
                $st = $conexion->prepare("UPDATE ncp_diagnosticos SET $sets WHERE id=?");
                $vals = array_map($f, $campos); $vals[]=$id;
                $st->bind_param(str_repeat('s',count($campos)).'i', ...$vals);
                $st->execute(); $st->close();
                $msg=['success','Diagnóstico actualizado.'];
            } else {
                $cols = implode(',', $campos); $ph = implode(',', array_fill(0,count($campos),'?'));
                $st = $conexion->prepare("INSERT INTO ncp_diagnosticos ($cols) VALUES ($ph)");
                $vals = array_map($f, $campos);
                $st->bind_param(str_repeat('s',count($campos)), ...$vals);
                $st->execute(); $st->close();
                $msg=['success','Diagnóstico creado.'];
            }
        }
    } elseif ($accion==='eliminar') {
        $id=(int)($_POST['id']??0);
        if ($id>0){ $conexion->query("DELETE FROM ncp_diagnosticos WHERE id=$id"); $msg=['success','Diagnóstico eliminado.']; }
    } elseif ($accion==='toggle') {
        $id=(int)($_POST['id']??0);
        if ($id>0){ $conexion->query("UPDATE ncp_diagnosticos SET activo=1-activo WHERE id=$id"); $msg=['success','Estado actualizado.']; }
    }
}

// Registro en edición
$edit = null;
if ($tabla && isset($_GET['edit'])) {
    $id=(int)$_GET['edit'];
    $r=$conexion->query("SELECT * FROM ncp_diagnosticos WHERE id=$id LIMIT 1");
    if ($r) $edit=$r->fetch_assoc();
}
$rows=[];
if ($tabla) { $q=$conexion->query("SELECT * FROM ncp_diagnosticos ORDER BY orden, enfermedad_es"); if($q) while($x=$q->fetch_assoc()) $rows[]=$x; }
$campoLabel = [
    'enfermedad'=>'Enfermedad / Disease','codigo'=>'Código (opcional)',
    'problema'=>'Problema (P)','etiologia'=>'Etiología (E — "related to")','signos'=>'Signos (S — "as evidenced by")',
    'intervencion'=>'Intervención','monitoreo'=>'Monitoreo'
];
function val($edit,$k){ return $edit ? htmlspecialchars((string)($edit[$k]??''), ENT_QUOTES,'UTF-8') : ''; }
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="images/favicon.svg">
    <title>Diagnósticos NCP/PES</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./main.css" rel="stylesheet">
    <script src="js/jquery.min.js"></script>
</head>
<body>
<div class="app-container app-theme-white body-tabs-shadow fixed-sidebar fixed-header">
    <div class="app-header header-shadow">
        <div class="app-header__logo"><div class="logo-src"></div>
            <div class="header__pane ml-auto"><button type="button" class="hamburger close-sidebar-btn hamburger--elastic" data-class="closed-sidebar"><span class="hamburger-box"><span class="hamburger-inner"></span></span></button></div></div>
        <div class="app-header__mobile-menu"><button type="button" class="hamburger hamburger--elastic mobile-toggle-nav"><span class="hamburger-box"><span class="hamburger-inner"></span></span></button></div>
        <div class="app-header__menu"><button type="button" class="btn-icon btn-icon-only btn btn-primary btn-sm mobile-toggle-header-nav"><span class="btn-icon-wrapper"><i class="fa fa-ellipsis-v"></i></span></button></div>
        <div class="app-header__content"><div class="app-header-left"></div>
            <div class="app-header-right"><div class="header-btn-lg pr-0"><div class="widget-content p-0"><div class="widget-content-wrapper">
                <div class="widget-content-left ml-3 header-user-info"><div class="widget-heading"><?php echo h($_SESSION['nombres']??''); ?></div><div class="widget-subheading"><?php echo h($_SESSION['rol']??''); ?></div></div>
            </div></div></div></div></div>
    </div>
    <div class="app-main">
        <div class="app-sidebar sidebar-shadow"><?php include("./menu/menu_adm.php"); ?></div>
        <div class="app-main__outer"><div class="app-main__inner">

            <div class="app-page-title mb-3"><div class="page-title-wrapper"><div class="page-title-heading">
                <div class="page-title-icon"><i class="pe-7s-note2 icon-gradient bg-plum-plate"></i></div>
                <div>Diagnósticos NCP/PES <div class="page-title-subheading">Catálogo bilingüe para insertar en la consulta (Problema / Etiología / Signos + Intervención + Monitoreo).</div></div>
            </div></div></div>

            <?php if(!$tabla): ?>
                <div class="alert alert-warning">Falta crear el catálogo. <a href="migrar_ncp_diagnosticos.php" class="alert-link">Ejecútalo aquí</a>.</div>
            <?php else: ?>
                <?php if($msg): ?><div class="alert alert-<?php echo h($msg[0]); ?>"><?php echo h($msg[1]); ?></div><?php endif; ?>

                <div class="card shadow-sm mb-3"><div class="card-body">
                    <h6 class="mb-3"><i class="bi bi-<?php echo $edit?'pencil-square':'plus-circle'; ?>"></i> <?php echo $edit?'Editar diagnóstico':'Nuevo diagnóstico'; ?></h6>
                    <form method="POST" class="row g-2">
                        <input type="hidden" name="accion" value="guardar">
                        <input type="hidden" name="id" value="<?php echo $edit?(int)$edit['id']:0; ?>">
                        <div class="col-md-5"><label class="form-label small fw-semibold">Enfermedad (ES) *</label><input name="enfermedad_es" class="form-control" required value="<?php echo val($edit,'enfermedad_es'); ?>"></div>
                        <div class="col-md-5"><label class="form-label small fw-semibold">Disease (EN) *</label><input name="enfermedad_en" class="form-control" required value="<?php echo val($edit,'enfermedad_en'); ?>"></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Código</label><input name="codigo" class="form-control" value="<?php echo val($edit,'codigo'); ?>"></div>
                        <?php
                        $pairs = [
                            ['problema','Problema (P)','Problem (P)'],
                            ['etiologia','Etiología (E — "relacionado con")','Etiology (E — "related to")'],
                            ['signos','Signos (S — "evidenciado por")','Signs (S — "as evidenced by")'],
                            ['intervencion','Intervención','Intervention'],
                            ['monitoreo','Monitoreo','Monitoring'],
                        ];
                        foreach ($pairs as $p): $esName=$p[0].'_es'; $enName=$p[0].'_en'; $isArea = in_array($p[0],['intervencion','monitoreo']); ?>
                            <div class="col-md-6"><label class="form-label small fw-semibold"><?php echo h($p[1]); ?></label>
                                <?php if($isArea): ?><textarea name="<?php echo $esName; ?>" class="form-control" rows="2"><?php echo val($edit,$esName); ?></textarea>
                                <?php else: ?><input name="<?php echo $esName; ?>" class="form-control" value="<?php echo val($edit,$esName); ?>"><?php endif; ?>
                            </div>
                            <div class="col-md-6"><label class="form-label small fw-semibold"><?php echo h($p[2]); ?></label>
                                <?php if($isArea): ?><textarea name="<?php echo $enName; ?>" class="form-control" rows="2"><?php echo val($edit,$enName); ?></textarea>
                                <?php else: ?><input name="<?php echo $enName; ?>" class="form-control" value="<?php echo val($edit,$enName); ?>"><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <div class="col-12 mt-2">
                            <button class="btn btn-primary"><i class="bi bi-save"></i> <?php echo $edit?'Guardar cambios':'Crear'; ?></button>
                            <?php if($edit): ?><a href="ncp_diagnosticos_admin.php" class="btn btn-outline-secondary">Cancelar</a><?php endif; ?>
                        </div>
                    </form>
                </div></div>

                <div class="card shadow-sm"><div class="card-body p-0"><div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Enfermedad</th><th>Problema (P)</th><th>Código</th><th>Estado</th><th class="text-end pe-3">Acciones</th></tr></thead>
                        <tbody>
                        <?php if($rows): foreach($rows as $r): ?>
                            <tr>
                                <td class="fw-semibold"><?php echo h($r['enfermedad_es']); ?><div class="text-muted small"><?php echo h($r['enfermedad_en']); ?></div></td>
                                <td><small><?php echo h($r['problema_es']?:'—'); ?></small></td>
                                <td><small><?php echo h($r['codigo']?:'—'); ?></small></td>
                                <td><?php echo $r['activo']?'<span class="badge bg-success">Activo</span>':'<span class="badge bg-secondary">Inactivo</span>'; ?></td>
                                <td class="text-end pe-3">
                                    <a href="?edit=<?php echo (int)$r['id']; ?>" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="bi bi-pencil"></i></a>
                                    <form method="POST" class="d-inline"><input type="hidden" name="accion" value="toggle"><input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>"><button class="btn btn-sm btn-outline-secondary py-0 px-2"><i class="bi bi-<?php echo $r['activo']?'eye-slash':'eye'; ?>"></i></button></form>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este diagnóstico?');"><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>"><button class="btn btn-sm btn-outline-danger py-0 px-2"><i class="bi bi-trash"></i></button></form>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Sin diagnósticos. Crea uno arriba o ejecuta la siembra.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div></div></div>
            <?php endif; ?>

        </div></div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
