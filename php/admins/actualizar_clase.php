<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}
require_once("../conexion.php");

// Recibir datos del POST
$id_horario = (int)($_POST['id_horario'] ?? 0);
$nombre = $_POST['nombre'] ?? '';
$fecha = $_POST['fecha'] ?? '';
$hora_inicio = $_POST['hora_inicio'] ?? '';
$duracion = (int)($_POST['duracion'] ?? 60);
$id_coach = (int)($_POST['coach'] ?? 0);
$cupo = (int)($_POST['cupo'] ?? 0);

if ($id_horario <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de clase no válido.']);
    exit();
}

$hora_fin = (new DateTime($hora_inicio))->modify("+$duracion minutes")->format('H:i:s');

try {
    // --- NUEVO: VERIFICACIÓN DE CONFLICTOS (IGNORANDO LA CLASE ACTUAL) ---
    $sql_check = "SELECT COUNT(id_horario) as count FROM horario_clases WHERE fecha = ? AND (? < hora_fin AND ? > hora_inicio) AND id_horario != ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("sssi", $fecha, $hora_inicio, $hora_fin, $id_horario);
    $stmt_check->execute();
    $conflictos = $stmt_check->get_result()->fetch_assoc()['count'];
    $stmt_check->close();

    if ($conflictos > 0) {
        echo json_encode(['success' => false, 'message' => 'El nuevo horario entra en conflicto con otra clase existente.']);
        exit();
    }
    // --- FIN DE LA VERIFICACIÓN ---

    $sql = "UPDATE horario_clases SET nombre_clase_especifico=?, fecha=?, hora_inicio=?, hora_fin=?, id_coach=?, cupo_maximo=? WHERE id_horario=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssiii", $nombre, $fecha, $hora_inicio, $hora_fin, $id_coach, $cupo, $id_horario);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Clase actualizada con éxito.']);
    } else {
        throw new Exception("Error al actualizar la clase.");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
?>