<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}

require_once("../conexion.php");

$producto = $_POST['producto'] ?? '';
$cantidad = (int)($_POST['cantidad'] ?? 0);
$precio_unitario = (float)($_POST['precio_unitario'] ?? 0.0);
$vendedor = $_SESSION['nombre_usuario'] ?? 'Admin';

if (empty($producto) || $cantidad <= 0 || $precio_unitario < 0) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
    exit();
}

$conn->begin_transaction();
try {
    // --- PASO 1: Insertar en la tabla `cafeteria` ---
    $sql_cafe = "INSERT INTO cafeteria (producto, cantidad, precio_unitario, vendedor) VALUES (?, ?, ?, ?)";
    $stmt_cafe = $conn->prepare($sql_cafe);
    $stmt_cafe->bind_param("sids", $producto, $cantidad, $precio_unitario, $vendedor);
    $stmt_cafe->execute();

    // Obtenemos el ID de la venta que acabamos de crear
    $id_venta_cafeteria = $conn->insert_id;
    $monto_total = $cantidad * $precio_unitario;

    // --- PASO 2: Insertar el registro correspondiente en `ingresos` ---
    $sql_ingreso = "INSERT INTO ingresos (tipo_ingreso, fecha, monto, concepto, responsable, id_venta_cafeteria) VALUES (?, CURDATE(), ?, ?, ?, ?)";
    $stmt_ingreso = $conn->prepare($sql_ingreso);
    
    $tipo_ingreso = 'Venta Cafeteria';
    $concepto = "Venta: {$cantidad}x {$producto}"; // Descripción automática
    
    // 's'tring, 'd'ouble, 's'tring, 's'tring, 'i'nteger
    $stmt_ingreso->bind_param("sdssi", $tipo_ingreso, $monto_total, $concepto, $vendedor, $id_venta_cafeteria);
    $stmt_ingreso->execute();

    // Si todo salió bien, confirmamos los cambios
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Venta registrada con éxito y reflejada en finanzas.']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error al registrar la venta: ' . $e->getMessage()]);
}
$conn->close();
?>