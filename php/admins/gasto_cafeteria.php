<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Acceso denegado."]);
    exit;
}

require_once("../conexion.php");

// Asumimos que el nombre del admin está en la sesión
// Si usas 'nombre_usuario' o algo diferente, ajústalo aquí.
$responsable = $_SESSION['nombre'] ?? 'Admin'; 
$descripcion = $_POST['descripcion'] ?? null;
$monto = (float)($_POST['monto'] ?? 0);

if (empty($descripcion) || $monto <= 0) {
    echo json_encode(["success" => false, "message" => "Datos incompletos o incorrectos."]);
    exit;
}

try {
    // Insertamos en la tabla de EGRESOS
    // Usamos 'Insumos Cafetería' como categoría fija para estos gastos.
    $sql = "INSERT INTO egresos (concepto, monto, responsable, tipo_egreso, fecha, created_at) 
        VALUES (?, ?, ?, 'Cafetería', NOW(), NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sds", $descripcion, $monto, $responsable);
    
    if ($stmt->execute()) {
        echo json_encode([
            "success" => true, 
            "message" => "Gasto de cafetería registrado correctamente."
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al registrar el gasto."]);
    }
    
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error en el servidor: " . $e->getMessage()]);
}
?>