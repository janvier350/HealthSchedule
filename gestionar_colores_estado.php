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
    $up = $conexion->prepare("UPDATE estado_cita_colores SET color=? WHERE clave=?");
    $n=0;
    foreach ($colores as $clave=>$color) {
        $color = trim($color);
        if (!preg_match('/^#[0-9a-fA-F]{6}$/',$color)) continue;
        $up->bind_param('ss',$color,$clave); $up->execute(); $n++;
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
                    <div class="row g-2" id="presets"></div>
                </div></div>

                <form method="POST">
                    <div class="card shadow-sm mb-3"><div class="card-body">
                        <div class="table-responsive"><table class="table align-middle">
                            <thead class="table-light"><tr><th>Estado</th><th>Color</th><th>Hex</th><th>Vista previa</th></tr></thead>
                            <tbody>
                            <?php foreach ($rows as $r): $c=h($r['color']); $k=h($r['clave']); ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo h($r['etiqueta']); ?></td>
                                    <td><input type="color" value="<?php echo $c; ?>" data-clave="<?php echo $k; ?>" onchange="syncHex(this)"></td>
                                    <td><input type="text" name="color[<?php echo $k; ?>]" id="hex_<?php echo $k; ?>" value="<?php echo $c; ?>" class="form-control form-control-sm" style="width:110px;" maxlength="7" oninput="syncColor('<?php echo $k; ?>',this.value)"></td>
                                    <td><span class="color-chip" id="chip_<?php echo $k; ?>" style="background:<?php echo $c; ?>;">10:00 Paciente</span></td>
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
// Paletas armónicas: cada una define color por clave
var PRESETS = {
    'Suave (pastel)': {
        pendiente:'#5c6b7a', confirmada:'#8e7cc3', atendida:'#6aa84f', cancelada:'#e69138',
        cancelacion_tardia:'#f1c232', cancelado_profesional:'#e6a0a0', no_asistio:'#cc6666', default:'#6d9eeb'
    },
    'Profesional': {
        pendiente:'#3b4252', confirmada:'#6f42c1', atendida:'#28a745', cancelada:'#fd7e14',
        cancelacion_tardia:'#ffc107', cancelado_profesional:'#ff8a80', no_asistio:'#dc3545', default:'#007bff'
    },
    'Océano (frío)': {
        pendiente:'#34495e', confirmada:'#2980b9', atendida:'#16a085', cancelada:'#e67e22',
        cancelacion_tardia:'#f39c12', cancelado_profesional:'#c39bd3', no_asistio:'#c0392b', default:'#3498db'
    },
    'Alto contraste': {
        pendiente:'#212529', confirmada:'#6610f2', atendida:'#198754', cancelada:'#fd7e14',
        cancelacion_tardia:'#ffca2c', cancelado_profesional:'#e35d6a', no_asistio:'#d00000', default:'#0d6efd'
    }
};
function syncHex(inp){ var k=inp.dataset.clave; syncColor(k, inp.value); document.getElementById('hex_'+k).value=inp.value; }
function syncColor(k,val){
    if(!/^#[0-9a-fA-F]{6}$/.test(val)) return;
    var chip=document.getElementById('chip_'+k); if(chip) chip.style.background=val;
    var picker=document.querySelector('input[type=color][data-clave="'+k+'"]'); if(picker) picker.value=val;
}
function aplicarPreset(name){
    var p=PRESETS[name]; if(!p) return;
    Object.keys(p).forEach(function(k){
        var hex=document.getElementById('hex_'+k);
        if(hex){ hex.value=p[k]; syncColor(k,p[k]); }
    });
}
(function(){
    var cont=document.getElementById('presets'); if(!cont) return;
    Object.keys(PRESETS).forEach(function(name){
        var p=PRESETS[name];
        var sw=Object.keys(p).map(function(k){return '<span class="preset-swatch" style="background:'+p[k]+'"></span>';}).join(' ');
        var col=document.createElement('div'); col.className='col-md-6 col-lg-3';
        col.innerHTML='<div class="card preset-card h-100" onclick="aplicarPreset(\''+name+'\')"><div class="card-body p-2">'
            +'<div class="fw-semibold small mb-1">'+name+'</div><div class="d-flex flex-wrap gap-1">'+sw+'</div></div></div>';
        cont.appendChild(col);
    });
})();
</script>
</body>
</html>
