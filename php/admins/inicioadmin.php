<?php
session_start();

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header("Location: /sieteveintitres/html/publico/iniciosesion.html");
    exit();
}

$nombre_admin = $_SESSION['nombre_usuario'] ?? 'Admin';
require_once("../conexion.php"); 

$hoy = date("Y-m-d");

// Reservaciones para hoy
$stmt = $conn->prepare("
    SELECT COUNT(r.id_reservacion) as total_reservas
    FROM reservaciones r
    JOIN horario_clases h ON r.id_horario = h.id_horario
    WHERE h.fecha = ? AND r.estatus = 'Activa'
");
$stmt->bind_param("s", $hoy);
$stmt->execute();
$reservaciones_hoy = $stmt->get_result()->fetch_assoc()['total_reservas'] ?? 0;

// Cupo total de hoy
$stmt = $conn->prepare("SELECT SUM(cupo_maximo) as total_cupo FROM horario_clases WHERE fecha = ?");
$stmt->bind_param("s", $hoy);
$stmt->execute();
$cupo_total_hoy = $stmt->get_result()->fetch_assoc()['total_cupo'] ?? 0;

// Ingresos del día
$stmt = $conn->prepare("SELECT SUM(monto) as total_ingresos FROM ingresos WHERE fecha = ?");
$stmt->bind_param("s", $hoy);
$stmt->execute();
$ingresos_dia = $stmt->get_result()->fetch_assoc()['total_ingresos'] ?? 0;

// Nuevos riders este mes
$stmt = $conn->prepare("
    SELECT COUNT(id_rider) as nuevos_riders
    FROM riders
    WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())
");
$stmt->execute();
$nuevos_riders_mes = $stmt->get_result()->fetch_assoc()['nuevos_riders'] ?? 0;

// Membresías activas
$stmt = $conn->prepare("SELECT COUNT(id_membresia) as activas FROM membresias WHERE estado = 'Activa'");
$stmt->execute();
$membresias_activas = $stmt->get_result()->fetch_assoc()['activas'] ?? 0;

// Bicicletas en mantenimiento
$stmt = $conn->prepare("SELECT COUNT(id_bicicleta) as en_mantenimiento FROM bicicletas WHERE estado = 'Mantenimiento'");
$stmt->execute();
$bicis_mantenimiento = $stmt->get_result()->fetch_assoc()['en_mantenimiento'] ?? 0;

$conn->close();

?>