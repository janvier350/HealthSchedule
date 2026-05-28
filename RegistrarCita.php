<?php
ob_start();
?>
<?php
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion=conectarse();
$fechaInicio;
$fechaFinn;
$startDate = date("Y-m-d");
    if ($_POST['fechainicio']!="") {
        $fechaInicio=$_POST['fechainicio'];
        $fechaFinn = $_POST['fechafin'];
    }else{
        $fechaInicio = $startDate;
        $fechaFinn = $startDate;
    }
    if(!isset($_SESSION["rol"])){
		header("Location: break.php");
	}else {
        $now = time(); // Checking the time now when home page starts.
        if ($now > $_SESSION['expire']) {
            session_destroy();
            header("Location: expirada.php");
        }}        
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximun-scale=1.0, minimun-scale=1.0">
        <title>Medical - Appointment|Inicia Sesion </title>
        <link rel="stylesheet" href="css/chosen.css">
         <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
        <!-- Minified JS library -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
        <!-- Minified Bootstrap JS -->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>          
        <link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.min.css" rel="stylesheet" id="bootstrap-css">
        <script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js"></script>
        
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        
        <script>
        function fechaBuscar(){
            var inicio = document.formul.fechainicio.value;
            var fin = document.formul.fechafin.value;            
            window.location='Home.php';
        }
        </script>
        <style>
        .button {
          text-decoration: none;
          color: white;
          padding: 10px;
          text-transform: uppercase;
          display: inline-block;
          text-shadow: -2px 2px 0 rgba(0, 0, 0, 0.2);
          font-weight: bold;
          padding-right: 50px;
          margin: 10px;
          -moz-transition: all 0.1s linear;
          -o-transition: all 0.1s linear;
          -webkit-transition: all 0.1s linear;
          transition: all 0.1s linear;
          -moz-transform: translateZ(0);
          -ms-transform: translateZ(0);
          -webkit-transform: translateZ(0);
          transform: translateZ(0);
          /*
          Kinda replicates keyline but looks dumb.
          @include filter(
            drop-shadow(0 1px 0 rgba(blue, 0.2))
            drop-shadow(0 -1px 0 rgba(blue, 0.2))
          );
          */
        }
        .button.blue {
          background: -moz-linear-gradient(top, #a2d3e9, #7abedf);
          background: -webkit-linear-gradient(top, #a2d3e9, #7abedf);
          background: linear-gradient(to bottom, #a2d3e9, #7abedf);
          box-shadow: -1px 0px 1px #6fadcb, 0px 1px 1px #54809d, -2px 1px 1px #6fadcb, -1px 2px 1px #54809d, -3px 2px 1px #6fadcb, -2px 3px 1px #54809d, -4px 3px 1px #6fadcb, -3px 4px 1px #54809d, -5px 4px 1px #6fadcb, -4px 5px 1px #54809d, -6px 5px 1px #6fadcb, -6px 7px 0 rgba(0, 0, 0, 0.05), -5px 8px 0 rgba(0, 0, 0, 0.05), -3px 9px 0 rgba(0, 0, 0, 0.04), -2px 10px 0 rgba(0, 0, 0, 0.04), -1px 11px 0 rgba(0, 0, 0, 0.03), 0px 12px 0 rgba(0, 0, 0, 0.03), 1px 13px 0 rgba(0, 0, 0, 0.02), 2px 14px 0 rgba(0, 0, 0, 0.02), 3px 15px 0 rgba(0, 0, 0, 0.01), 4px 16px 0 rgba(0, 0, 0, 0.01), 5px 17px 0 rgba(0, 0, 0, 0.01), 6px 18px 0 rgba(0, 0, 0, 0.01), inset 0 4px 5px -2px rgba(255, 255, 255, 0.5), inset 0 1px 0 0 rgba(0, 0, 0, 0.3);
        }
        .button.yellow {
          background: -moz-linear-gradient(top, #f2d851, #ecc92b);
          background: -webkit-linear-gradient(top, #f2d851, #ecc92b);
          background: linear-gradient(to bottom, #f2d851, #ecc92b);
          color: black;
          text-shadow: -2px 2px 0 rgba(255, 255, 255, 0.3);
          box-shadow: -1px 0px 1px #d9b826, 0px 1px 1px #b1961d, -2px 1px 1px #d9b826, -1px 2px 1px #b1961d, -3px 2px 1px #d9b826, -2px 3px 1px #b1961d, -4px 3px 1px #d9b826, -3px 4px 1px #b1961d, -5px 4px 1px #d9b826, -4px 5px 1px #b1961d, -6px 5px 1px #d9b826, -6px 7px 0 rgba(0, 0, 0, 0.05), -5px 8px 0 rgba(0, 0, 0, 0.05), -3px 9px 0 rgba(0, 0, 0, 0.04), -2px 10px 0 rgba(0, 0, 0, 0.04), -1px 11px 0 rgba(0, 0, 0, 0.03), 0px 12px 0 rgba(0, 0, 0, 0.03), 1px 13px 0 rgba(0, 0, 0, 0.02), 2px 14px 0 rgba(0, 0, 0, 0.02), 3px 15px 0 rgba(0, 0, 0, 0.01), 4px 16px 0 rgba(0, 0, 0, 0.01), 5px 17px 0 rgba(0, 0, 0, 0.01), 6px 18px 0 rgba(0, 0, 0, 0.01), inset 0 4px 5px -2px rgba(255, 255, 255, 0.5), inset 0 1px 0 0 rgba(0, 0, 0, 0.3);
        }
        .button.yellow:after, .button.yellow:before {
          background: black;
        }
        .button.yellow:after {
          -webkit-filter: drop-shadow(-2px 0 0 rgba(255, 255, 255, 0.4));
          filter: drop-shadow(-2px 0 0 rgba(255, 255, 255, 0.4));
        }
        .button.yellow:before {
          -webkit-filter: drop-shadow(0 -2px 0 rgba(255, 255, 255, 0.35));
          filter: drop-shadow(0 -2px 0 rgba(255, 255, 255, 0.35));
        }
        .button.yellow .arrow {
          -webkit-filter: drop-shadow(-2px 0 0 rgba(255, 255, 255, 0.4));
          filter: drop-shadow(-2px 0 0 rgba(255, 255, 255, 0.4));
        }
    .button:active {
      box-shadow: none;
      -moz-transform: translate3d(-6px, 6px, 0);
      -ms-transform: translate3d(-6px, 6px, 0);
      -webkit-transform: translate3d(-6px, 6px, 0);
      transform: translate3d(-6px, 6px, 0);
    }
    .button .arrow {
      -webkit-filter: drop-shadow(-2px 0 0 rgba(0, 0, 0, 0.2));
      filter: drop-shadow(-2px 0 0 rgba(0, 0, 0, 0.2));
    }
    .button:after {
      -webkit-filter: drop-shadow(-2px 0 0 rgba(0, 0, 0, 0.2));
      filter: drop-shadow(-2px 0 0 rgba(0, 0, 0, 0.2));
    }
    .button:after, .button:before {
      position: absolute;
      content: " ";
      right: 15px;
      top: 14px;
      width: 6px;
      height: 18px;
      background: white;
      -moz-transform: rotate(-45deg);
      -ms-transform: rotate(-45deg);
      -webkit-transform: rotate(-45deg);
      transform: rotate(-45deg);
      display: block;
      z-index: 2;
    }
    .button:before {
      height: 14px;
      top: 26px;
      right: 16px;
      z-index: 3;
      -moz-transform: rotate(-137deg);
      -ms-transform: rotate(-137deg);
      -webkit-transform: rotate(-137deg);
      transform: rotate(-137deg);
      -webkit-filter: drop-shadow(0 -2px 0 rgba(0, 0, 0, 0.15));
      filter: drop-shadow(0 -2px 0 rgba(0, 0, 0, 0.15));
    }

    body {
      padding: 50px;
    }
</style>
        <style>
                /** Import ROBOTO font **/


    body
    {
      font-family: 'Roboto', sans-serif;
      color: #B7C4CB;
      background-color: #F7F9F9;
      margin:0;
      padding:0;
      width: 100%;
      height: 100%;
    }

    body > div {
        margin-top: 10px;
    }

    .tabbable > .nav-tabs > li > p {
        border: solid 1px transparent;
        padding-top: 28px;
        padding-bottom: 0px;
        padding-left: 12px;
        padding-right: 12px;
        margin-right: -1px;
    }

    a, .account-link
    {
        color: #72B032;
    }

    .account-type
    {
        font-family: 'Roboto', sans-serif;
        color: #A0AEB6;
        font-size: 18px;
        font-weight: bold;
        line-height: 20px;
    }

    .account-amount
    {
        font-family: 'Roboto', sans-serif;
        color: #A0AEB6;
        font-size: 14px;
        line-height: 16px;
    }

    .account-link
    {
        font-family: 'Roboto', sans-serif;
        color: #72B032;
        font-size: 14px;
        line-height: 16px;
        cursor: pointer;
    }

    /* TABS */
    .tabs-left > .nav-tabs{
        margin-right:0px;
        padding: 0;
        height: 700px; /* Debe ser el mismo height que el que tenga .tab-content */
    }

    /* CONTENIDO DE LOS TABS */
    .tab-content {
        background-color: #FFFFFF;
        border:solid 1px #DCE1E5;
        border-left-style: none;
        height: 700px; /* Debe ser el mismo height que el que tenga .tabs-left > .nav-tabs */
        padding-left: 50px;

        border-radius: 0px 4px 4px 4px;
        -moz-border-radius: 0px 4px 4px 4px;
        -webkit-border-radius: 0px 4px 4px 4px;

        -webkit-box-shadow: 0px 0px 18px 2px rgba(0,0,0,0.05);
        -moz-box-shadow: 0px 0px 18px 2px rgba(0,0,0,0.05);
        box-shadow: 0px 0px 18px 2px rgba(0,0,0,0.05);
    }

    .tab-content > div {
        margin-top: 26px;
    }

    /* Color de los enlaces de los tabs */
    .tabs-left > .nav-tabs > a {
        color: #7FAD30;
    }

    /* Formato del primer elemento */
    .tabs-left > .nav-tabs > li:nth-child(1) > a,
    .tabs-left > .nav-tabs > li:nth-child(1) > a:hover,
    .tabs-left > .nav-tabs > li:nth-child(1) > a:focus,

    .tabs-left > .nav-tabs > li:nth-child(1) > .tabx,
    .tabs-left > .nav-tabs > li:nth-child(1) > .tabx:hover,
    .tabs-left > .nav-tabs > li:nth-child(1) > .tabx:focus 
    {
        border-top-left-radius: 4px;
    }

    /* Formato del tab activo */
    .tabs-left > .nav-tabs .active > a, 
    .tabs-left > .nav-tabs .active > a:hover, 
    .tabs-left > .nav-tabs .active > a:focus,

    .tabs-left > .nav-tabs .active .tabx, 
    .tabs-left > .nav-tabs .active .tabx:hover, 
    .tabs-left > .nav-tabs .active .tabx:focus
    {
        background-color: #FFFFFF;

        border-bottom: 1px solid #DCE1E5;
        border-left: 1px solid #DCE1E5;
        border-bottom-left-radius: 0px;
        border-right-style: none;

        margin-right: -1px;

        -webkit-box-shadow: -4px 0px 18px -1px rgba(0,0,0,0.05);
        -moz-box-shadow: -4px 0px 18px -1px rgba(0,0,0,0.05);
        box-shadow: -4px 0px 18px -1px rgba(0,0,0,0.05);
    }

    /* Formato de los tabs en general */
    .tabs-left > .nav-tabs > li > a, 
    .tabs-left > .nav-tabs > li > .tabx {
        margin-bottom: 0;
        padding-bottom: 0;

        padding-top: 2em;

        min-height: 105px;
        min-width: 105px;

        border:solid 1px #DCE1E5;
        border-radius: 0px;
    }
</style>          
        <style>
            .calendar-day {
  width: 100px;
  min-width: 100px;
  max-width: 100px;
  height: 80px;
}
.calendar-table {
  margin: 0 auto;
  width: 700px;
}
.selected {
  background-color: #eee;
}
.outside .date {
  color: #ccc;
}
.timetitle {
  white-space: nowrap;
  text-align: right;
}
.Atendido {
  border-top: 1px solid #b2dba1;
  border-bottom: 1px solid #b2dba1;
  background-image: linear-gradient(to bottom, #dff0d8 0px, #c8e5bc 100%);
  background-repeat: repeat-x;
  color: #3c763d;
  border-width: 1px;
  font-size: .75em;
  padding: 0 .75em;
  line-height: 2em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 1px;
}
.Cancelado {
  border-top: 1px solid #E74C3C;
  border-bottom: 1px solid #E74C3C;
  background-image: linear-gradient(to bottom, #F1948A 0px, #F5B7B1 100%);
  background-repeat: repeat-x;
  color: #943126  ;
  border-width: 1px;
  font-size: .75em;
  padding: 0 .75em;
  line-height: 2em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 1px;
}
.Atrasado {
  border-top: 1px solid #F39C12  ;
  border-bottom: 1px solid #F39C12  ;
  background-image: linear-gradient(to bottom, #F8C471 0px, #FAD7A0 100%);
  background-repeat: repeat-x;
  color: #B9770E;
  border-width: 1px;
  font-size: .75em;
  padding: 0 .75em;
  line-height: 2em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 1px;
}
.Pendiente {
  border-top: 1px solid #9acfea  ;
  border-bottom: 1px solid #9acfea  ;
  background-image: linear-gradient(to bottom, #d9edf7 0px, #b9def0 100%);
  background-repeat: repeat-x;
  color: #31708f;
  border-width: 1px;
  font-size: .75em;
  padding: 0 .75em;
  line-height: 2em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 1px;
} 
.event.begin {
  border-left: 1px solid #b2dba1;
  border-top-left-radius: 4px;
  border-bottom-left-radius: 4px;
}
.event.end {
  border-right: 1px solid #b2dba1;
  border-top-right-radius: 4px;
  border-bottom-right-radius: 4px;
}
.event.all-day {
  border-top: 1px solid #9acfea;
  border-bottom: 1px solid #9acfea;
  background-image: linear-gradient(to bottom, #d9edf7 0px, #b9def0 100%);
  background-repeat: repeat-x;
  color: #31708f;
  border-width: 1px;
}
.event.all-day.begin {
  border-left: 1px solid #9acfea;
  border-top-left-radius: 4px;
  border-bottom-left-radius: 4px;
}
.event.all-day.end {
  border-right: 1px solid #9acfea;
  border-top-right-radius: 4px;
  border-bottom-right-radius: 4px;
}
.event.clear {
  background: none;
  border: 1px solid transparent;
}
.table-tight > thead > tr > th,
.table-tight > tbody > tr > th,
.table-tight > tfoot > tr > th,
.table-tight > thead > tr > td,
.table-tight > tbody > tr > td,
.table-tight > tfoot > tr > td {
  padding-left: 0;
  padding-right: 0;
}
.table-tight-vert > thead > tr > th,
.table-tight-vert > tbody > tr > th,
.table-tight-vert > tfoot > tr > th,
.table-tight-vert > thead > tr > td,
.table-tight-vert > tbody > tr > td,
.table-tight-vert > tfoot > tr > td {
  padding-top: 0;
  padding-bottom: 0;
}
          </style>
        <link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.1.0/css/font-awesome.min.css">
        <link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.min.css" rel="stylesheet" id="bootstrap-css">
        <script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js"></script>
        <script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
        <!------ Include the above in your HEAD tag ---------->
        <script>
          $(document).ready(function(){

            /**
             * This object controls the nav bar. Implement the add and remove
             * action over the elements of the nav bar that we want to change.
             *
             * @type {{flagAdd: boolean, elements: string[], add: Function, remove: Function}}
             */
            var myNavBar = {

                flagAdd: true,

                elements: [],

                init: function (elements) {
                    this.elements = elements;
                },

                add : function() {
                    if(this.flagAdd) {
                        for(var i=0; i < this.elements.length; i++) {
                            document.getElementById(this.elements[i]).className += " fixed-theme";
                        }
                        this.flagAdd = false;
                    }
                },

                remove: function() {
                    for(var i=0; i < this.elements.length; i++) {
                        document.getElementById(this.elements[i]).className =
                                document.getElementById(this.elements[i]).className.replace( /(?:^|\s)fixed-theme(?!\S)/g , '' );
                    }
                    this.flagAdd = true;
                }

            };

            /**
             * Init the object. Pass the object the array of elements
             * that we want to change when the scroll goes down
             */
            myNavBar.init(  [
                this.elements = elements.filter(id => document.getElementById(id) !== null);
            ]);

            /**
             * Function that manage the direction
             * of the scroll
             */
            function offSetManager(){

                var yOffset = 0;
                var currYOffSet = window.pageYOffset;

                if(yOffset < currYOffSet) {
                    myNavBar.add();
                }
                else if(currYOffSet == yOffset){
                    myNavBar.remove();
                }

            }

            /**
             * bind to the document scroll detection
             */
            window.onscroll = function(e) {
                offSetManager();
            }

            /**
             * We have to do a first detectation of offset because the page
             * could be load with scroll down set.
             */
            offSetManager();
            });
      </script>      
        <style>
         /*
        * Style tweaks
        * --------------------------------------------------
        */
        html,
        body {
            overflow-x: hidden; /* Prevent scroll on narrow devices */
        }
        body {
            padding-top: 100px;
        }
        footer {
            padding: 30px 0;
        }

        /*
         * Custom styles
         */
        .navbar-brand {
            font-size: 24px;
        }

        .navbar-container {
            padding: 20px 0 20px 0;
        }

        .navbar.navbar-fixed-top.fixed-theme {
            background-color: #000;
            border-color: #080808;
            box-shadow: 0 0 5px rgba(0,0,0,.8);
        }

        .navbar-brand.fixed-theme {
            font-size: 18px;
        }

        .navbar-container.fixed-theme {
            padding: 0;
        }

        .navbar-brand.fixed-theme,
        .navbar-container.fixed-theme,
        .navbar.navbar-fixed-top.fixed-theme,
        .navbar-brand,
        .navbar-container{
            transition: 0.8s;
            -webkit-transition:  0.8s;
        }
      </style>
        <script>
        (function(){
            'use strict';
            var $ = jQuery;
            $.fn.extend({
                filterTable: function(){
                    return this.each(function(){
                        $(this).on('keyup', function(e){
                            var $this = $(this), search = $this.val().toLowerCase(), target = $this.attr('data-filters'), $rows = $(target).find('tbody tr');
                            if(search == '') {
                                $rows.show(); 
                            } else {
                                $rows.each(function(){
                                    var $this = $(this);
                                    $this.text().toLowerCase().indexOf(search) === -1 ? $this.hide() : $this.show();
                                })
                            }
                        });
                    });
                }
            });
            $('[data-action="filter"]').filterTable();
        })(jQuery);

        $(function(){
            // attach table filter plugin to inputs
            $('[data-action="filter"]').filterTable();

            $('.container').on('click', '.panel-heading span.filter', function(e){
                var $this = $(this), 
                        $panel = $this.parents('.panel');

                $panel.find('.panel-body').slideToggle();
                if($this.css('display') != 'none') {
                    $panel.find('.panel-body input').focus();
                }
            });
            $('[data-toggle="tooltip"]').tooltip();
        })
        </script>
    </head>
    <body>    
    <?php
    
    $sqlSP = "CALL SP_CITAS();";
    $querySP = $conexion -> query ($sqlSP);
    $rawdata = array();
    $paci = array();
    $slipsum = array();
    $estado = array();
    $anio = array();
    $mes = array();
    $dia = array();
    $hini = array();
    $mini = array();
    $hfin = array();
    $mfin = array();
    $cont =0;
    $sql="SELECT A.IDCITA, A.IDPACIENTE,CONCAT(B.NOMBRES, ' ', B.APELLIDOS , ' -- TIPO: ', C.NOMBRES , ' -- ESTADO: ', A.ESTADO_CITA) AS PACIENTE, A.FECHA_CITA, A.HORA_INICIO, A.HORA_FIN ,SUBSTRING(A.FECHA_CITA, 1, 4) AS ANIO,SUBSTRING(A.FECHA_CITA, 6, 2) AS  MES,SUBSTRING(A.FECHA_CITA, 9, 2) AS  DIA,SUBSTRING(A.HORA_INICIO, 1, 2) AS  HINI,SUBSTRING(A.HORA_INICIO, 4, 2) AS  MINI,SUBSTRING(A.HORA_FIN, 1, 2) AS  HFIN,SUBSTRING(A.HORA_FIN, 4, 2) AS  MFIN, C.NOMBRES, A.ESTADO_CITA, A.COMENTARIO FROM AG_CITA A INNER JOIN AG_PACIENTE B ON A.IDPACIENTE = B.IDPACIENTE INNER JOIN AG_TIPOCONSULTA C ON A.IDTIPOCONSULTA = C.IDTIPOCONSULTA";
    //generamos la consulta
    $query = $conexion -> query ($sql);
      while ($valores = mysqli_fetch_array($query)) {
        $rawdata[$cont] =$valores;
        $cont++;
      }
    for($i=0;$i<count($rawdata);$i++){
            $paci[$i] = $rawdata[$i]["PACIENTE"];
            $slipsum[$i] = $rawdata[$i]["PACIENTE"];
            $estado[$i] = $rawdata[$i]["ESTADO_CITA"];
            $anio[$i] = $rawdata[$i]["ANIO"];
            $mes[$i] = $rawdata[$i]["MES"];
            $dia[$i] = $rawdata[$i]["DIA"];
            $hini[$i] = $rawdata[$i]["HINI"];
            $mini[$i] = $rawdata[$i]["MINI"];
            $hfin[$i] = $rawdata[$i]["HFIN"];
            $mfin[$i] = $rawdata[$i]["MFIN"];
        }
?>
    <nav id="header" class="navbar navbar-fixed-top">
            <div id="header-container" class="container navbar-container">
                <div class="navbar-header">
                    <img src="image/MedicalAppointment.jpg"  alt="Medial-Appointment" style="width:70%">
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a id="brand" class="navbar-brand" href="#">
                    </a>
                    
                </div>
                <div id="navbar" class="collapse navbar-collapse">
                    <ul class="nav navbar-nav">
            <li class="active"><a href="Home.php">Home</a></li>
           
            <li><a href="Pacientes.php">Pacientes</a></li>
            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Panel Control <span class="caret"></span></a>
              <ul class="dropdown-menu">
                <li><a href="Pacientes.php">Pacientes</a></li>
                <li><a href="Doctores.php">Doctores</a></li>
                <li><a href="Usuarios.php">Usuarios</a></li>
                
              </ul>
            </li>
          </ul>
      <ul class="nav navbar-nav navbar-right">
            <li><a href=""><?php echo $_SESSION["username"]?></a></li>
            <li><a href=""><?php echo $startDate?></a></li>
            <li><a href="TodoList.php" class= "text-danger">Todo List</span></a></li>
            <li><a href="salir.php">Cerrar Sesi&oacute;n<span class="sr-only">(current)</span></a></li>
          </ul>
                </div><!-- /.nav-collapse -->
            </div><!-- /.container -->
        </nav><!-- /.navbar -->


        <div class="container-fluid" style="margin-top: 30px">
        <div class="row-fluid">
        <div id="myTabHolder" class="span10 offset1 tabbable tabs-left">
        <ul class="nav nav-tabs">
            <li class="active">
                <p class="tabx" data-target="#lA" data-toggle="tab">
                <!-- <a href="#lA" data-toggle="tab"> -->
                    <span class="account-type">AGENDA</span><br/>
                    <span class="account-amount"></span><br/>
                    <a href="#" onclick="window.location.reload(true)">Actualizar Citas</a><br/>
                    <a href="#" class="account-link"><?php echo $cont;?> Citas Pendientes</a>
                <!-- </a> -->
                </p >
            </li>
            <li class="">
                <a href="#lB" data-toggle="tab">
                    <span class="account-type">CREAR CITA</span><br/>
                    <span class="account-amount"></span><br/>
                    <span class="account-link">A&ntilde;adir Cita</span>
                    
                </a>
            </li>
            <li class="">
                <a href="#lC" data-toggle="tab">
                    <span class="account-type">CALENDARIO PUBLICACIONES</span><br/>
                    <!--<span class="account-amount">$587.00</span><br/>-->
                    <!--<span class="account-link">Investments</span>-->
                </a>
            </li>
            <li class="">
                <a href="#RP" data-toggle="tab">
                    <span class="account-type">REPORTES</span><br/>
                    <span class="account-amount"></span><br/>
                    <span class="account-link"></span>
                </a>
            </li>
            <li class="">
                <a href="#RPD" data-toggle="tab">
                    <span class="account-type">DOCTOR</span><br/>
                    <span class="account-amount"></span><br/>
                    <span class="account-link"></span>
                </a>
            </li>
        </ul>
        <div class="tab-content container-fluid">
        <div class="tab-pane active" id="lA">
            <div>        			    
            <div class="container-fluid">
            <div class="row">
                <div class="col-sm-12 col-lg-12">
                    <div class="container theme-showcase">
              calendario
                    <div id="holder" class="row" ></div>
                    </div>
                </div>
            </div>
            </div>
            </div>
        </div>
        <div class="tab-pane" id="lB">             
                <div>
                    <div class="panel panel-success">
                          <div class="panel-heading">CREAR CITA</div>
                          <div class="panel-body">
                           <div class="container">
                                <div class="row main">
                                    <div class="panel-heading">
                                       <div class="panel-title text-center">
                                           
                                        </div>
                                    </div> 
                                    <div class="main-login main-center">
                                        <form class="form-horizontal" method="post" name =" consulta" action="class/Insert_cita.php">

                                            <div class="form-group">
                                                <label for="name" class="cols-sm-2 control-label"></label>
                                                <div class="row-sm-10">
                                                    <div class="input-group">
                                                        <span class="input-group-addon"><i class="fa fa-calendar" aria-hidden="true"></i></span>
                                                        <input type="date"  name="fechafactura" id="fechafactura" placeholder="fecha" required>
                                                        <script>document.getElementById('fechafactura').value = new Date().toISOString().substring(0, 10);</script>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label for="username" class="cols-sm-2 control-label"> </label>
                                                <div class="row-sm-10">
                                                    <div class="input-group">
                                                        <span class=" input-group-addon"><i class="fa fa-user fa" aria-hidden="true"></i></span>
                                                        <select class="" name="IdPaciente" required>
                                                            <option value="">Seleccione Paciente:</option>
                                                            <?php
                                                              $query = $conexion -> query ("SELECT * FROM AG_PACIENTE WHERE ESTADO = 'A'");
                                                              while ($valores = mysqli_fetch_array($query)) {
                                                                echo '<option value="'.$valores['IDPACIENTE'].'">'.$valores['NOMBRES'].' '.$valores['APELLIDOS'].'</option>';
                                                              }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label for="email" class="cols-sm-2 control-label"></label>
                                                <div class="cols-sm-10">
                                                    <div class="input-group">
                                                        <span class="input-group-addon"><i class="fa fa-clock-o fa" aria-hidden="true"></i></span>
                                                        <div class="input-group date" data-provide="datepicker">
                                                            <input type="time" name="timeIni" max="22:00" min="07:00" step="1800" required>                          
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label for="confirm" class="cols-sm-2 control-label"> </label>
                                                <div class="cols-sm-10">
                                                    <div class="input-group">
                                                        <span class="input-group-addon"><i class="fa fa-list fa-lg" aria-hidden="true"></i></span>
                                                        <select name="Idconsulta" required>
                                                        <option value="">Seleccione Tipo Consulta:</option>
                                                                <?php
                                                                  $query = $conexion -> query ("SELECT * FROM AG_TIPOCONSULTA WHERE ESTADO = 'A'");
                                                                  while ($valores = mysqli_fetch_array($query)) {
                                                                    echo '<option value="'.$valores['IDTIPOCONSULTA'].'">'.$valores['NOMBRES'].'</option>';
                                                                  }
                                                                ?>
                                                        </select> 
                                                    </div>
                                                </div>
                                            </div>
                                                <div class="form-group">
                                                <label for="confirm" class="cols-sm-2 control-label"> </label>
                                                <div class="cols-sm-10">
                                                    <div class="input-group">
                                                        <span class="input-group-addon"><i class="fa fa-user-md fa-lg" aria-hidden="true"></i></span>
                                                        <select name="IdDoctor" required>                                                            
                                                            <?php
                                                              $query = $conexion -> query ("SELECT * FROM ADM_DOCTOR WHERE ESTADO = 'A'");
                                                              while ($valores = mysqli_fetch_array($query)) {
                                                                echo '<option value="'.$valores['IDDOCTOR'].'">'.$valores['NOMBRES'].' '.$valores['APELLIDOS'].'</option>';
                                                              }
                                                            ?>
                                                            </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <br> 
                                             <br>
                                            <br>
                                            <div class="form-group">
                                                <label for="confirm" class="cols-sm-2 control-label"> </label>
                                                <div class="cols-sm-10">
                                                    <button type="button" class="btn btn-default" data-dismiss="modal">TodoList</button>
                                             <input type="submit" name="submit" value="Guardar" class="btn btn-danger" /> 
                                                </div>
                                            </div>
                                             <div class="form-group">
                                                <label for="confirm" class="cols-sm-2 control-label"> </label>
                                                <div class="cols-sm-10">
                                                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                                             <input type="submit" name="submit" value="Guardar" class="btn btn-success" /> 
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>                                                         
                          </div>
                        </div>            
            </div>                                               
        </div>
        <div class="tab-pane" id="lC">                     
            <div>
                 <div class="panel panel-success ">
                          <div class="panel-heading">REGISTRO DE PACIENTE</div>
                          <div class="panel-body">
                           <div class="container">
                    <div class="row main">
                        <div class="panel-heading">
                           <div class="panel-title text-center">
                             
                            </div>
                        </div>                                             
                        <div class="main-login main-center">
                            
                            <form class="form-horizontal" method="post" action="class/Insert_Paciente.php">
                                  <div class="form-row">
                                    
                                    <div class="col-md-3 mb-3">
                                      <label for="validationDefault01">ID</label>
                                      <input type="text" class="input-group" id="cedula" placeholder="ID" value="09230065899001" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                      <label for="password">First name</label>
                                      <input type="text" class="input-group"  name="nombres" placeholder="First name" value="Mark" required>
                                    </div>
                                     
                                    
                                    <div class="col-md-3 mb-3">
                                      <label for="password">Last name</label>
                                      <input type="text" class="input-group"  name="apellidos" placeholder="Last name" value="Otto" required>
                                    </div>
                                     
                                    <div class="col-md-3 mb-2">
                                      <label class="mr-sm-2" for="inlineFormCustomSelect">Title</label>
                                      <select class="custom-select" id="title">
                                        <option selected>Choose...</option>
                                        <option value="1">Dr</option>
                                        <option value="2">Fr</option>
                                        <option value="3">Master</option>
                                        <option value="1">Miss</option>
                                        <option value="2">Mr</option>
                                        <option value="3">Mrs</option>
                                        <option value="1">Ms</option>
                                        <option value="2">Mx</option>
                                        <option value="3">Pr</option>
                                        <option value="2">Prof</option>
                                        <option value="3">Rev</option>
                                      </select>
                                    </div>
                                    
                                    
                                    
                                  </div>
                                  <div class="form-row">
                                     
                                    <div class="col-md-3 mb-3">
                                      <label for="password">Telefono</label>
                                      <input type="text" class="input-group"  name"telefono" placeholder="0994154441" value="0994154441" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                      <label for="password">E-mail</label>
                                      <input type="text" class="input-group"  name="email" placeholder="myemail@mydomian.com" value="myemail@mydomian.com" required>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                      <label class="mr-sm-2" for="inlineFormCustomSelect">Sex</label>
                                      <select class="custom-select" id="sex">
                                        <option selected>Choose...</option>
                                        <option value="1">Male</option>
                                        <option value="2">Fame</option>
                                     
                                      </select>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                      <label class="mr-sm-2" for="inlineFormCustomSelect">Gender Identity</label>
                                      <select class="custom-select" id="genderIdentity">
                                        <option selected>Choose...</option>
                                        <option value="1">Male</option>
                                        <option value="2">Fame</option>
                                        <option value="1">Transgender man/trans man/famale-to-male(FTM)</option>
                                        <option value="2">Trasngender woman/trans woman/male-to-famele(MTF)</option>
                                        <option value="1">Genderqueer/ gender nonconforming neither exclusively male nor famale</option>
                                        <option value="2">Decline to answer</option>
                                      </select>
                                    </div>
                                  </div>
                                  
                                  <div class="form-row">
                                     
                                   
                                    <div class="col-md-3 mb-3">
                                      <label for="password">BOD</label>
                                      <input type="date" class="input-group"  name="feNac" required>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                      
                                    </div>
                                    <div class="col-md-3 mb-3">
                                       
                                    </div>
                                </div>
                                <div class="form-row-group">
                                    
                                    <div class="col-md-12 mb-3">
                                       <hr size="4px" width="90%" noshade="noshade"  />
                                        <label for="confirm" class="cols-sm-2 control-label"> </label>
                                    <div class="cols-sm-10">
                                        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                                 <button type="submit"  class="btn btn-success class">Guardar</button>
                                    </div>
                                    </div>
                                </div>
                            <br>
                                  <!--<button class="btn btn-primary" type="submit">Submit form</button>-->
                                  
                                    
                                
                                  
                                </form>
                           
                            
                        </div>
                    </div>
        </div><br><br>                                                  
          </div>
        </div>
        <!--    <div class="panel panel-success ">-->
        <!--                  <div class="panel-heading">REGISTRO DE PACIENTE</div>-->
        <!--                  <div class="panel-body">-->
        <!--                   <div class="container">-->
        <!--            <div class="row main">-->
        <!--                <div class="panel-heading">-->
        <!--                   <div class="panel-title text-center">-->
                             
        <!--                    </div>-->
        <!--                </div>                                             -->
        <!--                <div class="main-login main-center">-->
                            
                            
        <!--                    <form class="form-horizontal" method="post" action="class/Insert_Paciente.php">-->

        <!--                                    <div class="form-group">-->
        <!--                                        <label for="password" class="cols-sm-2 control-label"></label>-->
        <!--                                        <div class="cols-sm-10">-->
        <!--                                            <div class="input-group">-->
        <!--                                                <span class="input-group-addon"> <i class="fa fa-user fa-lg" aria-hidden="true"></i></span>-->
        <!--                                                   <div class="input-group date" >-->
        <!--                                                        <input type="text" name="nombres" placeholder="Nombres" required>-->
        <!--                                                   </div>-->
        <!--                                            </div>-->
        <!--                                    </div>-->
        <!--                                    </div>-->
                                            
        <!--                                    <div class="form-group">-->
        <!--                                        <label for="password" class="cols-sm-2 control-label"></label>-->
        <!--                                        <div class="cols-sm-10">-->
        <!--                                            <div class="input-group">-->
        <!--                                                <span class="input-group-addon"> <i class="fa fa-user fa-lg" aria-hidden="true"></i></span>-->
        <!--                                                   <div class="input-group date" >-->
        <!--                                                        <input type="text" name="apellidos" placeholder="Apellidos" required>-->
        <!--                                                   </div>-->
        <!--                                            </div>-->
        <!--                                    </div>-->
        <!--                                    </div>-->
                                            
        <!--                                    <div class="form-group">-->
        <!--                                        <label for="password" class="cols-sm-2 control-label"></label>-->
        <!--                                        <div class="cols-sm-10">-->
        <!--                                            <div class="input-group">-->
        <!--                                                <span class="input-group-addon"> <i class="fa fa-envelope fa-lg" aria-hidden="true"></i></span>-->
        <!--                                                   <div class="input-group date" >-->
        <!--                                                        <input type="text" name="email" placeholder="correo electronico" required>-->
        <!--                                                   </div>-->
        <!--                                            </div>-->
        <!--                                    </div>-->
        <!--                                    </div>-->
                                            
        <!--                                    <div class="form-group">-->
        <!--                                        <label for="password" class="cols-sm-2 control-label"></label>-->
        <!--                                        <div class="cols-sm-10">-->
        <!--                                            <div class="input-group">-->
        <!--                                                <span class="input-group-addon"> <i class="fa fa-calendar fa-lg" aria-hidden="true"></i></span>-->
        <!--                                                   <div class="input-group date" >-->
        <!--                                                        <input type="date" name="feNac" placeholder="fecha nacimiento">-->
        <!--                                                   </div>-->
        <!--                                            </div>-->
        <!--                                    </div>-->
        <!--                                    </div>-->
                                            
        <!--                                    <div class="form-group">-->
        <!--                                        <label for="password" class="cols-sm-2 control-label"></label>-->
        <!--                                        <div class="cols-sm-10">-->
        <!--                                            <div class="input-group">-->
        <!--                                                <span class="input-group-addon"> <i class="fa fa-phone fa-lg" aria-hidden="true"></i></span>-->
        <!--                                                   <div class="input-group date" >-->
        <!--                                                        <input type="text" name="telefono" placeholder="teléfono" required>-->
        <!--                                                   </div>-->
        <!--                                            </div>-->
        <!--                                    </div>-->
        <!--                                    </div>-->
                                            
                                         
                                
        <!--                        <br>-->

        <!--                        <div class="form-group">-->
        <!--                            <label for="confirm" class="cols-sm-2 control-label"> </label>-->
        <!--                            <div class="cols-sm-10">-->
        <!--                                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>-->
        <!--                         <button type="submit"  class="btn btn-success class">Guardar</button>-->
        <!--                            </div>-->
        <!--                        </div>-->



        <!--                    </form>-->
        <!--                </div>-->
        <!--            </div>-->
        <!--</div><br><br>                                                  -->
        <!--  </div>-->
        <!--</div>-->
            </div>
        </div>
        <div class="tab-pane" id="lD">
            <div>
                <div class="panel panel-danger">
                          <div class="panel-heading">REGISTRO DE DOCTOR</div>
                          <div class="panel-body">
                           <div class="container">
                                <div class="row main">
                                    <div class="panel-heading">
                                       <div class="panel-title text-center">

                                        </div>
                                    </div> 


                                    <div class="main-login main-center">
                                        <form class="form-horizontal" method="post" action="#">
                                                        <div class="form-group">
                                                            <label for="password" class="cols-sm-2 control-label"></label>
                                                            <div class="cols-sm-10">
                                                                <div class="input-group">
                                                                    <span class="input-group-addon"> <i class="fa fa-user fa-lg" aria-hidden="true"></i></span>
                                                                       <div class="input-group date" >
                                                                            <input type="text" name="apellidos" placeholder="Nombres">
                                                                       </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="password" class="cols-sm-2 control-label"></label>
                                                            <div class="cols-sm-10">
                                                                <div class="input-group">
                                                                    <span class="input-group-addon"> <i class="fa fa-user fa-lg" aria-hidden="true"></i></span>
                                                                       <div class="input-group date" >
                                                                            <input type="text" name="apellidos" placeholder="Apellidos">
                                                                       </div>
                                                                </div>
                                                            </div>
                                                        </div>


                                                        <div class="form-group">
                                                            <label for="especialidad" class="cols-sm-2 control-label"></label>
                                                            <div class="cols-sm-10">
                                                                <div class="input-group">
                                                                    <span class="input-group-addon"> <i class="fa fa-user fa-lg" aria-hidden="true"></i></span>
                                                                       <div class="input-group date" >
                                                                            <input type="text" name="especialidad   " placeholder="ESPECIALIDAD">
                                                                       </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                            <br>

                                            <div class="form-group">
                                                <label for="confirm" class="cols-sm-2 control-label"> </label>
                                                <div class="cols-sm-10">
                                                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                                             <button type="submit"  class="btn btn-success class">Guardar</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
		                      </div> 
                          
                          </div>
                        </div>
            </div>
            <div>
                <div class="panel panel-danger">
                          <div class="panel-heading">REGISTRO DE USUARIO</div>
                          <div class="panel-body">
                           <div class="container">
                                <div class="row main">
                                    <div class="panel-heading">
                                       <div class="panel-title text-center">

                                        </div>
                                    </div> 


                                    <div class="main-login main-center">
                                        <form class="form-horizontal" method="post" action="#">
                                                        <div class="form-group">
                                                            <label for="password" class="cols-sm-2 control-label"></label>
                                                            <div class="cols-sm-10">
                                                                <div class="input-group">
                                                                    <span class="input-group-addon"> <i class="fa fa-user fa-lg" aria-hidden="true"></i></span>
                                                                       <div class="input-group date" >
                                                                            <input type="text" name="nombres" placeholder="Nombres">
                                                                       </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="password" class="cols-sm-2 control-label"></label>
                                                            <div class="cols-sm-10">
                                                                <div class="input-group">
                                                                    <span class="input-group-addon"> <i class="fa fa-user fa-lg" aria-hidden="true"></i></span>
                                                                       <div class="input-group date" >
                                                                            <input type="text" name="apellidos" placeholder="Apellidos">
                                                                       </div>
                                                                </div>
                                                            </div>
                                                        </div>


                                                        <div class="form-group">
                                                            <label for="especialidad" class="cols-sm-2 control-label"></label>
                                                            <div class="cols-sm-10">
                                                                <div class="input-group">
                                                                    <span class="input-group-addon"> <i class="fa fa-user fa-lg" aria-hidden="true"></i></span>
                                                                       <div class="input-group date" >
                                                                            <input type="text" name="telefono" placeholder="teléfono">
                                                                       </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="confirm" class="cols-sm-2 control-label"> </label>
                                                            <div class="cols-sm-10">
                                                                <div class="input-group">
                                                                    <span class="input-group-addon"><i class="fa fa-phone fa-lg" aria-hidden="true"></i></span>
                                                                    <select name="IdDoctor">
                                                                        <option value="0">Seleccione Doctor:</option>
                                                                         <option value="Doctor">Doctor</option>
                                                                          <option value="Agenda">Agenda</option>
                                                                          <option value="Sistema">Sistema</option>
                                                                        </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="usuario" class="cols-sm-2 control-label"></label>
                                                            <div class="cols-sm-10">
                                                                <div class="input-group">
                                                                    <span class="input-group-addon"> <i class="fa fa-user fa-lg" aria-hidden="true"></i></span>
                                                                       <div class="input-group date" >
                                                                            <input type="text" name="usuario" placeholder="usuario">
                                                                       </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <label for="clave" class="cols-sm-2 control-label"></label>
                                                            <div class="cols-sm-10">
                                                                <div class="input-group">
                                                                    <span class="input-group-addon"> <i class="fa fa-lock fa-lg" aria-hidden="true"></i></span>
                                                                       <div class="input-group date" >
                                                                            <input type="password" name="clave" placeholder="clave">
                                                                       </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                            <br>

                                            <div class="form-group">
                                                <label for="confirm" class="cols-sm-2 control-label"> </label>
                                                <div class="cols-sm-10">
                                                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                                             <button type="submit"  class="btn btn-success class">Guardar</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
		                      </div> 
                          
                          </div>
                        </div>
            </div>
        </div>
            <div class="tab-pane" id="RP">
            <div class="container">
            <h1>Lista de Citas por Usuario </h1>
                
            <div class="container">
            <div class="row">
                <form name="formul"  method="post" action="Home.php">     
                <div class='col-sm-4'>                    
                    <div class="form-group">                                            
                       <label class=" control-label" for="fechacont">Fecha de Inicio</label>
                        <input type="date" name="fechainicio" id="fechainicio" class="form-control" required />
                        <script>
                        document.getElementById('fechainicio').value = '<?php echo $fechaInicio;?>';
                        </script>
                    </div>
                </div>
                <div class='col-sm-4'>                    
                    <div class="form-group">
                       <label class=" control-label" for="fechacont">Fecha de Fin</label>
                        <input type="date" name="fechafin" id="fechafin" class="form-control" required />
                        <script>
                        document.getElementById('fechafin').value = '<?php echo $fechaFinn;?>';
                        </script>
                    </div>
                </div>
                <div class='col-sm-4'>
                    <div class="form-group">
                        <label class=" control-label" for="fechacont"></label>
                       <button type="submit"  class="btn btn-success">Buscar</button>
                    </div>
                </div>
                <div class='col-sm-4'>
                    <div class="form-group">
                        <label class=" control-label" for="fechacont"></label>
                       <a type="button" target="_blank" href="imprime.php?fechainicio=<?php echo $fechaInicio;?>&fechafin=<?php echo $fechaFinn;?>" class="btn btn-primary">Imprimir</a>
                    </div>
                </div>   
                 
               </form>
            </div>
        </div>    
    	<div class="row">			
			<div class="col-md-12">
				<div class="panel panel-success">
					<div class="panel-heading">
						<h4 class="panel-title">Citas</h4>						
					</div>
					<div class="panel-body">
						<input type="text" class="form-control" id="task-table-filter" data-action="filter" data-filters="#task-table" placeholder="Busqueda" />
					</div>
					<table class="table table-hover" id="task-table">
						<thead>
							<tr>
								<th>Usuario</th>
								<th>Paciente</th>
								<th>Tipo Consulta</th>
                                <th>Doctor</th>
                                <th>Fecha Consulta</th>
                                <th>Hora</th>
								<th>Status</th>
								<th>Vital</th>
                                <th>Atentido</th>
                                <th>Cancelar</th>
							</tr>
						</thead>
						<tbody>
				        <?php
                        $sql="SELECT A.IDCITA, A.IDPACIENTE,CONCAT(B.NOMBRES, ' ', B.APELLIDOS) AS PACIENTE, A.FECHA_CITA, A.HORA_INICIO, A.HORA_FIN ,SUBSTRING(A.FECHA_CITA, 1, 4) AS ANIO,SUBSTRING(A.FECHA_CITA, 6, 2) AS  MES,SUBSTRING(A.FECHA_CITA, 9, 2) AS  DIA, C.NOMBRES, A.ESTADO_CITA, A.COMENTARIO, CONCAT(D.NOMBRES, ' ', D.APELLIDOS) AS USUARIO,  CONCAT(E.NOMBRES, ' ', E.APELLIDOS) AS DOCTOR, A.ESTADO,A.FECHA_CREACION FROM AG_CITA A INNER JOIN AG_PACIENTE B ON A.IDPACIENTE = B.IDPACIENTE INNER JOIN AG_TIPOCONSULTA C ON A.IDTIPOCONSULTA = C.IDTIPOCONSULTA INNER JOIN ADM_USUARIO D ON A.IDUSUARIO= D.IDADM_USUARIO INNER JOIN ADM_DOCTOR E ON A.IDDOCTOR = E.IDDOCTOR WHERE A.FECHA_CITA>='".$fechaInicio."' and A.FECHA_CITA<='".$fechaFinn."' ORDER BY A.FECHA_CITA DESC, A.HORA_INICIO";
                        //generamos la consulta
                        $query = $conexion -> query ($sql);
                            while ($valores = mysqli_fetch_array($query)) {
                            ?>    
							<tr>
								<td><?php echo $valores[12]?></td>
								<td><?php echo $valores[2]?></td>
								<td><?php echo $valores[9]?></td>
								<td><?php echo $valores[13]?></td>
                                <td><?php echo $valores[3]?></td>
                                <td><?php echo $valores[4]?></td>
                                <?php if($valores[10]=='Pendiente'){ ?>
                                    <td style="background-color: #9acfea"><?php echo $valores[10]?></td>
                                <? }else if($valores[10]=='Atrasado'){?>
                                    <td style="background-color: #F8C471"><?php echo $valores[10]?></td>
                                <? }else if($valores[10]=='Cancelado'){?>
                                    <td style="background-color: #F1948A"><?php echo $valores[10]?></td>
                                <? }else if($valores[10]=='Atendido'){?>
                                    <td style="background-color: #dff0d8"><?php echo $valores[10]?></td>
                                <? } ?>  
                                <td ><a class="btn btn-info " href="#"><i class="material-icons" style="color:white;font-size:21px">medication</i></a></td>
                                <?php
                                if($valores[10]=='Pendiente'||$valores[10]=='Atrasado'){ ?>
                                     <td ><a class="btn btn-success" href="class/Atender_cita.php?IdCita=<?php echo $valores[0]?>"><i class="material-icons" style="color:white;font-size:21px">done</i></a></td> 
                                    <td ><a class="btn btn-danger" href="class/Cancela_cita.php?IdCita=<?php echo $valores[0]?>"><i class="material-icons" style="color:white;font-size:21px">delete_forever</i></a></td>
                                <? }else{?>
                                     <td ><a class="btn btn-success disabled" href="#"><i class="material-icons" style="color:white;font-size:21px">done</i></a></td> 
                                    <td ><a class="btn btn-danger disabled" href="#"><i class="material-icons" style="color:white;font-size:21px">delete_forever</i></a></td>
                                <? } ?>
                                    
							</tr>
                            <?php } ?>								
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div> 
            <div>                
            </div>
        </div>
        <div class="tab-pane" id="RPD">
            <div class="container">
            <h1>Lista de Citas </h1>
                
            <div class="container">
            <div class="row">
                <form name="formul"  method="post" action="Home.php">     
                <div class='col-sm-4'>                    
                    <div class="form-group">                                            
                       <label class=" control-label" for="fechacont">Fecha de Inicio</label>
                        <input type="date" name="fechainicio" id="fechainicio" class="form-control" required />
                        <script>
                        document.getElementById('fechainicio').value = '<?php echo $fechaInicio;?>';
                        </script>
                    </div>
                </div>
                <div class='col-sm-4'>                    
                    <div class="form-group">
                       <label class=" control-label" for="fechacont">Fecha de Fin</label>
                        <input type="date" name="fechafin" id="fechafin" class="form-control" required />
                        <script>
                        document.getElementById('fechafin').value = '<?php echo $fechaFinn;?>';
                        </script>
                    </div>
                </div>
                <div class='col-sm-4'>
                    <div class="form-group">
                        <label class=" control-label" for="fechacont"></label>
                       <button type="submit"  class="btn btn-success">Buscar</button>
                    </div>
                </div>
                <div class='col-sm-4'>
                    <div class="form-group">
                        <label class=" control-label" for="fechacont"></label>
                       <a type="button" target="_blank" href="imprime.php?fechainicio=<?php echo $fechaInicio;?>&fechafin=<?php echo $fechaFinn;?>" class="btn btn-primary">Imprimir</a>
                    </div>
                </div>    
               </form>
            </div>
        </div>    
    	<div class="row">			
			<div class="col-md-12">
				<div class="panel panel-success">
					<div class="panel-heading">
						<h4 class="panel-title">Citas</h4>						
					</div>
					<div class="panel-body">
						<input type="text" class="form-control" id="task-table-filter" data-action="filter" data-filters="#task-tableDr" placeholder="Busqueda" />
					</div>
					<table class="table table-hover" id="task-tableDr">
						<thead>
							<tr>
								<th>Usuario</th>
								<th>Paciente</th>
								<th>Tipo Consulta</th>
                                <th>Doctor</th>
                                <th>Fecha Consulta</th>
                                <th>Hora</th>
								<th>Status</th>
								<th>Vital</th>
                                <th>Atender</th>
                                <th>Cancelar</th>
							</tr>
						</thead>
						<tbody>
				        <?php
                        $sql="SELECT A.IDCITA, A.IDPACIENTE,CONCAT(B.NOMBRES, ' ', B.APELLIDOS) AS PACIENTE, A.FECHA_CITA, A.HORA_INICIO, A.HORA_FIN ,SUBSTRING(A.FECHA_CITA, 1, 4) AS ANIO,SUBSTRING(A.FECHA_CITA, 6, 2) AS  MES,SUBSTRING(A.FECHA_CITA, 9, 2) AS  DIA, C.NOMBRES, A.ESTADO_CITA, A.COMENTARIO, CONCAT(D.NOMBRES, ' ', D.APELLIDOS) AS USUARIO,  CONCAT(E.NOMBRES, ' ', E.APELLIDOS) AS DOCTOR, A.ESTADO,A.FECHA_CREACION FROM AG_CITA A INNER JOIN AG_PACIENTE B ON A.IDPACIENTE = B.IDPACIENTE INNER JOIN AG_TIPOCONSULTA C ON A.IDTIPOCONSULTA = C.IDTIPOCONSULTA INNER JOIN ADM_USUARIO D ON A.IDUSUARIO= D.IDADM_USUARIO INNER JOIN ADM_DOCTOR E ON A.IDDOCTOR = E.IDDOCTOR WHERE A.FECHA_CITA>='".$fechaInicio."' and A.FECHA_CITA<='".$fechaFinn."' ORDER BY A.FECHA_CITA DESC, A.HORA_INICIO";
                        //generamos la consulta
                        $query = $conexion -> query ($sql);
                            while ($valores = mysqli_fetch_array($query)) {
                            ?>    
							<tr>
								<td><?php echo $valores[12]?></td>
								<td><?php echo $valores[2]?></td>
								<td><?php echo $valores[9]?></td>
								<td><?php echo $valores[13]?></td>
                                <td><?php echo $valores[3]?></td>
                                <td><?php echo $valores[4]?></td>
                                <?php if($valores[10]=='Pendiente'){ ?>
                                    <td style="background-color: #9acfea"><?php echo $valores[10]?></td>
                                <? }else if($valores[10]=='Atrasado'){?>
                                    <td style="background-color: #F8C471"><?php echo $valores[10]?></td>
                                <? }else if($valores[10]=='Cancelado'){?>
                                    <td style="background-color: #F1948A"><?php echo $valores[10]?></td>
                                <? }else if($valores[10]=='Atendido'){?>
                                    <td style="background-color: #dff0d8"><?php echo $valores[10]?></td>
                                <? } ?>  
                                <td ><a class="btn btn-info " href="#"><i class="material-icons" style="color:white;font-size:21px">medication</i></a></td>
                                <?php
                                if($valores[10]=='Pendiente'||$valores[10]=='Atrasado'){ ?>
                                     <td ><a class="btn btn-success" href="class/Atender_cita.php?IdCita=<?php echo $valores[0]?>"><i class="material-icons" style="color:white;font-size:21px">done</i></a></td> 
                                    <td ><a class="btn btn-danger" href="class/Cancela_cita.php?IdCita=<?php echo $valores[0]?>"><i class="material-icons" style="color:white;font-size:21px">delete_forever</i></a></td>
                                <? }else{?>
                                     <td ><a class="btn btn-success disabled" href="#"><i class="material-icons" style="color:white;font-size:21px">done</i></a></td> 
                                    <td ><a class="btn btn-danger disabled" href="#"><i class="material-icons" style="color:white;font-size:21px">delete_forever</i></a></td>
                                <? } ?>
                                    
							</tr>
                            <?php } ?>								
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div> 
            <div>                
            </div>
        </div>
        </div>
        </div>
        </div>
        </div>
        <script src="js/chosen.jquery.js" type="text/javascript"></script> 
        <script src="js/init.js" type="text/javascript" ></script>
</body>    
<script type="text/tmpl" id="tmpl">
  {{ 
  var date = date || new Date(),
      month = date.getMonth(), 
      year = date.getFullYear(), 
      first = new Date(year, month, 1), 
      last = new Date(year, month + 1, 0),
      startingDay = first.getDay(), 
      thedate = new Date(year, month, 1 - startingDay),
      dayclass = lastmonthcss,
      today = new Date(),
      i, j; 
  if (mode === 'week') {
    thedate = new Date(date);
    thedate.setDate(date.getDate() - date.getDay());
    first = new Date(thedate);
    last = new Date(thedate);
    last.setDate(last.getDate()+6);
  } else if (mode === 'day') {
    thedate = new Date(date);
    first = new Date(thedate);
    last = new Date(thedate);
    last.setDate(thedate.getDate() + 1);
  }
  
  }}
  <table class="calendar-table table table-condensed table-tight">
    <thead>
      <tr>
        <td colspan="7" style="text-align: center">
          <table style="white-space: nowrap; width: 100%">
            <tr>
              <td style="text-align: left;">
                <span class="btn-group">
                  <button class="js-cal-prev btn btn-default"><</button>
                  <button class="js-cal-next btn btn-default">></button>
                </span>
                <button class="js-cal-option btn btn-default {{: first.toDateInt() <= today.toDateInt() && today.toDateInt() <= last.toDateInt() ? 'active':'' }}" data-date="{{: today.toISOString()}}" data-mode="month">{{: todayname }}</button>
              </td>
              <td>
                <span class="btn-group btn-group-lg">
                  {{ if (mode !== 'day') { }}
                    {{ if (mode === 'month') { }}<button class="js-cal-option btn btn-link" data-mode="year">{{: months[month] }}</button>{{ } }}
                    {{ if (mode ==='week') { }}
                      <button class="btn btn-link disabled">{{: shortMonths[first.getMonth()] }} {{: first.getDate() }} - {{: shortMonths[last.getMonth()] }} {{: last.getDate() }}</button>
                    {{ } }}
                    <button class="js-cal-years btn btn-link">{{: year}}</button> 
                  {{ } else { }}
                    <button class="btn btn-link disabled">{{: date.toDateString() }}</button> 
                  {{ } }}
                </span>
              </td>
              <td style="text-align: right">
                <span class="btn-group">
                  <button class="js-cal-option btn btn-default {{: mode==='year'? 'active':'' }}" data-mode="year">Año</button>
                  <button class="js-cal-option btn btn-default {{: mode==='month'? 'active':'' }}" data-mode="month">Month</button>
                  <button class="js-cal-option btn btn-default {{: mode==='week'? 'active':'' }}" data-mode="week">Week</button>
                  <button class="js-cal-option btn btn-default {{: mode==='day'? 'active':'' }}" data-mode="day">Day</button>
                </span>
              </td>
            </tr>
          </table>
          
        </td>
      </tr>
    </thead>
    {{ if (mode ==='year') {
      month = 0;
    }}
    <tbody>
      {{ for (j = 0; j < 3; j++) { }}
      <tr>
        {{ for (i = 0; i < 4; i++) { }}
        <td class="calendar-month month-{{:month}} js-cal-option" data-date="{{: new Date(year, month, 1).toISOString() }}" data-mode="month">
          {{: months[month] }}
          {{ month++;}}
        </td>
        {{ } }}
      </tr>
      {{ } }}
    </tbody>
    {{ } }}
    {{ if (mode ==='month' || mode ==='week') { }}
    <thead>
      <tr class="c-weeks">
        {{ for (i = 0; i < 7; i++) { }}
          <th class="c-name">
            {{: days[i] }}
          </th>
        {{ } }}
      </tr>
    </thead>
    <tbody>
      {{ for (j = 0; j < 6 && (j < 1 || mode === 'month'); j++) { }}
      <tr>
        {{ for (i = 0; i < 7; i++) { }}
        {{ if (thedate > last) { dayclass = nextmonthcss; } else if (thedate >= first) { dayclass = thismonthcss; } }}
        <td class="calendar-day {{: dayclass }} {{: thedate.toDateCssClass() }} {{: date.toDateCssClass() === thedate.toDateCssClass() ? 'selected':'' }} {{: daycss[i] }} js-cal-option" data-date="{{: thedate.toISOString() }}">
          <div class="date">{{: thedate.getDate() }}</div>
          {{ thedate.setDate(thedate.getDate() + 1);}}
        </td>
        {{ } }}
      </tr>
      {{ } }}
    </tbody>
    {{ } }}
    {{ if (mode ==='day') { }}
    <tbody>
      <tr>
        <td colspan="7">
          <table class="table table-striped table-condensed table-tight-vert" >
            <thead>
              <tr>
                <th> </th>
                <th style="text-align: center; width: 100%">{{: days[date.getDay()] }}</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <th class="timetitle" >All Day</th>
                <td class="{{: date.toDateCssClass() }}">  </td>
              </tr>
              <tr>
                <th class="timetitle" >Before 6 AM</th>
                <td class="time-0-0"> </td>
              </tr>
              {{for (i = 6; i < 22; i++) { }}
              <tr>
                <th class="timetitle" >{{: i <= 12 ? i : i - 12 }} {{: i < 12 ? "AM" : "PM"}}</th>
                <td class="time-{{: i}}-0"> </td>
              </tr>
              <tr>
                <th class="timetitle" >{{: i <= 12 ? i : i - 12 }}:30 {{: i < 12 ? "AM" : "PM"}}</th>
                <td class="time-{{: i}}-30"> </td>
              </tr>
              {{ } }}
              <tr>
                <th class="timetitle" >After 10 PM</th>
                <td class="time-22-0"> </td>
              </tr>
            </tbody>
          </table>
        </td>
      </tr>
    </tbody>
    {{ } }}

  </table>
  
</script>
<script>
    var $currentPopover = null;
  $(document).on('shown.bs.popover', function (ev) {
    var $target = $(ev.target);
    if ($currentPopover && ($currentPopover.get(0) != $target.get(0))) {
      $currentPopover.popover('toggle');
    }
    $currentPopover = $target;
  }).on('hidden.bs.popover', function (ev) {
    var $target = $(ev.target);
    if ($currentPopover && ($currentPopover.get(0) == $target.get(0))) {
      $currentPopover = null;
    }
  });


//quicktmpl is a simple template language I threw together a while ago; it is not remotely secure to xss and probably has plenty of bugs that I haven't considered, but it basically works
//the design is a function I read in a blog post by John Resig (http://ejohn.org/blog/javascript-micro-templating/) and it is intended to be loosely translateable to a more comprehensive template language like mustache easily
$.extend({
    quicktmpl: function (template) {return new Function("obj","var p=[],print=function(){p.push.apply(p,arguments);};with(obj){p.push('"+template.replace(/[\r\t\n]/g," ").split("{{").join("\t").replace(/((^|\}\})[^\t]*)'/g,"$1\r").replace(/\t:(.*?)\}\}/g,"',$1,'").split("\t").join("');").split("}}").join("p.push('").split("\r").join("\\'")+"');}return p.join('');")}
});

$.extend(Date.prototype, {
  //provides a string that is _year_month_day, intended to be widely usable as a css class
  toDateCssClass:  function () { 
    return '_' + this.getFullYear() + '_' + (this.getMonth() + 1) + '_' + this.getDate(); 
  },
  //this generates a number useful for comparing two dates; 
  toDateInt: function () { 
    return ((this.getFullYear()*12) + this.getMonth())*32 + this.getDate(); 
  },
  toTimeString: function() {
    var hours = this.getHours(),
        minutes = this.getMinutes(),
        hour = (hours > 12) ? (hours - 12) : hours,
        ampm = (hours >= 12) ? ' pm' : ' am';
    if (hours === 0 && minutes===0) { return ''; }
    if (minutes > 0) {
      return hour + ':' + minutes + ampm;
    }
    return hour + ampm;
  }
});


(function ($) {

  //t here is a function which gets passed an options object and returns a string of html. I am using quicktmpl to create it based on the template located over in the html block
  var t = $.quicktmpl($('#tmpl').get(0).innerHTML);
  
  function calendar($el, options) {
    //actions aren't currently in the template, but could be added easily...
    $el.on('click', '.js-cal-prev', function () {
      switch(options.mode) {
      case 'year': options.date.setFullYear(options.date.getFullYear() - 1); break;
      case 'month': options.date.setMonth(options.date.getMonth() - 1); break;
      case 'week': options.date.setDate(options.date.getDate() - 7); break;
      case 'day':  options.date.setDate(options.date.getDate() - 1); break;
      }
      draw();
    }).on('click', '.js-cal-next', function () {
      switch(options.mode) {
      case 'year': options.date.setFullYear(options.date.getFullYear() + 1); break;
      case 'month': options.date.setMonth(options.date.getMonth() + 1); break;
      case 'week': options.date.setDate(options.date.getDate() + 7); break;
      case 'day':  options.date.setDate(options.date.getDate() + 1); break;
      }
      draw();
    }).on('click', '.js-cal-option', function () {
      var $t = $(this), o = $t.data();
      if (o.date) { o.date = new Date(o.date); }
      $.extend(options, o);
      draw();
    }).on('click', '.js-cal-years', function () {
      var $t = $(this), 
          haspop = $t.data('popover'),
          s = '', 
          y = options.date.getFullYear() - 2, 
          l = y + 5;
      if (haspop) { return true; }
      for (; y < l; y++) {
        s += '<button type="button" class="btn btn-default btn-lg btn-block js-cal-option" data-date="' + (new Date(y, 1, 1)).toISOString() + '" data-mode="year">'+y + '</button>';
      }
      $t.popover({content: s, html: true, placement: 'auto top'}).popover('toggle');
      return false;
    }).on('click', '.event', function () {
      var $t = $(this), 
          index = +($t.attr('data-index')), 
          haspop = $t.data('popover'),
          data, time;
          
      if (haspop || isNaN(index)) { return true; }
      data = options.data[index];
      time = data.start.toTimeString();
      if (time && data.end) { time = time + ' - ' + data.end.toTimeString(); }
      $t.data('popover',true);
      $t.popover({content: '<p><strong>' + time + '</strong></p>'+data.text, html: true, placement: 'auto left'}).popover('toggle');
      return false;
    });
    function dayAddEvent(index, event) {
      if (!!event.allDay) {
        monthAddEvent(index, event);
        return;
      }
      var $event = $('<div/>', {'class': event.estado , text: event.title, title: event.estado, 'data-index': index}),
          start = event.start
          end = event.end || start,
          time = event.start.toTimeString(),
          hour = start.getHours(),
          timeclass = '.time-22-0',
          startint = start.toDateInt(),
          dateint = options.date.toDateInt(),
          endint = end.toDateInt();
      if (startint > dateint || endint < dateint) { return; }
      
      if (!!time) {
        $event.html('<strong>' + time + '</strong> ' + $event.html());
      }
      $event.toggleClass('begin', startint === dateint);
      $event.toggleClass('end', endint === dateint);
      if (hour < 6) {
        timeclass = '.time-0-0';
      }
      if (hour < 22) {
        timeclass = '.time-' + hour + '-' + (start.getMinutes() < 30 ? '0' : '30');
      }
      $(timeclass).append($event);
    }
    
    function monthAddEvent(index, event) {
      var $event = $('<div/>', {'class': event.estado, text: event.title, title: event.title, 'data-index': index}),
          e = new Date(event.start),
          dateclass = e.toDateCssClass(),
          day = $('.' + e.toDateCssClass()),
          empty = $('<div/>', {'class':'clear event', html:' '}), 
          numbevents = 0, 
          time = event.start.toTimeString(),
          endday = event.end && $('.' + event.end.toDateCssClass()).length > 0,
          checkanyway = new Date(e.getFullYear(), e.getMonth(), e.getDate()+40),
          existing,
          i;
      $event.toggleClass('all-day', !!event.allDay);
      if (!!time) {
        $event.html('<strong>' + time + '</strong> ' + $event.html());
      }
      if (!event.end) {
        $event.addClass('begin end');
        $('.' + event.start.toDateCssClass()).append($event);
        return;
      }
            
      while (e <= event.end && (day.length || endday || options.date < checkanyway)) {
        if(day.length) { 
          existing = day.find('.event').length;
          numbevents = Math.max(numbevents, existing);
          for(i = 0; i < numbevents - existing; i++) {
            day.append(empty.clone());
          }
          day.append(
            $event.
            toggleClass('begin', dateclass === event.start.toDateCssClass()).
            toggleClass('end', dateclass === event.end.toDateCssClass())
          );
          $event = $event.clone();
          $event.html(' ');
        }
        e.setDate(e.getDate() + 1);
        dateclass = e.toDateCssClass();
        day = $('.' + dateclass);
      }
    }
    function yearAddEvents(events, year) {
      var counts = [0,0,0,0,0,0,0,0,0,0,0,0];
      $.each(events, function (i, v) {
        if (v.start.getFullYear() === year) {
            counts[v.start.getMonth()]++;
        }
      });
      $.each(counts, function (i, v) {
        if (v!==0) {
            $('.month-'+i).append('<span class="badge">'+v+'</span>');
        }
      });
    }
    
    function draw() {
      $el.html(t(options));
      //potential optimization (untested), this object could be keyed into a dictionary on the dateclass string; the object would need to be reset and the first entry would have to be made here
      $('.' + (new Date()).toDateCssClass()).addClass('today');
      if (options.data && options.data.length) {
        if (options.mode === 'year') {
            yearAddEvents(options.data, options.date.getFullYear());
        } else if (options.mode === 'month' || options.mode === 'week') {
            $.each(options.data, monthAddEvent);
        } else {
            $.each(options.data, dayAddEvent);
        }
      }
    }
    
    draw();    
  }
  
  ;(function (defaults, $, window, document) {
    $.extend({
      calendar: function (options) {
        return $.extend(defaults, options);
      }
    }).fn.extend({
      calendar: function (options) {
        options = $.extend({}, defaults, options);
        return $(this).each(function () {
          var $this = $(this);
          calendar($this, options);
        });
      }
    });
  })({
    days: ["Domingo", "Lunes", "Martes", "Miercoles", "Jueves", "Viernes", "Sabado"],
    months: ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"],
    shortMonths: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
    date: (new Date()),
        daycss: ["c-sunday", "", "", "", "", "", "c-saturday"],
        todayname: "Today",
        thismonthcss: "current",
        lastmonthcss: "outside",
        nextmonthcss: "outside",
    mode: "month",
    data: []
  }, jQuery, window, document);
    
})(jQuery);    
    var cont = '<?php echo $cont;?>';
    var listPaci = <? echo json_encode($paci); ?>;
    var listslipsum = <? echo json_encode($slipsum); ?>;
    var listestado = <? echo json_encode($estado); ?>;
    var listanio = <? echo json_encode($anio); ?>;
    var listmes = <? echo json_encode($mes); ?>;
    var listdia = <? echo json_encode($dia); ?>;
    var listhini = <? echo json_encode($hini); ?>;
    var listmini = <? echo json_encode($mini); ?>;
    var listhfin = <? echo json_encode($hfin); ?>;
    var listmfin = <? echo json_encode($mfin); ?>;    
        
var data = [],
    date = new Date(),
    d = date.getDate(),
    d1 = d,
    m = date.getMonth(),
    y = date.getFullYear(),
    i,
    end, 
    j, 
    c = 1063, 
    c1 = 3329,
    h, 
    m,
    names = [listPaci],
    slipsum = [""];

  for(i = 0; i < cont; i++) {    
         
    data.push({ 
        title: listPaci[i], 
        start: new Date(listanio[i], listmes[i]-1, listdia[i], listhini[i], listmini[i]), 
        end: new Date(listanio[i], listmes[i]-1, listdia[i], listhfin[i], listmfin[i]), 
        allDay: (0),
        estado: listestado[i],
        text: listslipsum[i] });
  }
  
  data.sort(function(a,b) { return (+a.start) - (+b.start); });
  
//data must be sorted by start date

//Actually do everything
$('#holder').calendar({
  data: data
});
</script>

</html>
<?php
ob_end_flush();
?>