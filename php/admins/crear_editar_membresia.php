<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

// Protección: Solo un admin puede ejecutar este script
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}

require_once("../conexion.php");

// Recibir datos del formulario enviados por JavaScript
$id = (int)($_POST['id_tipo_membresia'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$precio = (float)($_POST['precio'] ?? 0);
$periodo = trim($_POST['periodo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$caracteristicas = trim($_POST['caracteristicas'] ?? '');
// El valor de un checkbox 'true'/'false' se convierte a 1/0 para la base de datos
$es_popular = isset($_POST['es_popular']) && $_POST['es_popular'] === 'true' ? 1 : 0;

// Validación simple
if (empty($nombre) || $precio <= 0) {
    echo json_encode(['success' => false, 'message' => 'El nombre y el precio son obligatorios.']);
    exit();
}

try {
    if ($id > 0) {
        // --- LÓGICA PARA ACTUALIZAR (EDITAR) ---
        $sql = "UPDATE tipos_membresia SET nombre=?, descripcion=?, precio=?, periodo=?, caracteristicas=?, es_popular=? WHERE id_tipo_membresia=?";
        $stmt = $conn->prepare($sql);
        // Los tipos de datos para bind_param: s=string, d=double(decimal), i=integer
        $stmt->bind_param("ssdssii", $nombre, $descripcion, $precio, $periodo, $caracteristicas, $es_popular, $id);
        $message = "Tipo de membresía actualizado con éxito.";

    } else {
        // --- LÓGICA PARA INSERTAR (CREAR) ---
        $sql = "INSERT INTO tipos_membresia (nombre, descripcion, precio, periodo, caracteristicas, es_popular, estatus) VALUES (?, ?, ?, ?, ?, ?, 'Activo')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdssi", $nombre, $descripcion, $precio, $periodo, $caracteristicas, $es_popular);
        $message = "Tipo de membresía creado con éxito.";
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        throw new Exception("Error al ejecutar la consulta en la base de datos.");
    }

    $stmt->close();
    
} catch (Exception $e) {
    // Manejo de errores (ej. nombre de membresía duplicado)
    if ($conn->errno == 1062) {
        echo json_encode(['success' => false, 'message' => 'Error: Ya existe un tipo de membresía con ese nombre.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
    }
}

$conn->close();
?>