<?php
ob_start();
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

// Verificación de sesión y tiempo de expiración
if(!isset($_SESSION["rol"])){
    header("Location: break.php");
}else {
    $now = time();
    if ($now > $_SESSION['expire']) {
        session_destroy();
        header("Location: expirada.php");
    }
}

// Consulta para obtener las citas
$query = "SELECT 
            A.IDCITA, 
            CONCAT(B.NOMBRES, ' ', B.APELLIDOS) AS PACIENTE,
			B.TELEFONO, -- Añadimos el teléfono aquí
            C.NOMBRES AS TIPO_CONSULTA,
            A.FECHA_CITA, 
            A.HORA_INICIO, 
            A.HORA_FIN,
            A.ESTADO_CITA,
            A.COMENTARIO,
            CONCAT(D.NOMBRES, ' ', D.APELLIDOS) AS DOCTOR
          FROM AG_CITA A 
          INNER JOIN AG_PACIENTE B ON A.IDPACIENTE = B.IDPACIENTE 
          INNER JOIN AG_TIPOCONSULTA C ON A.IDTIPOCONSULTA = C.IDTIPOCONSULTA
          INNER JOIN ADM_DOCTOR D ON A.IDDOCTOR = D.IDDOCTOR";

$resultado = $conexion->query($query);

$eventos = array();

while ($row = $resultado->fetch_assoc()) {
    // Determinar color según estado
    switch($row['ESTADO_CITA']) {
        case 'Confirmada':
            $color = '#28a745'; // Verde
            break;
        case 'Pendiente':
            $color = '#ffc107'; // Amarillo
            break;
        case 'Atrasado':
            $color = '#dc3545'; // Rojo
            break;
        case 'Cancelado':
            $color = '#dc3545'; // Gris  6c757d
            break;
        default:
            $color = '#dc3545'; // Azul por defecto
    }
    
    $eventos[] = array(
        'id' => $row['IDCITA'],
        'title' => $row['PACIENTE'] ,
        'start' => $row['FECHA_CITA'] . 'T' . $row['HORA_INICIO'],
        'end' => $row['FECHA_CITA'] . 'T' . $row['HORA_FIN'],
        'doctor' => $row['DOCTOR'],
        'description' => 'Estado: ' . $row['ESTADO_CITA'] . ' - ' . $row['COMENTARIO'],
        'backgroundColor' => $color, // Fondo del evento con el color del estado
        'borderColor' => $color, // Borde del evento con el mismo color
        'textColor' => '#ffffff', // Texto en blanco para contraste
        'extendedProps' => array(
            'cita' => $row['ESTADO_CITA'],
            'medico' => $row['DOCTOR'] ,
            'consulta' => $row['TIPO_CONSULTA'],
			'telefono' => $row['TELEFONO'] // Nuevo campo enviado al JS
        )
    );
}


?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Language" content="en">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Calendario de citas programas.</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, shrink-to-fit=no" />
    <meta name="description" content="Calendars are used in a lot of apps. We thought to include one for React.">
    <meta name="msapplication-tap-highlight" content="no">
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css' rel='stylesheet'>
    <script src="js/jquery.min.js"></script>
    <link href='./fullcalendar/main.css' rel='stylesheet' />
    <script src='./fullcalendar/main.js'></script>
    <script src="./js/calendar.js?2"></script>
    <link href="./main.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
 <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">

	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/clockpicker/0.0.7/bootstrap-clockpicker.min.css">
<!-- Bootstrap JS (necesario para que funcionen los modales)

 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

 -->

     <script>
        function fechaBuscar(){
            var inicio = document.formul.fechainicio.value;
            var fin = document.formul.fechafin.value;            
            window.location='Home.php';
        }
        </script>
        <style>
        .fc-event {
            cursor: pointer;
            font-size: 0.85em;
            padding: 2px 5px;
        }

        #eventModal .btn {
    transition: all 0.3s ease;
    white-space: nowrap;
}

#eventModal .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

#eventModal .bi {
    margin-right: 5px;
}
    </style>
       
    </head>
<body>


