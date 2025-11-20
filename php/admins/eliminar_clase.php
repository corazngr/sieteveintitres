<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}
require_once("../conexion.php");

$id_horario = $_POST['id_horario'] ?? 0;

if ($id_horario <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de clase no válido.']);
    exit();
}

try {
    $conn->begin_transaction();
    // Borramos reservaciones asociadas
    $stmt_reservas = $conn->prepare("DELETE FROM reservaciones WHERE id_horario = ?");
    $stmt_reservas->bind_param("i", $id_horario);
    $stmt_reservas->execute();

    // Borramos la clase
    $stmt_clase = $conn->prepare("DELETE FROM horario_clases WHERE id_horario = ?");
    $stmt_clase->bind_param("i", $id_horario);
    $stmt_clase->execute();
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Clase eliminada con éxito.']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error al eliminar la clase.']);
}
$conn->close();
?>