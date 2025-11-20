<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'coach') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}

require_once("../conexion.php");

$data = json_decode(file_get_contents('php://input'), true);
$id_asistencia = $data['id_asistencia'] ?? null;
$estatus = $data['estatus'] ?? null;
$id_coach_sesion = $_SESSION['id_usuario'];

if (!$id_asistencia || !$estatus) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
    exit();
}

try {
    // Seguridad: Asegurarse que el coach solo actualice asistencias de sus propias clases.
    $stmt = $conn->prepare("UPDATE asistencias a 
                            JOIN horario_clases hc ON a.id_horario = hc.id_horario 
                            SET a.estatus_asistencia = ? 
                            WHERE a.id_asistencia = ? AND hc.id_coach = ?");
    
    $stmt->bind_param("sii", $estatus, $id_asistencia, $id_coach_sesion);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo actualizar.']);
    }
    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}

$conn->close();
?>