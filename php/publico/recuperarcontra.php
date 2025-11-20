<?php
// Usar las clases de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Si recuperarcontra.php está en 'php/publico/' y PHPMailer en 'php/PHPMailer/', esta ruta es correcta.
require __DIR__ . '/../PHPMailer/src/Exception.php';
require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';

require_once __DIR__ . '/../conexion.php'; 
session_start();
header("Content-Type: application/json; charset=UTF-8");

$response = ['success' => false, 'message' => 'Error desconocido.'];

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    $response['message'] = 'Método no permitido.';
    echo json_encode($response);
    exit(); // Añadir exit
}

// 1. Obtener y validar el correo
$email = $_POST['recoveryEmail'] ?? '';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Por favor, ingresa un correo válido.';
    echo json_encode($response);
    exit(); // Añadir exit
}

try {
    // 2. Buscar al rider en la base de datos
    $stmt = $conn->prepare("SELECT nombre_rider, codigo_acceso FROM riders WHERE email_rider = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // 3. El rider existe, obtener sus datos
        $user = $result->fetch_assoc();
        $nombre = $user['nombre_rider'];
        $pin = $user['codigo_acceso'];

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

            // Asunto y cuerpo (ya los tenías definidos)
            $subject = "Recuperación de PIN - Siete Veintitrés";
            $body = "Hola $nombre,\n\n";
            $body .= "Recibimos una solicitud para recuperar tu PIN.\n\n";
            $body .= "Tu PIN de acceso es: $pin\n\n";
            $body .= "Nos vemos en el studio,\nEl equipo de Siete Veintitrés";

            // Contenido del correo
            $mail->isHTML(false); // Lo enviamos como texto plano (justo como lo escribiste)
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();

            // ¡Éxito!
            $response['success'] = true;
            $response['message'] = '✅ ¡Listo! Hemos enviado tu PIN a tu correo electrónico.';

        } catch (Exception $e) {
            // Captura errores de PHPMailer
            $response['message'] = "❌ El correo existe, pero no pudimos enviar el PIN. Error: {$mail->ErrorInfo}";
        }

    } else {
        // 5. El correo no se encontró
        $response['message'] = 'El correo no está asociado a ninguna cuenta de rider. Verifica que esté bien escrito.';
    }

    $stmt->close();

} catch (Exception $e) {
    $response['message'] = 'Error en el servidor: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
exit();
?>