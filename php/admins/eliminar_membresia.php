<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}
require_once("../conexion.php");

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID no válido.']);
    exit();
}

try {
    // 1. Verificar si la membresía está en uso en la tabla 'membresias'
    $sql_check = "SELECT COUNT(*) as count FROM membresias WHERE id_tipo_membresia = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $count = $stmt_check->get_result()->fetch_assoc()['count'];
    $stmt_check->close();

    if ($count > 0) {
        // Si está en uso, no se puede eliminar.
        throw new Exception("No se puede eliminar este tipo de membresía porque está o ha estado en uso por riders. Por favor, desactívala en su lugar.");
    }

    // 2. Si no está en uso, proceder con la eliminación
    $sql_delete = "DELETE FROM tipos_membresia WHERE id_tipo_membresia = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id);
    
    if ($stmt_delete->execute()) {
        echo json_encode(['success' => true, 'message' => 'Tipo de membresía eliminado permanentemente.']);
    } else {
        throw new Exception("Error al eliminar la membresía de la base de datos.");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
?>