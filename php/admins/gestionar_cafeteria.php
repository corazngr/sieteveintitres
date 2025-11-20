<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}
require_once("../conexion.php");

$filtro = $_GET['filtro'] ?? 'day'; // Por defecto, 'hoy'

try {
    $sql = "SELECT producto, cantidad, total, vendedor, created_at FROM cafeteria WHERE";
    $hoy = date('Y-m-d');

    switch ($filtro) {
        case 'week':
            $sql .= " DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL (DAYOFWEEK(CURDATE()) - 2) DAY)";
            break;
        case 'month':
            $sql .= " YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())";
            break;
        case 'day':
        default:
            $sql .= " DATE(created_at) = CURDATE()"; // <-- Usa la fecha de la BD
            break;
    }
    $sql .= " ORDER BY created_at DESC";

    $result = $conn->query($sql);
    $ventas = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['success' => true, 'data' => $ventas]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al obtener las ventas.']);
}

$conn->close();
?>