<div class="app-container app-theme-white body-tabs-shadow fixed-sidebar fixed-header">
        
        
        <div class="app-header header-shadow">
            <div class="app-header__logo">
                                <div class="logo-src"></div>
                                <div class="header__pane ml-auto">
                                    <div>
                                        <button type="button" class="hamburger close-sidebar-btn hamburger--elastic" data-class="closed-sidebar">
                                            <span class="hamburger-box">
                                                <span class="hamburger-inner"></span>
                                            </span>
                                        </button>
                                    </div>
                                </div>
            </div>
            <div class="app-header__mobile-menu">
                        <div>
                            <button type="button" class="hamburger hamburger--elastic mobile-toggle-nav">
                                <span class="hamburger-box">
                                    <span class="hamburger-inner"></span>
                                </span>
                            </button>
                        </div>
            </div>
            <div class="app-header__menu">
                <span>
                    <button type="button" class="btn-icon btn-icon-only btn btn-primary btn-sm mobile-toggle-header-nav">
                        <span class="btn-icon-wrapper">
                            <i class="fa fa-ellipsis-v fa-w-6"></i>
                        </span>
                    </button>
                </span>
            </div>    
            <div class="app-header__content">
                        <div class="app-header-left">
                         
                            <ul class="header-menu nav">
                                <li class="nav-item">
                                    <a href="javascript:void(0);" class="nav-link">
                                        <i class="nav-link-icon fa fa-database"> </i>
                                        Estadistica
                                    </a>
                                </li>
                                <li class="dropdown nav-item">
                                    <a href="javascript:void(0);" class="nav-link">
                                        <i class="nav-link-icon fa fa-cog"></i>
                                        Configuracion
                                    </a>
                                </li>
                            </ul>        
                        </div>
                        <div class="app-header-right">
                            <div class="header-btn-lg pr-0">
                                <div class="widget-content p-0">
                                    <div class="widget-content-wrapper">
                                        <div class="widget-content-left">
                                            <div class="btn-group">
                                                <a data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="p-0 btn">
                                                    <img width="42" class="rounded-circle" >
                                                    <i class="fa fa-angle-down ml-2 opacity-8"></i>
                                                </a>
                                                <div tabindex="-1" role="menu" aria-hidden="true" class="dropdown-menu dropdown-menu-right">
                                                    <button type="button" tabindex="0" class="dropdown-item">Perfil de Usuario</button>
                                                    <button type="button" tabindex="0" class="dropdown-item">Configuración</button>
                                                    <div tabindex="-1" class="dropdown-divider"></div>
                                                    <a type="button" tabindex="0" href="salir.php" class="dropdown-item">Cerrar Sesión</a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="widget-content-left  ml-3 header-user-info">
                                            <div class="widget-heading">
                                               Admin <?php echo $_SESSION["username"]?>
                                            </div>
                                            <div class="widget-subheading">
                                                Administrator - <?php echo $startDate?>
                                                
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>        
                        </div>
            </div>
        </div>
        <div class="ui-theme-settings" style="visibility:hidden">
                    <button type="button" id="TooltipDemo" class="btn-open-options btn btn-warning">
                        <i class="fa fa-cog fa-w-16 fa-spin fa-2x"></i>
                    </button>
                    <div class="theme-settings__inner">
                        <div class="scrollbar-container">
                            <div class="theme-settings__options-wrapper">
                               
                                
                                <h3 class="themeoptions-heading">
                                    <div>
                                        Cabecera
                                    </div>
                                    <button type="button" class="btn-pill btn-shadow btn-wide ml-auto btn btn-focus btn-sm switch-header-cs-class" data-class="">
                                        Restablecer
                                    </button>
                                </h3>
                                
                                <h3 class="themeoptions-heading">
                                    <div>Menu</div>
                                    <button type="button" class="btn-pill btn-shadow btn-wide ml-auto btn btn-focus btn-sm switch-sidebar-cs-class" data-class="">
                                        Restablecer
                                    </button>
                                </h3>  
                                <!-- Button trigger modal -->
                                
                                              
                                
                            </div>
                        </div>
                    </div>
        </div> 

        <div class="app-main">
            <div class="app-sidebar sidebar-shadow">
            <!-- ======= MENU========================  -->
            <?php include("./menu/menu_adm.php"); ?>          
                
                </div>    
                <div class="app-main__outer">
                    <div class="app-main__inner">
                        <div class="app-page-title">
                            <div class="page-title-wrapper">
                                <div class="page-title-heading">
                                    <div class="page-title-icon">
                                        <i class="pe-7s-date icon-gradient bg-warm-flame">
                                        </i>
                                    </div>
                                    <div>
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#editModal">
                                Agendar Cita saludos
                                </button> 
                                        <div class="page-title-subheading">Citas agendadas.
                                        </div>
                                    </div>

                                </div>
                                    
                            </div>
                        </div>   
                                
                        
                        <div class="tab-content">
                            <div class="tab-pane tabs-animation fade show active" id="tab-content-0" role="tabpanel">
                                <div class="main-card mb-3 card">
                                    <div class="card-body">
                                        <div id='calendar1'></div>
                                    </div>
                                </div>
                            </div>
                             
                           
                        </div>
                    </div>
                     
                </div>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Agendar Cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="insertCita" method="POST" action="class/Insert_cita.php">
                    
                    
                    <div class="mb-3">
                        <label class="form-label">Fecha Consulta</label>
                        <input type="date" class="form-control"  name="fechafactura" id="fechafactura" >
                        <script>document.getElementById('fechafactura').value = new Date().toISOString().substring(0, 10);</script>
                    </div>
                    
                   <div class="mb-3">
    <label class="form-label">Paciente</label>
    <select class="form-select select-busqueda" name="IdPaciente" required>
        <option value="">Buscar paciente...</option>
        <?php
            $queryP = $conexion->query("SELECT * FROM AG_PACIENTE WHERE ESTADO = 'A'");
            while ($valores = mysqli_fetch_array($queryP)) {
                echo '<option value="'.$valores['IDPACIENTE'].'">'.$valores['NOMBRES'].' '.$valores['APELLIDOS'].'</option>';
            }
        ?>
    </select>
