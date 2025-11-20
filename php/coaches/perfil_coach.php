<?php
header('Content-Type: application/json');
session_start();
require '../conexion.php';

// Validación de sesión
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'coach') {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit();
}

$id_coach = $_SESSION['id_usuario'];
$response = ['success' => false];

try {

    // CONSULTA PRINCIPAL DEL COACH
    $stmt = $conn->prepare("SELECT nombre_coach, 
            email_coach, 
            telefono_coach, 
            foto_coach, 
            estatus, 
            created_at AS fecha_registro 
        FROM coaches 
        WHERE id_coach = ?");
    $stmt->bind_param("i", $id_coach);
    $stmt->execute();
    $result = $stmt->get_result();
    $coach_data = $result->fetch_assoc();

    if ($coach_data) {
        $response['success'] = true;
        $response['data'] = $coach_data;

        // CLASES IMPARTIDAS
        $stmt_clases = $conn->prepare("
            SELECT COUNT(*) AS clases_impartidas
            FROM horario_clases
            WHERE id_coach = ?
        ");
        $stmt_clases->bind_param("i", $id_coach);
        $stmt_clases->execute();
        $result_clases = $stmt_clases->get_result();
        $stats_clases = $result_clases->fetch_assoc();

        $response['data']['clases_impartidas'] = $stats_clases['clases_impartidas'] ?? 0;

        // RIDERS ENTRENADOS
        $stmt_riders = $conn->prepare("
            SELECT COUNT(DISTINCT a.id_rider) AS riders_entrenados
            FROM asistencias a
            JOIN horario_clases hc ON a.id_horario = hc.id_horario
            WHERE hc.id_coach = ? AND a.estatus_asistencia = 'presente'
        ");
        $stmt_riders->bind_param("i", $id_coach);
        $stmt_riders->execute();
        $result_riders = $stmt_riders->get_result();
        $stats_riders = $result_riders->fetch_assoc();

        $response['data']['riders_entrenados'] = $stats_riders['riders_entrenados'] ?? 0;
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>
