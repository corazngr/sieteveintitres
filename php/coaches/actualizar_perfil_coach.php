<?php
header('Content-Type: application/json');
session_start();
require '../conexion.php';

// Verificamos que sea un coach logueado
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'coach') {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit();
}

$id_coach = $_SESSION['id_usuario'];
$response = ['success' => false, 'message' => 'No se recibieron datos.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Leemos los datos de texto del POST
    $nombre = $_POST['nombre'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    
    $foto_path_db = null;

    // --- MANEJO DE LA SUBIDA DE IMAGEN ---
    if (isset($_FILES['foto_coach']) && $_FILES['foto_coach']['error'] === UPLOAD_ERR_OK) {
    
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/sieteveintitres/uploads/coaches/';
        
        $file = $_FILES['foto_coach'];
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $unique_filename = uniqid('coach_' . $id_coach . '_', true) . '.' . $file_extension;
        $upload_file_path = $upload_dir . $unique_filename;

        if (move_uploaded_file($file['tmp_name'], $upload_file_path)) {

            $foto_path_db = '/sieteveintitres/uploads/coaches/' . $unique_filename;
        } else {
            $response['message'] = 'Error al mover el archivo. Verifica los permisos de la carpeta uploads.';
            echo json_encode($response);
            exit();
        }
    }

    // --- ACTUALIZACIÓN DE LA BASE DE DATOS ---
    try {
        if ($foto_path_db) {
            // Si hay foto nueva, la incluimos en la consulta
            $sql = "UPDATE coaches SET nombre_coach = ?, email_coach = ?, telefono_coach = ?, foto_coach = ? WHERE id_coach = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $nombre, $email, $telefono, $foto_path_db, $id_coach);
        } else {
            // Si no hay foto nueva, actualizamos solo el texto
            $sql = "UPDATE coaches SET nombre_coach = ?, email_coach = ?, telefono_coach = ? WHERE id_coach = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $nombre, $email, $telefono, $id_coach);
        }

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Perfil actualizado con éxito.';
            // Devolvemos la nueva ruta de la foto si se subió una
            if ($foto_path_db) {
                $response['newImageUrl'] = $foto_path_db;
            }
        } else {
            $response['message'] = 'Error al actualizar la base de datos.';
        }
        $stmt->close();
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
}

$conn->close();
echo json_encode($response);
?>