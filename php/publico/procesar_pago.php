<?php
header('Content-Type: application/json; charset=utf-8');
require_once("../conexion.php");

$membresia_id = $_GET['id'] ?? 0;

if ($membresia_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID no válido.']);
    exit();
}

try {
    $sql = "SELECT nombre, precio FROM tipos_membresia WHERE id_tipo_membresia = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $membresia_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        echo json_encode(['success' => true, 'data' => $result->fetch_assoc()]);
    } else {
        throw new Exception("Membresía no encontrada.");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>