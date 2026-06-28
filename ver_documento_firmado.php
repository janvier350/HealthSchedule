<?php
ob_start();
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

if (!isset($_SESSION["rol"])) { header("Location: break.php"); exit(); }

$id = (int)($_GET['id'] ?? 0);
$e = null;
if ($id) {
    $stmt = $conexion->prepare(
        "SELECT e.*, d.titulo, d.contenido,
                CONCAT(p.NOMBRES,' ',p.APELLIDOS) AS paciente, p.CEDULA
           FROM documento_envio e
           INNER JOIN documentos  d ON d.id_documento = e.id_documento
           INNER JOIN AG_PACIENTE p ON p.IDPACIENTE   = e.IDPACIENTE
          WHERE e.id_envio = ? LIMIT 1"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $e = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
$firmado = $e && $e['estado'] === 'Firmado';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Documento firmado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background:#f4f6f9; }
        .doc-wrap { max-width:780px; margin:24px auto; }
        .doc-content { border:1px solid #eee; border-radius:6px; padding:16px; background:#fff; }
        .firma-box { border:1px solid #ddd; border-radius:6px; padding:10px; background:#fff; display:inline-block; }
        @media print { .no-print { display:none !important; } body { background:#fff; } }
    </style>
</head>
<body>
<div class="doc-wrap">
    <?php if (!$e): ?>
        <div class="alert alert-danger">Documento no encontrado.</div>
        <a href="documentos_enviados.php" class="btn btn-secondary">Volver</a>
    <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mb-3 no-print">
            <a href="documentos_enviados.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
            <button onclick="window.print()" class="btn btn-outline-primary btn-sm"><i class="bi bi-printer"></i> Imprimir</button>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h4><?php echo htmlspecialchars($e['titulo']); ?></h4>
                <p class="text-muted mb-1">
                    <strong>Paciente:</strong> <?php echo htmlspecialchars($e['paciente']); ?>
                    <?php if (!empty($e['CEDULA'])): ?> · CI: <?php echo htmlspecialchars($e['CEDULA']); ?><?php endif; ?>
                </p>
                <p class="mb-3">
                    <strong>Estado:</strong>
                    <?php if ($firmado): ?>
                        <span class="badge bg-success">Firmado</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">Pendiente</span>
                    <?php endif; ?>
                    · Enviado: <?php echo $e['fecha_envio'] ? date('d/m/Y H:i', strtotime($e['fecha_envio'])) : '—'; ?>
                </p>

                <h6 class="text-muted">Contenido del documento</h6>
                <div class="doc-content mb-4"><?php echo $e['contenido']; ?></div>

                <h6 class="text-muted">Firma</h6>
                <?php if ($firmado && !empty($e['firma_img'])): ?>
                    <div class="firma-box mb-2">
                        <img src="<?php echo htmlspecialchars($e['firma_img']); ?>" alt="Firma" style="max-width:100%;height:auto;max-height:200px;">
                    </div>
                    <div class="small text-muted">
                        Firmado por <strong><?php echo htmlspecialchars($e['firmado_por'] ?: $e['paciente']); ?></strong>
                        el <?php echo $e['fecha_firma'] ? date('d/m/Y H:i', strtotime($e['fecha_firma'])) : '—'; ?>
                        <?php if (!empty($e['ip_firma'])): ?> · IP: <?php echo htmlspecialchars($e['ip_firma']); ?><?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        Aún no firmado. Código de acceso: <strong><?php echo htmlspecialchars($e['codigo']); ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
