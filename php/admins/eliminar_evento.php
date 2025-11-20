<?php
// 1. Incluye TU conexión
include '../conexion.php';

header('Content-Type: application/json');

// Lee el JSON enviado desde el JavaScript
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->id) || empty($data->id)) {
    echo json_encode(['success' => false, 'message' => 'ID de evento no proporcionado.']);
    exit;
}

$id_evento = $data->id;

// --- Lógica de Borrado con MYSQLI ---

// 1. Obtener la ruta de la imagen ANTES de borrar el registro
$sql_select = "SELECT ruta_imagen FROM eventos WHERE id = ?";
$stmt_select = $conn->prepare($sql_select);
$stmt_select->bind_param("i", $id_evento); // "i" = integer

$ruta_imagen = null;

if ($stmt_select->execute()) {
    $result = $stmt_select->get_result();
    if ($result->num_rows > 0) {
        $evento = $result->fetch_assoc();
        $ruta_imagen = $evento['ruta_imagen'];
    }
    $stmt_select->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Error al buscar evento: ' . $stmt_select->error]);
    $conn->close();
    exit;
}

// Si encontramos la imagen y la ruta
if ($ruta_imagen) {
    // 2. Borrar el registro de la base de datos
    $sql_delete = "DELETE FROM eventos WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id_evento);

    if ($stmt_delete->execute()) {
        // 3. Borrar el archivo de imagen del servidor
        $ruta_fisica = $_SERVER['DOCUMENT_ROOT'] . $ruta_imagen;

        if (file_exists($ruta_fisica)) {
            unlink($ruta_fisica); // Borra el archivo
        }

        echo json_encode(['success' => true, 'message' => 'Evento eliminado correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al borrar de BD: ' . $stmt_delete->error]);
    }
    $stmt_delete->close();

} else {
    echo json_encode(['success' => false, 'message' => 'Evento no encontrado (ID: ' . $id_evento . ').']);
}

$conn->close();
?>