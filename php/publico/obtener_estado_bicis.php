<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once("../conexion.php");

$id_horario = filter_input(INPUT_GET, 'id_horario', FILTER_VALIDATE_INT);

$response = [
    'success' => false,
    'total_bicis' => 15, 
    'ocupadas' => [],      
    'fuera_de_servicio' => [] 
];

if (!$id_horario) {
    $response['message'] = 'ID de clase no válido.';
    echo json_encode($response);
    exit();
}

try {
    $sql_ocupadas = "SELECT id_bicicleta FROM reservaciones WHERE id_horario = ? AND estatus = 'Activa'";
    $stmt_ocupadas = $conn->prepare($sql_ocupadas);
    $stmt_ocupadas->bind_param("i", $id_horario);
    $stmt_ocupadas->execute();
    $resultado = $stmt_ocupadas->get_result();

    $bicis_ocupadas = [];
    while ($fila = $resultado->fetch_assoc()) {
        $bicis_ocupadas[] = (int)$fila['id_bicicleta'];
    }
    $stmt_ocupadas->close();

    $sql_fuera = "SELECT id_bicicleta FROM bicicletas WHERE estado != 'Disponible'";
    $stmt_fuera = $conn->prepare($sql_fuera);
    $stmt_fuera->execute();
    $resultado_fuera = $stmt_fuera->get_result();

    $bicis_fuera = [];
    while ($fila_fuera = $resultado_fuera->fetch_assoc()) {
        $bicis_fuera[] = (int)$fila_fuera['id_bicicleta'];
    }
    $stmt_fuera->close();

    $response['success'] = true;
    $response['ocupadas'] = $bicis_ocupadas;
    $response['fuera_de_servicio'] = $bicis_fuera;
    
} catch (Exception $e) {
    $response['message'] = 'Error al consultar el estado de las bicis: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>