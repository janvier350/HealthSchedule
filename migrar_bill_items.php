<?php
/**
 * migrar_bill_items.php
 * Crea la tabla `bill_items` (catálogo de servicios facturables, tipo Kalix
 * "Bill Items": CPT/HCPC, precio unitario, unidades, POS, modifiers) y
 * siembra los 6 códigos MNT que usa SRoss Nutrition.
 *
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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Migrar Bill Items</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Crear catálogo de Bill Items (servicios facturables)</h4>
<p class="text-muted">Crea la tabla <code>bill_items</code> y siembra los 6 códigos MNT
(97802 / 97803 / 97804 en POS 11 y 12) a $50/unidad. Idempotente.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar</button>
<a class="btn btn-link" href="bill_items_admin.php">Volver</a></form></div></body></html>
    <?php
    exit;
}

$msgs = [];

// 1) Crear tabla si no existe
$sqlTabla = "CREATE TABLE IF NOT EXISTS bill_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NULL,
    pos VARCHAR(5) NULL,
    description VARCHAR(500) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    default_units INT NOT NULL DEFAULT 1,
    add_tax TINYINT(1) NOT NULL DEFAULT 0,
    tax_rate DECIMAL(5,2) NULL,
    contract_amount DECIMAL(10,2) NULL,
    modifiers VARCHAR(100) NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$msgs[] = $conexion->query($sqlTabla) ? ['NEW','Tabla bill_items lista'] : ['ERR','Tabla: '.$conexion->error];

// 2) Sembrar los 6 códigos MNT (upsert por code+pos)
$items = [
    ['97802','11','97802: Medical nutrition therapy; initial assessment and intervention, individual, face-to-face with the patient, each 15 minutes', 50.00, 4],
    ['97802','12','97802: Medical nutrition therapy; initial assessment and intervention, individual, face-to-face with the patient, each 15 minutes', 50.00, 4],
    ['97803','11','97803: Medical nutrition therapy; re-assessment and intervention, individual, face-to-face with the patient, each 15 minutes', 50.00, 4],
    ['97803','12','97803: Medical nutrition therapy; re-assessment and intervention, individual, face-to-face with the patient, each 15 minutes', 50.00, 4],
    ['97804','11','97804: Medical nutrition therapy; group (2 or more individual(s)), each 30 minutes', 50.00, 4],
    ['97804','12','97804: Medical nutrition therapy; group (2 or more individual(s)), each 30 minutes', 50.00, 4],
];

$chk = $conexion->prepare("SELECT id FROM bill_items WHERE code = ? AND pos = ? LIMIT 1");
foreach ($items as $it) {
    [$code,$pos,$desc,$price,$units] = $it;
    $chk->bind_param('ss', $code, $pos);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    if ($row) {
        $up = $conexion->prepare("UPDATE bill_items SET description=?, unit_price=?, default_units=? WHERE id=?");
        $up->bind_param('sdii', $desc, $price, $units, $row['id']);
        $msgs[] = $up->execute() ? ['OK',"Ya existía: $code (POS $pos)"] : ['ERR',"$code: ".$up->error];
        $up->close();
    } else {
        $ins = $conexion->prepare("INSERT INTO bill_items (code,pos,description,unit_price,default_units,estado) VALUES (?,?,?,?,?,1)");
        $ins->bind_param('sssdi', $code, $pos, $desc, $price, $units);
        $msgs[] = $ins->execute() ? ['NEW',"Creado: $code (POS $pos)"] : ['ERR',"$code: ".$ins->error];
        $ins->close();
    }
}
$chk->close();

$cls = ['NEW'=>'success','OK'=>'secondary','ERR'=>'danger'];
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Resultado</title>'
   . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>'
   . '<body class="p-4"><div class="container" style="max-width:720px;"><h4>Resultado — Bill Items</h4><ul class="list-group">';
foreach ($msgs as $m) echo '<li class="list-group-item"><span class="badge bg-'.($cls[$m[0]]??'secondary').' me-2">'.htmlspecialchars($m[0]).'</span>'.htmlspecialchars($m[1]).'</li>';
echo '</ul><a class="btn btn-primary mt-3" href="bill_items_admin.php">Ir a Bill Items</a></div></body></html>';
