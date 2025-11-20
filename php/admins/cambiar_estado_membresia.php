<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}
require_once("../conexion.php");

$id = (int)($_POST['id'] ?? 0);
$estatus = $_POST['estatus'] ?? '';

if ($id <= 0 || !in_array($estatus, ['Activo', 'Inactivo'])) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit();
}

try {
    $sql = "UPDATE tipos_membresia SET estatus = ? WHERE id_tipo_membresia = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $estatus, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Estado de la membresía actualizado.']);
    } else {
        throw new Exception("No se pudo actualizar el estado.");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
?>