</div>
<div class="mb-3">
    <label class="form-label">Hora Inicio</label>
    <div class="input-group">
        <span class="input-group-text"><i class="bi bi-clock"></i></span>
        <select class="form-select select-busqueda" name="timeIni" id="timeIni" required>
            <option value="">Seleccione hora...</option>
            <?php
                // Generamos horas desde las 07:00 hasta las 22:00 cada 30 min
                $inicio = new DateTime('07:00');
                $fin    = new DateTime('22:30');
                $intervalo = new DateInterval('PT30M');
                $periodo = new DatePeriod($inicio, $intervalo, $fin);

                foreach ($periodo as $hora) {
                    $v = $hora->format('H:i');
                    echo '<option value="'.$v.'">'.$hora->format('h:i A').'</option>';
                }
            ?>
        </select>
    </div>
</div>
					
<div class="mb-3">
    <label class="form-label">Tipo Consulta</label>
    <select class="form-select select-busqueda" name="Idconsulta" required>
        <option value="">Buscar tipo consulta...</option>
        <?php
            $queryC = $conexion->query("SELECT * FROM AG_TIPOCONSULTA WHERE ESTADO = 'A'");
            while ($valores = mysqli_fetch_array($queryC)) {
                echo '<option value="'.$valores['IDTIPOCONSULTA'].'">'.$valores['NOMBRES'].'</option>';
            }
        ?>
    </select> 
</div>

<div class="mb-3">
    <label class="form-label">Doctor</label>
    <select class="form-select select-busqueda" name="IdDoctor" required>   
        <option value="">Buscar doctor...</option>                                         
        <?php
            $queryD = $conexion->query("SELECT * FROM ADM_DOCTOR WHERE ESTADO = 'A'");
            while ($valores = mysqli_fetch_array($queryD)) {
                echo '<option value="'.$valores['IDDOCTOR'].'">'.$valores['NOMBRES'].' '.$valores['APELLIDOS'].'</option>';
            }
        ?>
    </select>
</div>

                    
                  <!--  <div class="mb-3">
                        <label class="form-label">Especialidad</label>
                        <input type="text" class="form-control" id="especialidadD" name="especialidadD">
                    </div> -->

                    
                    <button type="sumit" class="btn btn-primary" >Agendar</button>
                    
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Modal para citas -->

<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalLabel">Gestión de Cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formEstado" method="POST" action="class/actualizar_estado.php" onsubmit="return validarFormulario();">
                <div class="modal-body">
                    <div id="eventDetails" class="mb-4"></div>
                    <!-- Input oculto para enviar el ID de la cita -->
                    <input type="hidden" id="idCita" name="id">
                    <input type="hidden" id="estadoCita" name="estado">
                </div>
                <div class="modal-footer">
    <button id="btnAtender" class="btn btn-primary" type="button" onclick="irAConsulta()">
        <i class="bi bi-person-check-fill"></i> Atender Paciente
    </button>
    
    <button id="btnConfirmar" class="btn btn-success" type="submit" onclick="setEstado('Confirmada')">Confirmar</button>
    <button id="btnCancelar" class="btn btn-danger" type="submit" onclick="setEstado('Cancelada')">Cancelar</button>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
</div>
            </form>
        </div>
    </div>
</div>


<script>
// Función para actualizar el estado de la cita


function updateStatus(nuevoEstado, event) {
    const eventId = event.target.closest('button').dataset.eventId;

    if (confirm(`¿Está seguro de cambiar el estado a ${nuevoEstado}?`)) {
        fetch("class/actualizar_estado.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: `id=${eventId}&estado=${encodeURIComponent(nuevoEstado)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Estado actualizado con éxito");

                // Cerrar modal
                bootstrap.Modal.getInstance(document.getElementById("eventModal")).hide();

                // Redirigir a la página indicada
                window.location.href = data.redirect;
            } else {
                alert("Error al actualizar el estado: " + (data.message || "Desconocido"));
            }
        })
        .catch(error => console.error("Error:", error));
    }
}




