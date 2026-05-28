
<!doctype html>
<html lang="en">
<?
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion=conectarse();
?>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Language" content="en">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Calendar - Pendientes.</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, shrink-to-fit=no" />
    <meta name="description" content="Calendars are used in a lot of apps. We thought to include one for React.">
    <meta name="msapplication-tap-highlight" content="no">
    <!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Bootstrap JS (necesario para que funcionen los modales) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="js/jquery.min.js"></script>
    <link href='./fullcalendar/main.css' rel='stylesheet' />
    <script src='./fullcalendar/main.js'></script>
    <script src="./js/calendar.js?2"></script>
    <link href="./main.css" rel="stylesheet">

    <!-- cdn data tables -->
    <!-- <link href="https://cdn.datatables.net/v/dt/dt-1.13.2/datatables.min.css"/>
    <script src="https://cdn.datatables.net/v/dt/dt-1.13.2/datatables.min.js"></script>
 -->
<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap4.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap4.min.css">


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
                            </ul>        </div>
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
                                                Admin
                                            </div>
                                            <div class="widget-subheading">
                                                Administrator
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
                                    <div>Lista de citas Atendidas
                                        <div class="page-title-subheading">.
                                        </div>
                                    </div>
                                </div>
                                <div class="page-title-actions">
                                  <!--   <button type="button" data-toggle="tooltip" title="Example Tooltip" data-placement="bottom" class="btn-shadow mr-3 btn btn-dark">
                                        <i class="fa fa-star"></i>
                                    </button>
                                    <div class="d-inline-block dropdown">
                                        <button type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="btn-shadow dropdown-toggle btn btn-info">
                                            <span class="btn-icon-wrapper pr-2 opacity-7">
                                                <i class="fa fa-business-time fa-w-20"></i>
                                            </span>
                                            Buttons
                                        </button>
                                        <div tabindex="-1" role="menu" aria-hidden="true" class="dropdown-menu dropdown-menu-right">
                                            <ul class="nav flex-column">
                                                <li class="nav-item">
                                                    <a href="javascript:void(0);" class="nav-link">
                                                        <i class="nav-link-icon lnr-inbox"></i>
                                                        <span>
                                                            Inbox
                                                        </span>
                                                        <div class="ml-auto badge badge-pill badge-secondary">86</div>
                                                    </a>
                                                </li>
                                                <li class="nav-item">
                                                    <a href="javascript:void(0);" class="nav-link">
                                                        <i class="nav-link-icon lnr-book"></i>
                                                        <span>
                                                            Book
                                                        </span>
                                                        <div class="ml-auto badge badge-pill badge-danger">5</div>
                                                    </a>
                                                </li>
                                                <li class="nav-item">
                                                    <a href="javascript:void(0);" class="nav-link">
                                                        <i class="nav-link-icon lnr-picture"></i>
                                                        <span>
                                                            Picture
                                                        </span>
                                                    </a>
                                                </li>
                                                <li class="nav-item">
                                                    <a disabled href="javascript:void(0);" class="nav-link disabled">
                                                        <i class="nav-link-icon lnr-file-empty"></i>
                                                        <span>
                                                            File Disabled
                                                        </span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div> -->
                                </div>    
                            </div>
                        </div>            
                        <ul class="body-tabs body-tabs-layout tabs-animated body-tabs-animated nav">
                              <li class="nav-item">
                                <a role="tab" class="nav-link active" id="tab-1" data-toggle="tab" href="#tab-content-1">
                                    <span>List View</span>
                                </a>
                            </li>
                            <!-- <li class="nav-item">
                                <a role="tab" class="nav-link " id="tab-0" data-toggle="tab" href="#tab-content-0">
                                Lista</a>
                            </li>
                            -->
                           
                           
                            
                            
                            
                             <!--
                             <li class="nav-item">
                                <a role="tab" class="nav-link" id="tab-2" data-toggle="tab" href="#tab-content-2">
                                    <span>Background Events</span> 
                                </a>
                            </li>
                            -->
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane tabs-animation fade" id="tab-content-1" role="tabpanel">
                                <div class="main-card mb-3 card">
                                    <div class="card-body">
                                        <!-- <div id='calendar1'></div> -->
                                        <div class="main-card mb-3 card">
                                            <div class="card-body">
                                                <h5 class="card-title">Información del paciente</h5>
                                                <form class="needs-validation" novalidate method="post" action="class/Insert_Pacientev2.php">
                                                    <div class="form-row">
                                                        <div class="col-md-2 mb-3">
                                                            <label for="validationCustomID">ID</label>
                                                            <input type="text" class="form-control" id="validationCustomID" name= "cedula" placeholder="09923006589" value="" required>
                                                            <div class="valid-feedback">
                                                                Looks good!
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2 mb-3">
                                                            <label for="validationCustom05">Title</label>
                                                            <div class="position-relative form-group">
                                                                <select name="title" id="validationCustom05" class="form-control" required>
                                                                <option>Default Select</option>
                                                                <option value="Dr">Dr</option>
                                                                <option value="Fr">Fr</option>
                                                                <option value="Master">Master</option>
                                                                <option value="Miss">Miss</option>
                                                                <option value="Mr">Mr</option>
                                                                <option value="Mrs">Mrs</option>
                                                                <option value="Ms">Ms</option>
                                                                <option value="Mx">Mx</option>
                                                                <option value="Pr">Pr</option>
                                                                <option value="Prof">Prof</option>
                                                                <option value="Rev">Rev</option>
                                                                </select>
                                                            </div>
                                                            <div class="invalid-feedback">
                                                                Please provide a valid Title.
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3 mb-3">
                                                            <label for="validationCustom01">First name</label>
                                                            <input type="text" class="form-control" name="nombres" id="validationCustom01" placeholder="First name" value="Mark" required>
                                                            <div class="valid-feedback">
                                                                Looks good!
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3 mb-3">
                                                            <label for="validationCustom02">Last name</label>
                                                            <input type="text" class="form-control" id="validationCustom02"  name="apellidos" placeholder="Last name" value="Otto" required>
                                                            <div class="valid-feedback">
                                                                Looks good!
                                                            </div>
                                                        </div>
                                                       <div class="col-md-2 mb-3">
                                                           <label for="validationCustom04">Phone</label>
                                                           <input type="text" class="form-control" name="telefono" id="validationCustom04" placeholder="Pone" required>
                                                           <div class="invalid-feedback">
                                                               Please provide a valid state.
                                                           </div>
                                                       </div>
                                                    </div>
                                                    <div class="form-row">
                                                        <div class="col-md-4 mb-3">
                                                            <label for="validationCustomUsername">E-MAIL</label>
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text" id="inputGroupPrepend">@</span>
                                                                </div>
                                                                <input type="text" class="form-control" id="validationCustomUsername" name="email" placeholder="name@mail.com" aria-describedby="inputGroupPrepend" required>
                                                                <div class="invalid-feedback">
                                                                    Please choose a E-MAIL.
                                                                </div>
                                                            </div>
                                                        </div>
                                                     
                                                        <div class="col-md-3 mb-3">
                                                            <label for="validationCustom05">Sex</label>
                                                            <div class="position-relative form-group">
                                                                <select name="sex" id="validationCustom05" class="form-control" required>
                                                                <option value="N/A">Default Select</option>
                                                                <option value="Male">Male</option>
                                                                <option value="Fame">Fame</option>
                                                                </select>
                                                            </div>
                                                            <div class="invalid-feedback">
                                                                Please provide a valid Sex.
                                                            </div>
                                                        </div>

                                                        <div class="col-md-5 mb-3">
                                                            <label for="validationCustom05">Gender Identity</label>
                                                            <div class="position-relative form-group">
                                                                <select name="gender" id="validationCustom05" class="form-control" required>
                                                                <option value="Default Select">Default Select</option>
                                                                <option value="Male">Male</option>
                                                                <option value="Fame">Fame</option>
                                                                <option value="Transgender man/trans man/famale-to-male(FTM)">Transgender man/trans man/famale-to-male(FTM)</option>
                                                                <option value="Trasngender woman/trans woman/male-to-famele(MTF)">Trasngender woman/trans woman/male-to-famele(MTF)</option>
                                                                <option value="Genderqueer/ gender nonconforming neither exclusively male nor famale">Genderqueer/ gender nonconforming neither exclusively male nor famale</option>
                                                                <option value="Decline to answer">Decline to answer</option>
                                                               
                                                            </select>
                                                        </div>
                                                        </div>
                                                        
                                                           <div class="main-card mb-3 card">
                                                            <label for="validationCustom05"></label>
                                                            <div class="position-relative form-group">
                                                                <div class="">
                                                                   <div class="card-body">
                                                                       <h5 class="card-title">Birth of day</h5>
                                                                       <input type="date" name="feNac" class="form-control" data-toggle="datepicker-year">
                                                                   </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                    </div>
                                                    
                                                     
                                                    
                                                    <button class="btn btn-primary" type="submit">Save </button>
                                                </form>
                                        
                                                <script>
                                                    // Example starter JavaScript for disabling form submissions if there are invalid fields
                                                    (function() {
                                                        'use strict';
                                                        window.addEventListener('load', function() {
                                                            // Fetch all the forms we want to apply custom Bootstrap validation styles to
                                                            var forms = document.getElementsByClassName('needs-validation');
                                                            // Loop over them and prevent submission
                                                            var validation = Array.prototype.filter.call(forms, function(form) {
                                                                form.addEventListener('submit', function(event) {
                                                                    if (form.checkValidity() === false) {
                                                                        event.preventDefault();
                                                                        event.stopPropagation();
                                                                    }
                                                                    form.classList.add('was-validated');
                                                                }, false);
                                                            });
                                                        }, false);
                                                    })();
                                                </script>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane tabs-animation fade show active" id="tab-content-0" role="tabpanel">
                                <div class="main-card mb-3 card">
                                    <div class="card-body">
                                        <!-- <div id='calendar-list'></div> -->
                                        <div class="main-card mb-3 card">
                                    <div class="card-body">
                                        <!-- <div id='calendar1'></div> -->
                                        <style>
    .atendido {
        background-color: #d4edda !important; /* Verde pastel */
    }
