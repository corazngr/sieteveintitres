<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

$response = ['success' => false, 'message' => 'Acción no permitida.'];

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo json_encode($response);
    exit();
}

require_once("../conexion.php");

$specific_id = $_POST['specific_id'] ?? 0;
$tipo_usuario = $_POST['tipo'] ?? ''; // 'rider' o 'coach'

if ($specific_id <= 0 || empty($tipo_usuario)) {
    $response['message'] = 'Error: Faltan datos para la reactivación.';
    echo json_encode($response);
    exit();
}

try {
    $tabla_a_actualizar = '';
    $columna_id = '';

    if ($tipo_usuario === 'rider') {
        $tabla_a_actualizar = 'riders';
        $columna_id = 'id_rider';
    } elseif ($tipo_usuario === 'coach') {
        $tabla_a_actualizar = 'coaches';
        $columna_id = 'id_coach';
    } else {
        throw new Exception("Tipo de usuario no válido.");
    }

    // La consulta de reactivación
    $sql = "UPDATE $tabla_a_actualizar SET esta_activo = 1 WHERE $columna_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $specific_id);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Usuario reactivado correctamente.';
    } else {
        $response['message'] = 'No se pudo reactivar al usuario.';
    }
    $stmt->close();

} catch (Exception $e) {
    $response['message'] = 'Error en el servidor: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>