<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}

require_once("../conexion.php");

// Recibir datos del POST
$tipo = $_POST['tipo'] ?? '';
$concepto = $_POST['descripcion'] ?? ''; // El campo del form se llama 'descripcion'
$monto = (float)($_POST['monto'] ?? 0);
$responsable = $_POST['responsable'] ?? $_SESSION['nombre_usuario'];
$fecha = date('Y-m-d'); // Usamos la fecha actual del servidor

// La categoría depende del tipo de transacción
$categoria = ($tipo === 'Ingreso') ? ($_POST['categoria_ingreso'] ?? '') : ($_POST['categoria_gasto'] ?? '');

// Validación
if (empty($tipo) || empty($concepto) || $monto <= 0 || empty($categoria)) {
    echo json_encode(['success' => false, 'message' => 'Por favor, completa todos los campos requeridos.']);
    exit();
}

try {
    // Si es un Ingreso, insertamos en la tabla 'ingresos'
    if ($tipo === 'Ingreso') {
        $sql = "INSERT INTO ingresos (tipo_ingreso, fecha, monto, concepto, responsable) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        // Ojo a los tipos: 's'tring, 's'tring(date), 'd'ouble, 's'tring, 's'tring
        $stmt->bind_param("ssdss", $categoria, $fecha, $monto, $concepto, $responsable);
    
    // Si es un Gasto, insertamos en la tabla 'egresos'
    } elseif ($tipo === 'Gasto') {
        $sql = "INSERT INTO egresos (tipo_egreso, fecha, monto, concepto, responsable) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdss", $categoria, $fecha, $monto, $concepto, $responsable);
    
    } else {
        throw new Exception("Tipo de transacción no válido.");
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Transacción registrada con éxito.']);
    } else {
        throw new Exception("Error al registrar la transacción.");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}

$conn->close();
?>