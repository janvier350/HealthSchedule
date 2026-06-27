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

if(!isset($_GET['idCita']) || empty($_GET['idCita'])){
    die("Error: No se recibió el ID de la cita.");
}

$idCita = $conexion->real_escape_string($_GET['idCita']);

// Query expandida: trae doctor, agencia y tipo de consulta
$sql = "SELECT
            P.IDPACIENTE, P.NOMBRES, P.APELLIDOS, P.FECHANACIMIENTO, P.SEX,
            P.EMAIL, P.TELEFONO, P.CEDULA, P.ADDRESS,
            A.FECHA_CITA, A.HORA_INICIO, A.IDDOCTOR,
            D.NOMBRES  AS DOC_NOMBRES,
            D.APELLIDOS AS DOC_APELLIDOS,
            D.ESPECIALIDAD,
            AG.DESCRIPCION AS AGENCIA_NOMBRE,
            AG.DIRECCION  AS AGENCIA_DIRECCION,
            AG.TELEFONO   AS AGENCIA_TEL,
            TC.NOMBRES    AS TIPO_CONSULTA
        FROM AG_CITA A
        INNER JOIN AG_PACIENTE     P  ON A.IDPACIENTE      = P.IDPACIENTE
        LEFT  JOIN ADM_DOCTOR      D  ON A.IDDOCTOR         = D.IDDOCTOR
        LEFT  JOIN ADM_AGENCIA     AG ON AG.IDAGENCIA        = 1
        LEFT  JOIN AG_TIPOCONSULTA TC ON A.IDTIPOCONSULTA   = TC.IDTIPOCONSULTA
        WHERE A.IDCITA = '$idCita'";

$res = $conexion->query($sql);
if (!$res || $res->num_rows == 0) {
    die("Error: No se encontró la cita especificada.");
}
$d = $res->fetch_assoc();

