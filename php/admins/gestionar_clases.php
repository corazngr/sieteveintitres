<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}

require_once("../conexion.php");

$date_param = $_GET['date'] ?? 'now';   
$current_date = new DateTime($date_param);
$current_date->setISODate((int)$current_date->format('o'), (int)$current_date->format('W'), 1); // Lunes de la semana

$start_of_week = $current_date->format('Y-m-d');
$current_date->modify('+6 days'); // Domingo de la semana
$end_of_week = $current_date->format('Y-m-d');

$prev_week = (new DateTime($start_of_week))->modify('-7 days')->format('Y-m-d');
$next_week = (new DateTime($start_of_week))->modify('+7 days')->format('Y-m-d');

// Título de la semana
$week_display = date('d M', strtotime($start_of_week)) . ' - ' . date('d M Y', strtotime($end_of_week));

try {
    // Obtener clases de la semana
    $sql_clases = "
        SELECT 
            h.id_horario, h.nombre_clase_especifico, h.fecha, h.hora_inicio, h.hora_fin, h.id_coach, h.cupo_maximo,
            c.nombre_coach,
            (SELECT COUNT(*) FROM reservaciones r WHERE r.id_horario = h.id_horario AND r.estatus = 'Activa') as reservados
        FROM horario_clases h
        JOIN coaches c ON h.id_coach = c.id_coach
        WHERE h.fecha BETWEEN ? AND ?
        ORDER BY h.fecha, h.hora_inicio
    ";
    $stmt = $conn->prepare($sql_clases);
    $stmt->bind_param("ss", $start_of_week, $end_of_week);
    $stmt->execute();
    $clases_result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Obtener lista de coaches activos
    $sql_coaches = "SELECT id_coach, nombre_coach FROM coaches WHERE esta_activo = 1 ORDER BY nombre_coach";
    $coaches_result = $conn->query($sql_coaches)->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true,
        'week_display' => $week_display,
        'prev_week_date' => $prev_week,
        'next_week_date' => $next_week,
        'current_week_date' => $start_of_week,
        'clases' => $clases_result,
        'coaches' => $coaches_result
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al obtener datos: ' . $e->getMessage()]);
}
$conn->close();
?>