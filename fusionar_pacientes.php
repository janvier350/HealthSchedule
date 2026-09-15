<?php
/**
 * fusionar_pacientes.php
 * Detecta pacientes duplicados (mismo teléfono o misma cédula), muestra los
 * datos ligados a cada registro y permite FUSIONARLOS: reasigna todo lo que
 * apunta al duplicado (citas, facturas, seguros, historial, documentos, …)
 * hacia el paciente que se conserva y luego marca el duplicado como eliminado.
 * SISTEMA-only.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/lang/i18n.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
require_once("class/permisos.php"); requerir('pac.fusionar');
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$dbEsc  = $conexion->real_escape_string($dbName);

// ── Tablas que referencian IDPACIENTE (excepto la propia AG_PACIENTE) ──
$tablasFK = [];
$rt = $conexion->query("SELECT TABLE_NAME FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA='$dbEsc' AND COLUMN_NAME='IDPACIENTE' AND TABLE_NAME <> 'AG_PACIENTE'");
if ($rt) while ($x=$rt->fetch_assoc()) {
    if (preg_match('/^[A-Za-z0-9_]+$/', $x['TABLE_NAME'])) $tablasFK[] = $x['TABLE_NAME'];
}

// Etiquetas amigables para las tablas más comunes
$labelTabla = [
    'AG_CITA'          => 'Citas',
    'facturas'         => 'Facturas',
    'factura_detalle'  => 'Detalle facturas',
    'factura_pagos'    => 'Pagos',
    'seguro_paciente'  => 'Seguros',
    'plan_peso'        => 'Plan de peso',
    'documentos_enviados' => 'Documentos enviados',
];

// ¿Existen columnas de auditoría de eliminación?
$tieneAudit = (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$dbEsc' AND TABLE_NAME='AG_PACIENTE' AND COLUMN_NAME='MOTIVO_ELIMINACION'")->fetch_assoc()['c']>0;

// Cuenta cuántas filas ligadas tiene un paciente por cada tabla FK.
function contarLigados($conexion, $tablasFK, $idPac) {
    $idPac = (int)$idPac; $res = [];
    foreach ($tablasFK as $t) {
        $q = $conexion->query("SELECT COUNT(*) c FROM `$t` WHERE IDPACIENTE=$idPac");
        $res[$t] = $q ? (int)$q->fetch_assoc()['c'] : 0;
    }
    return $res;
}
function normTel($t){ $d = preg_replace('/\D/','',(string)$t); return strlen($d)>=10 ? substr($d,-10) : ($d!==''?$d:''); }

$msg = null;

// ── Acción: FUSIONAR ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion']??'')==='fusionar') {
    $keeper = (int)($_POST['keeper'] ?? 0);
    $losers = array_values(array_unique(array_map('intval', (array)($_POST['losers'] ?? []))));
    $losers = array_filter($losers, fn($x)=>$x>0 && $x!==$keeper);

    if ($keeper<=0 || !$losers) {
        $msg = ['danger','Selecciona el paciente a conservar y al menos un duplicado.'];
    } else {
        // Validar que todos existan y estén activos
        $idsAll = array_merge([$keeper], $losers);
        $inAll  = implode(',', array_map('intval',$idsAll));
        $activos = [];
        $rq = $conexion->query("SELECT IDPACIENTE FROM AG_PACIENTE WHERE IDPACIENTE IN ($inAll) AND ESTADO='A'");
        if ($rq) while($r=$rq->fetch_assoc()) $activos[(int)$r['IDPACIENTE']]=true;

        if (empty($activos[$keeper])) {
            $msg = ['danger','El paciente a conservar no está activo o no existe.'];
        } else {
            $movidos = 0; $detalle = []; $idUser=(int)($_SESSION['iduser']??0);
            foreach ($losers as $loser) {
                if (empty($activos[$loser])) { $detalle[]="#$loser omitido (no activo)"; continue; }
                // Reasignar todas las tablas FK
                foreach ($tablasFK as $t) {
                    $conexion->query("UPDATE `$t` SET IDPACIENTE=$keeper WHERE IDPACIENTE=$loser");
                    $movidos += $conexion->affected_rows > 0 ? $conexion->affected_rows : 0;
                }
                // Rellenar campos vacíos del keeper con los del loser (sin sobreescribir)
                foreach (['CEDULA','TELEFONO','EMAIL','FECHANACIMIENTO','SEX','ADDRESS','IDIOMA'] as $col) {
                    $ok=(int)$conexion->query("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$dbEsc' AND TABLE_NAME='AG_PACIENTE' AND COLUMN_NAME='".$conexion->real_escape_string($col)."'")->fetch_assoc()['c']>0;
                    if ($ok) {
                        $conexion->query("UPDATE AG_PACIENTE K
                            JOIN AG_PACIENTE L ON L.IDPACIENTE=$loser
                            SET K.`$col` = L.`$col`
                            WHERE K.IDPACIENTE=$keeper
                              AND (K.`$col` IS NULL OR K.`$col`='')
                              AND L.`$col` IS NOT NULL AND L.`$col`<>''");
                    }
                }
                // Marcar el duplicado como eliminado (soft-delete + auditoría)
                if ($tieneAudit) {
                    $motivo = "Fusionado con paciente #$keeper (duplicado)";
                    $st = $conexion->prepare("UPDATE AG_PACIENTE SET ESTADO='I', MOTIVO_ELIMINACION=?, FECHA_ELIMINACION=NOW(), ELIMINADO_POR=? WHERE IDPACIENTE=? AND ESTADO='A'");
                    $st->bind_param('sii',$motivo,$idUser,$loser); $st->execute(); $st->close();
                } else {
                    $conexion->query("UPDATE AG_PACIENTE SET ESTADO='I' WHERE IDPACIENTE=$loser AND ESTADO='A'");
                }
                $detalle[] = "#$loser → #$keeper";
            }
            $msg = ['success','Fusión completada. '.implode(', ',$detalle).". Registros reasignados: $movidos."];
        }
    }
}

// ── Comparación manual (por IDs) ───────────────────────────────────────
$comparar = [];
$idsGet = trim($_GET['ids'] ?? '');
if ($idsGet!=='') {
    $ids = array_filter(array_map('intval', explode(',', $idsGet)), fn($x)=>$x>0);
    if ($ids) {
        $in = implode(',', $ids);
        $rq = $conexion->query("SELECT * FROM AG_PACIENTE WHERE IDPACIENTE IN ($in)");
        if ($rq) while($r=$rq->fetch_assoc()){ $r['_ligados']=contarLigados($conexion,$tablasFK,$r['IDPACIENTE']); $comparar[]=$r; }
    }
}

// ── Detección automática de duplicados (mismo teléfono o misma cédula) ─
$pacientes=[]; $rp=$conexion->query("SELECT IDPACIENTE, NOMBRES, APELLIDOS, CEDULA, TELEFONO, EMAIL FROM AG_PACIENTE WHERE ESTADO='A'");
if ($rp) while($r=$rp->fetch_assoc()) $pacientes[(int)$r['IDPACIENTE']]=$r;

// Union-Find para agrupar por teléfono normalizado o cédula
$parent=[]; foreach($pacientes as $id=>$_) $parent[$id]=$id;
function ufFind(&$p,$x){ while($p[$x]!==$x){ $p[$x]=$p[$p[$x]]; $x=$p[$x]; } return $x; }
function ufUnion(&$p,$a,$b){ $ra=ufFind($p,$a); $rb=ufFind($p,$b); if($ra!==$rb) $p[$ra]=$rb; }
$byKey=[];
foreach ($pacientes as $id=>$r) {
    $tel = normTel($r['TELEFONO']);
    $ced = strtoupper(trim((string)$r['CEDULA']));
    if ($tel!=='' && strlen($tel)>=7) $byKey['tel:'.$tel][]=$id;
    if ($ced!=='' && strlen($ced)>=4) $byKey['ced:'.$ced][]=$id;
}
foreach ($byKey as $ids) {
    if (count($ids)<2) continue;
    for ($i=1;$i<count($ids);$i++) ufUnion($parent,$ids[0],$ids[$i]);
}
$grupos=[];
foreach ($pacientes as $id=>$_) { $root=ufFind($parent,$id); $grupos[$root][]=$id; }
$grupos = array_filter($grupos, fn($g)=>count($g)>=2);

$totalGrupos = count($grupos);
$grupos = array_slice($grupos, 0, 100, true); // cap de despliegue
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="images/favicon.svg">
    <title>Fusionar pacientes duplicados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./main.css" rel="stylesheet">
    <script src="js/jquery.min.js"></script>
    <style>
        .dup-card{ border:1px solid #e6e9f0; border-radius:12px; }
        .pac-opt{ border:1px solid #e6e9f0; border-radius:10px; padding:10px 12px; }
        .pac-opt.keep{ border-color:#198754; box-shadow:0 0 0 2px rgba(25,135,84,.12); }
        .cnt-badge{ font-size:.72rem; }
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
                <div class="page-title-icon"><i class="pe-7s-users icon-gradient bg-plum-plate"></i></div>
                <div>Fusionar pacientes duplicados
                    <div class="page-title-subheading">Reasigna todo (citas, facturas, seguros, historial…) al paciente que conservas y elimina el duplicado.</div></div>
            </div>
            <div class="page-title-actions"><a href="pacientes_crud.php" class="btn btn-outline-secondary btn-sm">Gestionar Pacientes</a></div>
            </div></div>

            <?php if ($msg): ?><div class="alert alert-<?php echo h($msg[0]); ?>"><?php echo h($msg[1]); ?></div><?php endif; ?>

            <div class="alert alert-info d-flex align-items-start gap-2">
                <i class="bi bi-info-circle mt-1"></i>
                <div>Al fusionar, <strong>todo</strong> lo que apunta al duplicado se reasigna al que conservas
                (citas, facturas, seguros, historial, documentos, plan de peso). Los campos vacíos del que conservas
                se rellenan con los del duplicado. El duplicado queda como <em>eliminado</em> (recuperable). La acción no borra información.</div>
            </div>

            <!-- Comparación manual por IDs -->
            <div class="card dup-card shadow-sm mb-3"><div class="card-body py-2">
                <form method="GET" class="row g-2 align-items-center">
                    <div class="col-auto"><label class="col-form-label small">Comparar por ID (separados por coma):</label></div>
                    <div class="col-sm-4"><input type="text" name="ids" class="form-control form-control-sm" placeholder="Ej: 1523, 1987" value="<?php echo h($idsGet); ?>"></div>
                    <div class="col-auto"><button class="btn btn-sm btn-primary">Comparar</button></div>
                </form>
            </div></div>

            <?php
            // Render de un grupo de posibles duplicados
            function renderGrupo($conexion,$tablasFK,$labelTabla,$miembros){
                // $miembros: array de filas de AG_PACIENTE (con _ligados)
                echo '<form method="POST" class="card dup-card shadow-sm mb-3" onsubmit="return confirm(\'¿Fusionar los seleccionados en el paciente marcado como CONSERVAR? Esta acción reasigna sus datos.\');">';
                echo '<input type="hidden" name="accion" value="fusionar">';
                echo '<div class="card-body">';
                echo '<div class="row g-2 align-items-start">';
                // el keeper por defecto: el que más registros ligados tenga
                $best=null;$bestC=-1;
                foreach($miembros as $m){ $tot=array_sum($m['_ligados']); if($tot>$bestC){$bestC=$tot;$best=(int)$m['IDPACIENTE'];} }
                foreach ($miembros as $m){
                    $id=(int)$m['IDPACIENTE'];
                    echo '<div class="col-md-6"><div class="pac-opt" data-id="'.$id.'">';
                    echo '<div class="form-check mb-1"><input class="form-check-input keeper-radio" type="radio" name="keeper" value="'.$id.'" id="k'.$id.'" '.($id===$best?'checked':'').'>';
                    echo '<label class="form-check-label fw-semibold" for="k'.$id.'">'.h(trim($m['APELLIDOS'].', '.$m['NOMBRES'])).' <span class="text-muted">#'.$id.'</span></label></div>';
                    echo '<div class="small text-muted mb-2">';
                    echo 'Cédula: '.h($m['CEDULA']?:'—').' · Tel: '.h($m['TELEFONO']?:'—').'<br>Email: '.h($m['EMAIL']?:'—');
                    echo '</div>';
                    // badges de conteos >0
                    echo '<div class="d-flex flex-wrap gap-1">';
                    $hay=false;
                    foreach ($m['_ligados'] as $t=>$c){ if($c>0){ $hay=true; $lbl=$labelTabla[$t]??$t; echo '<span class="badge bg-light text-dark border cnt-badge">'.h($lbl).': '.$c.'</span>'; } }
                    if(!$hay) echo '<span class="badge bg-secondary-subtle text-muted cnt-badge">Sin datos ligados</span>';
                    echo '</div>';
                    // checkbox para incluirlo como duplicado a fusionar (todos menos, se decide por radio)
                    echo '<div class="form-check mt-2"><input class="form-check-input loser-chk" type="checkbox" name="losers[]" value="'.$id.'" id="l'.$id.'" checked>';
                    echo '<label class="form-check-label small" for="l'.$id.'">Incluir en la fusión</label></div>';
                    echo '</div></div>';
                }
                echo '</div>';
                echo '<div class="mt-3 d-flex justify-content-end"><button class="btn btn-success btn-sm"><i class="bi bi-union"></i> Fusionar seleccionados</button></div>';
                echo '</div></form>';
            }

            // Comparación manual
            if ($comparar) {
                echo '<h6 class="mb-2"><i class="bi bi-search"></i> Comparación manual</h6>';
                renderGrupo($conexion,$tablasFK,$labelTabla,$comparar);
            }

            // Grupos automáticos
            echo '<div class="d-flex justify-content-between align-items-center mb-2"><h6 class="mb-0"><i class="bi bi-people"></i> Posibles duplicados (mismo teléfono o cédula)</h6>';
            echo '<span class="text-muted small">'.$totalGrupos.' grupo(s)'.($totalGrupos>100?' — mostrando 100':'').'</span></div>';

            if (!$grupos) {
                echo '<div class="card dup-card shadow-sm"><div class="card-body text-center text-muted py-5"><i class="bi bi-check2-circle fs-2 d-block mb-2"></i>No se detectaron duplicados por teléfono ni cédula.</div></div>';
            } else {
                foreach ($grupos as $g) {
                    $miembros=[];
                    foreach ($g as $id){
                        $r=$pacientes[$id]; $r['_ligados']=contarLigados($conexion,$tablasFK,$id); $miembros[]=$r;
                    }
                    renderGrupo($conexion,$tablasFK,$labelTabla,$miembros);
                }
            }
            ?>

        </div></div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// El paciente marcado como "conservar" no puede a la vez estar marcado como duplicado a fusionar.
document.querySelectorAll('form').forEach(function(f){
    function sync(){
        var keep = f.querySelector('.keeper-radio:checked');
        f.querySelectorAll('.loser-chk').forEach(function(chk){
            var isKeep = keep && chk.value===keep.value;
            chk.disabled = isKeep;
            if(isKeep) chk.checked = false;
            var box = chk.closest('.pac-opt');
            if(box) box.classList.toggle('keep', isKeep);
        });
    }
    f.querySelectorAll('.keeper-radio').forEach(r=>r.addEventListener('change',sync));
    sync();
});
</script>
</body>
</html>
