<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}
require_once("../conexion.php");

// Recibimos los datos del formulario (vengan o no)
$id = (int)($_POST['id_bicicleta'] ?? 0);
$estado = $_POST['estado'] ?? 'Disponible'; // Por defecto es 'Disponible' al crear
$descripcion = $_POST['descripcion'] ?? '';

try {
    // Si el ID es mayor que 0, significa que estamos EDITANDO
    if ($id > 0) {
        $sql = "UPDATE bicicletas SET estado = ?, descripcion = ? WHERE id_bicicleta = ?";
        $stmt = $conn->prepare($sql);
        // Ojo al orden de los parámetros: 's'tring, 's'tring, 'i'nteger
        $stmt->bind_param("ssi", $estado, $descripcion, $id);
        $message = "Bicicleta actualizada con éxito.";

    } else { // Si el ID es 0, estamos CREANDO una nueva
        $sql = "INSERT INTO bicicletas (estado, descripcion, fecha_registro) VALUES (?, ?, CURDATE())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $estado, $descripcion);
        $message = "Bicicleta nueva añadida con éxito.";
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
$conn->close();
?>