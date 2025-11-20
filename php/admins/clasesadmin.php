<?php
session_start();
require_once 'php/conexion.php';

// Validar sesión del admin
if (!isset($_SESSION['id_admin'])) {
    // header('Location: /login.php'); 
    // exit();
}

$date_param = $_GET['date'] ?? 'now';
$current_date = new DateTime($date_param);

// Calcular el lunes de esa semana
$start_of_week = clone $current_date;
$start_of_week->modify('monday this week');

// Calcular el domingo de esa semana
$end_of_week = clone $start_of_week;
$end_of_week->modify('+6 days');

$prev_week = clone $start_of_week;
$prev_week->modify('-1 week');
$next_week = clone $start_of_week;
$next_week->modify('+1 week');

$prev_week_link = 'clasesadmin.php?date=' . $prev_week->format('Y-m-d');
$next_week_link = 'clasesadmin.php?date=' . $next_week->format('Y-m-d');

$meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$mes_fin = $meses[(int)$end_of_week->format('n') - 1];
$week_display = $start_of_week->format('d') . ' – ' . $end_of_week->format('d') . ' de ' . $mes_fin . ', ' . $end_of_week->format('Y');


$sql_clases = "
    SELECT 
        hc.id_horario, hc.nombre_clase_especifico, hc.fecha, hc.hora_inicio, hc.cupo_maximo,
        c.nombre_coach,
        (SELECT COUNT(*) FROM reservaciones r WHERE r.id_horario = hc.id_horario AND r.estatus = 'Activa') AS reservados
    FROM horario_clases AS hc
    JOIN coaches AS c ON hc.id_coach = c.id_coach
    WHERE hc.fecha BETWEEN ? AND ?
    ORDER BY hc.fecha, hc.hora_inicio
";

$stmt_clases = $conexion->prepare($sql_clases);
$stmt_clases->execute([$start_of_week->format('Y-m-d'), $end_of_week->format('Y-m-d')]);
$clases_de_la_semana = $stmt_clases->fetchAll(PDO::FETCH_ASSOC);

$clases_por_dia = array_fill(1, 7, []); 

foreach ($clases_de_la_semana as $clase) {
    $dia_de_la_semana = (new DateTime($clase['fecha']))->format('N');
    $clases_por_dia[$dia_de_la_semana][] = $clase;
}

$stmt_coaches = $conexion->prepare("SELECT id_coach, nombre_coach FROM coaches WHERE esta_activo = 1 ORDER BY nombre_coach");
$stmt_coaches->execute();
$lista_coaches = $stmt_coaches->fetchAll(PDO::FETCH_ASSOC);

require '/html/admins/clasesadmin.html';