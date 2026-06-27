<?php
ob_start();
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

if(!isset($_SESSION["rol"])){
    header("Location: break.php");
    exit();
}

// Filtros
$busqueda  = isset($_GET['q'])      ? $conexion->real_escape_string(trim($_GET['q'])) : '';
$fechaDesde = isset($_GET['desde']) ? $conexion->real_escape_string($_GET['desde'])   : '';
$fechaHasta = isset($_GET['hasta']) ? $conexion->real_escape_string($_GET['hasta'])   : '';

$where = "WHERE H.ESTADO = 'A'";
if($busqueda)   $where .= " AND (P.NOMBRES LIKE '%$busqueda%' OR P.APELLIDOS LIKE '%$busqueda%' OR P.CEDULA LIKE '%$busqueda%')";
if($fechaDesde) $where .= " AND C.FECHA_CITA >= '$fechaDesde'";
if($fechaHasta) $where .= " AND C.FECHA_CITA <= '$fechaHasta'";

$sql = "SELECT
            H.IDHISTORIAL, H.IDCITA, H.FECHA_REGISTRO, H.PESO, H.TALLA, H.IMC,
            H.CONTENIDO_INFORME,
            P.NOMBRES, P.APELLIDOS, P.CEDULA, P.TELEFONO,
            C.FECHA_CITA, C.HORA_INICIO,
            TC.NOMBRES AS TIPO_CONSULTA,
            D.NOMBRES AS DOC_NOMBRES, D.APELLIDOS AS DOC_APELLIDOS
        FROM AG_HISTORIAL H
        INNER JOIN AG_CITA C       ON H.IDCITA        = C.IDCITA
        INNER JOIN AG_PACIENTE P   ON C.IDPACIENTE    = P.IDPACIENTE
        LEFT  JOIN AG_TIPOCONSULTA TC ON C.IDTIPOCONSULTA = TC.IDTIPOCONSULTA
        LEFT  JOIN ADM_DOCTOR D    ON C.IDDOCTOR      = D.IDDOCTOR
        $where
        ORDER BY H.FECHA_REGISTRO DESC";

$result = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Atenciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="main.css" rel="stylesheet">
    <script src="js/jquery.min.js"></script>
</head>
<body class="bg-light">

