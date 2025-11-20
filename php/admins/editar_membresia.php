<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}

require_once("../conexion.php");

// Recibir datos
$id = (int)($_POST['id_tipo_membresia'] ?? 0);
$nombre = $_POST['nombre'] ?? '';
$precio = (float)($_POST['precio'] ?? 0);
$periodo = $_POST['periodo'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$caracteristicas = $_POST['caracteristicas'] ?? '';
$es_popular = isset($_POST['es_popular']) && $_POST['es_popular'] === 'true' ? 1 : 0;

try {
    if ($id > 0) { // Actualizar
        $sql = "UPDATE tipos_membresia SET nombre=?, descripcion=?, precio=?, periodo=?, caracteristicas=?, es_popular=? WHERE id_tipo_membresia=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdssii", $nombre, $descripcion, $precio, $periodo, $caracteristicas, $es_popular, $id);
        $message = "Tipo de membresía actualizado con éxito.";
    } else { // Crear
        $sql = "INSERT INTO tipos_membresia (nombre, descripcion, precio, periodo, caracteristicas, es_popular) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdssi", $nombre, $descripcion, $precio, $periodo, $caracteristicas, $es_popular);
        $message = "Tipo de membresía creado con éxito.";
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        throw new Exception("Error en la operación de base de datos.");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>