// Nombre completo del doctor que atiende (sesión) y del asignado a la cita
$sessionNombres   = $_SESSION['nombres']   ?? '';
$sessionApellidos = $_SESSION['apellidos'] ?? '';
$docNombreCompleto = trim($d['DOC_NOMBRES'] . ' ' . $d['DOC_APELLIDOS']);
// Si el doctor de sesión es el mismo que el de la cita úsalo, si no, usa el de la cita
$doctorAtiende = ($sessionNombres || $sessionApellidos)
    ? trim($sessionNombres . ' ' . $sessionApellidos)
    : $docNombreCompleto;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Atención - <?php echo htmlspecialchars($d['NOMBRES'].' '.$d['APELLIDOS']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <link href="main.css" rel="stylesheet">
    <script src="js/jquery.min.js"></script>
    <style>
        .unit-toggle .btn { padding: 2px 8px; font-size: 0.78rem; }
        .medicion-group { display: flex; gap: 6px; align-items: center; }
        .medicion-group input { max-width: 100px; }
    </style>
</head>
<body class="bg-light">
<div class="container mt-4 mb-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-person-lines-fill me-2"></i>
                Atención Nutricional: <?php echo htmlspecialchars($d['NOMBRES'].' '.$d['APELLIDOS']); ?>
            </h5>
            <span class="badge bg-light text-primary fs-6">Cita #<?php echo $idCita; ?></span>
        </div>

        <div class="card-body">

            <!-- ── MEDICIONES ──────────────────────────────────────────── -->
            <div class="row g-3 mb-4 bg-light p-3 border rounded align-items-end">

                <!-- PESO -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Peso</label>
                    <div class="medicion-group">
                        <input type="number" id="peso" class="form-control" step="0.1"
                               placeholder="0.0" oninput="calcularIMC()">
                        <div class="btn-group unit-toggle" role="group">
                            <input type="radio" class="btn-check" name="unidadPeso" id="uKg" value="kg" checked onchange="calcularIMC()">
                            <label class="btn btn-outline-secondary" for="uKg">kg</label>
                            <input type="radio" class="btn-check" name="unidadPeso" id="uLbs" value="lbs" onchange="calcularIMC()">
                            <label class="btn btn-outline-secondary" for="uLbs">lbs</label>
                        </div>
                    </div>
                </div>

                <!-- TALLA -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Talla</label>
                    <div class="medicion-group">
                        <input type="number" id="talla" class="form-control" step="0.1"
                               placeholder="0.0" oninput="calcularIMC()">
                        <div class="btn-group unit-toggle" role="group">
                            <input type="radio" class="btn-check" name="unidadTalla" id="uCm" value="cm" checked onchange="calcularIMC()">
                            <label class="btn btn-outline-secondary" for="uCm">cm</label>
                            <input type="radio" class="btn-check" name="unidadTalla" id="uM" value="m" onchange="calcularIMC()">
                            <label class="btn btn-outline-secondary" for="uM">m</label>
                        </div>
                    </div>
                </div>

                <!-- IMC -->
                <div class="col-md-2">
                    <label class="form-label fw-bold">IMC</label>
                    <input type="text" id="imc" class="form-control bg-white fw-bold" readonly>
                </div>

                <!-- ESTADO IMC -->
                <div class="col-md-2">
                    <label class="form-label">Estado</label>
                    <div id="estado_imc" class="badge p-2 d-block fs-6">---</div>
                </div>

                <!-- PLANTILLA -->
                <div class="col-md-2">
                    <label class="form-label fw-bold">Plantilla de Informe</label>
                    <select id="selPlantilla" class="form-select" onchange="cargarPlantilla(this.value)">
                        <option value="">-- Seleccione Tipo de Nota --</option>
                        <?php
                        $plantillas = $conexion->query(
                            "SELECT id, nombre_plantilla FROM cat_plantillas_nutricion ORDER BY categoria, nombre_plantilla"
                        );
                        while($p = $plantillas->fetch_assoc()):
                        ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nombre_plantilla']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <!-- ── FIN MEDICIONES ──────────────────────────────────────── -->

            <!-- EDITOR -->
            <div class="mb-3">
                <textarea id="editorInforme" name="informe"></textarea>
            </div>

            <!-- BOTONES -->
            <div class="d-flex justify-content-between align-items-center">
                <a href="SCH_Calendar.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Regresar
                </a>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" onclick="imprimirInforme()">
                        <i class="bi bi-printer"></i> Vista Previa e Impresión
                    </button>
                    <button class="btn btn-success px-4" onclick="guardarAtencion()">
                        <i class="bi bi-file-earmark-check"></i> Guardar y Finalizar Atención
                    </button>
                </div>
            </div>

        </div><!-- card-body -->
    </div><!-- card -->
</div><!-- container -->

<!-- ── DATOS PHP → JS (sin exponer en HTML) ──────────────────────────── -->
<script>
const DATOS_CITA = {
    idCita:          "<?php echo $idCita; ?>",
    // Paciente
    pacienteNombre:  "<?php echo addslashes(htmlspecialchars($d['NOMBRES'].' '.$d['APELLIDOS'])); ?>",
    pacienteDOB:     "<?php echo $d['FECHANACIMIENTO']; ?>",
    pacienteEmail:   "<?php echo addslashes($d['EMAIL']); ?>",
    pacienteTel:     "<?php echo addslashes($d['TELEFONO']); ?>",
    pacienteCedula:  "<?php echo addslashes($d['CEDULA']); ?>",
    // Doctor de la cita
    docNombre:       "<?php echo addslashes($docNombreCompleto); ?>",
    docApellido:     "<?php echo addslashes($d['DOC_APELLIDOS']); ?>",
    docEspecialidad: "<?php echo addslashes($d['ESPECIALIDAD']); ?>",
    // Doctor que atiende (sesión)
    atiendNombre:    "<?php echo addslashes($doctorAtiende); ?>",
    atiendApellido:  "<?php echo addslashes($sessionApellidos); ?>",
    // Agencia/clínica
    agenciaNombre:   "<?php echo addslashes($d['AGENCIA_NOMBRE']); ?>",
    agenciaDirec:    "<?php echo addslashes($d['AGENCIA_DIRECCION']); ?>",
    agenciaTel:      "<?php echo addslashes($d['AGENCIA_TEL']); ?>",
    // Consulta
    tipoConsulta:    "<?php echo addslashes($d['TIPO_CONSULTA']); ?>",
    fechaCita:       "<?php echo $d['FECHA_CITA']; ?>",
    fechaHoy:        "<?php echo date('d/m/Y'); ?>"
};
</script>

<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── INICIALIZAR EDITOR ───────────────────────────────────────────────
$(document).ready(function(){
    $('#editorInforme').summernote({
        placeholder: 'Cargue una plantilla o escriba la nota aquí...',
        tabsize: 2,
        height: 520,
        lang: 'es-ES',
        toolbar: [
            ['style',  ['style']],
            ['font',   ['bold','underline','clear']],
            ['color',  ['color']],
            ['para',   ['ul','ol','paragraph']],
            ['table',  ['table']],
            ['insert', ['link','picture']],
            ['view',   ['fullscreen','codeview','help']]
        ]
    });
});

// ── CALCULAR IMC ─────────────────────────────────────────────────────
function calcularIMC(){
    const pesoInput  = parseFloat($('#peso').val());
    const tallaInput = parseFloat($('#talla').val());
    const uPeso  = $('input[name="unidadPeso"]:checked').val();
    const uTalla = $('input[name="unidadTalla"]:checked').val();

    if(!pesoInput || !tallaInput) return;

    const pesoKg = uPeso  === 'lbs' ? pesoInput  * 0.453592 : pesoInput;
    const tallaM = uTalla === 'cm'  ? tallaInput / 100       : tallaInput;

    const imc = (pesoKg / (tallaM * tallaM)).toFixed(2);
    $('#imc').val(imc);

    const est = $('#estado_imc');
    if      (imc < 18.5) est.text('Bajo Peso') .attr('class','badge p-2 d-block fs-6 bg-info');
    else if (imc < 25)   est.text('Normal')     .attr('class','badge p-2 d-block fs-6 bg-success');
    else if (imc < 30)   est.text('Sobrepeso')  .attr('class','badge p-2 d-block fs-6 bg-warning text-dark');
    else                 est.text('Obesidad')   .attr('class','badge p-2 d-block fs-6 bg-danger');
}

// ── CARGAR PLANTILLA ─────────────────────────────────────────────────
function cargarPlantilla(id){
    if(!id) return;

    const uPeso  = $('input[name="unidadPeso"]:checked').val()  || 'kg';
    const uTalla = $('input[name="unidadTalla"]:checked').val() || 'cm';
    const pesoVal  = $('#peso').val()  || '---';
    const tallaVal = $('#talla').val() || '---';
    const imcVal   = $('#imc').val()   || '---';

    // Fecha de nacimiento formateada
    const fechaNacJS = new Date(DATOS_CITA.pacienteDOB + 'T00:00:00');
    const dobFormateada = fechaNacJS.toLocaleDateString('en-US',{
        month:'long', day:'numeric', year:'numeric'
    });

    // Firma del doctor
    const firmaHtml = `
        <br><br>
        <div style="margin-top:40px; border-top:1px solid #ccc; padding-top:10px; font-family:Arial,sans-serif;">
            <strong>${DATOS_CITA.atiendNombre}</strong><br>
            <em>${DATOS_CITA.docEspecialidad}</em><br>
            ${DATOS_CITA.agenciaNombre}<br>
            ${DATOS_CITA.agenciaTel}
        </div>`;

    $.ajax({
        url: 'get_plantilla_html.php',
        type: 'GET',
        data: { id: id },
        success: function(html){

            // Todos los reemplazos disponibles
            const vars = {
                // Fechas
                '{{fecha_actual}}'      : DATOS_CITA.fechaHoy,
                '{{fecha_evaluacion}}'  : DATOS_CITA.fechaHoy,
                '{{fecha_cita}}'        : DATOS_CITA.fechaCita,
                // Paciente
                '{{paciente_nombre}}'   : DATOS_CITA.pacienteNombre,
                '{{paciente_dob}}'      : dobFormateada,
                '{{paciente_email}}'    : DATOS_CITA.pacienteEmail,
                '{{paciente_telefono}}' : DATOS_CITA.pacienteTel,
                '{{paciente_cedula}}'   : DATOS_CITA.pacienteCedula,
                // Doctor de la cita
                '{{doctor_nombre}}'     : DATOS_CITA.docNombre,
                '{{nombre_doctor}}'     : DATOS_CITA.docNombre,
                '{{apellido_doctor}}'   : DATOS_CITA.docApellido,
                '{{titulo_doctor}}'     : 'Dr./Dra.',
                '{{especialidad}}'      : DATOS_CITA.docEspecialidad,
                // Doctor que atiende (sesión)
                '{{firma_nombre}}'          : DATOS_CITA.atiendNombre,
                '{{firma_credenciales}}'    : DATOS_CITA.docEspecialidad,
                // Clínica
                '{{practica_nombre}}'   : DATOS_CITA.agenciaNombre,
                '{{direccion_1}}'       : DATOS_CITA.agenciaDirec,
                '{{direccion_2}}'       : '',
                '{{ciudad}}'            : '',
                '{{estado}}'            : '',
                '{{codigo_postal}}'     : '',
                '{{telefono_clinica}}'  : DATOS_CITA.agenciaTel,
                // Consulta
                '{{tipo_consulta}}'             : DATOS_CITA.tipoConsulta,
                '{{diagnostico_referencia}}'    : DATOS_CITA.tipoConsulta,
                // Mediciones
                '{{peso}}'              : `${pesoVal} ${uPeso}`,
                '{{talla}}'             : `${tallaVal} ${uTalla}`,
                '{{imc}}'               : imcVal,
                '{{antropometria}}'     : `Peso: ${pesoVal} ${uPeso}, Talla: ${tallaVal} ${uTalla}, IMC: ${imcVal}`,
                // Campos clínicos editables (el Dr. los completa en el editor)
                '{{bioquimica}}'        : '[Ingresar datos bioquímicos]',
                '{{hallazgos_fisicos}}' : '[Ingresar hallazgos físicos]',
                '{{historial_cliente}}' : '[Ingresar historial del paciente]',
                '{{plan_nutricional}}'  : '[Ingresar plan nutricional]',
                '{{objetivos}}'         : '[Ingresar objetivos]',
                '{{recomendaciones}}'   : '[Ingresar recomendaciones]',
                '{{diagnostico}}'       : '[Ingresar diagnóstico]',
                '{{tratamiento}}'       : '[Ingresar tratamiento]',
            };

            // Aplicar todos los reemplazos
            for(const [tag, val] of Object.entries(vars)){
                const regex = new RegExp(tag.replace(/[.*+?^${}()|[\]\\]/g,'\\$&'), 'g');
                html = html.replace(regex, val);
            }

            // Añadir firma al final
            html += firmaHtml;

            $('#editorInforme').summernote('code', html);
        },
        error: function(){
            alert('Error al cargar la plantilla. Verifica que get_plantilla_html.php existe.');
        }
    });
}

// ── GUARDAR ATENCIÓN ─────────────────────────────────────────────────
function guardarAtencion(){
    const contenido = $('#editorInforme').summernote('code');
    if(contenido.replace(/<[^>]+>/g,'').trim().length < 10){
        alert('El informe parece estar vacío. Complételo antes de guardar.');
        return;
    }

    const uPeso  = $('input[name="unidadPeso"]:checked').val()  || 'kg';
    const uTalla = $('input[name="unidadTalla"]:checked').val() || 'cm';
    let pesoVal  = parseFloat($('#peso').val())  || 0;
    let tallaVal = parseFloat($('#talla').val()) || 0;

    // Guardar siempre en kg y cm en la BD
    const pesoKg = uPeso  === 'lbs' ? pesoVal  * 0.453592 : pesoVal;
    const tallaCm = uTalla === 'm'  ? tallaVal * 100       : tallaVal;
    const imcVal  = parseFloat($('#imc').val()) || 0;

    if(!confirm('¿Desea finalizar la atención y guardar el historial?')) return;

    $.ajax({
        url: 'guardar_atencion.php',
        type: 'POST',
        data: {
            idCita:   DATOS_CITA.idCita,
            informe:  contenido,
            peso:     pesoKg.toFixed(2),
            talla:    tallaCm.toFixed(1),
            imc:      imcVal
        },
        success: function(res){
            if(res.trim() === 'OK'){
                alert('Atención guardada con éxito.');
                window.location.href = 'SCH_Calendar.php';
            } else {
                alert('Error al guardar: ' + res);
            }
        },
        error: function(){
            alert('Error de conexión al guardar. Intente de nuevo.');
        }
    });
}

// ── IMPRIMIR ──────────────────────────────────────────────────────────
function imprimirInforme(){
    const contenido = $('#editorInforme').summernote('code');
    const ventana = window.open('','_blank','height=800,width=900');
    ventana.document.write(`
        <html><head>
        <title>Informe - ${DATOS_CITA.pacienteNombre}</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 40px; color: #333; }
            @media print { .no-print { display:none; } }
        </style>
        </head><body>${contenido}</body></html>`);
    ventana.document.close();
    ventana.focus();
    setTimeout(() => ventana.print(), 500);
}
</script>
</body>
</html>