</style>

<table id="example" class="table table-striped table-bordered" style="width:100%">
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Inicio</th>
            <th>Fin</th>
            <th>Doctor</th>
            <th>Paciente</th>
            <th>Consulta</th>
            <th>Usuario</th>
            
        </tr>
    </thead>
    <tbody>
        <?php
        $sql = "SELECT 
            C.FECHA_CITA, 
            C.HORA_INICIO, 
            C.HORA_FIN,
            CONCAT(D.NOMBRES, ' ', D.APELLIDOS) AS DOCTOR,
            CONCAT(P.NOMBRES, ' ', P.APELLIDOS) AS PACIENTE,
            T.NOMBRES AS TIPO_CONSULTA,
            CONCAT(U.NOMBRES, ' ', U.APELLIDOS) AS USUARIO_REGISTRO
        FROM AG_CITA C
        INNER JOIN AG_PACIENTE P ON C.IDPACIENTE = P.IDPACIENTE
        INNER JOIN AG_TIPOCONSULTA T ON C.IDTIPOCONSULTA = T.IDTIPOCONSULTA
        INNER JOIN ADM_DOCTOR D ON C.IDDOCTOR = D.IDDOCTOR
        INNER JOIN ADM_USUARIO U ON C.IDUSUARIO = U.IDADM_USUARIO
        WHERE C.ESTADO_CITA = 'Atendido'";  

        $query = $conexion->query($sql);
        while ($valores = mysqli_fetch_array($query)) {
        ?>    
            <tr class="atendido"> <!-- Se agrega la clase -->
                <td><?php echo $valores[0]; ?></td>
                <td><?php echo $valores[1]; ?></td>
                <td><?php echo $valores[2]; ?></td>
                <td><?php echo $valores[3]; ?></td>
                <td><?php echo $valores[4]; ?></td>
                <td><?php echo $valores[5]; ?></td>
                <td><?php echo $valores[6]; ?></td>
               
            </tr>
        <?php } ?>		
    </tbody>
    <tfoot>
        <tr>
            <th>Fecha</th>
            <th>Inicio</th>
            <th>Fin</th>
            <th>Doctor</th>
            <th>Paciente</th>
            <th>Consulta</th>
            <th>Usuario</th>
            
        </tr>
    </tfoot>
