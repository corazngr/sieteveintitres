<?php
// 1. Incluye TU conexión
include '../conexion.php';

$eventos = []; // Inicia vacío por defecto
$sql = "SELECT * FROM eventos ORDER BY id DESC";

// Ejecuta la consulta
if ($result = $conn->query($sql)) {
    // Obtiene todos los resultados como un array asociativo
    $eventos = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
} else {
    // Si hay un error, lo registra
    error_log("Error al cargar eventos: " . $conn->error);
}

// 4. Cierra la conexión
$conn->close();

// 5. Devuelve los eventos como JSON para que JavaScript los lea
header('Content-Type: application/json');
echo json_encode($eventos);
?>