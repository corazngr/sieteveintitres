<?php
include '../conexion.php';

$coaches_disponibles = [];

$sql = "SELECT id_coach, nombre_coach 
        FROM coaches 
        WHERE esta_activo = 1 
        AND id_coach NOT IN (SELECT coach_id FROM perfiles_coaches)";

if ($result = $conn->query($sql)) {
    $coaches_disponibles = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
} else {
    error_log("Error al cargar lista de coaches: " . $conn->error);
}

$conn->close();

header('Content-Type: application/json');
echo json_encode($coaches_disponibles);
?>