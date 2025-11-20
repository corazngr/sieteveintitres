<?php
include '../conexion.php';

// --- CONFIGURACIÓN ---
$target_dir = "../../uploads/coaches/";
$message = "";
$upload_ok = 1;
$status = "error";

// --- CREAR CARPETA ---
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

// --- VERIFICAR POST ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- OBTENER DATOS (usando coach_id) ---
    $coach_id = $_POST['coach-id'];
    $especialidad = $_POST['coach-especialidad'];
    $descripcion = $_POST['coach-descripcion'];
    $fb_link = !empty($_POST['coach-facebook']) ? $_POST['coach-facebook'] : NULL;
    $ig_link = !empty($_POST['coach-instagram']) ? $_POST['coach-instagram'] : NULL;

    // --- PREPARAR ARCHIVOS ---
    $img_perfil_name = time() . '_perfil_' . basename($_FILES["coach-img-perfil"]["name"]);
    $img_gustos_name = time() . '_gustos_' . basename($_FILES["coach-img-gustos"]["name"]);
    
    $target_perfil_file = $target_dir . $img_perfil_name;
    $target_gustos_file = $target_dir . $img_gustos_name;

    $db_path_perfil = "/sieteveintitres/uploads/coaches/" . $img_perfil_name;
    $db_path_gustos = "/sieteveintitres/uploads/coaches/" . $img_gustos_name;

    // --- VALIDAR AMBOS ARCHIVOS ---
    $check1 = getimagesize($_FILES["coach-img-perfil"]["tmp_name"]);
    $check2 = getimagesize($_FILES["coach-img-gustos"]["tmp_name"]);
    if ($check1 === false || $check2 === false) {
        $message = "Uno o ambos archivos no son imágenes.";
        $upload_ok = 0;
    }

    if ($upload_ok == 1) {
        if (move_uploaded_file($_FILES["coach-img-perfil"]["tmp_name"], $target_perfil_file)) {
            if (move_uploaded_file($_FILES["coach-img-gustos"]["tmp_name"], $target_gustos_file)) {
                
                $sql = "INSERT INTO perfiles_coaches (coach_id, especialidad, descripcion, ruta_img_perfil, ruta_img_gustos, link_facebook, link_instagram) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issssss", $coach_id, $especialidad, $descripcion, $db_path_perfil, $db_path_gustos, $fb_link, $ig_link);

                if ($stmt->execute()) {
                    $message = "El perfil del coach ha sido subido con éxito.";
                    $status = "success";
                } else {
                    $message = "Error al guardar en BD: " . $stmt->error;
                    unlink($target_perfil_file);
                    unlink($target_gustos_file);
                }
                $stmt->close();

            } else {
                $message = "Error al subir la imagen de gustos.";
                unlink($target_perfil_file); 
            }
        } else {
            $message = "Error al subir la imagen de perfil.";
        }
    }

    $conn->close();
} else {
    $message = "Método no permitido.";
}

header("Location: /sieteveintitres/html/admins/vistapublico.html?status=" . $status);
exit();
?>