</table>

                                       
                                        <script>
                                            function confirmarEliminacion(idPaciente) {
                                                if (confirm("¿Estás seguro de eliminar este paciente?")) {
                                                    window.location.href = "class/Eliminar_pacienteV2.php?idPaciente=" + idPaciente;
                                                }
                                            }
                                        </script>
                                     
<script>
    function cargarDatos(paciente) {
        document.getElementById('idPaciente').value = paciente.IDPACIENTE;
        document.getElementById('nombre').value = paciente.NOMBRES;
        document.getElementById('apellido').value = paciente.APELLIDOS;
        document.getElementById('email').value = paciente.EMAIL;
        document.getElementById('telefono').value = paciente.TELEFONO;
        document.getElementById('fecNac').value = paciente.FECHANACIMIENTO;
        document.getElementById('identificacion').value = paciente.CEDULA;
        document.getElementById('titler').value = paciente.TITLE;
        document.getElementById('sex').value = paciente.SEX;
        document.getElementById('gender').value = paciente.GENDER;

    }

    function guardarEdicion() {
        var formData = new FormData(document.getElementById("formEditar"));

        fetch("class/Editar_paciente.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            alert(data);
            location.reload(); // Recargar la página tras la edición
        })
        .catch(error => console.error("Error:", error));
    }
