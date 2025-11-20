<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

// 1. Verificar sesión y permisos
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}

require_once("../conexion.php");

// 2. Obtener el ID de la clase a editar
$id_horario = $_GET['id_horario'] ?? null;

if (!$id_horario || !is_numeric($id_horario)) {
    echo json_encode(['success' => false, 'message' => 'ID de clase no válido.']);
    exit();
}

try {
    // 3. Consulta corregida: Seleccionamos hora_inicio y hora_fin
    $sql = "
        SELECT 
            id_horario, 
            id_coach, 
            fecha, 
            hora_inicio, 
            hora_fin,         -- <-- OBTENEMOS ESTO
            cupo_maximo, 
            nombre_clase_especifico 
        FROM horario_clases
        WHERE id_horario = ?
        LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_horario);
    $stmt->execute();
    $result = $stmt->get_result();
    $clase = $result->fetch_assoc();
    $stmt->close();

    if ($clase) {
        
        // 4. CALCULAR LA DURACIÓN EN MINUTOS
        $inicio = new DateTime($clase['hora_inicio']);
        $fin = new DateTime($clase['hora_fin']);
        $intervalo = $inicio->diff($fin);
        // Convertimos el intervalo (horas y minutos) a solo minutos
        $duracion_en_minutos = ($intervalo->h * 60) + $intervalo->i;

        // 5. Añadimos la duración calculada a los datos
        $clase['duracion_calculada'] = $duracion_en_minutos;
        
        // 6. Devolver los datos (incluyendo la duración)
        echo json_encode(['success' => true, 'data' => $clase]);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Clase no encontrada.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}

$conn->close();
?>