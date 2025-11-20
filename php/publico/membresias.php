<?php
// Le decimos al navegador que la respuesta será en formato JSON
header('Content-Type: application/json');

// Incluimos tu archivo de conexión
require_once("../conexion.php");

// Arreglo para la respuesta
$response = [];

// Consulta para obtener solo las membresías con estatus 'Activo'
$sql = "SELECT id_tipo_membresia, nombre, descripcion, precio, periodo, caracteristicas, es_popular 
        FROM tipos_membresia 
        WHERE estatus = 'Activo' 
        ORDER BY es_popular DESC, precio DESC";

$resultado = $conn->query($sql);

if ($resultado) {
    $membresias = [];
    while ($fila = $resultado->fetch_assoc()) {
        $membresias[] = $fila;
    }
    $response['status'] = 'success';
    $response['data'] = $membresias;
} else {
    $response['status'] = 'error';
    $response['message'] = 'No se pudieron obtener las membresías: ' . $conn->error;
}

// Cerramos la conexión
$conn->close();

// Devolvemos la respuesta en formato JSON
echo json_encode($response);
?>