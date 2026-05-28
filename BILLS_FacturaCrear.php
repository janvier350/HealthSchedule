<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Language" content="en">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Calendar - Calendars are used in a lot of apps. We thought to include one for React.</title>
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

    <!-- cdn data tables -->
    <!-- <link href="https://cdn.datatables.net/v/dt/dt-1.13.2/datatables.min.css"/>
    <script src="https://cdn.datatables.net/v/dt/dt-1.13.2/datatables.min.js"></script>
 -->
<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap4.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap4.min.css
">
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
                                    <div>Registrar Bills
                                        <div class="page-title-subheading">Bills.
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
                                                <h5 class="card-title">Información del bills</h5>
                                                <form class="needs-validation" novalidate>
                                                    <div class="form-row">
                                                        <div class="col-md-2 mb-3">
                                                            <label for="validationCustomID">ID de bills</label>
                                                            <input type="text" class="form-control" id="validationCustomID" placeholder="billed12212" value="" required>
                                                            <div class="valid-feedback">
                                                                Looks good!
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2 mb-3">
                                                            <label for="validationCustom05">Forma de pago</label>
                                                            <div class="position-relative form-group">
                                                                <select name="select" id="validationCustom05" class="form-control" required>
                                                                <option>Default Select</option>
                                                                <option value="1">Efectivo</option>
                                                                <option value="2">Debito</option>
                                                                <option value="3">Transferencia</option>
                                                                <option value="4">Movil Pay</option>
                                                                <option value="5">Cheque</option>
                                                                </select>
                                                            </div>
                                                            <div class="invalid-feedback">
                                                                Please provide a valid Forma de pago.
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3 mb-3">
                                                            <h5 class="card-title">Date Bills</h5>
                                                                       <input type="date" class="form-control" data-toggle="datepicker-year">
                                                            <div class="valid-feedback">
                                                                Looks good!
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3 mb-3">
                                                            <label for="validationCustom02">Cliente</label>
                                                            <input type="text" class="form-control" id="validationCustom02" placeholder="Last name" value="Otto Zonesholder" required>
                                                            <div class="valid-feedback">
                                                                Looks good!
                                                            </div>
                                                        </div>
                                                       <div class="col-md-2 mb-3">
                                                           <label for="validationCustom04">Phone</label>
                                                           <input type="text" class="form-control" id="validationCustom04" placeholder="Pone" required>
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
                                                                <input type="text" class="form-control" id="validationCustomUsername" placeholder="name@mail.com" aria-describedby="inputGroupPrepend" required>
                                                                <div class="invalid-feedback">
                                                                    Please choose a E-MAIL.
                                                                </div>
                                                            </div>
                                                        </div>
                                                     
                                                        <div class="col-md-3 mb-3">
                                                            <label for="validationCustom05">Valor Bills</label>
                                                            <input type="text" class="form-control" id="validationCustom04" placeholder="$ 1.500,80" required>
                                                            <div class="invalid-feedback">
                                                                Please provide a valid Sex.
                                                            </div>
                                                        </div>

                                                        <div class="col-md-5 mb-3">
                                                            <label for="validationCustom05">Clinician (Medico)</label>
                                                            <div class="position-relative form-group">
                                                                <select name="select" id="validationCustom05" class="form-control" required>
                                                                <option value="1">Default Select</option>
                                                                <option value="2">Silvia Ross</option>
                                                                <option value="3">Medico #2</option>
                                                                <option value="3">Medico #3</option>
                                                               
                                                            </select>
                                                        </div>
                                                        </div>

                                                    </div>
                                                        
                                                    <div class="form-row">
                                                            <div class="col-md-7 mb-3">
                                                             <label for="validationCustom05">Address</label>
                                                            <div class="position-relative form-group">
                                                                 <input type="text" class="form-control" id="validationCustom04" placeholder="25 huber pl New Rochelle NY 10801-6818" required>
                                                            </div>
                                                            </div>

                                                              <div class="col-md-3 mb-3">
                                                            
                                                            <label for="validationCustom05">Suplementos</label>
                                                            <div class="position-relative form-group">
                                                                 <div class="form-group">
                                                                   <div class="form-check">
                                                                       <input id="closeButton" type="checkbox" value="checked" class="form-check-input"/>
                                                                       <label class="form-check-label" for="closeButton">
                                                                           Suplemento # 1
                                                                       </label>
                                                                   </div>
                                                                   <div class="form-check">
                                                                       <input id="addBehaviorOnToastClick" type="checkbox" value="checked" class="form-check-input"/>
                                                                       <label class="form-check-label" for="addBehaviorOnToastClick">
                                                                           Suplemento # 2
                                                                       </label>
                                                                   </div>
                                                                   <div class="form-check">
                                                                       <input disabled id="addBehaviorOnToastCloseClick" type="checkbox" value="checked" class="form-check-input"/>
                                                                       <label class="form-check-label" for="addBehaviorOnToastCloseClick">
                                                                           Suplemento # 3
                                                                       </label>
                                                                   </div>
                                                                   <div class="form-check">
                                                                       <input id="debugInfo" type="checkbox" value="checked" class="form-check-input"/>
                                                                       <label class="form-check-label" for="debugInfo">
                                                                           Suplemento # 4
                                                                       </label>
                                                                   </div>
                                                               </div>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-2 mb-3">
                                                             <label for="validationCustom05">Therapy</label>
                                                            <div class="position-relative form-group">
                                                                 <div class="form-group">
                                                                   <div class="form-check">
                                                                       <input id="closeButton" type="checkbox" value="checked" class="form-check-input"/>
                                                                       <label class="form-check-label" for="closeButton">
                                                                           Inbody Test $ 150
                                                                       </label>
                                                                   </div>
                                                                 
                                                                   
                                                                   <div class="form-check">
                                                                       <input id="debugInfo" type="checkbox" value="checked" class="form-check-input"/>
                                                                       <label class="form-check-label" for="debugInfo">
                                                                           Light Therapy $ 50
                                                                       </label>
                                                                   </div>
                                                                   <div class="form-check">
                                                                       <input id="debugInfo" type="checkbox" value="checked" class="form-check-input"/>
                                                                       <label class="form-check-label" for="debugInfo">
                                                                           Foot Detox $ 50
                                                                       </label>
                                                                   </div>
                                                                   <div class="form-check">
                                                                       <input id="debugInfo" type="checkbox" value="checked" class="form-check-input"/>
                                                                       <label class="form-check-label" for="debugInfo">
                                                                           Vibration Plate $ 30
                                                                       </label>
                                                                   </div>
                                                                   <div class="form-check">
                                                                       <input id="debugInfo" type="checkbox" value="checked" class="form-check-input"/>
                                                                       <label class="form-check-label" for="debugInfo">
                                                                           Exercise Techniques $ 70
                                                                       </label>
                                                                   </div>
                                                                   <div class="form-check">
                                                                       <input id="debugInfo" type="checkbox" value="checked" class="form-check-input"/>
                                                                       <label class="form-check-label" for="debugInfo">
                                                                           ADN $ 80
                                                                       </label>
                                                                   </div>
                                                                   <div class="form-check">
                                                                       <input id="debugInfo" type="checkbox" value="checked" class="form-check-input"/>
                                                                       <label class="form-check-label" for="debugInfo">
                                                                           Follow Up With Light $ 140
                                                                       </label>
                                                                   </div>
                                                                   <div class="form-check">
                                                                       <input id="debugInfo" type="checkbox" value="checked" class="form-check-input"/>
                                                                       <label class="form-check-label" for="debugInfo">
                                                                           Initial and Follow Ups $ 50
                                                                       </label>
                                                                   </div>
                                                                   <div class="form-check">
                                                                       <input id="debugInfo" type="checkbox" value="checked" class="form-check-input"/>
                                                                       <label class="form-check-label" for="debugInfo">
                                                                           6 Consultations $ 650
                                                                       </label>
                                                                   </div>
                                                               </div>
                                                            </div>    
                                                        </div>

                                                        </div>
                                                      
                                                  <div class="form-row">
                                                        <div class="col-md-2 mb-3">
                                                           <label for="validationCustom05">Costo Consulta</label>
                                                            <div class="position-relative form-group">
                                                                 <input type="text" class="form-control" id="validationCustom04" placeholder="$1.500" required>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-2 mb-3">
                                                            
                                                            <label for="validationCustom05">Suma Items</label>
                                                            <div class="position-relative form-group">
                                                                 <input type="text" class="form-control" id="validationCustom04" placeholder="$50" required>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2 mb-3">
                                                          <label for="validationCustom05">Suma Suplementos</label>
                                                            <div class="position-relative form-group">
                                                                 <input type="text" class="form-control" id="validationCustom04" placeholder="$150" required>
                                                            </div>
                                                        </div>
                                                         <div class="col-md-2 mb-3">
                                                          <label for="validationCustom05">Abono</label>
                                                            <div class="position-relative form-group">
                                                                <input type="text" class="form-control" id="validationCustom04" placeholder="1.000" value="10"  required>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2 mb-3">
                                                           
                                                        <label for="validationCustom05" class="text-danger"><b> Total Bills</b></label>
                                                            <div class="position-relative form-group">
                                                                 <input type="text" class="form-control text-danger" id="validationCustom04" placeholder="$1.700,8"  value="1.700,8" required>
                                                            </div>
                                                        </div>
                                                       <div class="col-md-2 mb-3">
                                                           
                                                        <label for="validationCustom05" class="text-danger"><b> Saldo</b></label>
                                                            <div class="position-relative form-group">
                                                                 <input type="text" class="form-control text-danger" id="validationCustom04" placeholder="$450"  value="700,8" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    
                                               
                                                      <div class="form-group">
                                                        <div class="form-check">
                                                           <!--  <input class="form-check-input" type="checkbox" value="" id="invalidCheck" required>
                                                            <label class="form-check-label" for="invalidCheck">
                                                                Agree to terms and conditions
                                                            </label> -->
                                                            <div class="invalid-feedback">
                                                                You must agree before submitting.
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <button class="btn btn-success" type="submit">Save Bills</button>

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
                <th>Id Bills</th>
                <th>Date</th>
                <th>Name</th>
                <th>Valor Bills</th>
                <th>Saldo</th>
                <td>Estado</td>
                <th>Clinician</th>
                
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>billed65</td>
                <td>2011-04-25</td>
                <td>Tiger Nixon</td>
                <td>$ 750</td>
                <td>0</td>
                <td>Pagado</td>
                <td>Silvia Ross</td>
                
            </tr>
            <tr>
                <td>billed66</td>
                <td>2011-04-25</td>
                <td>Garrett Winters</td>
                <td>$170,750</td>
                <td>$ 750</td>
                <td>Pendiente</td>
                <td>Loayda</td>
                
            </tr>
            <tr>
                <td>billed67</td>
                <td>2011-04-25</td>
                <td>Ashton Cox</td>
                <td>$86,000</td>
                <td>$ 6000</td>
                <td>Pendiente</td>
                <td>Silvia Ross</td>
               
              
            </tr>
            <tr>
                <td>billed68</td>
                <td>2011-04-25</td>
                <td>Cedric Kelly</td>
                <td>$433,060</td>
                <td>$ 33000</td>
                <td>Pendiente</td>
                <td>Loayda</td>
                
                
            </tr>
            <tr>
                <td>billed69</td>
                <td>2011-04-25</td>
                <td>Airi Satou</td>
                <td>$162,700</td>
                <td>$ 700</td>
                <td>Pendiente</td>
                <td>Silvia Ross</td>
            </tr>
            <tr>
                <td>billed70</td>
                <td>2011-04-25</td>
                <td>Brielle Williamson</td>
                <td>$372,000</td>
                <td>0</td>
                <td>Pagado</td>
                <td>Silvia Ross</td>
            </tr>
            
        </tbody>
        <tfoot>
            <tr>
                <th>Id Bills</th>
                <th>Date</th>
                <th>Name</th>
                <th>Valor Bills</th>
                <th>Saldo</th>
                <td>Estado</td>
                <th>Clinician</th>
            </tr>
        </tfoot>
    </table>
                                        
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

</body>
</html>
