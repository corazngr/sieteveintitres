<?php
header('Content-Type: application/json; charset=UTF-8');

$response = [
    'status' => 'error',
    'message' => 'Ocurrió un error inesperado.',
    'data' => [],
    'week_info' => null
];
require_once("../conexion.php");

if ($conn->connect_error) {
    http_response_code(500);
    $response['message'] = "Error de Conexión a la Base de Datos: " . $conn->connect_error;
    echo json_encode($response);
    exit();
}

try {
    date_default_timezone_set('America/Mexico_City');
    setlocale(LC_TIME, 'es_ES.UTF-8', 'Spanish_Spain', 'Spanish');

    $hoy = new DateTime();
    $dia_semana_hoy = (int)$hoy->format('N');
    
    $modificador_semana = ($dia_semana_hoy == 7) ? 'next monday' : 'monday this week';
    $lunes = new DateTime($modificador_semana);
    $sabado = (new DateTime($modificador_semana))->modify('+5 days');
    
    $fecha_inicio_semana = $lunes->format('Y-m-d');
    $fecha_fin_semana = $sabado->format('Y-m-d');
    
    $mes_lunes = strftime('%B', $lunes->getTimestamp());
    $mes_sabado = strftime('%B', $sabado->getTimestamp());
    $range_string = 'Semana del ' . $lunes->format('d') . ($mes_lunes !== $mes_sabado ? ' de ' . ucfirst($mes_lunes) : '') . ' al ' . $sabado->format('d') . ' de ' . ucfirst($mes_sabado);
    
    $week_days = [];
    $current_day = clone $lunes;
    $day_names = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    for ($i = 0; $i < 6; $i++) {
        $week_days[] = ['name' => $day_names[$i], 'date' => $current_day->format('d')];
        $current_day->modify('+1 day');
    }
    
    $response['week_info'] = ['range_string' => $range_string, 'days' => $week_days];

    $sql = "SELECT 
                hc.id_horario, hc.nombre_clase_especifico, hc.fecha, hc.hora_inicio, c.nombre_coach
            FROM horario_clases hc
            JOIN coaches c ON hc.id_coach = c.id_coach
            WHERE 
                hc.estatus = 'Programada' 
                AND c.estatus = 'Activo'
                AND hc.fecha BETWEEN '{$fecha_inicio_semana}' AND '{$fecha_fin_semana}' 
            ORDER BY hc.fecha, hc.hora_inicio";

    $resultado = $conn->query($sql);

    if (!$resultado) {
        throw new Exception("Error en la consulta SQL: " . $conn->error);
    }

    $horario_estructurado = [];
    while ($clase = $resultado->fetch_assoc()) {
        $hora = date("H:i", strtotime($clase['hora_inicio']));
        $dia_semana = date("N", strtotime($clase['fecha']));
        if ($dia_semana <= 6) {
            $horario_estructurado[$hora][$dia_semana] = [
                'nombre_clase' => $clase['nombre_clase_especifico'],
                'coach' => $clase['nombre_coach'], 
                'id_horario' => $clase['id_horario'],
                'fecha' => $clase['fecha'], 
                'hora_inicio' => $clase['hora_inicio'] 
            ];
        }
    }
    ksort($horario_estructurado);

    $response['status'] = 'success';
    $response['message'] = 'Horarios obtenidos correctamente.';
    $response['data'] = $horario_estructurado;

} catch (Exception $e) {
    http_response_code(500); 
    $response['message'] = $e->getMessage();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

echo json_encode($response);
?>