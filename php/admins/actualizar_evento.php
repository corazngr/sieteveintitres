<?php
// 1. Incluye TU conexión
include '../conexion.php';

$target_dir = "../../uploads/events/"; 
$upload_ok = 1;
$message = "";

// Crea la carpeta si no existe
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

// Revisa si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $titulo = $_POST['event-title'];
    $descripcion = $_POST['event-description'];
    
    $image_name = time() . '_' . basename($_FILES["event-image"]["name"]);
    $target_file = $target_dir . $image_name;
    $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // --- Validaciones (igual que antes) ---
    $check = getimagesize($_FILES["event-image"]["tmp_name"]);
    if ($check === false) {
        $message = "El archivo no es una imagen.";
        $upload_ok = 0;
    }
    if ($_FILES["event-image"]["size"] > 5000000) { // 5MB
        $message = "Tu archivo es demasiado grande (máx 5MB).";
        $upload_ok = 0;
    }
    if ($image_file_type != "jpg" && $image_file_type != "png" && $image_file_type != "jpeg") {
        $message = "Solo se permiten archivos JPG, JPEG y PNG.";
        $upload_ok = 0;
    }

    // --- Fin Validaciones ---

    if ($upload_ok == 1) {
        // Mueve el archivo subido
        if (move_uploaded_file($_FILES["event-image"]["tmp_name"], $target_file)) {
            
            // Guarda la RUTA PÚBLICA en la BD
            $db_path = "/sieteveintitres/uploads/events/" . $image_name;

            // --- Lógica de Inserción con MYSQLI ---
            $sql = "INSERT INTO eventos (titulo, descripcion, ruta_imagen) VALUES (?, ?, ?)";
            
            // Prepara la consulta
            $stmt = $conn->prepare($sql);
            
            // Vincula los parámetros ("sss" significa 3 variables de tipo string)
            $stmt->bind_param("sss", $titulo, $descripcion, $db_path);

            // Ejecuta la consulta
            if ($stmt->execute()) {
                $message = "El evento ha sido subido con éxito.";
                $stmt->close();
                $conn->close();
                // Redirige de vuelta al panel de admin con éxito
                header("Location: /sieteveintitres/html/admins/vistapublico.html?status=success&msg=" . urlencode($message));
                exit();
            } else {
                $message = "Error al guardar en la base de datos: " . $stmt->error;
                // Si falla la BD, borra la imagen que ya se subió
                unlink($target_file);
            }
            // --- Fin Lógica MYSQLI ---
            
        } else {
            $message = "Hubo un error al mover el archivo subido.";
        }
    }

    // Si algo falló ($upload_ok == 0 o error de BD), redirige con error
    $conn->close();
    header("Location: /sieteveintitres/html/admins/vistapublico.html?status=error&msg=" . urlencode($message));
    exit();
}
?>