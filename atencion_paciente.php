<?php
ob_start();
session_start();

// 1. IMPORTANTE: Debes incluir la conexión y funciones aquí también
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

// Verificación de sesión (copiada de tu archivo anterior)
if(!isset($_SESSION["rol"])){
    header("Location: break.php");
    exit();
}

// 2. Recoger el ID de la cita y validar que existe
if(!isset($_GET['idCita']) || empty($_GET['idCita'])){
    die("Error: No se recibió el ID de la cita.");
}

$idCita = $conexion->real_escape_string($_GET['idCita']);

// 3. Consulta para obtener los datos del paciente y su última medición
// He ajustado los nombres de tablas según lo que vi en tus capturas previas
$sql = "SELECT P.IDPACIENTE, P.NOMBRES, P.APELLIDOS, P.FECHANACIMIENTO, P.SEX, 
               A.FECHA_CITA,
               -- Intentamos traer peso y talla de la tabla AG_PACIENTE si ahí los guardas
               -- o déjalos vacíos si prefieres que la Dra. los llene desde cero
               '0' as PESO_PREVIO, 
               '0' as TALLA_PREVIA
        FROM AG_CITA A
        INNER JOIN AG_PACIENTE P ON A.IDPACIENTE = P.IDPACIENTE
        WHERE A.IDCITA = '$idCita'";

$res = $conexion->query($sql);

if (!$res || $res->num_rows == 0) {
    die("Error: No se encontró la cita especificada en la base de datos.");
}

$paciente = $res->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Atención de Paciente - SROSS</title>
    <link href="main.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <script src="js/jquery.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

	<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
	
</head>
<body class="bg-light">

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between">
            <h5 class="mb-0">Atención Nutricional: <?php echo $paciente['NOMBRES'] . " " . $paciente['APELLIDOS']; ?></h5>
            <span>Cita #<?php echo $idCita; ?></span>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4 bg-light p-3 border rounded">
				<div class="col-md-2">
                    <label class="form-label">Peso (kg)</label>
                    <input type="number" id="peso" class="form-control" step="0.1" oninput="calcularIMC()">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Talla (cm)</label>
                    <input type="number" id="talla" class="form-control" oninput="calcularIMC()">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">IMC</label>
                    <input type="text" id="imc" class="form-control bg-white" readonly>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estado</label>
                    <div id="estado_imc" class="badge p-2 d-block">---</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Elegir Plantilla de Informe</label>
                    <select id="selPlantilla" class="form-select" onchange="cargarPlantilla(this.value)">
                        <option value="">-- Seleccione Tipo de Nota --</option>
                        <?php
                        $plantillas = $conexion->query("SELECT id, nombre_plantilla FROM cat_plantillas_nutricion");
                        while($p = $plantillas->fetch_assoc()){
                            echo "<option value='{$p['id']}'>{$p['nombre_plantilla']}</option>";
                        }
                        ?>
                    </select>
                </div>
                
            </div>

            <div class="mb-3">
                <textarea id="editorInforme" name="informe" class="form-control" rows="15" placeholder="Cargue una plantilla para comenzar..."></textarea>
            </div>

            <div class="text-end">
                <a href="SCH_Calendar.php" class="btn btn-secondary">Regresar</a>
                	<button class="btn btn-success px-4" onclick="guardarAtencion()">
    					<i class="bi bi-file-earmark-check"></i> Guardar y Finalizar Atención
					</button>
				<button class="btn btn-outline-primary" onclick="imprimirInforme()">
    <i class="bi bi-printer"></i> Vista Previa e Impresión
</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Inicializar Summernote
    $('#editorInforme').summernote({
        placeholder: 'Cargue una plantilla o escriba la nota aquí...',
        tabsize: 2,
        height: 500,
        lang: 'es-ES',
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'underline', 'clear']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ]
    });
});

	function imprimirInforme() {
    const contenido = $('#editorInforme').summernote('code');
    
    // Abrimos una ventana nueva
    const ventana = window.open('', '_blank', 'height=800,width=900');
    
    ventana.document.write('<html><head><title>Informe Nutricional - SROSS</title>');
    // Importante: Volvemos a pasar el estilo de la plantilla para que la impresión se vea igual
    ventana.document.write('<style>body { font-family: Arial; padding: 40px; } @media print { .no-print { display: none; } }</style>');
    ventana.document.write('</head><body>');
    ventana.document.write(contenido); // Metemos el HTML del editor
    ventana.document.write('</body></html>');
    
    ventana.document.close();
    ventana.print(); // Dispara el cuadro de impresión del navegador
}
	
