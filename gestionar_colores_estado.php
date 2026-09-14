<?php
/**
 * gestionar_colores_estado.php
 * Editor de colores de los estados del calendario, con paletas armónicas
 * predefinidas y vista previa. SISTEMA-only.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/lang/i18n.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
if (!isset($_SESSION["rol"]) || strtoupper($_SESSION["rol"]) !== 'SISTEMA') {
    header("Location: break.php"); exit();
}
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$tablaOk = ($conexion->query("SHOW TABLES LIKE 'estado_cita_colores'")->num_rows ?? 0) > 0;

$msg = null;
if ($tablaOk && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $colores = $_POST['color'] ?? [];
    $textos  = $_POST['text_color'] ?? [];
    $up = $conexion->prepare("UPDATE estado_cita_colores SET color=?, text_color=? WHERE clave=?");
    $n=0;
    foreach ($colores as $clave=>$color) {
        $color = trim($color);
        $tcol  = trim($textos[$clave] ?? '#ffffff');
        if (!preg_match('/^#[0-9a-fA-F]{6}$/',$color)) continue;
        if (!preg_match('/^#[0-9a-fA-F]{6}$/',$tcol)) $tcol = '#ffffff';
        $up->bind_param('sss',$color,$tcol,$clave); $up->execute(); $n++;
    }
    $up->close();
    $msg = ['success', "Colores actualizados ($n). Los cambios se ven al recargar el calendario."];
}

$rows=[];
if ($tablaOk) {
    $r=$conexion->query("SELECT * FROM estado_cita_colores ORDER BY orden");
    if ($r) while($x=$r->fetch_assoc()) $rows[]=$x;
}
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
        .color-chip { display:inline-block; width:120px; padding:6px 10px; border-radius:6px; color:#fff; font-size:.8rem; text-align:center; }
        .preset-card { cursor:pointer; border:2px solid transparent; }
        .preset-card:hover { border-color:#0d6efd; }
        .preset-swatch { width:20px; height:20px; border-radius:4px; display:inline-block; }
        input[type=color]{ width:46px; height:38px; padding:2px; border:1px solid #ced4da; border-radius:6px; }
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
                <div>Colores de estados <div class="page-title-subheading">Personaliza los colores del calendario según los estados de las citas.</div></div>
            </div>
            <div class="page-title-actions"><a href="SCH_Calendar.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-calendar3"></i> Ver calendario</a></div>
            </div></div>

            <?php if (!$tablaOk): ?>
                <div class="alert alert-warning">Falta la tabla de colores. <a href="migrar_estado_colores.php" class="alert-link">Créala aquí</a>.</div>
            <?php else: ?>
                <?php if ($msg): ?><div class="alert alert-<?php echo h($msg[0]); ?>"><?php echo h($msg[1]); ?></div><?php endif; ?>

                <div class="card shadow-sm mb-3"><div class="card-body">
                    <h6 class="mb-2"><i class="bi bi-palette2"></i> Paletas predefinidas (armónicas)</h6>
                    <p class="text-muted small">Haz clic en una paleta para aplicarla a todos los estados; luego puedes ajustar colores individuales y Guardar.</p>
                    <div class="row g-2 align-items-start" id="presets"></div>
                </div></div>

                <form method="POST">
                    <div class="card shadow-sm mb-3"><div class="card-body">
                        <div class="table-responsive"><table class="table align-middle">
                            <thead class="table-light"><tr><th>Estado</th><th>Fondo</th><th>Letra</th><th>Vista previa</th></tr></thead>
                            <tbody>
                            <?php foreach ($rows as $r): $c=h($r['color']); $tc=h($r['text_color'] ?? '#ffffff'); $k=h($r['clave']); ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo h($r['etiqueta']); ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <input type="color" value="<?php echo $c; ?>" data-bg="<?php echo $k; ?>" onchange="syncHex(this,'bg')">
                                            <input type="text" name="color[<?php echo $k; ?>]" id="hex_<?php echo $k; ?>" value="<?php echo $c; ?>" class="form-control form-control-sm" style="width:92px;" maxlength="7" oninput="syncColor('<?php echo $k; ?>')">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <input type="color" value="<?php echo $tc; ?>" data-tx="<?php echo $k; ?>" onchange="syncHex(this,'tx')">
                                            <input type="text" name="text_color[<?php echo $k; ?>]" id="txt_<?php echo $k; ?>" value="<?php echo $tc; ?>" class="form-control form-control-sm" style="width:92px;" maxlength="7" oninput="syncColor('<?php echo $k; ?>')">
                                        </div>
                                    </td>
                                    <td><span class="color-chip" id="chip_<?php echo $k; ?>" style="background:<?php echo $c; ?>;color:<?php echo $tc; ?>;">10:00 Paciente</span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table></div>
                        <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar colores</button>
                        <a href="gestionar_colores_estado.php" class="btn btn-link">Cancelar</a>
                    </div></div>
                </form>
            <?php endif; ?>
        </div></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Paletas armónicas: cada clave define { bg, tx } (fondo y letra)
var D='#ffffff', K='#212529'; // blanco / casi-negro
var PRESETS = {
    'Suave (pastel)': {
        pendiente:{bg:'#5c6b7a',tx:D}, confirmada:{bg:'#b4a7d6',tx:K}, atendida:{bg:'#a8d5a2',tx:K}, cancelada:{bg:'#f6b26b',tx:K},
        cancelacion_tardia:{bg:'#ffe599',tx:K}, cancelado_profesional:{bg:'#ea9999',tx:K}, no_asistio:{bg:'#e06666',tx:D}, default:{bg:'#9fc5e8',tx:K}
    },
    'Profesional': {
        pendiente:{bg:'#3b4252',tx:D}, confirmada:{bg:'#6f42c1',tx:D}, atendida:{bg:'#28a745',tx:D}, cancelada:{bg:'#fd7e14',tx:D},
        cancelacion_tardia:{bg:'#ffc107',tx:K}, cancelado_profesional:{bg:'#ff8a80',tx:K}, no_asistio:{bg:'#dc3545',tx:D}, default:{bg:'#007bff',tx:D}
    },
    'Océano (frío)': {
        pendiente:{bg:'#34495e',tx:D}, confirmada:{bg:'#2980b9',tx:D}, atendida:{bg:'#16a085',tx:D}, cancelada:{bg:'#e67e22',tx:D},
        cancelacion_tardia:{bg:'#f39c12',tx:K}, cancelado_profesional:{bg:'#c39bd3',tx:K}, no_asistio:{bg:'#c0392b',tx:D}, default:{bg:'#3498db',tx:D}
    },
    'Alto contraste': {
        pendiente:{bg:'#212529',tx:D}, confirmada:{bg:'#6610f2',tx:D}, atendida:{bg:'#198754',tx:D}, cancelada:{bg:'#fd7e14',tx:K},
        cancelacion_tardia:{bg:'#ffca2c',tx:K}, cancelado_profesional:{bg:'#e35d6a',tx:D}, no_asistio:{bg:'#d00000',tx:D}, default:{bg:'#0d6efd',tx:D}
    }
};
// inp = input color; tipo = 'bg' | 'tx' → refleja al hex de texto correspondiente
function syncHex(inp,tipo){
    var k = tipo==='bg' ? inp.dataset.bg : inp.dataset.tx;
    var target = tipo==='bg' ? document.getElementById('hex_'+k) : document.getElementById('txt_'+k);
    if(target) target.value = inp.value;
    syncColor(k);
}
function syncColor(k){
    var bg=(document.getElementById('hex_'+k)||{}).value;
    var tx=(document.getElementById('txt_'+k)||{}).value;
    var chip=document.getElementById('chip_'+k);
    if(chip){ if(/^#[0-9a-fA-F]{6}$/.test(bg)) chip.style.background=bg; if(/^#[0-9a-fA-F]{6}$/.test(tx)) chip.style.color=tx; }
    var pb=document.querySelector('input[type=color][data-bg="'+k+'"]'); if(pb && /^#[0-9a-fA-F]{6}$/.test(bg)) pb.value=bg;
    var pt=document.querySelector('input[type=color][data-tx="'+k+'"]'); if(pt && /^#[0-9a-fA-F]{6}$/.test(tx)) pt.value=tx;
}
function aplicarPreset(name){
    var p=PRESETS[name]; if(!p) return;
    Object.keys(p).forEach(function(k){
        var bg=document.getElementById('hex_'+k), tx=document.getElementById('txt_'+k);
        if(bg) bg.value=p[k].bg; if(tx) tx.value=p[k].tx; syncColor(k);
    });
}
(function(){
    var cont=document.getElementById('presets'); if(!cont) return;
    Object.keys(PRESETS).forEach(function(name){
        var p=PRESETS[name];
        var sw=Object.keys(p).map(function(k){return '<span class="preset-swatch" style="background:'+p[k].bg+'"></span>';}).join(' ');
        var col=document.createElement('div'); col.className='col-md-6 col-lg-3';
        col.innerHTML='<div class="card preset-card" onclick="aplicarPreset(\''+name+'\')"><div class="card-body p-2">'
            +'<div class="fw-semibold small mb-1">'+name+'</div><div class="d-flex flex-wrap gap-1">'+sw+'</div></div></div>';
        cont.appendChild(col);
    });
})();
</script>
</body>
</html>
