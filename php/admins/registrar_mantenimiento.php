<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}
require_once("../conexion.php");

$id_bicicleta = (int)($_POST['id_bicicleta'] ?? 0);
$fecha = $_POST['fecha'] ?? '';
// Esta es la descripción que usaremos para AMBAS tablas
$descripcion = $_POST['descripcion'] ?? '';
$responsable = $_POST['responsable'] ?? '';

if ($id_bicicleta <= 0 || empty($fecha) || empty($descripcion) || empty($responsable)) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos para el registro.']);
    exit();
}

$conn->begin_transaction();
try {
    $sql_insert = "INSERT INTO mantenimientos (id_bicicleta, descripcion, responsable, fecha) VALUES (?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("isss", $id_bicicleta, $descripcion, $responsable, $fecha);
    $stmt_insert->execute();

    $sql_update = "UPDATE bicicletas SET estado = 'Mantenimiento', descripcion = ? WHERE id_bicicleta = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("si", $descripcion, $id_bicicleta);
    $stmt_update->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Mantenimiento registrado. El estado y la descripción de la bici han sido actualizados.']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
}
$conn->close();
?>