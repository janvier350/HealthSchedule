<?php
session_start();
require_once("class/conexionBD.php");
$conexion=conectarse();

    if(!isset($_SESSION["loggedin"])){
        header("Location: break.php");
    }else {
        $now = time(); // Checking the time now when home page starts.
        if ($now > $_SESSION['expire']) {
            session_destroy();
            header("Location: expirada.php");
        }}
    if($_SESSION['idrol']==1){
        $th = '<th class="text-center">Eliminar</th>';  
        $button= '';
    }else if($_SESSION['idrol']==4){
        $th = '<th class="text-center">Eliminar</th>';   
    }else if($_SESSION['idrol']==2){
        $th = '' ;   
        $button = '';
    }             
?>