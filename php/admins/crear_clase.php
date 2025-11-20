<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}
require_once("../conexion.php");

// Recibir datos del POST
$nombre = $_POST['nombre'] ?? '';
$fecha = $_POST['fecha'] ?? '';
$hora_inicio = $_POST['hora_inicio'] ?? '';
$duracion = (int)($_POST['duracion'] ?? 60);
$id_coach = (int)($_POST['coach'] ?? 0);
$cupo = (int)($_POST['cupo'] ?? 0);

// Calcular hora de fin
$hora_fin = (new DateTime($hora_inicio))->modify("+$duracion minutes")->format('H:i:s');

try {
    $sql_check = "SELECT COUNT(id_horario) as count FROM horario_clases WHERE fecha = ? AND (? < hora_fin AND ? > hora_inicio)";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("sss", $fecha, $hora_inicio, $hora_fin);
    $stmt_check->execute();
    $conflictos = $stmt_check->get_result()->fetch_assoc()['count'];
    $stmt_check->close();

    if ($conflictos > 0) {
        echo json_encode(['success' => false, 'message' => 'Ya existe una clase programada que se cruza con este horario. Por favor, elige otra hora.']);
        exit();
    }

    $sql = "INSERT INTO horario_clases (nombre_clase_especifico, fecha, hora_inicio, hora_fin, id_coach, cupo_maximo) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssii", $nombre, $fecha, $hora_inicio, $hora_fin, $id_coach, $cupo);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Clase programada con éxito.']);
    } else {
        throw new Exception("Error al guardar la clase.");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
?>