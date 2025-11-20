<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

$response = ['success' => false, 'message' => 'Acción no permitida.'];

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo json_encode($response);
    exit();
}

require_once("../conexion.php");

$specific_id = $_POST['specific_id'] ?? 0;
$tipo_usuario = $_POST['tipo'] ?? ''; // 'rider' o 'coach'

if ($specific_id <= 0 || empty($tipo_usuario)) {
    $response['message'] = 'Error: Faltan datos para la desactivación.';
    echo json_encode($response);
    exit();
}

// --- CAMBIO: Iniciar una transacción ---
$conn->begin_transaction();

try {
    if ($tipo_usuario === 'rider') {
        
        // --- ACCIÓN 1: Desactivar al rider ---
        $stmt_rider = $conn->prepare("UPDATE riders SET esta_activo = 0 WHERE id_rider = ?");
        $stmt_rider->bind_param("i", $specific_id);
        $stmt_rider->execute();
        $stmt_rider->close();

        // --- ACCIÓN 2 (NUEVA): Finalizar sus membresías activas ---
        // Se establece la fecha de fin a HOY y el estado a 'Vencida'.
        $stmt_membresia = $conn->prepare(
            "UPDATE membresias 
             SET estado = 'Vencida', fecha_fin = CURDATE() 
             WHERE id_rider = ? AND estado IN ('Activa', 'Por Vencer')"
        );
        $stmt_membresia->bind_param("i", $specific_id);
        $stmt_membresia->execute();
        $stmt_membresia->close();

    } elseif ($tipo_usuario === 'coach') {
        
        // El coach solo se desactiva
        $stmt_coach = $conn->prepare("UPDATE coaches SET esta_activo = 0 WHERE id_coach = ?");
        $stmt_coach->bind_param("i", $specific_id);
        $stmt_coach->execute();
        $stmt_coach->close();

    } elseif ($tipo_usuario === 'admin') {
         $response['message'] = 'No se puede desactivar a un administrador.';
         $conn->rollback(); // Cancelamos la transacción
         echo json_encode($response);
         exit();
    } else {
        throw new Exception("Tipo de usuario no válido.");
    }

    // Si todo salió bien, guardamos los cambios
    $conn->commit();
    $response['success'] = true;
    $response['message'] = 'Usuario desactivado. Sus membresías activas han sido finalizadas.';

} catch (Exception $e) {
    // Si algo falló, deshacemos todo
    $conn->rollback();
    $response['message'] = 'Error en el servidor: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>