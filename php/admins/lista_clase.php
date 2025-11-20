<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

// Verificación de sesión de admin (¡importante!)
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}

require_once("../conexion.php");

$id_horario = filter_input(INPUT_GET, 'id_horario', FILTER_VALIDATE_INT);

if (!$id_horario) {
    echo json_encode(['success' => false, 'message' => 'ID de horario no válido.']);
    exit();
}

try {
    // 💡 CAMBIO CLAVE: Añadimos 'res.id_bicicleta' a la consulta SQL
    $sql = "SELECT 
                r.nombre_rider, 
                CONCAT('/sieteveintitres/uploads/riders/', r.foto_rider) AS foto_rider, 
                res.id_bicicleta 
            FROM reservaciones res
            JOIN riders r ON res.id_rider = r.id_rider
            WHERE res.id_horario = ? AND res.estatus = 'Activa'
            ORDER BY r.nombre_rider ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_horario);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['success' => true, 'data' => $data]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al obtener la lista de riders: ' . $e->getMessage()]);
}

$conn->close();
?>