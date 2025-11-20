<?php
session_start();
require_once("../conexion.php");

header("Content-Type: application/json; charset=UTF-8");

// 1. VERIFICAR SI EL USUARIO HA INICIADO SESIÓN Y ES RIDER
if (!isset($_SESSION['id_rider']) || $_SESSION['tipo_usuario'] !== 'rider') {
    echo json_encode([
        'success' => false,
        'message' => 'Acceso denegado. Debes iniciar sesión como rider.',
        'redirectUrl' => '/sieteveintitres/html/publico/iniciosesion.html'
    ]);
    exit();
}

$id_rider = $_SESSION['id_rider'];
$response = ['success' => false, 'message' => 'No se pudo obtener la información.'];

try {
    // 2. CONSULTA DE DATOS DEL RIDER (Esta parte estaba correcta)
    $stmtRider = $conn->prepare("SELECT nombre_rider, email_rider, telefono_rider, foto_rider, codigo_acceso FROM riders WHERE id_rider = ?");
    $stmtRider->bind_param("i", $id_rider);
    $stmtRider->execute();
    $resultRider = $stmtRider->get_result();
    
    if ($resultRider->num_rows === 0) {
        throw new Exception('Usuario no encontrado.');
    }
    $rider_data = $resultRider->fetch_assoc();
    $stmtRider->close();

    // 3. CONSULTA DE MEMBRESÍA ACTIVA (AQUÍ ESTÁ LA CORRECCIÓN)
    $stmtMembresia = $conn->prepare(
        "SELECT 
            tm.nombre as tipo_membresia,
            m.fecha_inicio, 
            m.fecha_fin
        FROM membresias m 
        JOIN tipos_membresia tm ON m.id_tipo_membresia = tm.id_tipo_membresia 
        WHERE m.id_rider = ? AND m.estado IN ('Activa', 'Por Vencer') 
        ORDER BY m.fecha_inicio DESC 
        LIMIT 1"
    );
    $stmtMembresia->bind_param("i", $id_rider);
    $stmtMembresia->execute();
    $resultMembresia = $stmtMembresia->get_result();
    $rider_data['membresia'] = $resultMembresia->fetch_assoc(); // Será null si no hay membresía activa
    $stmtMembresia->close();
    
    // 4. CONSULTA DEL HISTORIAL DE RESERVACIONES (Esta parte estaba correcta)
    $stmtHistorial = $conn->prepare(
        "SELECT 
            hc.fecha, 
            hc.hora_inicio, 
            c.nombre_coach, 
            res.estatus,
            res.id_bicicleta,
            res.id_reservacion
         FROM reservaciones res
         JOIN horario_clases hc ON res.id_horario = hc.id_horario
         JOIN coaches c ON hc.id_coach = c.id_coach
         WHERE res.id_rider = ?
         ORDER BY hc.fecha DESC, hc.hora_inicio DESC"
    );
    $stmtHistorial->bind_param("i", $id_rider);
    $stmtHistorial->execute();
    $rider_data['historial'] = $stmtHistorial->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtHistorial->close();

    // 5. DEVOLVER TODO JUNTO
    $response = [
        'success' => true,
        'data' => $rider_data
    ];

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Error en el servidor: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>