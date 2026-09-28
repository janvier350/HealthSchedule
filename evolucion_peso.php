<?php
/**
 * evolucion_peso.php — Gráfica de evolución de Peso e IMC de un paciente,
 * pensada para mostrarla al paciente. Alterna entre Peso, IMC o ambos.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/lang/i18n.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

if (!isset($_SESSION["rol"])) { header("Location: break.php"); exit(); }
if (isset($_SESSION['expire']) && time() > $_SESSION['expire']) { session_destroy(); header("Location: expirada.php"); exit(); }

$en = (current_lang() === 'en');
$idPaciente = (int)($_GET['idPaciente'] ?? $_GET['id'] ?? 0);
if ($idPaciente <= 0) { exit($en ? 'Invalid patient.' : 'Paciente inválido.'); }

// Datos del paciente
$pac = null;
if ($st = $conexion->prepare("SELECT NOMBRES, APELLIDOS, FECHANACIMIENTO FROM AG_PACIENTE WHERE IDPACIENTE=? LIMIT 1")) {
    $st->bind_param('i', $idPaciente); $st->execute();
    $pac = $st->get_result()->fetch_assoc(); $st->close();
}
if (!$pac) { exit($en ? 'Patient not found.' : 'Paciente no encontrado.'); }
$nombre = trim($pac['NOMBRES'].' '.$pac['APELLIDOS']);
$edad = '';
if (!empty($pac['FECHANACIMIENTO']) && $pac['FECHANACIMIENTO'] !== '0000-00-00') {
    try { $edad = (new DateTime($pac['FECHANACIMIENTO']))->diff(new DateTime('today'))->y; } catch (Exception $e) {}
}

// Serie de peso/IMC por fecha de consulta
$labels = []; $pesos = []; $imcs = [];
if ($st = $conexion->prepare(
    "SELECT C.FECHA_CITA fecha, H.PESO peso, H.IMC imc
       FROM AG_HISTORIAL H INNER JOIN AG_CITA C ON C.IDCITA = H.IDCITA
      WHERE C.IDPACIENTE = ? AND H.PESO IS NOT NULL AND H.PESO > 0
      ORDER BY C.FECHA_CITA ASC, C.HORA_INICIO ASC")) {
    $st->bind_param('i', $idPaciente); $st->execute();
    $rs = $st->get_result();
    while ($r = $rs->fetch_assoc()) {
        $labels[] = date('m/d/Y', strtotime($r['fecha']));
        $pesos[]  = ($r['peso'] !== null) ? round((float)$r['peso'], 1) : null;
        $imcs[]   = ($r['imc']  !== null && (float)$r['imc'] > 0) ? round((float)$r['imc'], 1) : null;
    }
    $st->close();
}
$hayDatos = count($labels) > 0;
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $en ? 'Weight & BMI progress' : 'Evolución de Peso e IMC'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        body { background:#f5f7fb; }
        .wrap { max-width: 980px; margin: 0 auto; padding: 18px 16px 40px; }
        .card-chart { background:#fff; border-radius:14px; box-shadow:0 2px 14px rgba(0,0,0,.08); padding:18px; }
        .pac-head { display:flex; align-items:center; gap:14px; flex-wrap:wrap; margin-bottom:8px; }
        .pac-avatar { width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;font-weight:700;font-size:1.2rem;display:flex;align-items:center;justify-content:center; }
        .pac-name { font-size:1.35rem;font-weight:800;color:#243b53;line-height:1.1; }
        .pac-sub { color:#6c757d;font-size:.95rem; }
        canvas { max-height: 460px; }
        @media print { .no-print { display:none !important; } body{background:#fff;} .card-chart{box-shadow:none;} }
    </style>
</head>
<body>
<div class="wrap">
    <div class="pac-head">
        <div class="pac-avatar"><?php echo htmlspecialchars(strtoupper(substr($pac['NOMBRES'],0,1).substr($pac['APELLIDOS'],0,1))); ?></div>
        <div class="flex-grow-1">
            <div class="pac-name"><?php echo htmlspecialchars($nombre); ?></div>
            <div class="pac-sub">
                <i class="bi bi-graph-up-arrow me-1"></i><?php echo $en ? 'Weight & BMI progress' : 'Evolución de Peso e IMC'; ?>
                <?php if ($edad !== ''): ?> · <?php echo (int)$edad; ?> <?php echo $en ? 'years' : 'años'; ?><?php endif; ?>
            </div>
        </div>
        <div class="no-print d-flex gap-2">
            <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> <?php echo $en?'Print':'Imprimir'; ?></button>
            <button class="btn btn-outline-secondary btn-sm" onclick="window.close()"><i class="bi bi-x-lg"></i> <?php echo $en?'Close':'Cerrar'; ?></button>
        </div>
    </div>

    <div class="card-chart">
        <?php if (!$hayDatos): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-clipboard-x fs-1 d-block mb-2"></i>
                <?php echo $en ? 'No weight records yet for this patient.' : 'Este paciente aún no tiene registros de peso.'; ?>
            </div>
        <?php else: ?>
            <div class="no-print btn-group btn-group-sm mb-3" role="group">
                <input type="radio" class="btn-check" name="modo" id="mAmbos" value="ambos" checked onchange="setModo('ambos')">
                <label class="btn btn-outline-primary" for="mAmbos"><?php echo $en?'Both':'Ambos'; ?></label>
                <input type="radio" class="btn-check" name="modo" id="mPeso" value="peso" onchange="setModo('peso')">
                <label class="btn btn-outline-primary" for="mPeso"><?php echo $en?'Weight':'Peso'; ?></label>
                <input type="radio" class="btn-check" name="modo" id="mImc" value="imc" onchange="setModo('imc')">
                <label class="btn btn-outline-primary" for="mImc">IMC</label>
            </div>
            <canvas id="chartPeso"></canvas>
        <?php endif; ?>
    </div>
</div>

<?php if ($hayDatos): ?>
<script>
const LABELS = <?php echo json_encode($labels); ?>;
const PESOS  = <?php echo json_encode($pesos); ?>;
const IMCS   = <?php echo json_encode($imcs); ?>;
const T = {
    peso: <?php echo json_encode($en ? 'Weight (kg)' : 'Peso (kg)'); ?>,
    imc:  'IMC'
};

const ctx = document.getElementById('chartPeso').getContext('2d');
const chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: LABELS,
        datasets: [
            {
                label: T.peso, data: PESOS, yAxisID: 'yPeso',
                borderColor: '#3d5af1', backgroundColor: 'rgba(61,90,241,.12)',
                tension: .3, spanGaps: true, pointRadius: 4, pointHoverRadius: 6, fill: true
            },
            {
                label: T.imc, data: IMCS, yAxisID: 'yImc',
                borderColor: '#e67e22', backgroundColor: 'rgba(230,126,34,.10)',
                tension: .3, spanGaps: true, pointRadius: 4, pointHoverRadius: 6, fill: false
            }
        ]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { position: 'top' } },
        scales: {
            yPeso: { type:'linear', position:'left',  title:{display:true, text:T.peso} },
            yImc:  { type:'linear', position:'right', title:{display:true, text:'IMC'}, grid:{drawOnChartArea:false} }
        }
    }
});

function setModo(m) {
    // dataset 0 = peso, dataset 1 = imc
    chart.data.datasets[0].hidden = (m === 'imc');
    chart.data.datasets[1].hidden = (m === 'peso');
    chart.options.scales.yPeso.display = (m !== 'imc');
    chart.options.scales.yImc.display  = (m !== 'peso');
    chart.update();
}
</script>
<?php endif; ?>
</body>
</html>
