<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'coach') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}

require_once("../conexion.php");

$id_horario = filter_input(INPUT_GET, 'id_horario', FILTER_VALIDATE_INT);
$id_coach_sesion = $_SESSION['id_usuario'];

if (!$id_horario) {
    echo json_encode(['success' => false, 'message' => 'ID de horario no válido.']);
    exit();
}

$response = ['success' => false, 'data' => []];

try {
    $stmt_clase = $conn->prepare("SELECT nombre_clase_especifico, fecha, hora_inicio 
                                  FROM horario_clases 
                                  WHERE id_horario = ? AND id_coach = ?");
    $stmt_clase->bind_param("ii", $id_horario, $id_coach_sesion);
    $stmt_clase->execute();
    $result_clase = $stmt_clase->get_result();
    $clase_details = $result_clase->fetch_assoc();

    if (!$clase_details) {
        throw new Exception("Clase no encontrada o no tienes permiso.");
    }
    $response['data']['clase_details'] = $clase_details;

    // Obtener los riders de esa clase
    $stmt_riders = $conn->prepare("SELECT a.id_asistencia, r.nombre_rider, a.estatus_asistencia, a.numero_bici
        FROM asistencias a 
        JOIN riders r ON a.id_rider = r.id_rider 
        WHERE a.id_horario = ?
        ORDER BY a.numero_bici ASC");
    $stmt_riders->bind_param("i", $id_horario);
    $stmt_riders->execute();
    $result_riders = $stmt_riders->get_result();
    
    $response['data']['riders'] = $result_riders->fetch_all(MYSQLI_ASSOC);
    $response['success'] = true;

    $stmt_clase->close();
    $stmt_riders->close();

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>