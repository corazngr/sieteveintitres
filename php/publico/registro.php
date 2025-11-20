<?php
ob_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");
$response = ['success' => false, 'message' => 'Error desconocido'];

try {
    $baseDir = __DIR__ . '/..'; 
    if (!file_exists($baseDir . '/PHPMailer/src/Exception.php')) throw new Exception("Falta librería PHPMailer");
    
    require $baseDir . '/PHPMailer/src/Exception.php';
    require $baseDir . '/PHPMailer/src/PHPMailer.php';
    require $baseDir . '/PHPMailer/src/SMTP.php';

    if (!file_exists("../conexion.php")) throw new Exception("No se encuentra el archivo conexion.php");
    require_once("../conexion.php");

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // Datos Generales
        $userType = $_POST['userType'] ?? '';
        $nombre   = trim($_POST['nameInput'] ?? '');
        $email    = trim($_POST['emailInput'] ?? '');
        $telefono = trim($_POST['phoneInput'] ?? '');

        // Validación básica
        if (empty($userType) || empty($nombre) || empty($email)) {
            throw new Exception("Faltan datos obligatorios.");
        }

        // =========================================================
        // REGISTRO DE RIDER
        // =========================================================
        if ($userType === 'rider') {
            
            $conn->begin_transaction(); 

            $codigo_acceso = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 4);
            $ruta_foto = null;

            // Subir Foto Rider
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
                $upload_dir = '../../uploads/riders/';
                if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
                
                $file_ext = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
                $file_name = uniqid() . '.' . $file_ext;
                $target_file = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
                    $ruta_foto = '/sieteveintitres/uploads/riders/' . $file_name;
                }
            }

            // Insertar en tabla riders
            $sql_rider = "INSERT INTO riders (codigo_acceso, nombre_rider, email_rider, telefono_rider, foto_rider) VALUES (?, ?, ?, ?, ?)";
            $stmt_rider = $conn->prepare($sql_rider);
            if (!$stmt_rider) throw new Exception("Error SQL Rider: " . $conn->error);
            
            $stmt_rider->bind_param("sssss", $codigo_acceso, $nombre, $email, $telefono, $ruta_foto);
            $stmt_rider->execute();
            $id_rider_nuevo = $conn->insert_id;

            // Insertar en tabla usuarios
            $sql_usuario = "INSERT INTO usuarios (id_rider, tipo_usuario) VALUES (?, 'rider')";
            $stmt_usuario = $conn->prepare($sql_usuario);
            $stmt_usuario->bind_param("i", $id_rider_nuevo);
            $stmt_usuario->execute();

            // Envío de Correo al Rider
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'studioindoor.723@gmail.com';
                $mail->Password   = 'czge tptf dqgc qoad'; // CLAVE DE APLICACIÓN
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;
                $mail->CharSet    = 'UTF-8';

                $mail->setFrom('studioindoor.723@gmail.com', 'Siete Veintitrés Studio');
                $mail->addAddress($email, $nombre);

                $mail->isHTML(true);
                $mail->Subject = '¡Bienvenido a Siete Veintitrés!';
                
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto;'>
                        <h2 style='color: #000;'>¡Hola {$nombre}, bienvenido a Siete Veintitrés!</h2>
                        <p>Estamos muy emocionados de tenerte con nosotros.</p>
                        <p>Tu cuenta ha sido creada exitosamente. Para iniciar sesión en nuestro sistema, necesitarás tu correo y tu código de acceso personal.</p>
                        <p>Tu código de acceso es:</p>
                        <div style='font-size: 48px; font-weight: bold; color: #000; letter-spacing: 4px; text-align: center; background: #f4f4f4; padding: 30px 20px; border-radius: 8px; margin: 20px 0;'>
                            {$codigo_acceso}
                        </div>
                        <p>¡Nos vemos en el studio!</p>
                        <br>
                        <p><strong>El equipo de Siete Veintitrés</strong></p>
                    </div>
                ";
                
                $mail->AltBody = "¡Hola {$nombre}!\n\nTu código de acceso es: {$codigo_acceso}\n\n¡Nos vemos en el studio!";

                $mail->send();
                $response['message'] = '¡Registro exitoso! Te hemos enviado un correo con tu código de acceso.';
            } catch (Exception $e) {
                $response['message'] = "¡Registro exitoso! Pero no se pudo enviar el correo. Tu código es: {$codigo_acceso}.";
            }
            
            $conn->commit();
            $response['success'] = true;


        // =========================================================
        // REGISTRO DE COACH
        // =========================================================
        } elseif ($userType === 'coach') {
            
            $conn->begin_transaction();

            $rfc = trim($_POST['rfcInput'] ?? '');
            if (empty($rfc)) throw new Exception("El RFC es obligatorio para coaches.");

            $ruta_foto = null;

            // Subir Foto Coach
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
                $upload_dir = '../../uploads/coaches/'; 
                if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
                
                $file_ext = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
                $file_name = uniqid() . '_coach.' . $file_ext;
                $target_file = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
                    $ruta_foto = '/sieteveintitres/uploads/coaches/' . $file_name;
                }
            }

            // Insertar en tabla coaches
            $sql_coach = "INSERT INTO coaches (nombre_coach, email_coach, telefono_coach, rfc_coach, foto_coach) VALUES (?, ?, ?, ?, ?)";
            $stmt_coach = $conn->prepare($sql_coach);
            if (!$stmt_coach) throw new Exception("Error SQL Coach: " . $conn->error);

            $stmt_coach->bind_param("sssss", $nombre, $email, $telefono, $rfc, $ruta_foto);
            $stmt_coach->execute();
            $id_coach_nuevo = $conn->insert_id;

            // Insertar en tabla usuarios
            $sql_usuario = "INSERT INTO usuarios (id_coach, tipo_usuario) VALUES (?, 'coach')";
            $stmt_usuario = $conn->prepare($sql_usuario);
            $stmt_usuario->bind_param("i", $id_coach_nuevo);
            $stmt_usuario->execute();

            $conn->commit();
            
            $response['success'] = true;
            $response['message'] = '¡Coach registrado con éxito! Ahora puedes iniciar sesión.';
        
        } else {
            throw new Exception("Tipo de usuario no válido: " . $userType);
        }

    } else {
        throw new Exception("Método no permitido.");
    }

} catch (mysqli_sql_exception $e) {
    if (isset($conn)) $conn->rollback();
    
    $error_msg = $e->getMessage();
    // Manejo de errores de duplicidad (Email o RFC repetido)
    if ($e->getCode() == 1062 || $e->getCode() == 23000) {
        if (strpos($error_msg, 'email') !== false) {
            $response['message'] = 'Este correo electrónico ya está registrado.';
        } elseif (strpos($error_msg, 'rfc') !== false) {
            $response['message'] = 'Este RFC ya está registrado.';
        } else {
            $response['message'] = 'Error: Datos duplicados en el sistema.';
        }
    } else {
        $response['message'] = 'Error de base de datos: ' . $error_msg;
    }
} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    $response['message'] = 'Error del servidor: ' . $e->getMessage();
}

ob_clean(); 
echo json_encode($response);
exit;
?>