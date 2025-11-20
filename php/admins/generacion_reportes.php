<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}
require_once("../conexion.php");

$reporte_type = $_GET['reporte_type'] ?? '';
$periodo = $_GET['periodo'] ?? 'dia';

// Determinar rango de fechas
$fecha_fin = date('Y-m-d');
switch ($periodo) {
    case 'semana': $fecha_inicio = date('Y-m-d', strtotime('-6 days')); break;
    case 'mes': $fecha_inicio = date('Y-m-d', strtotime('-29 days')); break; // Cambiado a últimos 30 días para coincidir con el JS
    case 'dia': default: $fecha_inicio = date('Y-m-d'); break;
}

$response = [];
try {
    switch ($reporte_type) {
        case 'membresias':
            $estado = $_GET['estado'] ?? 'Activa';
            $sql = "SELECT r.nombre_rider, tm.nombre as nombre_membresia, m.fecha_inicio, m.fecha_fin, m.estado 
                    FROM membresias m
                    JOIN riders r ON m.id_rider = r.id_rider
                    JOIN tipos_membresia tm ON m.id_tipo_membresia = tm.id_tipo_membresia
                    WHERE m.estado = ? AND m.fecha_inicio BETWEEN ? AND ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) throw new Exception("Error en la consulta de membresías: " . $conn->error);
            $stmt->bind_param("sss", $estado, $fecha_inicio, $fecha_fin);
            break;
            
        case 'financiero':
            $sql = "(SELECT created_at as fecha, concepto, 'Ingreso' as tipo, monto FROM ingresos WHERE fecha BETWEEN ? AND ?)
                    UNION ALL
                    (SELECT created_at as fecha, concepto, 'Gasto' as tipo, monto FROM egresos WHERE fecha BETWEEN ? AND ?)
                    ORDER BY fecha DESC";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) throw new Exception("Error en la consulta financiera: " . $conn->error);
            $stmt->bind_param("ssss", $fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin);
            break;

        case 'asistencia':
            // Gráfico 1: Asistencia por Clase
            $sql1 = "SELECT hc.nombre_clase_especifico as label, COUNT(a.id_asistencia) as value 
                     FROM asistencias a JOIN horario_clases hc ON a.id_horario = hc.id_horario
                     WHERE a.fecha BETWEEN ? AND ? GROUP BY label ORDER BY value DESC";
            $stmt1 = $conn->prepare($sql1);
            if ($stmt1 === false) throw new Exception("Error en SQL Gráfico 1: " . $conn->error);
            $stmt1->bind_param("ss", $fecha_inicio, $fecha_fin);
            $stmt1->execute();
            $response['clase'] = $stmt1->get_result()->fetch_all(MYSQLI_ASSOC);

            // Gráfico 2: Asistencia por Coach
            $sql2 = "SELECT c.nombre_coach as label, COUNT(a.id_asistencia) as value 
                     FROM asistencias a 
                     JOIN horario_clases hc ON a.id_horario = hc.id_horario
                     JOIN coaches c ON hc.id_coach = c.id_coach
                     WHERE a.fecha BETWEEN ? AND ? GROUP BY label ORDER BY value DESC";
            $stmt2 = $conn->prepare($sql2);
            if ($stmt2 === false) throw new Exception("Error en SQL Gráfico 2: " . $conn->error);
            $stmt2->bind_param("ss", $fecha_inicio, $fecha_fin);
            $stmt2->execute();
            $response['coach'] = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Gráfico 3: Asistencia por Horario
            $sql3 = "SELECT DATE_FORMAT(hc.hora_inicio, '%h:%i %p') as label, COUNT(a.id_asistencia) as value 
                     FROM asistencias a JOIN horario_clases hc ON a.id_horario = hc.id_horario
                     WHERE a.fecha BETWEEN ? AND ? GROUP BY label ORDER BY hc.hora_inicio ASC";
            $stmt3 = $conn->prepare($sql3);
            if ($stmt3 === false) throw new Exception("Error en SQL Gráfico 3: " . $conn->error);
            $stmt3->bind_param("ss", $fecha_inicio, $fecha_fin);
            $stmt3->execute();
            $response['horario'] = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $response]);
            exit();

        default:
            throw new Exception("Tipo de reporte no válido.");
    }

    $stmt->execute();
    $response['data'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $response['success'] = true;
    echo json_encode($response);

} catch (Exception $e) {
    // Ahora, si algo falla, nos enviará un mensaje de error claro.
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>