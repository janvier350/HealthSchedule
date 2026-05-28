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
        $menu = './menu/menu_adm.php' ;   
    }else if($_SESSION['idrol']==4){
        $menu = './menu/menu_control_vta.php' ;   
    }else if($_SESSION['idrol']==2){
        $menu = './menu/menu_vta.php' ;   
    }             
?>