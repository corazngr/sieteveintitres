<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'coach') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}

require_once("../conexion.php");
date_default_timezone_set('America/Mexico_City'); // Asegura la zona horaria

try {
    $id_coach = $_SESSION['id_usuario']; // Asumimos que el id_coach se guarda en id_usuario

    // Calcular inicio y fin de la semana actual
    $lunes = date('Y-m-d', strtotime('monday this week'));
    $domingo = date('Y-m-d', strtotime('sunday this week'));

    // 💡 CAMBIO CLAVE: Usamos LEFT JOIN y COUNT para contar los inscritos
    $sql = "SELECT 
                hc.id_horario, 
                hc.nombre_clase_especifico, 
                hc.fecha, 
                hc.hora_inicio, 
                hc.cupo_maximo,
                COUNT(res.id_reservacion) AS inscritos
            FROM horario_clases hc
            LEFT JOIN reservaciones res ON hc.id_horario = res.id_horario AND res.estatus = 'Activa'
            WHERE hc.id_coach = ? 
              AND hc.fecha BETWEEN ? AND ?
            GROUP BY hc.id_horario
            ORDER BY hc.fecha, hc.hora_inicio";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $id_coach, $lunes, $domingo);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['success' => true, 'data' => $data]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al cargar el horario: ' . $e->getMessage()]);
}

$conn->close();
?>