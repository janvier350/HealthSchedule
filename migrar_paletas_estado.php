<?php
/**
 * migrar_paletas_estado.php
 * Sistema de PALETAS de colores de estados, por usuario.
 *   - paletas_estado          (paletas del sistema + personalizadas por usuario)
 *   - paleta_estado_colores   (colores fondo/letra por estado de cada paleta)
 *   - ADM_USUARIO.IDPALETA_ESTADO (paleta elegida por cada usuario)
 * Siembra 8 paletas del sistema (4 existentes + 4 nuevas).
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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Paletas de estados</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Crear sistema de paletas de colores (por usuario)</h4>
<p class="text-muted">Crea <code>paletas_estado</code>, <code>paleta_estado_colores</code> y
<code>ADM_USUARIO.IDPALETA_ESTADO</code>. Siembra 8 paletas del sistema. Idempotente.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar</button>
<a class="btn btn-link" href="gestionar_colores_estado.php">Volver</a></form></div></body></html>
    <?php
    exit;
}

$msgs = [];
$conexion->query("CREATE TABLE IF NOT EXISTS paletas_estado (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    es_sistema TINYINT(1) NOT NULL DEFAULT 0,
    id_usuario INT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (id_usuario),
    INDEX idx_sistema (es_sistema)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4")
    ? $msgs[]=['OK','Tabla paletas_estado lista'] : $msgs[]=['ERR',$conexion->error];

$conexion->query("CREATE TABLE IF NOT EXISTS paleta_estado_colores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_paleta INT NOT NULL,
    clave VARCHAR(40) NOT NULL,
    color VARCHAR(7) NOT NULL,
    text_color VARCHAR(7) NOT NULL DEFAULT '#ffffff',
    INDEX idx_paleta (id_paleta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4")
    ? $msgs[]=['OK','Tabla paleta_estado_colores lista'] : $msgs[]=['ERR',$conexion->error];

// Columna de selección por usuario
$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$tieneCol = (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='".$conexion->real_escape_string($dbName)."' AND TABLE_NAME='ADM_USUARIO' AND COLUMN_NAME='IDPALETA_ESTADO'")->fetch_assoc()['c']>0;
if (!$tieneCol) {
    $conexion->query("ALTER TABLE ADM_USUARIO ADD COLUMN IDPALETA_ESTADO INT NULL")
        ? $msgs[]=['NEW','Columna ADM_USUARIO.IDPALETA_ESTADO agregada'] : $msgs[]=['ERR','IDPALETA_ESTADO: '.$conexion->error];
}

// Definición de las 8 paletas del sistema: clave => [bg, tx]
$K='#212529'; $D='#ffffff';
$paletas = [
 'Profesional' => [
    'pendiente'=>['#3b4252',$D],'confirmada'=>['#6f42c1',$D],'atendida'=>['#28a745',$D],'cancelada'=>['#fd7e14',$D],
    'cancelacion_tardia'=>['#ffc107',$K],'cancelado_profesional'=>['#ff8a80',$K],'no_asistio'=>['#dc3545',$D],'default'=>['#007bff',$D]],
 'Suave (pastel)' => [
    'pendiente'=>['#5c6b7a',$D],'confirmada'=>['#b4a7d6',$K],'atendida'=>['#a8d5a2',$K],'cancelada'=>['#f6b26b',$K],
    'cancelacion_tardia'=>['#ffe599',$K],'cancelado_profesional'=>['#ea9999',$K],'no_asistio'=>['#e06666',$D],'default'=>['#9fc5e8',$K]],
 'Océano (frío)' => [
    'pendiente'=>['#34495e',$D],'confirmada'=>['#2980b9',$D],'atendida'=>['#16a085',$D],'cancelada'=>['#e67e22',$D],
    'cancelacion_tardia'=>['#f39c12',$K],'cancelado_profesional'=>['#c39bd3',$K],'no_asistio'=>['#c0392b',$D],'default'=>['#3498db',$D]],
 'Alto contraste' => [
    'pendiente'=>['#212529',$D],'confirmada'=>['#6610f2',$D],'atendida'=>['#198754',$D],'cancelada'=>['#fd7e14',$K],
    'cancelacion_tardia'=>['#ffca2c',$K],'cancelado_profesional'=>['#e35d6a',$D],'no_asistio'=>['#d00000',$D],'default'=>['#0d6efd',$D]],
 'Pastel Clínico' => [
    'pendiente'=>['#d0e1f9',$K],'confirmada'=>['#e2d4f0',$K],'atendida'=>['#d4eed1',$K],'cancelada'=>['#fde0c5',$K],
    'cancelacion_tardia'=>['#fff2cc',$K],'cancelado_profesional'=>['#fadadd',$K],'no_asistio'=>['#ffd1d1',$K],'default'=>['#eaeaea',$K]],
 'Tonos Tierra Suaves' => [
    'pendiente'=>['#c9d6df',$K],'confirmada'=>['#d9d1df',$K],'atendida'=>['#d0e5d2',$K],'cancelada'=>['#f5d7c4',$K],
    'cancelacion_tardia'=>['#fbe5b9',$K],'cancelado_profesional'=>['#eac8ca',$K],'no_asistio'=>['#f2c6c2',$K],'default'=>['#efebe9',$K]],
 'Brillo Pastel' => [
    'pendiente'=>['#bce3ff','#121212'],'confirmada'=>['#d8b4f8','#121212'],'atendida'=>['#aee6b9','#121212'],'cancelada'=>['#ffcba4','#121212'],
    'cancelacion_tardia'=>['#fff099','#121212'],'cancelado_profesional'=>['#ffc4d9','#121212'],'no_asistio'=>['#ffb3b3','#121212'],'default'=>['#c4f5f8','#121212']],
 'Minimalista Frío' => [
    'pendiente'=>['#e0f2fe','#1e293b'],'confirmada'=>['#e0e7ff','#1e293b'],'atendida'=>['#d1fae5','#1e293b'],'cancelada'=>['#ffedd5','#1e293b'],
    'cancelacion_tardia'=>['#fef08a','#1e293b'],'cancelado_profesional'=>['#fce7f3','#1e293b'],'no_asistio'=>['#fee2e2','#1e293b'],'default'=>['#f3f4f6','#1e293b']],
];

$chkP = $conexion->prepare("SELECT id FROM paletas_estado WHERE nombre=? AND es_sistema=1 LIMIT 1");
foreach ($paletas as $nombre => $colores) {
    $chkP->bind_param('s',$nombre); $chkP->execute();
    if ($chkP->get_result()->fetch_assoc()) { $msgs[]=['OK',"Ya existía: $nombre"]; continue; }
    $ins = $conexion->prepare("INSERT INTO paletas_estado (nombre, es_sistema, id_usuario) VALUES (?, 1, NULL)");
    $ins->bind_param('s',$nombre);
    if (!$ins->execute()) { $msgs[]=['ERR',"$nombre: ".$ins->error]; $ins->close(); continue; }
    $idPal = (int)$conexion->insert_id; $ins->close();
    $insC = $conexion->prepare("INSERT INTO paleta_estado_colores (id_paleta, clave, color, text_color) VALUES (?,?,?,?)");
    foreach ($colores as $clave=>$par) {
        $insC->bind_param('isss',$idPal,$clave,$par[0],$par[1]); $insC->execute();
    }
    $insC->close();
    $msgs[]=['NEW',"Creada paleta: $nombre"];
}
$chkP->close();

$cls=['NEW'=>'success','OK'=>'secondary','ERR'=>'danger'];
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Resultado</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="p-4"><div class="container" style="max-width:680px;"><h4>Resultado — Paletas de estados</h4><ul class="list-group">';
foreach ($msgs as $m) echo '<li class="list-group-item"><span class="badge bg-'.($cls[$m[0]]??'secondary').' me-2">'.htmlspecialchars($m[0]).'</span>'.htmlspecialchars($m[1]).'</li>';
echo '</ul><a class="btn btn-primary mt-3" href="gestionar_colores_estado.php">Ir a Colores de Estados</a></div></body></html>';
