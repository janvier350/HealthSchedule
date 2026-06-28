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
    <title>Calendar - Calendars are used in a lot of apps. We thought to include one for React.</title>
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
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap4.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap4.min.css">


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
                                                    <a type="button" tabindex="0" href="" class="dropdown-item">Cerrar Sesión</a>
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
                                        <i class="pe-7s-add-user icon-gradient bg-warm-flame">
                                        </i>
                                    </div>
                                    <div>Crear nuevo
                                        <div class="page-title-subheading">Paciente.
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
                                <a role="tab" class="nav-link active" id="tab-0" data-toggle="tab" href="#tab-content-0">
                                    <span>Register</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a role="tab" class="nav-link" id="tab-1" data-toggle="tab" href="#tab-content-1">
                                    <span>List View</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a role="tab" class="nav-link" id="tab-2" data-toggle="tab" href="#tab-content-2">
                                    <!-- <span>Background Events</span> -->
                                </a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane tabs-animation fade show active" id="tab-content-0" role="tabpanel">
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
                                                            <input type="text" class="form-control" name="nombres" id="validationCustom01" placeholder="First name"  required>
                                                            <div class="valid-feedback">
                                                                Looks good!
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3 mb-3">
                                                            <label for="validationCustom02">Last name</label>
                                                            <input type="text" class="form-control" id="validationCustom02"  name="apellidos" placeholder="Last name"  required>
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
                                                    <div class="form-row">
                                                        <div class="col-md-4 mb-4">
                                                            <label for="validationCustomID">ADDRESS</label>
                                                            <input type="text" class="form-control" id="address" name= "address" placeholder=""  >
                                                            <div class="valid-feedback">
                                                                Looks good!
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="col-md-4 mb-4">
                                                            <label for="validationCustom01">Notes</label>
                                                            <input type="text" class="form-control" name="notes" id="notes" placeholder=""  >
                                                            <div class="valid-feedback">
                                                                Looks good!
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4 mb-4">
                                                            <label for="validationCustom02">Add Notes</label>
                                                            <input type="text" class="form-control" id="addNotes"  name="addNotes" placeholder=""  >
                                                            <div class="valid-feedback">
                                                                Looks good!
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
                            <div class="tab-pane tabs-animation fade" id="tab-content-1" role="tabpanel">
                                <div class="main-card mb-3 card">
                                    <div class="card-body">
                                        <!-- <div id='calendar-list'></div> -->
                                        <div class="main-card mb-3 card">
                                    <div class="card-body">
                                        <!-- <div id='calendar1'></div> -->
                                        <table id="example" class="table table-striped table-bordered" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Name</th>
                                                    <th>LastName</th>
                                                    <th>E-MAIL</th>
                                                    <th>Fecha Nacimiento</th>
                                                    <th>Telefono</th>
                                                    <th>Id</th>
                                                    <th>Title</th>
                                                    <th>Sex</th>
                                                    <th>Gender</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                        $sql="SELECT * FROM AG_PACIENTE WHERE ESTADO = 'A'  ORDER BY `AG_PACIENTE`.`IDPACIENTE` DESC";
                                                        //generamos la consulta
                                                        $query = $conexion -> query ($sql);
                                                            while ($valores = mysqli_fetch_array($query)) {
                                                            ?>    
                                                <tr>
                                                    <td><?php echo $valores[0]?></td>
                                                    <td><?php echo $valores[1]?></td>
                                                    <td><?php echo $valores[2]?></td>
                                                    <td><?php echo $valores[3]?></td>
                                                    <td><?php echo $valores[4]?></td>
                                                    <td><?php echo $valores[5]?></td>
                                                     <td><?php echo $valores[6]?></td>
                                                     <td><?php echo $valores[7]?></td>
                                                     <td><?php echo $valores[8]?></td>
                                                     <td><?php echo $valores[9]?></td>
                                                      <td>
                                                            <button class="btn btn-outline-warning fa fa-edit"
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editModal" 
                                                                onclick="cargarDatos(<?php echo htmlspecialchars(json_encode($valores), ENT_QUOTES, 'UTF-8'); ?>)">
                                                            </button>
                                                            <!-- Botón para eliminar con confirmación -->
                                                            <button class="btn-shadow btn btn-outline-danger fa fa-minus-circle" 
                                                                onclick="confirmarEliminacion(<?php echo $valores['IDPACIENTE']; ?>)">
                                                            </button>
                                                        </td>
                                                </tr>
                                                 <?php } ?>		
                                               
                                                
                                                
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Name</th>
                                                    <th>LastName</th>
                                                    <th>E-MAIL</th>
                                                    <th>Fecha Nacimiento</th>
                                                    <th>Telefono</th>
                                                    <th>Id</th>
                                                    <th>Title</th>
                                                    <th>Sex</th>
                                                    <th>Gender</th>
                                                    <th>Actions</th>
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
        // Cargar los seguros del paciente primero (independiente de los demás campos)
        cargarSegurosPaciente(paciente.IDPACIENTE);
        document.getElementById('nombre').value = paciente.NOMBRES;
        document.getElementById('apellido').value = paciente.APELLIDOS;
        document.getElementById('email').value = paciente.EMAIL;
        document.getElementById('telefono').value = paciente.TELEFONO;
        document.getElementById('fecNac').value = paciente.FECHANACIMIENTO;
        document.getElementById('identificacion').value = paciente.CEDULA;
        document.getElementById('title').value = paciente.TITLE;
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

    // ── Seguros del paciente ──────────────────────────────────────────
    function cargarSegurosPaciente(idPaciente) {
        const cont = document.getElementById('psLista');
        if (!cont) return;
        cont.innerHTML = '<div class="text-muted small">Cargando…</div>';
        fetch('seguro_paciente_listar.php?id_paciente=' + encodeURIComponent(idPaciente))
            .then(r => r.text())
            .then(html => { cont.innerHTML = html; })
            .catch(() => { cont.innerHTML = '<div class="text-danger small">Error al cargar seguros.</div>'; });
    }

    function agregarSeguroPaciente() {
        const idPaciente = document.getElementById('idPaciente').value;
        const idSeguro   = document.getElementById('psSeguro').value;
        const poliza     = document.getElementById('psPoliza').value;
        const prioridad  = document.getElementById('psPrioridad').value;
        if (!idPaciente) { alert('Primero abra un paciente para editar.'); return; }
        if (!idSeguro)   { alert('Seleccione una aseguradora.'); return; }

        fetch('seguro_paciente_guardar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ id_paciente: idPaciente, id_seguro: idSeguro, num_poliza: poliza, prioridad: prioridad })
        })
        .then(r => r.text())
        .then(res => {
            res = res.trim();
            if (res === 'OK') {
                document.getElementById('psSeguro').value = '';
                document.getElementById('psPoliza').value = '';
                document.getElementById('psPrioridad').value = 'Primario';
                cargarSegurosPaciente(idPaciente);
            } else if (res === 'DUP') {
                alert('Ese seguro ya está asignado a este paciente.');
            } else {
                alert('No se pudo agregar: ' + res);
            }
        })
        .catch(() => alert('Error de conexión.'));
    }

    function eliminarSeguroPaciente(id) {
        if (!confirm('¿Quitar este seguro del paciente?')) return;
        const idPaciente = document.getElementById('idPaciente').value;
        fetch('seguro_paciente_eliminar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ id: id })
        })
        .then(r => r.text())
        .then(res => {
            if (res.trim() === 'OK') cargarSegurosPaciente(idPaciente);
            else alert('No se pudo quitar: ' + res);
        })
        .catch(() => alert('Error de conexión.'));
    }

    function subirImagenSeguro(input, id, lado) {
        if (!input.files || !input.files[0]) return;
        const fd = new FormData();
        fd.append('id_paciente_seguro', id);
        fd.append('lado', lado);
        fd.append('imagen', input.files[0]);
        fetch('seguro_paciente_subir_imagen.php', { method: 'POST', body: fd })
            .then(r => r.text())
            .then(res => {
                if (res.trim().startsWith('OK')) {
                    cargarSegurosPaciente(document.getElementById('idPaciente').value);
                } else {
                    alert('No se pudo subir la imagen: ' + res);
                }
            })
            .catch(() => alert('Error de conexión al subir la imagen.'));
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
    $('#example').DataTable({
        responsive: true,
        autoWidth: false
    });
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
                        <label class="form-label">ADDRESS</label>
                        <input type="text" class="form-control" id="address" name="address">
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

                <hr class="my-3">
                <div>
                    <h6 class="mb-2">🛡️ Seguros del paciente</h6>
                    <div class="row g-2 align-items-end mb-2">
                        <div class="col-12 col-md-5">
                            <label class="form-label small mb-1">Aseguradora</label>
                            <select id="psSeguro" class="form-select form-select-sm">
                                <option value="">— Seleccione —</option>
                                <?php
                                $resSegPac = $conexion->query("SELECT Id_seguro, Empresa_seguro FROM seguros WHERE estado = 1 ORDER BY Empresa_seguro");
                                while ($resSegPac && $sp = $resSegPac->fetch_assoc()):
                                ?>
                                <option value="<?php echo (int)$sp['Id_seguro']; ?>"><?php echo htmlspecialchars($sp['Empresa_seguro']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small mb-1">N° póliza</label>
                            <input type="text" id="psPoliza" class="form-control form-control-sm" maxlength="60">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small mb-1">Prioridad</label>
                            <select id="psPrioridad" class="form-select form-select-sm">
                                <option value="Primario">Primario</option>
                                <option value="Secundario">Secundario</option>
                                <option value="Terciario">Terciario</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <button type="button" class="btn btn-sm btn-success w-100" onclick="agregarSeguroPaciente()">+ Agregar</button>
                        </div>
                    </div>
                    <div id="psLista"><div class="text-muted small">—</div></div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