// Modificar el eventClick para incluir el ID en los botones
eventClick: function(info) {
    document.querySelectorAll('#eventModal [data-event-id]').forEach(btn => {
        btn.dataset.eventId = info.event.id;
    });

    // Mostrar modal con opciones de cambio de estado
    document.getElementById('btnConfirmar').dataset.eventId = info.event.id;
    document.getElementById('btnCancelar').dataset.eventId = info.event.id;

    // Mostrar modal
    bootstrap.Modal.getInstance(document.getElementById('eventModal')).show();
}

function validarFormulario() {
    let id = document.getElementById('idCita').value;
    let estado = document.getElementById('estadoCita').value;

    if (!id || !estado) {
        alert("Error: Los datos del formulario no están completos.");
        return false;
    }

    return true;
}

	
</script>




<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js'></script>
<script src='./fullcalendar/main.js'></script>
<script src='./fullcalendar/locales/es.js'></script>

<script>

document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar1');
    
    var calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'es',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        initialView: 'dayGridMonth',
        events: <?php echo json_encode($eventos); ?>,
        eventClick: function(info) {
              // Limpiamos el número de teléfono (quitamos espacios o caracteres especiales)
    var telLimpio = info.event.extendedProps.telefono ? info.event.extendedProps.telefono.replace(/\D/g, '') : '';
    
    // Si el número no tiene código de país, le agregamos el de Ecuador (593) por defecto
    if (telLimpio.length === 9 && telLimpio.startsWith('9')) {
        telLimpio = '593' + telLimpio;
    } else if (telLimpio.length === 10 && telLimpio.startsWith('09')) {
        telLimpio = '593' + telLimpio.substring(1);
    }

    // Mensaje personalizado
    var mensaje = encodeURIComponent(`Hola, le saludamos de SROSS Nutritions. Le recordamos su cita de ${info.event.extendedProps.consulta} para el día ${info.event.start.toLocaleDateString()} a las ${info.event.start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}. ¿Nos confirma su asistencia?`);

    var details = `
        <div class="row">
            <div class="col-md-8">
                <p><strong>Paciente:</strong> ${info.event.title}</p>
                <p><strong>Teléfono:</strong> ${info.event.extendedProps.telefono || 'No registrado'}</p>
                <p><strong>Inicio:</strong> ${info.event.start.toLocaleString()}</p>
                <p><strong>Doctor:</strong> ${info.event.extendedProps.medico}</p>
                <p><strong>Estado:</strong> <span class="badge ${info.event.extendedProps.cita === 'Confirmada' ? 'bg-success' : 'bg-warning'}">${info.event.extendedProps.cita}</span></p>
            </div>
            <div class="col-md-4 text-center">
                ${telLimpio ? `
                    <a href="https://wa.me/${telLimpio}?text=${mensaje}" target="_blank" class="btn btn-outline-success btn-lg mb-2">
                        <i class="bi bi-whatsapp"></i> Confirmar por WhatsApp
                    </a>
                ` : '<p class="text-danger">Sin número para WhatsApp</p>'}
            </div>
        </div>
        <hr>
        <p><strong>Tipo consulta:</strong> ${info.event.extendedProps.consulta}</p>
    `;
            document.getElementById('eventDetails').innerHTML = details;

    // Asignar el ID de la cita
    document.getElementById('idCita').value = info.event.id;

    // Asignar el estado actual de la cita
    document.getElementById('estadoCita').value = info.event.extendedProps.cita || 'Pendiente';

    // Mostrar el modal
    var modal = new bootstrap.Modal(document.getElementById('eventModal'));
    modal.show();
        }
    });

    calendar.render();
});

function setEstado(estado) {
    document.getElementById('estadoCita').value = estado;
}
function irAConsulta() {
    const idCita = document.getElementById('idCita').value;
    // Redirigimos a tu visor de plantillas enviando la cita
    window.location.href = `atencion_paciente.php?idCita=${idCita}`;
}
</script>
<script>
$(document).ready(function() {
    // Inicializar Select2 al abrir el modal
    $('#editModal').on('shown.bs.modal', function () {
        $('.select-busqueda').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#editModal'), // Importante para que funcione dentro de modales
            width: '100%'
        });
    });
});
</script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/clockpicker/0.0.7/bootstrap-clockpicker.min.js"></script>
<script>
    $('.clockpicker').clockpicker();
</script>
<script type="text/javascript" src="./assets/scripts/main.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</body>
</html>