function guardarAtencion() {
    const contenido = $('#editorInforme').summernote('code');
    const idCita = "<?php echo $idCita; ?>";
    const peso = $('#peso').val();
    const talla = $('#talla').val();
    const imc = $('#imc').val();

    if (contenido.length < 20) {
        alert("El informe parece estar vacío. Por favor, complételo antes de guardar.");
        return;
    }

    if(confirm("¿Desea finalizar la atención y guardar el historial?")) {
        $.ajax({
            url: 'guardar_atencion.php',
            type: 'POST',
            data: {
                idCita: idCita,
                informe: contenido,
                peso: peso,
                talla: talla,
                imc: imc
            },
            success: function(res) {
                if(res == "OK") {
                    alert("Atención guardada con éxito.");
                    window.location.href = "SCH_Calendar.php"; // Regresamos al calendario
                } else {
                    alert("Error al guardar: " + res);
                }
            }
        });
    }
}
function cargarPlantilla(id) {
    if (!id) return;

    // 1. Datos dinámicos desde PHP y la pantalla
    const datosSesion = {
        nombrePaciente: "<?php echo $paciente['NOMBRES'] . ' ' . $paciente['APELLIDOS']; ?>",
        dobPaciente: "<?php echo date('d/m/Y', strtotime($paciente['FECHANACIMIENTO'])); ?>",

	
	
        // Tomamos el nombre del Dr. de la sesión (ajusta el nombre de la variable si es diferente)
        nombreDoctor: "<?php echo $_SESSION['username']; ?>", 
        fechaActual: "<?php echo date('d/m/Y'); ?>",
        practica: "SROSS Nutrition PLLC"
    };
	const fechaNac = new Date("<?php echo $paciente['FECHANACIMIENTO']; ?>T00:00:00");
		const dobFormateada = fechaNac.toLocaleDateString('en-US', { 
		    month: 'long', 
		    day: 'numeric', 
		    year: 'numeric' 
		});
	
    const imcActual = $('#imc').val() || "---";
    const pesoActual = $('#peso').val() || "---";
    const tallaActual = $('#talla').val() || "---";

    $.ajax({
        url: 'get_plantilla_html.php',
        type: 'GET',
        data: { id: id },
        success: function(respuesta) {
            let htmlFinal = respuesta;

            const reemplazos = {
                "{{fecha_actual}}": datosSesion.fechaActual,
                "{{paciente_nombre}}": datosSesion.nombrePaciente,
                "{{paciente_dob}}": dobFormateada,
                "{{antropometria}}": `Peso: ${pesoActual} kg, Talla: ${tallaActual} cm, IMC: ${imcActual}`,
                "{{firma_nombre}}": datosSesion.nombreDoctor, // <--- Aquí se pone el nombre del Dr. logueado
                "{{practica_nombre}}": datosSesion.practica,
                "{{doctor_nombre}}": datosSesion.nombreDoctor,
                "{{fecha_evaluacion}}": datosSesion.fechaActual
            };

            for (let tag in reemplazos) {
                let regex = new RegExp(tag.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g');
                htmlFinal = htmlFinal.replace(regex, reemplazos[tag]);
            }

            $('#editorInforme').summernote('code', htmlFinal);
        }
    });
}

function calcularIMC() {
    let peso = parseFloat($('#peso').val());
    let talla = parseFloat($('#talla').val());
    let imcField = $('#imc');
    let estadoField = $('#estado_imc');

    if (peso > 0 && talla > 0) {
        let tallaM = talla / 100;
        let imc = (peso / (tallaM * tallaM)).toFixed(2);
        imcField.val(imc);

        if (imc < 18.5) { estadoField.text("Bajo Peso").attr('class', 'badge p-2 d-block bg-info'); }
        else if (imc < 25) { estadoField.text("Normal").attr('class', 'badge p-2 d-block bg-success'); }
        else if (imc < 30) { estadoField.text("Sobrepeso").attr('class', 'badge p-2 d-block bg-warning text-dark'); }
        else { estadoField.text("Obesidad").attr('class', 'badge p-2 d-block bg-danger'); }
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>