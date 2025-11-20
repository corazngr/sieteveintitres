<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') { /* ... */ }
require_once("../conexion.php");

// Recibimos el mes y año que el usuario quiere ver. Si no se envía nada, usamos el mes y año actuales.
$mes_solicitado = (int)($_GET['month'] ?? date('m'));
$ano_solicitado = (int)($_GET['year'] ?? date('Y'));

try {
    $response = [];
    $hoy = date('Y-m-d');

    // --- CÁLCULO DE RESÚMENES ---
    // Ingresos y Gastos de HOY (estos no cambian, siempre muestran el día actual)
    $stmt_ih = $conn->prepare("SELECT SUM(monto) as total FROM ingresos WHERE DATE(fecha) = ?");
    $stmt_ih->bind_param("s", $hoy);
    $stmt_ih->execute();
    $ingresos_hoy = $stmt_ih->get_result()->fetch_assoc()['total'] ?? 0;

    $stmt_gh = $conn->prepare("SELECT SUM(monto) as total FROM egresos WHERE DATE(fecha) = ?");
    $stmt_gh->bind_param("s", $hoy);
    $stmt_gh->execute();
    $gastos_hoy = $stmt_gh->get_result()->fetch_assoc()['total'] ?? 0;

    // Saldo del MES SOLICITADO (la consulta ahora es dinámica)
    $stmt_im = $conn->prepare("SELECT SUM(monto) as total FROM ingresos WHERE MONTH(fecha) = ? AND YEAR(fecha) = ?");
    $stmt_im->bind_param("ii", $mes_solicitado, $ano_solicitado);
    $stmt_im->execute();
    $ingresos_mes = $stmt_im->get_result()->fetch_assoc()['total'] ?? 0;

    $stmt_gm = $conn->prepare("SELECT SUM(monto) as total FROM egresos WHERE MONTH(fecha) = ? AND YEAR(fecha) = ?");
    $stmt_gm->bind_param("ii", $mes_solicitado, $ano_solicitado);
    $stmt_gm->execute();
    $gastos_mes = $stmt_gm->get_result()->fetch_assoc()['total'] ?? 0;

    $response['summary'] = [
        "ingresos_hoy" => (float)$ingresos_hoy, "gastos_hoy" => (float)$gastos_hoy,
        "saldo_hoy" => (float)($ingresos_hoy - $gastos_hoy), "saldo_mes" => (float)($ingresos_mes - $gastos_mes)
    ];

    // --- LISTA DE TRANSACCIONES DEL MES SOLICITADO ---
    $sql_transacciones = "
        (SELECT id_ingreso AS id, created_at AS fecha_completa, concepto AS descripcion, 'Ingreso' AS tipo, tipo_ingreso AS categoria, monto, responsable
        FROM ingresos WHERE MONTH(fecha) = ? AND YEAR(fecha) = ?)
        UNION ALL
        (SELECT id_egreso AS id, created_at AS fecha_completa, concepto AS descripcion, 'Gasto' AS tipo, tipo_egreso AS categoria, monto, responsable
        FROM egresos WHERE MONTH(fecha) = ? AND YEAR(fecha) = ?)
        ORDER BY fecha_completa DESC
    ";
    $stmt_trans = $conn->prepare($sql_transacciones);
    $stmt_trans->bind_param("iiii", $mes_solicitado, $ano_solicitado, $mes_solicitado, $ano_solicitado);
    $stmt_trans->execute();
    $response['transactions'] = $stmt_trans->get_result()->fetch_all(MYSQLI_ASSOC);

    // --- NUEVO: OBTENER AÑOS CON TRANSACCIONES ---
    $sql_years = "(SELECT DISTINCT YEAR(fecha) as anio FROM ingresos) UNION (SELECT DISTINCT YEAR(fecha) as anio FROM egresos) ORDER BY anio DESC";
    $response['available_years'] = $conn->query($sql_years)->fetch_all(MYSQLI_ASSOC);

    $response['success'] = true;
    echo json_encode($response);

} catch (Exception $e) { /* ... */ }
$conn->close();
?>