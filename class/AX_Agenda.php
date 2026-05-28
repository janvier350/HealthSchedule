<?php

require_once("conexionBD.php");
$conexion = conectarse();

//recoger datos del formulario    
    $data = array();
	$id= $_REQUEST['id'];     
    $sqlSP = "CALL SP_CITAS();";
    $querySP = $conexion -> query ($sqlSP);

    $sqlValida= "SELECT A.IDCITA, A.IDPACIENTE,CONCAT(B.NOMBRES, ' ', B.APELLIDOS , ' -- TIPO: ', C.NOMBRES , ' -- ESTADO: ', A.ESTADO_CITA) AS PACIENTE, A.FECHA_CITA, A.HORA_INICIO, A.HORA_FIN ,SUBSTRING(A.FECHA_CITA, 1, 4) AS ANIO,SUBSTRING(A.FECHA_CITA, 6, 2) AS  MES,SUBSTRING(A.FECHA_CITA, 9, 2) AS  DIA,SUBSTRING(A.HORA_INICIO, 1, 2) AS  HINI,SUBSTRING(A.HORA_INICIO, 4, 2) AS  MINI,SUBSTRING(A.HORA_FIN, 1, 2) AS  HFIN,SUBSTRING(A.HORA_FIN, 4, 2) AS  MFIN, C.NOMBRES, A.ESTADO_CITA, A.COMENTARIO, CONCAT(A.FECHA_CITA,'T',A.HORA_INICIO) AS FINICIO , CONCAT(A.FECHA_CITA,'T',A.HORA_FIN) AS FFIN FROM AG_CITA A INNER JOIN AG_PACIENTE B ON A.IDPACIENTE = B.IDPACIENTE INNER JOIN AG_TIPOCONSULTA C ON A.IDTIPOCONSULTA = C.IDTIPOCONSULTA";
    $query = $conexion -> query ($sqlValida);
       
    $i=0;
    while ($valores = mysqli_fetch_array($query)) {
        
        $data[$i]['title'] = $valores['PACIENTE'];
        $data[$i]['start'] = $valores['FINICIO'];
        $data[$i]['end'] = $valores['FFIN'];

        $i++;
}
echo json_encode($data);

?>