<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}

require_once("../conexion.php");

try {
    $sql_tipos = "SELECT * FROM tipos_membresia ORDER BY estatus, precio DESC";
    $tipos_membresia = $conn->query($sql_tipos)->fetch_all(MYSQLI_ASSOC);

    // Obtener membresías de riders
    $sql_activas = "
        SELECT
            m.id_membresia,
            r.nombre_rider,
            tm.nombre AS nombre_tipo_membresia,
            m.fecha_inicio,
            m.fecha_fin,
            
            DATEDIFF(m.fecha_fin, CURDATE()) AS dias_restantes,
            
            CASE
                WHEN tm.limite_clases IS NULL THEN 'Ilimitadas'
                ELSE (tm.limite_clases - COUNT(DISTINCT res.id_reservacion))
            END AS clases_restantes_calculadas,
            
            CASE
                WHEN m.fecha_fin < CURDATE() THEN 'Vencida'
                WHEN DATEDIFF(m.fecha_fin, CURDATE()) <= 3 THEN 'Por Vencer'
                ELSE 'Activa'
            END AS estado

        FROM
            membresias AS m
        JOIN
            riders AS r ON m.id_rider = r.id_rider
        JOIN
            tipos_membresia AS tm ON m.id_tipo_membresia = tm.id_tipo_membresia
        LEFT JOIN
            reservaciones AS res ON m.id_rider = res.id_rider
        LEFT JOIN
            horario_clases AS h ON res.id_horario = h.id_horario
            AND h.fecha BETWEEN m.fecha_inicio AND m.fecha_fin
            AND h.fecha < CURDATE() 
        WHERE
            m.fecha_fin >= CURDATE()
        GROUP BY
            m.id_membresia, r.nombre_rider, tm.nombre, tm.limite_clases, m.fecha_inicio, m.fecha_fin
        ORDER BY 
            m.fecha_fin ASC
    ";
    
    $result_activas = $conn->query($sql_activas);
    
    if ($result_activas === false) {
        throw new Exception("Error en la consulta SQL de membresías activas: " . $conn->error);
    }
    
    $membresias_activas = $result_activas->fetch_all(MYSQLI_ASSOC);

    $membresias_listas = [];
    foreach ($membresias_activas as $mem) {
        $mem['clases_restantes'] = $mem['clases_restantes_calculadas']; // Renombramos
        unset($mem['clases_restantes_calculadas']); // Limpiamos
        $membresias_listas[] = $mem;
    }

    echo json_encode([
        'success' => true,
        'tipos' => $tipos_membresia,
        'activas' => $membresias_listas // Enviamos la lista con el nombre correcto
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>