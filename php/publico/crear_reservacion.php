    <?php
    session_start();
    header('Content-Type: application/json; charset=utf-8');
    require_once("../conexion.php");

    if (!isset($_SESSION['id_rider']) || $_SESSION['tipo_usuario'] !== 'rider') {
        echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión como rider para reservar.']);
        exit();
    }

    $id_horario = filter_input(INPUT_POST, 'id_horario', FILTER_VALIDATE_INT);
    $id_bici = filter_input(INPUT_POST, 'id_bici', FILTER_VALIDATE_INT);
    $id_rider = $_SESSION['id_rider'];

    if (!$id_horario || !$id_bici || $id_bici <= 0) {
        echo json_encode(['success' => false, 'message' => 'Datos de reservación incompletos o inválidos.']);
        exit();
    }

    $conn->begin_transaction();
try {
    date_default_timezone_set('America/Mexico_City');
    $hoy = date('Y-m-d'); 

    // ✅ VALIDACIÓN 1: ¿La clase ya pasó?
    $stmt_check_time = $conn->prepare("SELECT CONCAT(fecha, ' ', hora_inicio) as class_datetime FROM horario_clases WHERE id_horario = ?");
    $stmt_check_time->bind_param("i", $id_horario);
    $stmt_check_time->execute();
    $result_time = $stmt_check_time->get_result()->fetch_assoc();

    if (!$result_time) {
        throw new Exception("La clase seleccionada ya no existe.");
    }

    if (new DateTime($result_time['class_datetime']) < new DateTime()) {
        throw new Exception('Esta clase ya ha finalizado y no puede ser reservada.');
    }
    $stmt_check_time->close();

    // ✅ VALIDACIÓN 2: ¿La bici sigue disponible?
    $sql_check_bike = "SELECT id_reservacion FROM reservaciones WHERE id_horario = ? AND id_bicicleta = ?";
    $stmt_check_bike = $conn->prepare($sql_check_bike);
    $stmt_check_bike->bind_param("ii", $id_horario, $id_bici);
    $stmt_check_bike->execute();
    if ($stmt_check_bike->get_result()->num_rows > 0) {
        throw new Exception("¡Alguien fue más rápido! Esa bici ya no está disponible. Por favor, elige otra.");
    }
    $stmt_check_bike->close();

    // ✅ VALIDACIÓN 3: ¿El rider ya tiene una reserva para esta clase?
    $sql_check_rider = "SELECT id_reservacion FROM reservaciones WHERE id_horario = ? AND id_rider = ?";
    $stmt_check_rider = $conn->prepare($sql_check_rider);
    $stmt_check_rider->bind_param("ii", $id_horario, $id_rider);
    $stmt_check_rider->execute();
    if ($stmt_check_rider->get_result()->num_rows > 0) {
        throw new Exception("Ya tienes una reservación para esta clase.");
    }
    $stmt_check_rider->close();

    // ✅ VALIDACIÓN 4: ¿El rider tiene membresía activa?
    $stmt_mem = $conn->prepare(
        "SELECT id_membresia, fecha_fin, clases_restantes 
        FROM membresias 
        WHERE id_rider = ? AND estado IN ('Activa', 'Por Vencer')"
    );

    if (!$stmt_mem) {
        throw new Exception("Error al preparar la consulta de membresía: " . $conn->error);
    }

    $stmt_mem->bind_param("i", $id_rider);
    $stmt_mem->execute();
    $membresia = $stmt_mem->get_result()->fetch_assoc();
    $stmt_mem->close();

    if (!$membresia) {
        throw new Exception("No tienes una membresía activa para reservar.");
    }

    if ($membresia['fecha_fin'] < $hoy) {
        $conn->query("UPDATE membresias SET estado = 'Vencida' WHERE id_membresia = " . $membresia['id_membresia']);
        throw new Exception("Tu membresía ha expirado el " . $membresia['fecha_fin'] . ".");
    }

    if ($membresia['clases_restantes'] !== null && $membresia['clases_restantes'] <= 0) {
        throw new Exception("Ya no te quedan clases disponibles en tu membresía.");
    }

    // ✅ VALIDACIÓN 5 (NUEVA): ¿La bici está disponible en el sistema?
    $stmt_bici = $conn->prepare("SELECT estado FROM bicicletas WHERE id_bicicleta = ?");
    $stmt_bici->bind_param("i", $id_bici);
    $stmt_bici->execute();
    $bici_result = $stmt_bici->get_result();
    
    if ($bici_result->num_rows === 0) {
        throw new Exception("La bicicleta #{$id_bici} no existe.");
    }

    $bici_estado = $bici_result->fetch_assoc();
    $stmt_bici->close();

    if ($bici_estado['estado'] !== 'Disponible') {
        throw new Exception("La bicicleta #{$id_bici} no está disponible por mantenimiento. Por favor, elige otra.");
    }

    $sql_insert = "INSERT INTO reservaciones (id_rider, id_horario, id_bicicleta, estatus) VALUES (?, ?, ?, 'Activa')";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("iii", $id_rider, $id_horario, $id_bici);

    if (!$stmt_insert->execute()) {
        throw new Exception("No se pudo procesar tu reservación.");
    }
    $stmt_insert->close();

    if ($membresia['clases_restantes'] !== null) {
        $stmt_update = $conn->prepare(
            "UPDATE membresias SET clases_restantes = clases_restantes - 1 WHERE id_membresia = ?"
        );
        $stmt_update->bind_param("i", $membresia['id_membresia']);
        $stmt_update->execute();
        $stmt_update->close();
    }

    $fecha_clase = explode(' ', $result_time['class_datetime'])[0]; 
    $sql_asistencia = "INSERT INTO asistencias (id_rider, id_horario, numero_bici, fecha, estatus_asistencia) VALUES (?, ?, ?, ?, 'Pendiente')";
    $stmt_asistencia = $conn->prepare($sql_asistencia);
    $stmt_asistencia->bind_param("iiis", $id_rider, $id_horario, $id_bici, $fecha_clase);
    $stmt_asistencia->execute();
    $stmt_asistencia->close();

    $conn->commit();

    echo json_encode(['success' => true, 'message' => '¡Reservación confirmada! Nos vemos en el studio.']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => '❌ ' . $e->getMessage()]);
}

    $conn->close();
?>