</script>
                                    </div>
                                </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane tabs-animation fade" id="tab-content-2" role="tabpanel">
                                <div class="main-card mb-3 card">
                                    <div class="card-body">
                                        <!-- <div id="calendar-bg-events"></div> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="app-wrapper-footer">
                        <div class="app-footer">
                            <div class="app-footer__inner">
                                
                            </div>
                        </div>
                    </div>    
                </div>
        </div>
    </div>
<script type="text/javascript" src="./assets/scripts/main.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
    $('#example').DataTable();
});
</script>
   <!-- Modal de Edición -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Editar Paciente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="formEditar">
                    <input type="hidden" id="idPaciente" name="idPaciente">
                    
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombres">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Apellido</label>
                        <input type="text" class="form-control" id="apellido" name="apellidos">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="telefono" name="telefono">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Fecha Nacimiento</label>
                        <input type="date" class="form-control" id="fecNac" name="fecNac">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ID</label>
                        <input type="text" class="form-control" id="identificacion" name="identificacion">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <select name="title" id="title" class="form-control" required>
                                                                <option>Default Select</option>
                                                                <option value="Dr">Dr</option>
                                                                <option value="Fr">Fr</option>
                                                                <option value="Master">Master</option>
                                                                <option value="Miss">Miss</option>
                                                                <option value="Mr">Mr</option>
                                                                <option value="Mrs">Mrs</option>
                                                                <option value="Ms">Ms</option>
                                                                <option value="Mx">Mx</option>
                                                                <option value="Pr">Pr</option>
                                                                <option value="Prof">Prof</option>
                                                                <option value="Rev">Rev</option>
                                                                </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Sex</label>
                        
                        
                                                                <select name="sex" id="sex" class="form-control" required>
                                                                
                                                                <option value="Male">Male</option>
                                                                <option value="Fame">Fame</option>
                                                                </select>
                                                            
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Gender</label>
                       <select name="gender" id="gender" class="form-control" required>
                                                                <option value="Default Select">Default Select</option>
                                                                <option value="Male">Male</option>
                                                                <option value="Fame">Fame</option>
                                                                <option value="Transgender man/trans man/famale-to-male(FTM)">Transgender man/trans man/famale-to-male(FTM)</option>
                                                                <option value="Trasngender woman/trans woman/male-to-famele(MTF)">Trasngender woman/trans woman/male-to-famele(MTF)</option>
                                                                <option value="Genderqueer/ gender nonconforming neither exclusively male nor famale">Genderqueer/ gender nonconforming neither exclusively male nor famale</option>
                                                                <option value="Decline to answer">Decline to answer</option>
                                                                </select>
                    </div>

                    

                    <button type="button" class="btn btn-primary" onclick="guardarEdicion()">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>
