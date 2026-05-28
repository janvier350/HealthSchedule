$(document).ready(function(){
    $(".btn").click(function(){
        $("#myModal").modal('show');
    });
});

function validate(id){
    var dataen ='id='+id;  
    var response;
    $.ajax({
        type: 'POST',
        url: 'class/AX_Vta_Validate.php',                 
        data: dataen, 
        dataType: "json",                 
        success:function(resp){ 
            statuss = resp['status'];   
            msg = resp['msg'];                 
            console.log(msg);
            if(statuss == 'ok')
            {
                concretarVta(id);
            }
            else if(statuss == 'no')
            {
                swal(msg,'Validacion de Datos', {
                    icon: "error",
                }); 
            }
        },error: function (resp) {  
            console.log(resp);                 
            swal('Error con la petición '+resp[0], {
                icon: "error",
            });
        }                              
    });
    return response;
}

function concretar(id) {                    
    swal({
        title: "Enviar a Ventas Concretadas?",
        text: "Estas Seguro de Continuar?",
        icon: "warning",
        buttons: true,
        dangerMode: false,
        closeModal: false,
    })
    .then((Actualizar) => {
        if (Actualizar) {
            validate(id);                                      
        } else {
            swal("Accion Cancelada!", {
                icon: "error",
            });
            swal.stopLoading();
            swal.close();
        }
    });   

    
    return false;  
}

function prioridad(id) {            
    var dataen ='id='+id;
    swal({
        title: "Enviar a Prioridad?",
        text: "Estas Seguro de Continuar?",
        icon: "warning",
        buttons: true,
        dangerMode: false,
        closeModal: false,
    })
    .then((Actualizar) => {
        if (Actualizar) {
            $.ajax({
                type: 'POST',
                url: 'class/AX_Vta_Prioridad.php',                 
                data: dataen, 
                dataType: "json",                 
                success:function(resp){                                         
                    if(resp["status"]=='ok'){ 
                        swal("Venta Prioridad con Exito!", {
                            icon: "success",
                        }).then((value) =>{
                            location.reload();     
                        }); 
                    }
                },error: function (resp) {
                    swal('Error con la petición '+resp[0], {
                        icon: "error",
                    });
                }                              
            });                                    
        } else {
            swal("Accion Cancelada!", {
                icon: "error",
            });
            swal.stopLoading();
            swal.close();
        }
    });  

return false;  
}

function negar(id) {            
    var dataen ='id='+id;

    swal({
        title: "Enviar a Ventas Negadas?",
        text: "Estas Seguro de Continuar?",
        icon: "warning",
        buttons: true,
        dangerMode: true,
        closeModal: false,
    })
    .then((Actualizar) => {
        if (Actualizar) {                        
            $.ajax({
                type: 'post',
                url: 'class/AX_Vta_Negar.php',                 
                data: dataen, 
                dataType: "json",                 
                success:function(resp){                  
                    if(resp["status"]=='ok'){ 
                        swal("Venta Negada con Exito!", {
                            icon: "success",
                        }).then((value) =>{
                            location.reload();     
                        }); 
                    }
                },error: function (response) {
                    swal('Error con la petición '+resp[0], {
                        icon: "error",
                    });
                }                              
            });
        } else {
            swal("Accion Cancelada!", {
                icon: "error",
            });
            swal.stopLoading();
            swal.close();
        }
    });         
return false;  
}


function view(id) {            
    var dataen ='id='+id;       
    alert('view');       
    $.ajax({
         type: 'post',
         url: 'class/view_vta.php',                 
         data: dataen, 
         dataType: "json",  
         success:function(resp){                  
            if(resp["status"]=='ok'){ 
                alert('Venta Negada');
                location.reload();
            }
         },error: function (response) {
            alert('error con la petición'+response);
        }                  
                                      
     });
return false;  
}

function concretarVta(id){
    var dataen ='id='+id;  
    $.ajax({
        type: 'POST',
        url: 'class/AX_Vta_Concretar.php',                 
        data: dataen, 
        dataType: "json",                 
        success:function(resp){                                         
           if(resp["status"]=='ok'){                        
               swal("Venta Concretada con Exito!", {
                    icon: "success",
                }).then((value) =>{
                    location.reload();     
                }); 

           }
        },error: function (resp) {                   
            swal('Error con la petición '+resp[0], {
                icon: "error",
            });
        }                              
    });
}