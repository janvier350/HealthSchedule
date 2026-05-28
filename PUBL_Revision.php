<?php
ob_start();
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
$conexion->query("SET NAMES 'utf8'");

if (!isset($_SESSION["rol"])) {
    header("Location: break.php");
} else {
    $now = time();
    if ($now > $_SESSION['expire']) {
        session_destroy();
        header("Location: expirada.php");
    }
}

// Obtener la fecha actual si no existe
$startDate = date("Y-m-d");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todo - Zen | Calendario filtro</title>    
    <!-- Bootstrap CSS -->
    <!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">-->
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap4.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        .table-responsive { margin-top: 20px; }
        .copy-out-cell { max-width: 550px; overflow: hidden; text-overflow: ellipsis; }
    </style>
    
    <style>
        .Publicado {
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
.Atrasado {
  border-top: 1px solid #C0392B;
  border-bottom: 1px solid #C0392B;
  background-image: linear-gradient(to bottom, #E57373 0%, #EF9A9A 100%);
  background-repeat: repeat-x;
  color: #B71C1C;
  border-width: 1px;
  font-size: 0.75em;
  padding: 0 0.75em;
  line-height: 2em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 1px;
}

.EnRevision {
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
.Cancelado {
  border-top: 1px solid #D81B60;
  border-bottom: 1px solid #D81B60;
  background-image: linear-gradient(to bottom, #F8BBD0 0px, #F48FB1 100%);
  background-repeat: repeat-x;
  color: #AD1457;
  border-width: 1px;
  font-size: .75em;
  padding: 0 .75em;
  line-height: 2em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 1px;
}
.Aprobado {
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
.Idea {
  border-top: 1px solid #F3F702;
  border-bottom: 1px solid #F3F702;
  background-image: linear-gradient(to bottom, #FFFF99 0px, #F3F702 100%);
  background-repeat: repeat-x;
  color: #6B6B00;
  border-width: 1px;
  font-size: .75em;
  padding: 0 .75em;
  line-height: 2em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 1px;
}

.EnCurso {
  border-top: 1px solid #9B59B6;
  border-bottom: 1px solid #9B59B6;
  background-image: linear-gradient(to bottom, #DAB6E3 0px, #9B59B6 100%);
  background-repeat: repeat-x;
  color: #4A235A;
  border-width: 1px;
  font-size: .75em;
  padding: 0 .75em;
  line-height: 2em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 1px;
}
.EnEdicion {
  border-top: 1px solid #E67E22;
  border-bottom: 1px solid #E67E22;
  background-image: linear-gradient(to bottom, #FAD7A0 0px, #F39C12 100%);
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

    </style>
  
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap4.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap4.min.css">
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <a class="navbar-brand" href="#">Todo-Zen</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item active"><a class="nav-link" href="#Home.php">Home</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="controlPanelDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Panel Control</a>
                    <div class="dropdown-menu" aria-labelledby="controlPanelDropdown">
                        <a class="dropdown-item" href="#Usuarios.php">Usuarios</a>
                    </div>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item"><a class="nav-link text-warning"><?php echo $startDate; ?></a></li>
                <li class="nav-item"><a class="nav-link text-warning" href="TodoList.php">Todo List</a></li>
                <!--<li class="nav-item"><a class="nav-link text-warning" href="lista_publicaciones.php">Sin fecha</a></li>-->
                <!--<li class="nav-item"><a class="nav-link text-danger" href="reportes.php">Reportes</a></li>-->
                <li class="nav-item dropdown">
                    <a class="nav-link text-warning dropdown-toggle" href="#" id="profileDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Reportes</a>
                    <div class="dropdown-menu" aria-labelledby="profileDropdown">
                        <a class="dropdown-item" href="reportes_filtro.php">Filtro</a>
                        <a class="dropdown-item" href="reportes.php">Reportes</a>
                        
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link text-warning dropdown-toggle" href="#" id="profileDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Perfil</a>
                    <div class="dropdown-menu" aria-labelledby="profileDropdown">
                        <a class="dropdown-item" href="#">Usuario: <?php echo $_SESSION["username"]; ?></a>
                        <a class="dropdown-item" href="#">Nombres: <?php echo $_SESSION["nombres"] . " " . $_SESSION["apellidos"]; ?></a>
                        <a class="dropdown-item" href="#">Rol: <?php echo $_SESSION["rol"]; ?></a>
                    </div>
                </li>
                <li class="nav-item"><a class="nav-link" href="salir.php">Cerrar Sesion</a></li>
            </ul>
        </div>
    </nav>
    
    
<!--<div class="">-->
     <div class="container-fluid mt-5 pt-4 table-responsive">
        
    <h2>Tabla con Publicacion</h2>
    <table class="table table-striped table-bordered miTabla">

    <!--<table id="miTabla" class="table table-striped table-bordered">-->
        <thead>
            <tr>
                <th>#</th>
                <th>Estado</th>
                <th>Fechas</th>
                <th>Categoria</th>
                <th>Tema</th>
                <th>Copy In</th>
                <th>Copy Out</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $registrosContador = 0;
            $calendario = "SELECT a.id_publ_calendario as idPublicidad, a.categoria, a.tema, a.copy_in, a.copy_out, 
                                  a.multimedia, b.publicacion, a.fecha_publicacion, a.fecha_emision, 
                                  a.fecha_aprobacion, a.estado, a.cliente, a.idAsignado 
                           FROM PUBL_CALENDARIO a 
                           JOIN PUBL_TIPO b ON a.ID_PUBL_TIPO = b.IDTIPOPUBLICACION AND a.ESTADO = 'EnRevision'";
            $query = $conexion->query($calendario);
            
            // while ($valores = mysqli_fetch_array($query)) {
            while ($valores = mysqli_fetch_assoc($query)) {

                $registrosContador++;
            ?>
            
            <?php
    $columnas = 8; // Debe coincidir con el n¨²mero de <th> en <thead>
    $contadorColumnas = 0;
?>
                <tr>
    <td>
        <button class="btn-shadow btn btn-outline-primary fa fa-check-circle">
            <?php echo $registrosContador; ?>
        </button>
    </td>
    <?php $contadorColumnas++; ?>
    
    <td>
        <?php if($valores['estado']=='Publicado'){?><span class="Publicado">Publicado</span><?php
                                }?>
                                <?php if($valores['estado']=='EnRevision'){?><span class="EnRevision">EnRevision</span><?php
                                }?>
                                <?php if($valores['estado']=='Aprobado'){?><span class="Aprobado">Aprobado</span><?php
                                }?>
                                <?php if($valores['estado']=='Idea'){?><span class="Idea">Idea</span><?php
                                }?>
                                <?php if($valores['estado']=='EnCurso'){?><span class="EnCurso">En Curso</span><?php
                                }?>
                                <p><strong>Tipo: </strong><?php echo $valores['publicacion']; ?></p>
                                <p><strong>Cliente: </strong><?php echo $valores['cliente']; ?></p>
                                <p><strong>Asignado: </strong><?php echo $valores['idAsignado']; ?></p>
        <!--<?php echo isset($valores['estado']) ? $valores['estado'] : 'Sin Estado'; ?>-->
    </td>
    <?php $contadorColumnas++; ?>
    
    <td>
        
        <p><strong>Publicacion:</strong> <?php echo $valores['fecha_publicacion'] ?? 'N/A'; ?></p>
        <p><strong>Emision:</strong> <?php echo $valores['fecha_emision'] ?? 'N/A'; ?></p>
        <p><strong>Aprobacion:</strong> <?php echo $valores['fecha_aprobacion'] ?? 'N/A'; ?></p>
    </td>
    <?php $contadorColumnas++; ?>
    
    <td><?php echo $valores['categoria'] ?? 'N/A'; ?></td>
    <?php $contadorColumnas++; ?>
    
    <td>
        <?php echo $valores['tema'] ?? 'N/A'; ?>
       
        </td>
    <?php $contadorColumnas++; ?>
    
    <!--<td>-->
    <!--    <?php echo $valores['copy_in'] ?? 'N/A'; ?>-->
    <!--    </td>-->
    <td>
    <?php 
        $texto = $valores['copy_in'] ?? 'N/A'; 
        $limite = 50; // N¨²mero de caracteres a mostrar antes de "Leer m¨¢s"
        
        if (strlen($texto) > $limite) {
            $textoCorto = substr($texto, 0, $limite) . '...';
            echo '<span class="texto-corto">' . $textoCorto . '</span>';
            echo '<span class="texto-completo" style="display: none;">' . $texto . '</span>';
            echo ' <button class="leer-mas btn btn-link">Leer mas</button>';
        } else {
            echo $texto;
        }
    ?>
</td>
    <?php $contadorColumnas++; ?>
    
    <!--<td>-->
    <!--    <?php echo $valores['copy_out'] ?? 'N/A'; ?>-->
    <!--</td>-->
    <td>
    <?php 
        $texto = $valores['copy_out'] ?? 'N/A'; 
        
        if (strlen($texto) > $limite) {
            $textoCorto = substr($texto, 0, $limite) . '...';
            echo '<span class="texto-corto">' . $textoCorto . '</span>';
            echo '<span class="texto-completo" style="display: none;">' . $texto . '</span>';
            echo ' <button class="leer-mas btn btn-link">Leer mas</button>';
        } else {
            echo $texto;
        }
    ?>
</td>
    <?php $contadorColumnas++; ?>
    
    <td>
        <p><a class="btn btn-info" href="EditarPublicacion.php?idPublicacion=<?php echo $valores['idPublicidad'] ?>"><i class="bi bi-pencil"></i></a></p>
        <p><a class="btn btn-warning" href="ComentarioPublicacion.php?idPublicacion=<?php echo $valores['idPublicidad'] ?>"><i class="bi bi-pencil"></i></a></p>
                                
        
        <?php
                            //   $valores['idadm_rol'] = (int)$valores['idadm_rol'];

                               if( $_SESSION["rol"]== 'SISTEMA'){ ?>
                               <p> <a class="btn btn-danger" href="class/Eliminar_Publicacion.php?idPublicacion=<?php echo $valores['idPublicidad'] ?>"><i class="bi bi-trash"></i></a></p>
                               <?php } ?>
                               <p><a class="btn btn-success" href="class/AprobarPublicacion.php?idPublicacion=<?php echo $valores['idPublicidad'] ?>"><i class="bi bi-check-square-fill"></i></a></p>
    </td>
    <?php $contadorColumnas++; ?>
</tr>

<?php
    if ($contadorColumnas !== $columnas) {
        echo "<script>console.error('Error en la tabla: hay $contadorColumnas columnas en esta fila en lugar de $columnas');</script>";
    }
?>
                </tr>
            <?php } ?>
            <script>
                        $(document).ready(function(){
                          $("#myInput").on("keyup", function() {
                            var value = $(this).val().toLowerCase();
                            $("#myTable tr").filter(function() {
                              $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                            });
                          });
                        });
                        </script>
            <script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".leer-mas").forEach(function(boton) {
            boton.addEventListener("click", function() {
                let fila = this.closest("td");
                let textoCorto = fila.querySelector(".texto-corto");
                let textoCompleto = fila.querySelector(".texto-completo");

                if (textoCompleto.style.display === "none") {
                    textoCompleto.style.display = "inline";
                    textoCorto.style.display = "none";
                    this.textContent = "Leer menos";
                } else {
                    textoCompleto.style.display = "none";
                    textoCorto.style.display = "inline";
                    this.textContent = "Leer m¨¢s";
                }
            });
        });
    });
</script>
        </tbody>
    </table>
</div>

 <!-- jQuery, Popper.js, Bootstrap JS, DataTables -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>


 <script>

$(document).ready(function() {
    setTimeout(function() {
        $('.miTabla').DataTable({
            "destroy": true, // Para evitar inicializaciones repetidas
            "paging": true,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
            }
        });
    }, 1000);
});

 </script>


<?php
function getColorForState($estado) {
    switch ($estado) {
        case 'EnRevision': return '#9acfea';
        case 'Atrasado': return '#F8C471';
        case 'Cancelado': return '#F1948A';
        case 'Aprobado': return '#8aecf1';
        case 'Idea': return '#f3f702';
        case 'EnEdicion': return '#f1cb8a';
        case 'EnCurso': return '#F1948A';
        
        // <span class="Publicado">Publicado</span>
        case 'Publicado': return '#b2dba1';
        default: return '#ffffff';
    }
}

?>
</body>
</html>
<?php ob_end_flush(); ?>