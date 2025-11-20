<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') { /* ... Acceso denegado ... */ }
require_once("../conexion.php");

try {
    // Consulta para obtener todas las bicicletas y la fecha de su último mantenimiento
    $sql = "
        SELECT 
            b.*, 
            MAX(m.fecha) as ultimo_servicio
        FROM bicicletas b
        LEFT JOIN mantenimientos m ON b.id_bicicleta = m.id_bicicleta
        GROUP BY b.id_bicicleta
        ORDER BY b.id_bicicleta ASC
    ";
    $bicicletas = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

    // Consulta para contar los estados
    $sql_counts = "SELECT estado, COUNT(*) as count FROM bicicletas GROUP BY estado";
    $counts_result = $conn->query($sql_counts)->fetch_all(MYSQLI_ASSOC);
    $counts = [
        'all' => 0,
        'Disponible' => 0,
        'Mantenimiento' => 0,
        'Fuera de Servicio' => 0
    ];
    foreach($counts_result as $row) {
        $counts[$row['estado']] = (int)$row['count'];
        $counts['all'] += (int)$row['count'];
    }

    echo json_encode(['success' => true, 'bicicletas' => $bicicletas, 'counts' => $counts]);

} catch (Exception $e) { /* ... Manejo de error ... */ }
$conn->close();
?>