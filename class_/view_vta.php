<?php

require_once("conexionBD.php");
$conexion = conectarse();

//recoger datos del formulario    
 
	$id= $_POST['id'];     

    $sql="SELECT a.idVta_registro,a.fecha_registro,b.nombre_cliente,b.apellido_cliente,b.telefono,c.nombre_ciudad, a.valor_ofrecido,a.observacion,a.Requerimiento
    FROM vta_registro a inner join adm_cliente b on a.idAdm_cliente=b.idAdm_cliente INNER JOIN adm_ciudad c on a.idAdm_ciudad_destino=c.idAdm_ciudad WHERE a.idVta_registro= ".$id;
                              
    $query = $conexion -> query ($sql);
    if($query){
        while ($valores = mysqli_fetch_array($query)) {
       
            ?>   

            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModalCenter">
              Launch demo modal
            </button>

            <!-- Modal -->
            <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Modal title</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                    </button>
                  </div>
                  <div class="modal-body">
                    ...
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
                  </div>
                </div>
              </div>
            </div>
            <?php }  }   

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Show Bootstrap Modal Window Using jQuery</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
<script>
    $(document).ready(function(){
        $(".btn").click(function(){
            $("#myModal").modal('show');
        });
    });
</script>
<style>
    .bs-example{
        margin: 20px;
    }
</style>
</head>
<body>
<div class="bs-example">
    <!-- Button HTML (to Trigger Modal) -->
    <button type="button" class="btn btn-lg btn-primary">Show Modal</button>
 
    <!-- Modal HTML -->
    <div id="myModal" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modal Title</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p>This is a simple Bootstrap modal. Click the "Cancel button", "cross icon" or "dark gray area" to close or hide the modal.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">Save</button>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>