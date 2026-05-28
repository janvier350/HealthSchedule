<?php
session_start();


if($_SESSION['idrol']==1){
        echo "<Script language='JavaScript'>";
        echo 'self.location = "../home_adm.php"';
        echo"</script>"; 
    }else if($_SESSION['idrol']==4){
        echo "<Script language='JavaScript'>";
        echo 'self.location = "../home_control.php"';
        echo"</script>";
    }else if($_SESSION['idrol']==3){
        echo "<Script language='JavaScript'>";
        echo 'self.location = "../home_gerente.php"';
        echo"</script>";
    }else if($_SESSION['idrol']==2){
        echo "<Script language='JavaScript'>";
        echo 'self.location = "../home_vta.php"';
        echo"</script>";
    }else if($_SESSION['idrol']==5){
        echo "<Script language='JavaScript'>";
        echo 'self.location = "../home_paq.php"';
        echo"</script>";
    } else if($_SESSION['idrol']==6){
        echo "<Script language='JavaScript'>";
        echo 'self.location = "../home_control.php"';
        echo"</script>";
    }else if($_SESSION['idrol']==7){
        echo "<Script language='JavaScript'>";
        echo 'self.location = "../home_control.php"';
        echo"</script>";
    }  
 
 ?>
