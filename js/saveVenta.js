
    var cantidadSum =0;
    var pvpSum =0;
    var totalSum=0;

    $(document).ready(function(){                
        $("#save").on('click',function(){            
        saveVenta();
        });

        $("#addItem").on('click',function(){            
        addItem();
        });

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
                    updateSubTotal();
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

        $("#createClient").on('click',function(){            
        createClient();
        });
        
    });
    
    function deleteItemPresupuesto(){
        $("#ventaTable tbody").find('input[name="record"]').each(function(){
            if($(this).is(":checked")){
                $(this).parents("tr").remove();
            }
        });
    }
    
    function buscarCliente(){                     
            var buscaCliente =document.getElementById('buscaCliente').value;
            var dataen ='buscaCliente='+buscaCliente;
            
             $.ajax({
                 type: 'post',
                 url: 'class/AX_ClienteBuscar.php',                 
                 data: dataen, 
                 dataType: "json",                 
                 success:function(html){   
                    $("#idCliente").val(''+html["idCliente"]);                    
                    $("#apellido").val(''+html["apellido"]);                    
                    $("#nombre").val(''+html["nombre"]);
                    $("#telefono").val(''+html["telefono"]);                    
                 }                              
             });
        return false;         
    } 

    function addItem(){                
        var cantidad = document.getElementById("cantidad").value;
        var detalle= document.getElementById("detalle").value;            
        var pvp= document.getElementById("pvp").value;         
        var total= document.getElementById("total_detalle").value;         
        if (cantidad == 0){            
            swal('La Cantidad ingresada debe ser mayor a 0, Revise los datos ingresados.','Registro de Ventas','warning').then((value) =>{
                    
            });    
        }else{    
            var markup = "<tr><td><input type='checkbox' name='record'></td><td>" + cantidad + "</td>"+
            "<td>" + detalle + "</td><td class='text-right'>" + pvp + "</td><td class='text-right'>" + total + "</td></tr>";
            $("#ventaTable tbody").append(markup);

                document.getElementById("cantidad").value=1;                
                document.getElementById("pvp").value=0;                
                document.getElementById("total_detalle").value=0;
                updateSubTotal();
            }    
        }                    
    

    function updateSubTotal() {                                   
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
                //cost = parseFloat(rows[i].cells[3].innerHTML);
                tot = parseFloat(rows[i].cells[4].innerHTML);
            }
            total_cnt += cnt;
            total_pvp += pvp;
            //total_costo += cost;
            total_tot += tot;
            pvp =0;
            //cost =0;
            tot =0;
            cnt = 0;
            console.log (total_cnt);
            console.log (total_pvp);
            //console.log (total_costo);
            console.log (total_tot);                
            $('#ventaTable tfoot tr th').eq(3).text("" + total_pvp.toFixed(2));
            //$('#myTable tfoot tr th').eq(3).text("" + total_costo.toFixed(2));
            $('#ventaTable tfoot tr th').eq(4).text("" + total_tot.toFixed(2));

            $("#valor_ofrecido").val("" + total_pvp.toFixed(2));
            $("#total").val("" + total_tot.toFixed(2));
            //$("#costo").val("" + total_costo.toFixed(2));
            $("#cnt").val("" + total_cnt.toFixed(2));
        }                  
    }

    // change idcliente
    $(document).ready(function() {
        $('#idCliente').change(function(){      
            var id=document.getElementById('idCliente').value;
            var dataString = 'id='+ id;        
            console.log("change_cliente");
            document.getElementById('apellido').readOnly = true;
            document.getElementById('nombre').readOnly = true;
            document.getElementById('telefono').readOnly = true;            
            $.ajax({
                    type: 'POST',
                    url: 'class/AX_ClienteBuscar.php',
                    data: dataString,
                    dataType: "json",
                     success:function(html){                                                          
                        $("#apellido").val(''+html["apellido"]);
                        $("#nombre").val(''+html["nombre"]);
                        $("#telefono").val(''+html["telefono"]);                    
                     } 
            });        
        });
    });     

    // change costotext
    $(document).ready(function() {
        $('#costo').change(function(){              
            addItem();            
        });

    });

    // change tipo de servicio    
    $(document).ready(function() {
        $('#tserv').change(function(){   
            console.log("change_tipoServicio");           
            var tipo_ser=document.getElementById('tserv').value;
            console.log(tipo_ser);
            if (tipo_ser =="VENTA")
                document.getElementById("div_viaje").style.visibility = "visible";
            else if (tipo_ser != "VENTA")
                document.getElementById("div_viaje").style.visibility = "hidden";
        });
    });

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

    function createClient(){        
        document.getElementById('apellido').readOnly = false;
        document.getElementById('apellido').value = ''; 
        document.getElementById('nombre').readOnly = false;
        document.getElementById('nombre').value = ''; 
        document.getElementById('telefono').readOnly = false;  
        document.getElementById('telefono').value = ''; 
       
    }

    function saveClient(){
        var apellido = document.getElementById("apellido").value;
        var nombre = document.getElementById("nombre").value;                
        var telefono = document.getElementById("telefono").value;                
        var idSaveCliente;                     
            $.ajax({
                async: false,
                type: 'POST',
                url: 'class/AX_InsertClient.php',
                data: { apellido : apellido, nombre : nombre, telefono :telefono },
                dataType: "json",
                success:function(dataInsert){                                  
                    console.log(dataInsert);
                    idSaveCliente = dataInsert['id']; 
                    var newOption = $('<option value="'+idSaveCliente+'" selected="selected">'+nombre+' '+apellido+'</option>');
                    $('#idCliente').append(newOption);                                                       
                    $('.chosen-select').trigger('chosen:updated');
                    $('.chosen-select').trigger('chosen:activate');
                    console.log("idAxInsertCliente " + idSaveCliente);
                } 
            });                              
        return parseInt(idSaveCliente);
    }

    function saveCabVenta(idCliente){
        console.log("SaveCabVenta");
        var tserv = document.getElementById("tserv").value;        
        var fecha_salida = document.getElementById("fecha_salida").value;
        var fecha_regreso = document.getElementById("fecha_regreso").value;
        var aerolinea = document.getElementById("aerolinea").value;
        var tpasajero = '0';
        var valor_ofrecido = document.getElementById("valor_ofrecido").value;        
        var cnt = document.getElementById("cnt").value;
        var total = document.getElementById("total").value;
        var costo = 0;
        var requerimiento = '';        
        var observacion = document.getElementById("observacion").value;
        var origen = document.getElementById("origen").value;
        var destino = document.getElementById("destino").value;
        // var forma_pago = document.getElementById("forma_pago").value;
        // var forma_pago_fee = document.getElementById("forma_pago_fee").value;
        var idVenta;
        
        $('#saveBar').show();
        console.log("fecha_salida "+fecha_salida);
        console.log("fecha_regreso "+fecha_regreso);        
        $.ajax({
            async: false,
            type: 'POST',
            url: 'class/Insert_Vta_Registro.php',
            data: { idCliente : idCliente, tserv : tserv, fecha_salida : fecha_salida, fecha_regreso : fecha_regreso, 
                aerolinea : aerolinea, tpasajero : tpasajero, valor_ofrecido : valor_ofrecido,
                cnt : cnt, total : total, costo : costo, requerimiento : requerimiento, observacion : observacion, origen :origen, destino :destino},
            dataType: "json",
            success:function(dataInsert){              
                console.log('dataInsert:');
                console.log(dataInsert);
                idVenta = dataInsert['id'];   
                console.log("idAxInsertVta " + idVenta);             
            },error:function(err){
                console.log(err);
            }

        }); 
        
        return parseInt(idVenta);
    }

    function saveVenta(){
        var idVenta;
        var idCliente;        
        var idClienteTxt = $("#idCliente").chosen().find("option:selected").val();
        var total = document.getElementById("total").value;
        console.log("cliente combo "+idClienteTxt);
    	console.log("saveVenta"); 
        $('#saveBarCliente').show();
        $('#saveBarCliente').animate({width: "10%"}, 100);        
        
        if (parseInt(idClienteTxt) > 0 ){
            
            idCliente = parseInt(idClienteTxt);                     
        }else {
            console.log("cliente nuevo");
            idCliente = saveClient(); 
            $('#saveBarCliente').animate({width: "20%"}, 100); 
        }                 
        console.log ("idCliente Save Cliente "+ idCliente);        
        if (parseInt(idCliente) == 0){
            console.log("cliente Existe");
            swal('Cliente Ya existe, Revise los datos ingresados.','Registro de Ventas','error').then((value) =>{
                   
            });               
        }else if (parseInt(idCliente) > 0){
            console.log("cliente > 0");
            $('#saveBarCliente').animate({width: "30%"}, 100);         
            $('#saveBarVenta').show();
            $('#saveBarVenta').animate({width: "50%"}, 100);  
            var tab = document.getElementById("detalleTb");   
            var rows = tab.rows;
            if (rows.length != 0)
            {
                idVenta = saveCabVenta(idCliente);            
                if(parseInt(idVenta) > 0){
                    console.log ("idVenta "+ idVenta);
                    $('#saveBarVenta').animate({width: "70%"}, 100);  
                    recorreTable(idVenta);                                   
                }                   
            }else{
                swal('La tabla de Valores esta vacia, Revise los datos ingresados.','Registro de Ventas','error').then((value) =>{ });  
            }                                                 
        }            
    }

    function recorreTable(idVenta) {
        var tab = document.getElementById("detalleTb");             
        var cantidad;
        var detalle;
        var precio = 0;                           
        var costo = 0;    
        var total = 0;   
        var rows=0; 
        rows = tab.rows;
        console.log("recorreTable filas : "+rows.length); // Obtiene el número de filas en la tabla
        $('#saveBarDetalle').show();
        $('#saveBarDetalle').animate({width: "70%"}, 100); 
        for (var i = 0; i <rows.length; i ++) {// recorre las filas de la tabla
            for (var j = 0; j <rows[i].cells.length; j ++) {//0atraviesa columnas cada fila               
                cantidad = rows[i].cells[1].innerHTML;                  
                detalle = rows[i].cells[2].innerHTML;                   
                precio = parseFloat(rows[i].cells[3].innerHTML);                                                 
                //costo = parseFloat(rows[i].cells[3].innerHTML);     
                total = parseFloat(rows[i].cells[4].innerHTML);     
            }   
            console.log("cantidad: " + cantidad +" - detalle: " + detalle+" - precio: " + precio +" - total: " + total) ;
            $('#saveBarDetalle').animate({width: "80%"}, 100); 
            saveRegDetalle(idVenta, cantidad, detalle, precio, costo, total);                                                      
        }      
    }

    function saveRegDetalle(idVenta, cantidad, detalle, precio, costo, total){    
        $('#saveBarDetalle').animate({width: "90%"}, 100);      
        $.ajax({
            async: false,
            type: 'POST',
            url: 'class/Insert_Vta_Registro_Detalle.php',
            data: { idVenta : idVenta, cantidad : cantidad, detalle: detalle, precio : precio, costo : costo, total : total },
            dataType: "json",
            success:function(dataInsert){              
                console.log('dataInsert:');
                console.log(dataInsert);
                id = dataInsert['id'];   
                msg = dataInsert['msg'];   
                console.log("idAxInsertFact: " + id);                                                    
                              
                $('#saveBarDetalle').animate({width: "100%"}, 100); 
                
                swal(msg,'Registro de Ventas','success').then((value) =>{
                    location.reload();     
                });                     
            },error:function(err){
                console.log(err);
            }
        });     
    }