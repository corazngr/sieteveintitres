<?php
include '../conexion.php';

$coaches = [];
$sql = "SELECT p.*, c.nombre_coach 
        FROM perfiles_coaches p
        JOIN coaches c ON p.coach_id = c.id_coach
        ORDER BY c.nombre_coach ASC";

if ($result = $conn->query($sql)) {
    $coaches = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
} else {
    error_log("Error al cargar perfiles de coaches: " . $conn->error);
}

$conn->close();

header('Content-Type: application/json');
echo json_encode($coaches);
?>