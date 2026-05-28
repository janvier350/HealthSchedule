
function eliminarCliente(id) {            
    var dataen ='id='+id;     
    swal({
        title: "Eliminar Cliente?",
        text: "Estas Seguro de Continuar?",
        icon: "warning",
        buttons: true,
        dangerMode: true,
        closeModal: false,
    })
    .then((Actualizar) => {
        if (Actualizar) {
            $.ajax({
                type: 'POST',
                url: 'class/Inactive_Cliente.php',                 
                data: dataen, 
                dataType: "json",                 
                success:function(resp){                                         
                   if(resp["status"]=='ok'){                        
                       swal("Cliente Eliminado con Exito!", {
                            icon: "success",
                        }).then((value) =>{
                            self.location ='PNC_ClienteListado.php';   
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
function eliminarUsuario(id) {            
    var dataen ='id='+id;     
    swal({
        title: "Eliminar Usuario?",
        text: "Estas Seguro de Continuar?",
        icon: "warning",
        buttons: true,
        dangerMode: true,
        closeModal: false,
    })
    .then((Actualizar) => {
        if (Actualizar) {
            $.ajax({
                type: 'POST',
                url: 'class/Inactive_Usuario.php',                 
                data: dataen, 
                dataType: "json",                 
                success:function(resp){                                         
                   if(resp["status"]=='ok'){                        
                       swal("Usuario Eliminado con Exito!", {
                            icon: "success",
                        }).then((value) =>{
                            self.location ='PNC_UsuarioListado.php';   
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