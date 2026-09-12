<?php
/**
 * migrar_facturas_schema.php
 * Crea el esquema de facturación (modelo Kalix):
 *   facturas         — cabecera (1 por cita normalmente), con total/pagado/saldo
 *   factura_detalle  — líneas (bill items) de cada factura
 *   factura_pagos    — pagos/abonos registrados contra una factura
 * Idempotente (CREATE TABLE IF NOT EXISTS). SISTEMA-only, POST-driven.
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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Esquema de facturación</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Crear esquema de facturación</h4>
<p class="text-muted">Crea las tablas <code>facturas</code>, <code>factura_detalle</code> y
<code>factura_pagos</code> (modelo Kalix). Idempotente.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar</button>
<a class="btn btn-link" href="cuentas_por_cobrar.php">Volver</a></form></div></body></html>
    <?php
    exit;
}

$msgs = [];

$sqls = [
'facturas' => "CREATE TABLE IF NOT EXISTS facturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    billing_number VARCHAR(40) NULL,
    IDPACIENTE INT NULL,
    kalix_client_id VARCHAR(40) NULL,
    client_name VARCHAR(200) NULL,
    IDCITA INT NULL,
    kalix_appointment_id VARCHAR(40) NULL,
    fecha DATE NULL,
    fecha_vencimiento DATE NULL,
    status VARCHAR(40) NULL,
    descripcion VARCHAR(500) NULL,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    contract DECIMAL(10,2) NULL,
    pagado DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    impuesto DECIMAL(10,2) NULL,
    saldo DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    notas TEXT NULL,
    origen VARCHAR(20) NOT NULL DEFAULT 'app',
    estado TINYINT(1) NOT NULL DEFAULT 1,
    creado_por INT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_billing (billing_number),
    INDEX idx_paciente (IDPACIENTE),
    INDEX idx_saldo (saldo),
    INDEX idx_fecha (fecha),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

'factura_detalle' => "CREATE TABLE IF NOT EXISTS factura_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_factura INT NOT NULL,
    billing_number VARCHAR(40) NULL,
    id_bill_item INT NULL,
    code VARCHAR(20) NULL,
    description VARCHAR(500) NULL,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    quantity INT NOT NULL DEFAULT 1,
    importe DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    INDEX idx_factura (id_factura),
    INDEX idx_billing (billing_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

'factura_pagos' => "CREATE TABLE IF NOT EXISTS factura_pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_factura INT NOT NULL,
    fecha DATE NULL,
    monto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    metodo VARCHAR(30) NULL,
    referencia VARCHAR(100) NULL,
    registrado_por INT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_factura (id_factura)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

foreach ($sqls as $t => $sql) {
    $msgs[] = $conexion->query($sql) ? ['OK', "Tabla lista: $t"] : ['ERR', "$t: ".$conexion->error];
}

$cls = ['OK'=>'success','ERR'=>'danger'];
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Resultado</title>'
   . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>'
   . '<body class="p-4"><div class="container" style="max-width:640px;"><h4>Resultado — Esquema de facturación</h4><ul class="list-group">';
foreach ($msgs as $m) echo '<li class="list-group-item"><span class="badge bg-'.($cls[$m[0]]??'secondary').' me-2">'.htmlspecialchars($m[0]).'</span>'.htmlspecialchars($m[1]).'</li>';
echo '</ul><a class="btn btn-primary mt-3" href="importar_facturas_kalix.php">Ir a importar facturas de Kalix</a></div></body></html>';
