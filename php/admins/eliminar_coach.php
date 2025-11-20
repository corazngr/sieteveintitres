<?php
include '../conexion.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"));

// El 'id' que se recibe es el de la tabla 'perfiles_coaches'
if (!isset($data->id) || empty($data->id)) {
    echo json_encode(['success' => false, 'message' => 'ID de perfil no proporcionado.']);
    exit;
}

$id_perfil = $data->id;
$ruta_img_perfil = null;
$ruta_img_gustos = null;

try {
    // 1. Obtener las rutas de AMBAS imágenes
    $sql_select = "SELECT ruta_img_perfil, ruta_img_gustos FROM perfiles_coaches WHERE id = ?";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $id_perfil);
    
    if ($stmt_select->execute()) {
        $result = $stmt_select->get_result();
        if ($result->num_rows > 0) {
            $coach = $result->fetch_assoc();
            $ruta_img_perfil = $coach['ruta_img_perfil'];
            $ruta_img_gustos = $coach['ruta_img_gustos'];
        }
        $stmt_select->close();
    } else {
        throw new Exception("Error al buscar perfil: " . $stmt_select->error);
    }

    if ($ruta_img_perfil) {
        // 2. Borrar el registro de la BD
        $sql_delete = "DELETE FROM perfiles_coaches WHERE id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $id_perfil);

        if ($stmt_delete->execute()) {
            // 3. Borrar AMBOS archivos de imagen del servidor
            $ruta_fisica_perfil = $_SERVER['DOCUMENT_ROOT'] . $ruta_img_perfil;
            $ruta_fisica_gustos = $_SERVER['DOCUMENT_ROOT'] . $ruta_img_gustos;
            
            if (file_exists($ruta_fisica_perfil)) {
                unlink($ruta_fisica_perfil);
            }
            if (file_exists($ruta_fisica_gustos)) {
                unlink($ruta_fisica_gustos);
            }

            echo json_encode(['success' => true, 'message' => 'Perfil de coach eliminado correctamente.']);
        } else {
            throw new Exception("Error al borrar de BD: " . $stmt_delete->error);
        }
        $stmt_delete->close();

    } else {
        throw new Exception("Perfil no encontrado (ID: " . $id_perfil . ").");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>