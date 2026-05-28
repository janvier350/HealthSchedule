<?php
require_once("funciones.php");
require_once("conexionBD.php");
$conexion = conectarse();
session_start();

// Obtener y limpiar los datos del formulario
$cieCode = trim($_POST['cieCode']);
$description = trim($_POST['description']);
$category = $_POST['category'];

// Convertir a minúsculas para comparar sin importar mayúsculas/minúsculas
$cieCodeLower = strtolower($cieCode);
$descriptionLower = strtolower($description);

// Verificar si ya existe un registro con el mismo CODIGO y DESCRIPCION (ignorando mayúsculas)
$verifica_sql = "
    SELECT * FROM ENFE_DIAG_COD 
    WHERE LOWER(TRIM(CODIGO)) = ? 
      AND LOWER(TRIM(DESCRIPCION)) = ?
";
$stmt = $conexion->prepare($verifica_sql);
$stmt->bind_param("ss", $cieCodeLower, $descriptionLower);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    echo "<script>alert('Ya existe un registro con ese código y descripción.');</script>";
    echo "<script>window.location.href = '../PNC_CIE-10Crear.php';</script>";
} else {
    // Insertar si no existe duplicado
    $sql = "INSERT INTO ENFE_DIAG_COD (ID_ENFERMEDAD, CODIGO, DESCRIPCION) VALUES (?, ?, ?)";
    $stmt_insert = $conexion->prepare($sql);
    $stmt_insert->bind_param("sss", $category, $cieCode, $description);
    if ($stmt_insert->execute()) {
        echo "<script>alert('Datos Creados Correctamente!');</script>";
        echo "<script>window.location.href = '../PNC_CIE-10Crear.php';</script>";
    } else {
        echo "Error al insertar: " . $stmt_insert->error;
    }
}
?>
