<?php
// 1. CONFIGURACIÓN INICIAL
header('Content-Type: application/json; charset=utf-8');
session_start();
require '../conexion.php';

// Respuesta por defecto
$response = ['success' => false, 'message' => 'No se pudo cargar la información.'];

// 2. VERIFICACIÓN DE SESIÓN
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'coach') {
    $response['message'] = 'Acceso no autorizado.';
    echo json_encode($response);
    exit();
}

$id_coach = $_SESSION['id_usuario'];

try {
    // 3. OBTENER DATOS DEL COACH
    $stmt_coach = $conn->prepare("SELECT nombre_coach FROM coaches WHERE id_coach = ?");
    $stmt_coach->bind_param("i", $id_coach);
    $stmt_coach->execute();
    $coach = $stmt_coach->get_result()->fetch_assoc();

    if (!$coach) {
        throw new Exception("Coach no encontrado.");
    }
    
    $datos_completos = ['nombre_coach' => $coach['nombre_coach'], 'clases' => []];

    // =========================================================================
    // 💡 CAMBIO CLAVE: Consulta única y eficiente para obtener clases y el conteo de inscritos
    // =========================================================================
    $sql_clases = "SELECT 
                        hc.id_horario, 
                        hc.nombre_clase_especifico, 
                        hc.fecha, 
                        hc.hora_inicio, 
                        hc.cupo_maximo,
                        COUNT(res.id_reservacion) AS inscritos
                   FROM horario_clases hc
                   LEFT JOIN reservaciones res ON hc.id_horario = res.id_horario AND res.estatus = 'Activa'
                   WHERE hc.id_coach = ? AND hc.fecha >= CURDATE() AND hc.estatus = 'Programada'
                   GROUP BY hc.id_horario
                   ORDER BY hc.fecha, hc.hora_inicio";

    $stmt_clases = $conn->prepare($sql_clases);
    $stmt_clases->bind_param("i", $id_coach);
    $stmt_clases->execute();
    $clases = $stmt_clases->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_clases->close();

    // 5. PARA CADA CLASE, OBTENER LA LISTA DE NOMBRES DE RIDERS
    foreach ($clases as &$clase) { // Usamos '&' para modificar el array directamente
        $id_horario = $clase['id_horario'];
        
        $stmt_riders = $conn->prepare(
            "SELECT r.nombre_rider, a.id_asistencia FROM asistencias a
             JOIN riders r ON a.id_rider = r.id_rider
             WHERE a.id_horario = ?
             ORDER BY r.nombre_rider ASC"
        );
        $stmt_riders->bind_param("i", $id_horario);
        $stmt_riders->execute();
        $riders = $stmt_riders->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_riders->close();

        // Añadimos la lista de riders a la clase
        $clase['riders'] = $riders;
    }
    
    $datos_completos['clases'] = $clases;
    
    $response = ['success' => true, 'data' => $datos_completos];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>  