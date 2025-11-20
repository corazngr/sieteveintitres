<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') { /* ... Acceso denegado ... */ }
require_once("../conexion.php");

$id_bicicleta = (int)($_GET['id'] ?? 0);

try {
    $sql = "SELECT fecha, descripcion, responsable FROM mantenimientos WHERE id_bicicleta = ? ORDER BY fecha DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_bicicleta);
    $stmt->execute();
    $historial = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'data' => $historial]);
} catch (Exception $e) { /* ... Manejo de error ... */ }
$conn->close();
?>