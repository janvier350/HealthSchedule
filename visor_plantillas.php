<?php
ob_start();
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once("class/CalculadoraNutricional.php");


// 1. Conexión usando tu función personalizada
$conexion = conectarse();

// 2. Verificación de sesión según tu estándar
if(!isset($_SESSION["rol"])){
    header("Location: break.php");
    exit();
} else {
    $now = time();
    if ($now > $_SESSION['expire']) {
        session_destroy();
        header("Location: expirada.php");
        exit();
    }
}

// 3. Lógica del visor
$id_seleccionado = isset($_GET['id']) ? (int)$_GET['id'] : 1;

// Obtener lista para el selector
$query_lista = "SELECT id, nombre_plantilla FROM cat_plantillas_nutricion ORDER BY id ASC";
$todas = $conexion->query($query_lista);

// Obtener la plantilla específica (usando tu variable $conexion)
$stmt = $conexion->prepare("SELECT * FROM cat_plantillas_nutricion WHERE id = ?");
$stmt->bind_param("i", $id_seleccionado);
$stmt->execute();
$plantilla = $stmt->get_result()->fetch_assoc();

// Datos demo para el reemplazo de etiquetas
$datos_demo = [
    // ... (mantén los campos anteriores)

	'fecha_actual' => date('d/m/Y'),
    'estatura' => $talla_paciente . " cm",
    'peso' => round($peso_paciente * 2.204, 1), // Convertir a lbs para la plantilla
    'imc_valor' => $imc,
    'imc_clase' => $clasificacion,
    'peso_ideal' => $peso_ideal_lbs,
    'energia_kcal_dia' => $calorias,
    'prot_necesidad' => round($peso_paciente * 0.8), // 0.8g/kg estándar
    'prot_ratio' => '0.8',
    'fluidos_totales' => $peso_paciente * 35, // 35ml/kg estándar
    'factor_actividad' => '1.2 (Sedentario)',
	
    'fecha_actual' => date('F d, Y'),
    'doctor_nombre' => 'DR. ROBERTO ANCHUNDIA',
    'practica_nombre' => 'BUADNET CLINIC',
    'direccion_1' => 'Av. Principal 123',
    'direccion_2' => 'Edificio Profesional - Of. 402',
    'ciudad' => 'Guayaquil',
    'estado' => 'Guayas',
    'codigo_postal' => '090101',
    'titulo_doctor' => 'Dr.',
    'apellido_doctor' => 'Anchundia',
    'paciente_nombre' => 'JUAN PÉREZ ARMENDÁRIZ',
    'paciente_dob' => '12/08/1990',
    'diagnostico_referencia' => 'Diabetes Mellitus Tipo 2',
    'fecha_evaluacion' => date('d/m/Y'),
    'antropometria' => 'Peso: 85kg, Talla: 1.75m, IMC: 27.7.',
    'bioquimica' => 'HbA1c: 7.2%.',
    'hallazgos_fisicos' => 'Sin edemas presentes.',
    'historial_cliente' => 'Sedentarismo.',
    'diagnostico_nutricional' => 'Ingesta excesiva de carbohidratos.',
    'intervencion' => 'Educación en conteo de carbohidratos.',
    'monitoreo' => 'Control en 15 días.',
    'firma_nombre' => 'DR. JAVIER [APELLIDO]', // Tu nombre o el del Dr.
    'firma_credenciales' => 'Registered Dietitian Nutritionist, MS, RDN, LDN'
];

function renderizar($html, $datos) {
    foreach ($datos as $tag => $val) {
        $html = str_replace("{{" . $tag . "}}", $val, $html);
    }
    return $html;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Visor de Plantillas Kalix | Buadnet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card-plantilla { min-height: 500px; border: 1px solid #ddd; }
        .sidebar-menu { height: 100vh; overflow-y: auto; }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 bg-white shadow-sm sidebar-menu p-4">
                <h5 class="text-primary mb-4">Plantillas Nutrición</h5>
                <div class="list-group">
                    <?php while($row = $todas->fetch_assoc()): ?>
                        <a href="?id=<?php echo $row['id']; ?>" 
                           class="list-group-item list-group-item-action <?php echo ($id_seleccionado == $row['id']) ? 'active' : ''; ?>">
                            <?php echo $row['nombre_plantilla']; ?>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>
            
            <div class="col-md-9 p-5">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-uppercase">Vista Previa: <?php echo $plantilla['nombre_plantilla'] ?? 'Seleccione'; ?></span>
                        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">Imprimir</button>
                    </div>
                    <div class="card-body bg-white p-5 card-plantilla">
                        <?php 
                        if($plantilla) {
                            echo renderizar($plantilla['cuerpo_html'], $datos_demo); 
                        } else {
                            echo '<div class="alert alert-warning">No hay datos en la tabla cat_plantillas_nutricion.</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>