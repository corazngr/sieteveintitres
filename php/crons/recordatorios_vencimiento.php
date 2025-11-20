<?php
require_once(__DIR__ . "/../conexion.php");
require_once(__DIR__ . '/../PHPMailer/src/Exception.php');
require_once(__DIR__ . '/../PHPMailer/src/PHPMailer.php');
require_once(__DIR__ . '/../PHPMailer/src/SMTP.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "--- Iniciando script de recordatorios (Siete Veintitrés) ---\n";
echo "Hora: " . date('Y-m-d H:i:s') . "\n";

try {
    $sql = "SELECT 
                r.nombre_rider, 
                r.email_rider, 
                m.fecha_fin, 
                tm.nombre AS nombre_membresia 
            FROM membresias AS m
            JOIN riders AS r ON m.id_rider = r.id_rider
            JOIN tipos_membresia AS tm ON m.id_tipo_membresia = tm.id_tipo_membresia
            WHERE 
                m.estado = 'Activa' 
                AND DATEDIFF(m.fecha_fin, CURDATE()) = 3"; // 3 días de diferencia

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Error en la consulta SQL: " . $conn->error);
    }

    if ($result->num_rows > 0) {
        echo "Se encontraron " . $result->num_rows . " membresías por vencer.\n";

        while($row = $result->fetch_assoc()) {
            $nombre = $row['nombre_rider'];
            $email = $row['email_rider'];
            $fecha_fin = date("d \d\e F", strtotime($row['fecha_fin'])); 
            $membresia = $row['nombre_membresia'];

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
                $mail->addAddress($email, $nombre);

                $mail->isHTML(true);
                $mail->Subject = '¡Tu membresía está por vencer!';
                $mail->Body    = "
                    <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                        <h2 style='color: #000;'>¡Hola {$nombre}!</h2>
                        <p>Este es un recordatorio amistoso de que tu <strong>{$membresia}</strong> está por vencer.</p>
                        <p>Tu último día para usarla es este:</p>
                        <h1 style='font-size: 32px; color: #000; text-align: center;'>
                            {$fecha_fin}
                        </h1>
                        <p>¡No pierdas tu lugar y renueva pronto! Te esperamos en el studio.</p>
                        <br>
                        <p><strong>El equipo de Siete Veintitrés</strong></p>
                    </div>
                ";
                
                $mail->AltBody = "Hola {$nombre}, tu membresía '{$membresia}' vence el {$fecha_fin}. ¡Renueva pronto!";

                $mail->send();
                echo "-> Correo de recordatorio enviado a: {$email}\n";

            } catch (Exception $e) {
                echo "-> ERROR al enviar correo a {$email}: {$mail->ErrorInfo}\n";
            }
            sleep(1); 
        }
    } else {
        echo "No hay recordatorios para enviar hoy.\n";
    }

} catch (Exception $e) {
    echo "--- ERROR CRÍTICO EN EL SCRIPT ---\n";
    echo $e->getMessage() . "\n";
}

$conn->close();
echo "--- Script finalizado ---\n";
?>