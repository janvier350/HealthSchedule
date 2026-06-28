<?php
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

if (!isset($_SESSION["rol"])) { http_response_code(403); exit; }

$idPac = (int)($_GET['id_paciente'] ?? 0);
if (!$idPac) { echo '<div class="text-muted small">Paciente no válido.</div>'; exit; }

$stmt = $conexion->prepare(
    "SELECT PS.id_paciente_seguro, PS.num_poliza, PS.prioridad, PS.img_frente, PS.img_reverso,
            S.Empresa_seguro, T.Descripcion AS TIPO
       FROM paciente_seguro PS
       INNER JOIN seguros S     ON S.Id_seguro      = PS.Id_seguro
       LEFT  JOIN tipo_seguro T ON T.id_tipo_seguro = S.id_tipo_seguro
      WHERE PS.IDPACIENTE = ? AND PS.estado = 1
   ORDER BY FIELD(PS.prioridad,'Primario','Secundario','Terciario'), S.Empresa_seguro"
);
$stmt->bind_param("i", $idPac);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (!$rows) {
    echo '<div class="text-muted small fst-italic">Sin seguros asignados.</div>';
    exit;
}

// Render del control de imagen (muestra miniatura si existe, o botón "Subir")
function imgControl($id, $lado, $ruta) {
    $label = $lado === 'frente' ? 'Frente' : 'Reverso';
    $html  = '<div class="text-center" style="min-width:90px;">';
    $html .= '<div class="small text-muted mb-1">' . $label . '</div>';
    if ($ruta) {
        $rEsc = htmlspecialchars($ruta);
        $html .= '<a href="' . $rEsc . '" target="_blank">'
               . '<img src="' . $rEsc . '" style="height:50px;width:auto;max-width:90px;border:1px solid #ddd;border-radius:4px;object-fit:cover;"></a>';
        $html .= '<label class="d-block small text-primary mt-1" style="cursor:pointer;">Cambiar'
               . '<input type="file" accept="image/*" hidden onchange="subirImagenSeguro(this,' . (int)$id . ',\'' . $lado . '\')"></label>';
    } else {
        $html .= '<label class="btn btn-sm btn-outline-primary" style="cursor:pointer;">Subir'
               . '<input type="file" accept="image/*" hidden onchange="subirImagenSeguro(this,' . (int)$id . ',\'' . $lado . '\')"></label>';
    }
    $html .= '</div>';
    return $html;
}
?>
<?php foreach ($rows as $r):
    $id = (int)$r['id_paciente_seguro'];
?>
<div class="border rounded p-2 mb-2">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <strong><?php echo htmlspecialchars($r['Empresa_seguro']); ?></strong>
            <span class="badge bg-<?php echo $r['prioridad'] === 'Primario' ? 'success' : 'secondary'; ?> ms-1">
                <?php echo htmlspecialchars($r['prioridad']); ?>
            </span>
            <div class="small text-muted">
                <?php echo htmlspecialchars($r['TIPO'] ?: 'Sin tipo'); ?>
                · Póliza: <?php echo htmlspecialchars($r['num_poliza'] ?: '—'); ?>
            </div>
        </div>
        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" title="Quitar"
                onclick="eliminarSeguroPaciente(<?php echo $id; ?>)">✕</button>
    </div>
    <div class="d-flex gap-3 mt-2 flex-wrap">
        <?php echo imgControl($id, 'frente',  $r['img_frente']); ?>
        <?php echo imgControl($id, 'reverso', $r['img_reverso']); ?>
    </div>
</div>
<?php endforeach; ?>
