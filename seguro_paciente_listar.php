<?php
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

if (!isset($_SESSION["rol"])) { http_response_code(403); exit; }

$idPac = (int)($_GET['id_paciente'] ?? 0);
if (!$idPac) { echo '<div class="text-muted small">Paciente no válido.</div>'; exit; }

$stmt = $conexion->prepare(
    "SELECT PS.id_paciente_seguro, PS.num_poliza, PS.prioridad,
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
?>
<div class="table-responsive">
<table class="table table-sm align-middle mb-0">
    <thead class="table-light">
        <tr>
            <th>Aseguradora</th>
            <th>Tipo</th>
            <th>Póliza</th>
            <th>Prioridad</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?php echo htmlspecialchars($r['Empresa_seguro']); ?></td>
            <td class="small text-muted"><?php echo htmlspecialchars($r['TIPO'] ?: '—'); ?></td>
            <td><?php echo htmlspecialchars($r['num_poliza'] ?: '—'); ?></td>
            <td>
                <span class="badge bg-<?php echo $r['prioridad'] === 'Primario' ? 'success' : 'secondary'; ?>">
                    <?php echo htmlspecialchars($r['prioridad']); ?>
                </span>
            </td>
            <td class="text-end">
                <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2"
                        title="Quitar"
                        onclick="eliminarSeguroPaciente(<?php echo (int)$r['id_paciente_seguro']; ?>)">✕</button>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
