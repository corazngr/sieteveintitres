<?php
    session_start();
    header("Content-Type: application/json; charset=UTF-8");

    // Protección: Solo admins pueden acceder
    if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
        exit();
    }

    require_once("../conexion.php"); 

    $hoy = date("Y-m-d");
    $datos = [];

    // Empaquetamos el nombre del admin
    $datos['nombre_admin'] = $_SESSION['nombre_usuario'] ?? 'Admin';

    // Reservaciones para hoy
    $stmt = $conn->prepare("SELECT COUNT(r.id_reservacion) as total FROM reservaciones r JOIN horario_clases h ON r.id_horario = h.id_horario WHERE h.fecha = ? AND r.estatus = 'Activa'");
    $stmt->bind_param("s", $hoy);
    $stmt->execute();
    $datos['reservaciones_hoy'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    // Cupo total de hoy
    $stmt = $conn->prepare("SELECT SUM(cupo_maximo) as total FROM horario_clases WHERE fecha = ?");
    $stmt->bind_param("s", $hoy);
    $stmt->execute();
    $datos['cupo_total_hoy'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    // Ingresos del día
    $stmt = $conn->prepare("SELECT SUM(monto) as total FROM ingresos WHERE fecha = ?");
    $stmt->bind_param("s", $hoy);
    $stmt->execute();
    $ingresos = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $datos['ingresos_dia'] = number_format($ingresos, 2); // Formateamos el número aquí

    // Nuevos riders este mes
    $stmt = $conn->prepare("SELECT COUNT(id_rider) as total FROM riders WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
    $stmt->execute();
    $datos['nuevos_riders_mes'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    // Membresías activas
    $stmt = $conn->prepare("SELECT COUNT(id_membresia) as total FROM membresias WHERE estado = 'Activa'");
    $stmt->execute();
    $datos['membresias_activas'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    // Bicicletas en mantenimiento
    $stmt = $conn->prepare("SELECT COUNT(id_bicicleta) as total FROM bicicletas WHERE estado = 'Mantenimiento'");
    $stmt->execute();
    $datos['bicis_mantenimiento'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    $conn->close();

    // Devolvemos todos los datos en un solo JSON
    echo json_encode(['success' => true, 'data' => $datos]);
?>