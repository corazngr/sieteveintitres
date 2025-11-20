<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/../PHPMailer/src/Exception.php';
require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once("../conexion.php");
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}

$id_rider = (int)($_POST['id_rider'] ?? 0);
$id_tipo_membresia = (int)($_POST['id_tipo_membresia'] ?? 0);
$fecha_inicio_str = $_POST['fecha_inicio'] ?? '';

if ($id_rider <= 0 || $id_tipo_membresia <= 0 || empty($fecha_inicio_str)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
    exit();
}

$conn->begin_transaction();
$email_notification_warning = '';

try {

    $sql_check = "SELECT COUNT(*) as count FROM membresias WHERE id_rider = ? AND estado = 'Activa'";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $id_rider);
    $stmt_check->execute();
    if ($stmt_check->get_result()->fetch_assoc()['count'] > 0) {
        throw new Exception("Este rider ya tiene una membresía activa.");
    }
    $stmt_check->close();

    $stmt_tipo = $conn->prepare("SELECT nombre, precio FROM tipos_membresia WHERE id_tipo_membresia = ?");
    $stmt_tipo->bind_param("i", $id_tipo_membresia);
    $stmt_tipo->execute();
    $tipo_details = $stmt_tipo->get_result()->fetch_assoc();
    $stmt_tipo->close();

    if (!$tipo_details) {
        throw new Exception("El tipo de membresía no existe.");
    }
    
    $fecha_inicio = new DateTime($fecha_inicio_str);
    $fecha_fin = clone $fecha_inicio;
    $clases_restantes = null;
    $nombre_membresia = $tipo_details['nombre']; 
    $nombre_membresia_lower = strtolower($nombre_membresia);

    if (strpos($nombre_membresia_lower, 'sabatina') !== false || strpos($nombre_membresia_lower, 'visita') !== false) {
        $clases_restantes = 1; $fecha_fin->modify('+1 day');
    } elseif (strpos($nombre_membresia_lower, 'media') !== false) {
        $fecha_fin->modify('+15 days'); $clases_restantes = 15;
    } elseif (strpos($nombre_membresia_lower, 'mensual') !== false) {
        $fecha_fin->modify('+1 month'); $clases_restantes = null;
    } else {
        $clases_restantes = 1; $fecha_fin->modify('+1 day');
    }
    $fecha_fin_str = $fecha_fin->format('Y-m-d');
    

    $stmt_insert = $conn->prepare(
        "INSERT INTO membresias (id_rider, id_tipo_membresia, monto, estado, fecha_inicio, fecha_fin, clases_restantes) 
         VALUES (?, ?, ?, 'Activa', ?, ?, ?)"
    );
    $stmt_insert->bind_param("iidssi", $id_rider, $id_tipo_membresia, $tipo_details['precio'], $fecha_inicio_str, $fecha_fin_str, $clases_restantes);
    $stmt_insert->execute();
    $stmt_insert->close();
    

    $concepto = "Asignación de membresía: " . $nombre_membresia;
    $responsable = $_SESSION['nombre_usuario'] ?? 'Admin';
    $stmt_ingreso = $conn->prepare("INSERT INTO ingresos (tipo_ingreso, fecha, monto, concepto, responsable, id_rider) VALUES ('Membresia', ?, ?, ?, ?, ?)");
    $stmt_ingreso->bind_param("sdssi", $fecha_inicio_str, $tipo_details['precio'], $concepto, $responsable, $id_rider);
    $stmt_ingreso->execute();
    $stmt_ingreso->close();

    $stmt_reactivate = $conn->prepare("UPDATE riders SET esta_activo = 1 WHERE id_rider = ?");
    $stmt_reactivate->bind_param("i", $id_rider);
    $stmt_reactivate->execute();
    $stmt_reactivate->close();
    
    $stmt_rider = $conn->prepare("SELECT nombre_rider, email_rider FROM riders WHERE id_rider = ?");
    $stmt_rider->bind_param("i", $id_rider);
    $stmt_rider->execute();
    $rider_details = $stmt_rider->get_result()->fetch_assoc();
    $stmt_rider->close();

    if ($rider_details) {
        $nombre_rider = $rider_details['nombre_rider'];
        $email_rider = $rider_details['email_rider'];
        
        $fecha_fin_obj = new DateTime($fecha_fin_str);
        $fecha_fin_formateada = $fecha_fin_obj->format('d \d\e F \d\e Y');
        
        $mail = new PHPMailer(true);
        try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'studioindoor.723@gmail.com';
                $mail->Password   = 'czge tptf dqgc qoad'; // TU CLAVE DE APLICACIÓN
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;
                $mail->CharSet    = 'UTF-8';

                $mail->setFrom('studioindoor.723@gmail.com', 'Siete Veintitrés Studio');
                $mail->addAddress($email_rider, $nombre_rider);

            $mail->isHTML(true);
            $mail->Subject = '¡Tu membresía en Siete Veintitrés está activa!';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                    <h2 style='color: #000;'>¡Hola {$nombre_rider}, tu membresía ha sido activada!</h2>
                    <p>Tu <strong>{$nombre_membresia}</strong> ya está lista para usarse.</p>
                    <p>Puedes disfrutar de tus clases desde hoy y hasta el día:</p>
                    <h1 style='font-size: 32px; color: #000; text-align: center; background: #f4f4f4; padding: 20px; border-radius: 5px;'>
                        {$fecha_fin_formateada}
                    </h1>
                    <p>¡Nos vemos en el studio!</p>
                    <br>
                    <p><strong>El equipo de Siete Veintitrés</strong></p>
                </div>
            ";
            $mail->AltBody = "Hola {$nombre_rider}, tu membresía '{$nombre_membresia}' está activa y vence el {$fecha_fin_formateada}.";

            $mail->send();
        
        } catch (Exception $e) {

            $email_notification_warning = " (Advertencia: no se pudo enviar el correo de notificación. Error: {$mail->ErrorInfo})";
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => '✅ Membresía asignada. El usuario ha sido activado.' . $email_notification_warning]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => '❌ Error al asignar la membresía: ' . $e->getMessage()]);
}

$conn->close();
?>