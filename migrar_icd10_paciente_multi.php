<?php
/**
 * migrar_icd10_paciente_multi.php
 * Permite VARIOS diagnósticos ICD-10 por paciente.
 *  - Crea la tabla paciente_icd10 (relación paciente ↔ ENFE_DIAG_COD).
 *  - Copia el ICD-10 principal actual (AG_PACIENTE.IDICD10) a la nueva tabla.
 * Idempotente. SISTEMA-only, POST-driven.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
if (!isset($_SESSION["rol"]) || strtoupper($_SESSION["rol"]) !== 'SISTEMA') {
    http_response_code(403); exit('Acceso restringido: sólo SISTEMA.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>ICD-10 múltiple por paciente</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Diagnósticos ICD-10 múltiples por paciente</h4>
<p class="text-muted">Crea la tabla <code>paciente_icd10</code> y copia el ICD-10 principal actual
(<code>AG_PACIENTE.IDICD10</code>) a la nueva tabla. Idempotente.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar</button>
<a class="btn btn-link" href="home.php">Volver</a></form></div></body></html>
    <?php
    exit;
}
$msgs = [];
$ok = $conexion->query("CREATE TABLE IF NOT EXISTS paciente_icd10 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    IDPACIENTE INT NOT NULL,
    ID_ENFE_DIAG_COD INT NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_usuario INT NULL,
    UNIQUE KEY uq_pac_icd (IDPACIENTE, ID_ENFE_DIAG_COD),
    INDEX idx_pac (IDPACIENTE)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$msgs[] = $ok ? ['OK','Tabla paciente_icd10 lista.'] : ['ERR',$conexion->error];

// Copiar el ICD-10 principal existente (si la columna existe) a la nueva tabla
$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$tieneCol = (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='".$conexion->real_escape_string($dbName)."' AND TABLE_NAME='AG_PACIENTE' AND COLUMN_NAME='IDICD10'")->fetch_assoc()['c']>0;
if ($tieneCol) {
    $r = $conexion->query("INSERT IGNORE INTO paciente_icd10 (IDPACIENTE, ID_ENFE_DIAG_COD)
        SELECT IDPACIENTE, IDICD10 FROM AG_PACIENTE
        WHERE IDICD10 IS NOT NULL AND IDICD10 > 0");
    $msgs[] = $r ? ['NEW','Códigos principales existentes copiados: '.$conexion->affected_rows.'.'] : ['ERR','Copia: '.$conexion->error];
} else {
    $msgs[] = ['OK','No existe AG_PACIENTE.IDICD10; nada que copiar.'];
}
$cls=['NEW'=>'success','OK'=>'secondary','ERR'=>'danger'];
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Resultado</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="p-4"><div class="container" style="max-width:640px;"><h4>Resultado</h4><ul class="list-group">';
foreach ($msgs as $m) echo '<li class="list-group-item"><span class="badge bg-'.($cls[$m[0]]??'secondary').' me-2">'.htmlspecialchars($m[0]).'</span>'.htmlspecialchars($m[1]).'</li>';
echo '</ul><a class="btn btn-primary mt-3" href="home.php">Volver</a></div></body></html>';
