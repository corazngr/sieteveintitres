<?php
require_once("../conexion.php");
session_start();

$response = ['success' => false, 'message' => 'Credenciales incorrectas.', 'redirectUrl' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $userType = $_POST['userType'] ?? '';
    $email = $_POST['emailInput'] ?? '';
    $credential = $_POST['credentialInput'] ?? '';

    switch ($userType) {
        case 'rider':
            $sql = "SELECT id_rider, nombre_rider FROM riders WHERE email_rider = ? AND codigo_acceso = ? AND esta_activo = 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $email, $credential);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();             
                session_regenerate_id(true);                
                $_SESSION['id_rider'] = $user['id_rider']; 
                $_SESSION['nombre_usuario'] = $user['nombre_rider'];
                $_SESSION['tipo_usuario'] = 'rider';
                $response['success'] = true;
                $response['redirectUrl'] = '/sieteveintitres/html/publico/miusuario.html';
            }
        break;

        case 'coach':
            $sql = "SELECT id_coach, nombre_coach FROM coaches WHERE email_coach = ? AND rfc_coach = ? AND esta_activo = 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $email, $credential);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                session_regenerate_id(true);
                $_SESSION['id_usuario'] = $user['id_coach'];
                $_SESSION['nombre_usuario'] = $user['nombre_coach'];
                $_SESSION['tipo_usuario'] = 'coach';
                $response['success'] = true;
                $response['redirectUrl'] = '/sieteveintitres/html/coaches/iniciocoach.html';
            }
        break;

        case 'admin':
                $sql = "SELECT id_admin, nombre_admin, password FROM admin WHERE email_admin = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    
                    if (password_verify($credential, $user['password'])) {
                        // ¡Éxito! La contraseña es correcta.
                        session_regenerate_id(true);
                        
                        // Guardamos las variables de sesión que el resto de tus scripts esperan.
                        $_SESSION['id_usuario'] = $user['id_admin'];
                        $_SESSION['nombre_usuario'] = $user['nombre_admin'];
                        $_SESSION['tipo_usuario'] = 'admin'; // <-- La variable clave
                        
                        $response['success'] = true;
                        $response['redirectUrl'] = '/sieteveintitres/html/admins/inicioadmin.html';
                    }
                }
        break;
    }
}

$conn->close();
echo json_encode($response);
?>