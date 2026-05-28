    
    var cantidadSum =0;
    var pvpSum =0;
    var totalSum=0;

    $(document).ready(function(){
        window.onload = function() {
            updateSubTotalEdit();
        }
        $("#deleteItem").on('click',function(){            
            swal({
                title: "Estas Seguro?",
                text: "Si eliminas, el registro se perdera!",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
            .then((willDelete) => {
            if (willDelete) {
                deleteItem();
                updateSubTotalEdit();
                swal("Registro Eliminado con Exito!", {
                    icon: "success",
                });
            } else {
                swal("Accion Cancelada!", {
                    icon: "error",
                    });
            }
            }); 
         });

        $("#actualizar").on('click',function(){   
            swal({
                title: "Se va actualizar la venta!",
                text: "Estas Seguro de Continuar?",
                icon: "warning",
                buttons: true,
                dangerMode: false,
                closeModal: false,
            })
            .then((Actualizar) => {
            if (Actualizar) {
                editVenta();   
                                
            } else {
                swal("Accion Cancelada!", {
                    icon: "error",
                    });
                swal.stopLoading();
                swal.close();
            }
            });                             
        
        });

         $("#addItem").on('click',function(){            
            addItemEdit();
         });
    });
    

    function deleteItem(){
        $("#ventaTable tbody").find('input[name="record"]').each(function(){
            if($(this).is(":checked")){
                idrecord = $(this).val();
                console.log(idrecord);                
                $(this).parents("tr").remove();
                deleteRegDetalle(idrecord);
            }
        });
    }

    function addItemEdit(){       
        var cantidad = document.getElementById("cantidad").value;        
        var detalle = $("#detalle").val();          
        var pvp= document.getElementById("pvp").value; 
        var costo= document.getElementById("costoDetalle").value;
        var total= document.getElementById("total_detalle").value;
        var idVta = document.getElementById("idVta").value;
            if (cantidad == 0)
            {            
                swal('La Cantidad ingresada debe ser mayor a 0, Revise los datos ingresados.','Edicion de Ventas','warning').then((value) =>{
                        
                });    
            }
            if (detalle == 0)
            {            
                swal('Seleccione Detalle, Revise los datos ingresados.','Edicion de Ventas','warning').then((value) =>{
                        
                });    
            }
            if (costo == 0)
            {            
                swal('El costo debe ser mayor a 0, Revise los datos ingresados.','Edicion de Ventas','warning').then((value) =>{
                        
                });    
            }
            else
            {    
                var markup = "<tr><td><input type='checkbox' name='record'></td><td>" + cantidad + "</td>"+
                "<td>" + detalle + "</td><td class='text-right'>" + pvp + "</td><td class='text-right'>" + costo 
                + "</td><td class='text-right'>" + total + "</td></tr>";
                $("#ventaTable tbody").append(markup);
                document.getElementById("cantidad").value=1;                
                document.getElementById("pvp").value=0;     
                document.getElementById("costoDetalle").value=0;              
                document.getElementById("total_detalle").value=0;
                saveRegDetalle(idVta, cantidad, detalle, pvp, costo, total); 
                updateSubTotalEdit();
            }                    
        }                    
    

    function updateSubTotalEdit() {
        var total_pvp = 0;
        var pvp = 0;    
        var total_costo =0;
        var cost =0;
        var total_tot =0;
        var tot =0;  
        var cnt = 0;
        var total_cnt =0 ;      
        var rows =  $('#detalleTb tr');
        console.log("updateSubTotal filas:"+rows.length); 

        for (var i = 0; i <rows.length; i ++) {// recorre las filas de la tabla
            for (var j = 0; j <rows[i].cells.length; j ++) {//0atraviesa columnas cada fila
                console.log ("fila" + (i+1) +", el valor colum" +(j+1)+ "es:" + rows[i].cells[j].innerHTML);
                cnt = parseFloat(rows[i].cells[1].innerHTML);
                pvp = parseFloat(rows[i].cells[3].innerHTML);
                cost = parseFloat(rows[i].cells[4].innerHTML);
                tot = parseFloat(rows[i].cells[5].innerHTML);
            }
            total_cnt += cnt;
            total_pvp += pvp;
            total_costo += cost;
            total_tot += tot;
            pvp =0;
            cost =0;
            tot =0;
            cnt = 0;
            console.log (total_cnt);
            console.log (total_pvp);
            console.log (total_costo);
            console.log (total_tot);                
            $('#ventaTable tfoot tr th').eq(3).text("" + total_pvp.toFixed(2));
            $('#ventaTable tfoot tr th').eq(4).text("" + total_costo.toFixed(2));
            $('#ventaTable tfoot tr th').eq(5).text("" + total_tot.toFixed(2));

            $("#valor_ofrecido").val("" + total_pvp.toFixed(2));
            $("#total").val("" + total_tot.toFixed(2));
            $("#costo").val("" + total_costo.toFixed(2));
            $("#cnt").val("" + total_cnt.toFixed(2));
        }                  
    }
   

    //change pvp text    
    $(document).ready(function() {
        $('#pvp').change(function(){      
            console.log("change_pvp");   
            var cantidad = document.getElementById('cantidad').value;
            var pvp = document.getElementById('pvp').value;
            //tot.value = cantidad;
            $("#total_detalle").val(parseFloat(cantidad*pvp).toFixed(2));
        });
    });

    

    function editCabVenta(idVta){
        console.log("EditCabVenta");            
        var fecha_salida = document.getElementById("fecha_salida").value;
        var fecha_regreso = document.getElementById("fecha_regreso").value;
        var aerolinea = document.getElementById("aerolinea").value;
        var tpasajero = document.getElementById("tpasajero").value;
        var valor_ofrecido = document.getElementById("valor_ofrecido").value;        
        var cnt = document.getElementById("cnt").value;
        var total = document.getElementById("total").value;
        var costo = document.getElementById("costo").value;
        var requerimiento = '';        
        var observacion = document.getElementById("observacion").value;
        var origen = document.getElementById("origen").value;
        var destino = document.getElementById("destino").value;
        var forma_pago = document.getElementById("forma_pago").value;
        var forma_pago_fee = document.getElementById("forma_pago_fee").value;
        var idVenta;
        
        $('#saveBar').show();
        console.log("fecha_salida "+fecha_salida);
        console.log("fecha_regreso "+fecha_regreso);        
        $.ajax({
            async: false,
            type: 'POST',
            url: 'class/Update_Vta.php',
            data: { idVta : idVta, fecha_salida : fecha_salida, fecha_regreso : fecha_regreso, 
                aerolinea : aerolinea, tpasajero : tpasajero, valor_ofrecido : valor_ofrecido,
                cnt : cnt, total : total, costo : costo, requerimiento : requerimiento, observacion : observacion, 
                origen :origen, destino :destino, forma_pago : forma_pago, forma_pago_fee : forma_pago_fee },
            dataType: "json",
            success:function(dataInsert){              
                console.log('dataInsert:');
                console.log(dataInsert);
                idVenta = dataInsert['id']; 
                msg = dataInsert['msg'];   
                console.log("idAxInsertVta " + idVenta);                              
            },error:function(err){
                console.log(err);
            }

        }); 
        
        return parseInt(idVenta);
    }

    function editVenta(){
        var idVenta;
        var idVta = document.getElementById("idVta").value;   
        
    	console.log("editVenta");                                                                    
               
        $('#saveBarVenta').show();
        $('#saveBarVenta').animate({width: "50%"}, 100);  
        var tab = document.getElementById("detalleTb");   
        var rows = tab.rows;
        if (rows.length != 0)
        {
            idVenta = editCabVenta(idVta);            
            if(parseInt(idVenta) > 0){
                console.log ("idVenta "+ idVenta);
                $('#saveBarVenta').animate({width: "70%"}, 100); 
                swal.stopLoading();
                
                swal('Venta Actualizada Correctamente!','Edicion de Ventas','success').then((value) =>{
                    location.reload();     
                });   
                //recorreTable(idVenta);                                   
            }                   
        }else{
            swal('La tabla de Valores esta vacia, Revise los datos ingresados.','Registro de Ventas','error').then((value) =>{ });  
        }                                                 
                   
    }

    function recorreTable(idVenta) {
        var tab = document.getElementById("detalleTb");             
        var cantidad;
        var detalle;
        var precio = 0;                           
        var costo = 0;    
        var total = 0;    
        var rows = tab.rows;
        console.log("filas: "+rows.length); // Obtiene el número de filas en la tabla
        $('#saveBarDetalle').show();
        $('#saveBarDetalle').animate({width: "70%"}, 100); 
        for (var i = 1; i <rows.length; i ++) {// recorre las filas de la tabla
            for (var j = 0; j <rows[i].cells.length; j ++) {//0atraviesa columnas cada fila               
                cantidad = rows[i].cells[0].innerHTML;                  
                detalle = rows[i].cells[1].innerHTML;                   
                precio = parseFloat(rows[i].cells[2].innerHTML);                                                 
                //costo = parseFloat(rows[i].cells[3].innerHTML);     
                total = parseFloat(rows[i].cells[3].innerHTML);     
            }   
            console.log("cantidad: " + cantidad +" - detalle: " + detalle+" - precio: " + precio +" - total: " + total) ;
            $('#saveBarDetalle').animate({width: "80%"}, 100); 
            saveRegDetalle(idVenta, cantidad, detalle, precio, costo, total);                                                      
        }      
    }

    function saveRegDetalle(idVenta, cantidad, detalle, precio, costo, total){             
        $.ajax({
            async: false,
            type: 'POST',
            url: 'class/Insert_Vta_Registro_Detalle.php',
            data: { idVenta : idVenta, cantidad : cantidad, detalle: detalle, precio : precio, costo : costo, total : total },
            dataType: "json",
            success:function(dataInsert){                              
                console.log(dataInsert);
                id = dataInsert['id'];   
                msg = dataInsert['msg'];   
                console.log("idAxInsertFact: " + id);                                                    
                location.reload();                         
            },error:function(err){
                console.log(err);
            }
        });     
    }


    function deleteRegDetalle(id){            
        $.ajax({
            async: false,
            type: 'POST',
            url: 'class/Delete_Vta_Detalle.php',
            data: { id : id },
            dataType: "json",
            success:function(dataInsert){                              
                console.log(dataInsert);                                                                                                                     
            },error:function(err){
                console.log(err);
            }
        });     
    }