<div class="container-fluid mt-4 mb-5">
    <div class="card shadow">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-clipboard2-pulse me-2"></i>Historial de Atenciones</h5>
            <a href="SCH_Calendar.php" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left"></i> Calendario
            </a>
        </div>

        <div class="card-body">

            <!-- FILTROS -->
            <form method="GET" class="row g-2 mb-4 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Buscar paciente o cédula</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" class="form-control"
                               placeholder="Nombre o cédula..."
                               value="<?php echo htmlspecialchars($busqueda); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Desde</label>
                    <input type="date" name="desde" class="form-control"
                           value="<?php echo htmlspecialchars($fechaDesde); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="hasta" class="form-control"
                           value="<?php echo htmlspecialchars($fechaHasta); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="historial_atenciones.php" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </a>
                </div>
            </form>

            <!-- TABLA -->
            <?php if(!$result || $result->num_rows === 0): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>No se encontraron atenciones con los filtros aplicados.
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Paciente</th>
                            <th>Cédula</th>
                            <th>Fecha Cita</th>
                            <th>Tipo Consulta</th>
                            <th>Doctor</th>
                            <th class="text-center">Peso (kg)</th>
                            <th class="text-center">Talla (cm)</th>
                            <th class="text-center">IMC</th>
                            <th class="text-center">Fecha Registro</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $n = 1;
                    while($h = $result->fetch_assoc()):
                        // Estado IMC
                        $imc = (float)$h['IMC'];
                        if     ($imc < 18.5) { $imcBadge = 'bg-info';    $imcLabel = 'Bajo Peso'; }
                        elseif ($imc < 25)   { $imcBadge = 'bg-success'; $imcLabel = 'Normal'; }
                        elseif ($imc < 30)   { $imcBadge = 'bg-warning text-dark'; $imcLabel = 'Sobrepeso'; }
                        else                 { $imcBadge = 'bg-danger';  $imcLabel = 'Obesidad'; }
                    ?>
                        <tr>
                            <td><?php echo $n++; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($h['NOMBRES'].' '.$h['APELLIDOS']); ?></strong><br>
                                <small class="text-muted"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($h['TELEFONO']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($h['CEDULA']); ?></td>
                            <td>
                                <?php echo date('d/m/Y', strtotime($h['FECHA_CITA'])); ?><br>
                                <small class="text-muted"><?php echo substr($h['HORA_INICIO'],0,5); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($h['TIPO_CONSULTA'] ?? '—'); ?></td>
                            <td>
                                <?php
                                $docN = trim($h['DOC_NOMBRES'].' '.$h['DOC_APELLIDOS']);
                                echo htmlspecialchars($docN ?: '—');
                                ?>
                            </td>
                            <td class="text-center"><?php echo $h['PESO'] ? number_format($h['PESO'],1) : '—'; ?></td>
                            <td class="text-center"><?php echo $h['TALLA'] ? number_format($h['TALLA'],1) : '—'; ?></td>
                            <td class="text-center">
                                <?php if($imc > 0): ?>
                                    <span class="badge <?php echo $imcBadge; ?>">
                                        <?php echo number_format($imc,2); ?><br>
                                        <small><?php echo $imcLabel; ?></small>
                                    </span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td class="text-center">
                                <small><?php echo date('d/m/Y H:i', strtotime($h['FECHA_REGISTRO'])); ?></small>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary"
                                        onclick="verInforme(<?php echo $h['IDHISTORIAL']; ?>)"
                                        title="Ver informe">
                                    <i class="bi bi-file-earmark-text"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary"
                                        onclick="imprimirInforme(<?php echo $h['IDHISTORIAL']; ?>)"
                                        title="Imprimir">
                                    <i class="bi bi-printer"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

        </div><!-- card-body -->
    </div><!-- card -->
</div>

<!-- MODAL VER INFORME -->
<div class="modal fade" id="modalInforme" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-file-earmark-text me-2"></i>Informe de Atención</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalInformeBody" style="min-height:400px;">
                <div class="text-center p-4"><div class="spinner-border text-primary"></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btnImprimirModal" onclick="imprimirDesdeModal()">
                    <i class="bi bi-printer"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const informesCache = {};

function verInforme(idHistorial){
    $('#modalInformeBody').html('<div class="text-center p-4"><div class="spinner-border text-primary"></div></div>');
    const modal = new bootstrap.Modal(document.getElementById('modalInforme'));
    modal.show();

    if(informesCache[idHistorial]){
        $('#modalInformeBody').html(informesCache[idHistorial]);
        return;
    }

    $.get('get_informe_html.php', { id: idHistorial }, function(html){
        informesCache[idHistorial] = html;
        $('#modalInformeBody').html(html);
    }).fail(function(){
        $('#modalInformeBody').html('<div class="alert alert-danger">Error al cargar el informe.</div>');
    });
}

function imprimirDesdeModal(){
    const contenido = $('#modalInformeBody').html();
    const ventana = window.open('','_blank','height=800,width=900');
    ventana.document.write(`<html><head><title>Informe</title>
        <style>body{font-family:Arial,sans-serif;padding:40px;color:#333;}</style>
        </head><body>${contenido}</body></html>`);
    ventana.document.close();
    setTimeout(()=>ventana.print(), 500);
}

function imprimirInforme(idHistorial){
    $.get('get_informe_html.php', { id: idHistorial }, function(html){
        const ventana = window.open('','_blank','height=800,width=900');
        ventana.document.write(`<html><head><title>Informe</title>
            <style>body{font-family:Arial,sans-serif;padding:40px;color:#333;}</style>
            </head><body>${html}</body></html>`);
        ventana.document.close();
        setTimeout(()=>ventana.print(), 500);
    });
}
</script>
</body>
</html>
