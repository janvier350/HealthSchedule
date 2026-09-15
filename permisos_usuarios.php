<?php
/**
 * permisos_usuarios.php — Panel de Control de permisos por usuario.
 * Por cada persona, permite dejar cada módulo "Según rol", "Permitir" o
 * "Bloquear", sobreescribiendo el default del rol. SISTEMA-only.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once("class/permisos.php");
require_once(__DIR__ . "/lang/i18n.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
if (!isset($_SESSION["rol"]) || strtoupper($_SESSION["rol"]) !== 'SISTEMA') { header("Location: break.php"); exit(); }
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$dbEsc = $conexion->real_escape_string($conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db']);
$tabla = (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.TABLES WHERE TABLE_SCHEMA='$dbEsc' AND TABLE_NAME='usuario_permisos'")->fetch_assoc()['c']>0;

// Usuarios activos (con su rol)
$usuarios = [];
$ru = $conexion->query("SELECT A.IDADM_USUARIO id, A.NOMBRES, A.APELLIDOS, A.USUARIO, B.CARGO
                        FROM ADM_USUARIO A INNER JOIN ADM_ROL B ON A.IDADM_ROL=B.IDADM_ROL
                        WHERE A.ESTADO='A' ORDER BY B.CARGO, A.NOMBRES");
if ($ru) while($x=$ru->fetch_assoc()) $usuarios[]=$x;

$catalogo = permisos_catalogo();
$idSel = (int)($_GET['u'] ?? ($_POST['idUsuario'] ?? 0));
$rolSel = ''; $nombreSel = '';
foreach ($usuarios as $u) { if ((int)$u['id']===$idSel){ $rolSel=strtoupper($u['CARGO']); $nombreSel=trim($u['NOMBRES'].' '.$u['APELLIDOS']); } }

$msg = null;
if ($tabla && $_SERVER['REQUEST_METHOD']==='POST' && $idSel>0 && ($_POST['accion']??'')==='guardar') {
    $idUser = (int)$_SESSION['iduser'];
    $vals = $_POST['perm'] ?? [];   // modulo => inherit|allow|block
    $up = $conexion->prepare("INSERT INTO usuario_permisos (IDADM_USUARIO, modulo, permitido, updated_by)
                              VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE permitido=VALUES(permitido), updated_by=VALUES(updated_by), fecha=NOW()");
    $del = $conexion->prepare("DELETE FROM usuario_permisos WHERE IDADM_USUARIO=? AND modulo=?");
    foreach ($catalogo as $key=>$info) {
        $v = $vals[$key] ?? 'inherit';
        if ($v === 'allow' || $v === 'block') {
            $permitido = ($v === 'allow') ? 1 : 0;
            $up->bind_param('isii', $idSel, $key, $permitido, $idUser); $up->execute();
        } else {
            $del->bind_param('is', $idSel, $key); $del->execute();
        }
    }
    $up->close(); $del->close();
    $msg = ['success','Permisos guardados para '.$nombreSel.'.'];
}

// Overrides actuales del usuario seleccionado
$ov = ($idSel>0) ? permisos_overrides($idSel) : [];

// Agrupar catálogo
$grupos = [];
foreach ($catalogo as $key=>$info) { $grupos[$info[1]][$key] = $info; }
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="images/favicon.svg">
    <title>Permisos por usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./main.css" rel="stylesheet">
    <script src="js/jquery.min.js"></script>
    <style>
        .perm-def{ font-size:.72rem; padding:1px 7px; border-radius:20px; }
        .perm-si{ background:#d1e7dd; color:#0f5132; } .perm-no{ background:#f1f1f4; color:#6c757d; }
        .eff-si{ color:#0f5132; font-weight:600; } .eff-no{ color:#b02a37; font-weight:600; }
        select.perm-sel{ max-width:170px; }
    </style>
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
                <div class="page-title-icon"><i class="pe-7s-key icon-gradient bg-plum-plate"></i></div>
                <div>Permisos por usuario <div class="page-title-subheading">Sobreescribe, por persona, el acceso que da su rol a cada módulo.</div></div>
            </div></div></div>

            <?php if(!$tabla): ?>
                <div class="alert alert-warning">Falta preparar los permisos por usuario.
                    <a href="migrar_permisos_usuarios.php" class="alert-link">Ejecútalo aquí</a>.</div>
            <?php else: ?>
                <?php if($msg): ?><div class="alert alert-<?php echo h($msg[0]); ?>"><?php echo h($msg[1]); ?></div><?php endif; ?>

                <div class="card shadow-sm mb-3"><div class="card-body">
                    <form method="GET" class="row g-2 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Usuario</label>
                            <select name="u" class="form-select" onchange="this.form.submit()">
                                <option value="">— Selecciona un usuario —</option>
                                <?php foreach($usuarios as $u): ?>
                                    <option value="<?php echo (int)$u['id']; ?>" <?php echo $idSel===(int)$u['id']?'selected':''; ?>>
                                        <?php echo h(trim($u['NOMBRES'].' '.$u['APELLIDOS'])); ?> — <?php echo h($u['CARGO']); ?> (<?php echo h($u['USUARIO']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-auto"><span class="text-muted small">El Admin (SISTEMA) siempre tiene acceso total.</span></div>
                    </form>
                </div></div>

                <?php if($idSel>0 && $rolSel==='SISTEMA'): ?>
                    <div class="alert alert-info"><b><?php echo h($nombreSel); ?></b> es <b>Administrador (SISTEMA)</b>: tiene acceso total y no se puede limitar aquí.</div>
                <?php elseif($idSel>0): ?>
                    <form method="POST">
                        <input type="hidden" name="accion" value="guardar">
                        <input type="hidden" name="idUsuario" value="<?php echo $idSel; ?>">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div><span class="text-muted">Configurando:</span> <b><?php echo h($nombreSel); ?></b>
                                <span class="badge bg-secondary ms-1"><?php echo h($rolSel); ?></span></div>
                            <button class="btn btn-primary btn-sm"><i class="bi bi-save"></i> Guardar permisos</button>
                        </div>
                        <?php foreach($grupos as $gnombre=>$mods): ?>
                        <div class="card shadow-sm mb-3"><div class="card-body p-0">
                            <div class="px-3 py-2 fw-semibold border-bottom bg-light"><?php echo h($gnombre); ?></div>
                            <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                                <thead class="table-light"><tr>
                                    <th>Módulo</th><th class="text-center" style="width:120px;">Según el rol</th>
                                    <th style="width:190px;">Permiso para esta persona</th><th class="text-center" style="width:110px;">Resultado</th>
                                </tr></thead>
                                <tbody>
                                <?php foreach($mods as $key=>$info):
                                    $defRol = (int)($info[2][$rolSel] ?? 0);
                                    $estado = array_key_exists($key,$ov) ? ($ov[$key]===1?'allow':'block') : 'inherit';
                                    $efectivo = array_key_exists($key,$ov) ? $ov[$key]===1 : $defRol===1;
                                ?>
                                    <tr>
                                        <td><?php echo h($info[0]); ?><div class="text-muted small"><?php echo h($key); ?></div></td>
                                        <td class="text-center">
                                            <span class="perm-def <?php echo $defRol?'perm-si':'perm-no'; ?>"><?php echo $defRol?'Sí':'No'; ?></span>
                                        </td>
                                        <td>
                                            <select name="perm[<?php echo h($key); ?>]" class="form-select form-select-sm perm-sel" onchange="markEff(this,<?php echo $defRol; ?>)">
                                                <option value="inherit" <?php echo $estado==='inherit'?'selected':''; ?>>Según el rol (<?php echo $defRol?'Sí':'No'; ?>)</option>
                                                <option value="allow"   <?php echo $estado==='allow'?'selected':''; ?>>Permitir</option>
                                                <option value="block"   <?php echo $estado==='block'?'selected':''; ?>>Bloquear</option>
                                            </select>
                                        </td>
                                        <td class="text-center eff-cell"><span class="<?php echo $efectivo?'eff-si':'eff-no'; ?>"><?php echo $efectivo?'Con acceso':'Sin acceso'; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table></div>
                        </div></div>
                        <?php endforeach; ?>
                        <div class="d-flex justify-content-end mb-4">
                            <button class="btn btn-primary"><i class="bi bi-save"></i> Guardar permisos</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-secondary">Selecciona un usuario para configurar sus permisos.</div>
                <?php endif; ?>
            <?php endif; ?>

        </div></div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function markEff(sel, defRol){
    var v = sel.value, on;
    if (v === 'allow') on = true; else if (v === 'block') on = false; else on = (defRol === 1);
    var cell = sel.closest('tr').querySelector('.eff-cell');
    cell.innerHTML = '<span class="'+(on?'eff-si':'eff-no')+'">'+(on?'Con acceso':'Sin acceso')+'</span>';
}
</script>
</body>
</html>
