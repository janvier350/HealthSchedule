<?php
/**
 * gestionar_colores_estado.php
 * Paletas de colores de estados POR USUARIO:
 *  - Elegir una paleta (del sistema o propia) -> se guarda en su perfil.
 *  - Crear una paleta personalizada (fondo + letra por estado).
 *  - Eliminar sus propias paletas.
 * El calendario usa la paleta elegida por el usuario.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/lang/i18n.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
if (!isset($_SESSION["rol"], $_SESSION["iduser"])) { header("Location: break.php"); exit(); }
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$idUser = (int)$_SESSION['iduser'];

// Detección defensiva del sistema de paletas. Evita 500 cuando la migración
// aún no se ha ejecutado (o quedó incompleta) y mysqli lanza excepciones.
$rt = $conexion->query("SHOW TABLES LIKE 'paletas_estado'");
$tablaOk = $rt ? (($rt->num_rows ?? 0) > 0) : false;

$rc0 = $conexion->query("SHOW COLUMNS FROM ADM_USUARIO LIKE 'IDPALETA_ESTADO'");
$colUsuarioOk = $rc0 ? (($rc0->num_rows ?? 0) > 0) : false;

// El sistema está listo sólo si existen las tablas y la columna de perfil.
$sistemaListo = $tablaOk && $colUsuarioOk;

$estadosDef = [
    'pendiente'=>'Pendiente / Reagendada','confirmada'=>'Confirmada','atendida'=>'Atendida',
    'cancelada'=>'Cancelada','cancelacion_tardia'=>'Cancelación tardía',
    'cancelado_profesional'=>'Cancelado por profesional','no_asistio'=>'No asistió','default'=>'Otro / sin estado'
];

$msg = null;
if ($sistemaListo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'seleccionar') {
        $idPal = (int)($_POST['id_paleta'] ?? 0);
        // Validar que la paleta sea del sistema o del usuario
        $chk = $conexion->prepare("SELECT id FROM paletas_estado WHERE id=? AND (es_sistema=1 OR id_usuario=?) LIMIT 1");
        $chk->bind_param('ii',$idPal,$idUser); $chk->execute();
        if ($chk->get_result()->fetch_assoc()) {
            $up = $conexion->prepare("UPDATE ADM_USUARIO SET IDPALETA_ESTADO=? WHERE IDADM_USUARIO=?");
            $up->bind_param('ii',$idPal,$idUser); $up->execute(); $up->close();
            $msg = ['success','Paleta aplicada. Recarga el calendario para verla.'];
        }
        $chk->close();
    } elseif ($accion === 'crear') {
        $nombre = trim($_POST['nombre'] ?? '');
        $colores= $_POST['color'] ?? [];
        $textos = $_POST['text_color'] ?? [];
        if ($nombre === '') { $msg=['danger','Ponle un nombre a tu paleta.']; }
        else {
            $ins = $conexion->prepare("INSERT INTO paletas_estado (nombre, es_sistema, id_usuario) VALUES (?, 0, ?)");
            $ins->bind_param('si',$nombre,$idUser);
            if ($ins->execute()) {
                $idPal=(int)$conexion->insert_id; $ins->close();
                $insC=$conexion->prepare("INSERT INTO paleta_estado_colores (id_paleta,clave,color,text_color) VALUES (?,?,?,?)");
                foreach ($estadosDef as $clave=>$lbl) {
                    $c = trim($colores[$clave] ?? '#007bff'); if(!preg_match('/^#[0-9a-fA-F]{6}$/',$c)) $c='#007bff';
                    $t = trim($textos[$clave] ?? '#ffffff');  if(!preg_match('/^#[0-9a-fA-F]{6}$/',$t)) $t='#ffffff';
                    $insC->bind_param('isss',$idPal,$clave,$c,$t); $insC->execute();
                }
                $insC->close();
                $conexion->query("UPDATE ADM_USUARIO SET IDPALETA_ESTADO=$idPal WHERE IDADM_USUARIO=$idUser");
                $msg=['success','Paleta personalizada creada y aplicada.'];
            } else { $msg=['danger','Error: '.$ins->error]; $ins->close(); }
        }
    } elseif ($accion === 'eliminar') {
        $idPal=(int)($_POST['id_paleta'] ?? 0);
        // Solo puede borrar sus propias paletas
        $chk=$conexion->prepare("SELECT id FROM paletas_estado WHERE id=? AND id_usuario=? AND es_sistema=0 LIMIT 1");
        $chk->bind_param('ii',$idPal,$idUser); $chk->execute();
        if ($chk->get_result()->fetch_assoc()) {
            $conexion->query("DELETE FROM paleta_estado_colores WHERE id_paleta=$idPal");
            $conexion->query("DELETE FROM paletas_estado WHERE id=$idPal");
            $conexion->query("UPDATE ADM_USUARIO SET IDPALETA_ESTADO=NULL WHERE IDADM_USUARIO=$idUser AND IDPALETA_ESTADO=$idPal");
            $msg=['success','Paleta eliminada.'];
        }
        $chk->close();
    }
}

// Paleta seleccionada del usuario (sólo si la columna existe)
$idSel = 0;
if ($colUsuarioOk) {
    $rs = $conexion->query("SELECT IDPALETA_ESTADO FROM ADM_USUARIO WHERE IDADM_USUARIO=$idUser LIMIT 1");
    if ($rs && ($r=$rs->fetch_assoc())) $idSel=(int)($r['IDPALETA_ESTADO'] ?? 0);
}

// Cargar paletas disponibles + sus colores
$paletas=[];
if ($tablaOk) {
    $rp=$conexion->query("SELECT * FROM paletas_estado WHERE es_sistema=1 OR id_usuario=$idUser ORDER BY es_sistema DESC, nombre");
    if ($rp) while($x=$rp->fetch_assoc()) { $x['colores']=[]; $paletas[(int)$x['id']]=$x; }
    if ($paletas) {
        $ids=implode(',',array_map('intval',array_keys($paletas)));
        $rc=$conexion->query("SELECT * FROM paleta_estado_colores WHERE id_paleta IN ($ids)");
        if ($rc) while($x=$rc->fetch_assoc()){ $paletas[(int)$x['id_paleta']]['colores'][$x['clave']]=['bg'=>$x['color'],'tx'=>$x['text_color']]; }
    }
}
// Colores de arranque para el creador (los de la paleta seleccionada, o Profesional)
$base = $paletas[$idSel]['colores'] ?? ($paletas ? reset($paletas)['colores'] : []);
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="images/favicon.svg">
    <title>Colores de estados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./main.css" rel="stylesheet">
    <script src="js/jquery.min.js"></script>
    <style>
        .paleta-card{ border:2px solid #e6e9f0; border-radius:10px; transition:.15s; }
        .paleta-card.sel{ border-color:#0d6efd; box-shadow:0 0 0 2px rgba(13,110,253,.15); }
        .paleta-swatch{ height:26px; border-radius:5px; display:flex; align-items:center; justify-content:center; font-size:.62rem; }
        .color-chip{ display:inline-block; min-width:120px; padding:6px 10px; border-radius:6px; font-size:.8rem; text-align:center; }
        input[type=color]{ width:44px; height:36px; padding:2px; border:1px solid #ced4da; border-radius:6px; }
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
                <div class="page-title-icon"><i class="pe-7s-paint-bucket icon-gradient bg-plum-plate"></i></div>
                <div>Colores de estados <div class="page-title-subheading">Elige tu paleta o crea una propia. Se guarda en tu perfil.</div></div>
            </div>
            <div class="page-title-actions"><a href="SCH_Calendar.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-calendar3"></i> Ver calendario</a></div>
            </div></div>

            <?php if (!$sistemaListo): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    Falta preparar el sistema de paletas<?php echo ($tablaOk && !$colUsuarioOk) ? ' (falta la columna de perfil <code>IDPALETA_ESTADO</code>)' : ''; ?>.
                    <a href="migrar_paletas_estado.php" class="alert-link">Ejecútalo aquí</a> (es idempotente, puedes correrlo sin riesgo).
                </div>
            <?php else: ?>
                <?php if ($msg): ?><div class="alert alert-<?php echo h($msg[0]); ?>"><?php echo h($msg[1]); ?></div><?php endif; ?>

                <!-- Paletas elegibles -->
                <div class="card shadow-sm mb-3"><div class="card-body">
                    <h6 class="mb-3"><i class="bi bi-palette2"></i> Elige tu paleta</h6>
                    <div class="row g-3 align-items-start">
                        <?php foreach ($paletas as $id=>$pal):
                            $sel = ($id===$idSel);
                            $propia = ((int)$pal['id_usuario']===$idUser && (int)$pal['es_sistema']===0);
                        ?>
                        <div class="col-md-6 col-lg-4 col-xl-3">
                            <div class="paleta-card p-2 <?php echo $sel?'sel':''; ?>">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-semibold small"><?php echo h($pal['nombre']); ?>
                                        <?php echo $propia?'<span class="badge bg-info text-dark ms-1">Mía</span>':''; ?>
                                        <?php echo $sel?'<span class="badge bg-primary ms-1">En uso</span>':''; ?>
                                    </span>
                                </div>
                                <div class="d-grid gap-1 mb-2" style="grid-template-columns:repeat(4,1fr);">
                                    <?php foreach ($estadosDef as $clave=>$lbl): $cc=$pal['colores'][$clave]??['bg'=>'#ccc','tx'=>'#000']; ?>
                                        <div class="paleta-swatch" style="background:<?php echo h($cc['bg']); ?>;color:<?php echo h($cc['tx']); ?>;" title="<?php echo h($lbl); ?>">Aa</div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="d-flex gap-1">
                                    <?php if(!$sel): ?>
                                    <form method="POST" class="flex-grow-1"><input type="hidden" name="accion" value="seleccionar"><input type="hidden" name="id_paleta" value="<?php echo $id; ?>">
                                        <button class="btn btn-sm btn-primary w-100"><i class="bi bi-check2"></i> Usar</button></form>
                                    <?php else: ?>
                                    <button class="btn btn-sm btn-success w-100" disabled><i class="bi bi-check-lg"></i> En uso</button>
                                    <?php endif; ?>
                                    <?php if($propia): ?>
                                    <form method="POST" onsubmit="return confirm('¿Eliminar esta paleta?');"><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id_paleta" value="<?php echo $id; ?>">
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div></div>

                <!-- Crear paleta personalizada -->
                <div class="card shadow-sm mb-4"><div class="card-body">
                    <h6 class="mb-2"><i class="bi bi-plus-circle"></i> Crear paleta personalizada</h6>
                    <p class="text-muted small">Define fondo y letra por estado. Se guarda en tu perfil y queda elegible.</p>
                    <form method="POST">
                        <input type="hidden" name="accion" value="crear">
                        <div class="row g-2 mb-3"><div class="col-md-5">
                            <label class="form-label small fw-semibold">Nombre de la paleta</label>
                            <input type="text" name="nombre" class="form-control" placeholder="Ej: Mis colores" required>
                        </div></div>
                        <div class="table-responsive"><table class="table align-middle">
                            <thead class="table-light"><tr><th>Estado</th><th>Fondo</th><th>Letra</th><th>Vista previa</th></tr></thead>
                            <tbody>
                            <?php foreach ($estadosDef as $clave=>$lbl):
                                $bg=$base[$clave]['bg']??'#007bff'; $tx=$base[$clave]['tx']??'#ffffff'; $k=h($clave); ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo h($lbl); ?></td>
                                    <td><div class="d-flex gap-1 align-items-center">
                                        <input type="color" value="<?php echo h($bg); ?>" data-bg="<?php echo $k; ?>" onchange="syncHex(this,'bg')">
                                        <input type="text" name="color[<?php echo $k; ?>]" id="hex_<?php echo $k; ?>" value="<?php echo h($bg); ?>" class="form-control form-control-sm" style="width:92px;" maxlength="7" oninput="syncColor('<?php echo $k; ?>')">
                                    </div></td>
                                    <td><div class="d-flex gap-1 align-items-center">
                                        <input type="color" value="<?php echo h($tx); ?>" data-tx="<?php echo $k; ?>" onchange="syncHex(this,'tx')">
                                        <input type="text" name="text_color[<?php echo $k; ?>]" id="txt_<?php echo $k; ?>" value="<?php echo h($tx); ?>" class="form-control form-control-sm" style="width:92px;" maxlength="7" oninput="syncColor('<?php echo $k; ?>')">
                                    </div></td>
                                    <td><span class="color-chip" id="chip_<?php echo $k; ?>" style="background:<?php echo h($bg); ?>;color:<?php echo h($tx); ?>;">10:00 Paciente</span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table></div>
                        <button class="btn btn-primary"><i class="bi bi-save"></i> Guardar y usar</button>
                    </form>
                </div></div>
            <?php endif; ?>
        </div></div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function syncHex(inp,tipo){
    var k = tipo==='bg' ? inp.dataset.bg : inp.dataset.tx;
    var t = tipo==='bg' ? document.getElementById('hex_'+k) : document.getElementById('txt_'+k);
    if(t) t.value=inp.value; syncColor(k);
}
function syncColor(k){
    var bg=(document.getElementById('hex_'+k)||{}).value, tx=(document.getElementById('txt_'+k)||{}).value;
    var chip=document.getElementById('chip_'+k);
    if(chip){ if(/^#[0-9a-fA-F]{6}$/.test(bg))chip.style.background=bg; if(/^#[0-9a-fA-F]{6}$/.test(tx))chip.style.color=tx; }
    var pb=document.querySelector('input[type=color][data-bg="'+k+'"]'); if(pb&&/^#[0-9a-fA-F]{6}$/.test(bg))pb.value=bg;
    var pt=document.querySelector('input[type=color][data-tx="'+k+'"]'); if(pt&&/^#[0-9a-fA-F]{6}$/.test(tx))pt.value=tx;
}
</script>
</body>
</html>
