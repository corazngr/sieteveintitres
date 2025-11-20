<?php
session_start();
require_once("../conexion.php");

header("Content-Type: application/json; charset=UTF-8");

// Verificar sesión y tipo de usuario
if (!isset($_SESSION['id_rider']) || $_SESSION['tipo_usuario'] !== 'rider') {
    echo json_encode([
        "success" => false,
        "message" => "Acceso denegado. Debes iniciar sesión como rider.",
        'redirectUrl' => '/sieteveintitres/html/publico/iniciosesion.html'
    ]);
    exit();
}

$id_rider = $_SESSION['id_rider'];

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "success" => false,
        "message" => "Método no permitido."
    ]);
    exit();
}

// Obtener los datos
$nombre = trim($_POST['nombre'] ?? '');
$email = trim($_POST['email'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');

// Validar campos obligatorios
if (empty($nombre) || empty($email) || empty($telefono)) {
    echo json_encode([
        "success" => false,
        "message" => "Todos los campos son obligatorios."
    ]);
    exit();
}

// --- MANEJO DE IMAGEN ---
$foto_nombre = null;
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $foto_tmp = $_FILES['foto']['tmp_name'];
    $foto_original = basename($_FILES['foto']['name']);
    $extension = strtolower(pathinfo($foto_original, PATHINFO_EXTENSION));

    // Validar extensión
    $ext_permitidas = ['jpg', 'jpeg', 'png'];
    if (!in_array($extension, $ext_permitidas)) {
        echo json_encode([
            "success" => false,
            "message" => "Formato de imagen no válido. Solo se permiten JPG, PNG o GIF."
        ]);
        exit();
    }

    // Carpeta destino
    $carpeta_destino = "../../uploads/riders/";
    if (!is_dir($carpeta_destino)) {
        mkdir($carpeta_destino, 0777, true);
    }

    // Generar nombre único (evita sobreescribir)
    $foto_nombre = "rider_" . $id_rider . "_" . time() . "." . $extension;
    $ruta_destino = $carpeta_destino . $foto_nombre;

    // Mover archivo
    if (!move_uploaded_file($foto_tmp, $ruta_destino)) {
        echo json_encode([
            "success" => false,
            "message" => "Error al guardar la imagen en el servidor."
        ]);
        exit();
    }
}

// --- CONSTRUCCIÓN DE LA CONSULTA ---
$sql = "UPDATE riders 
        SET nombre_rider = ?, email_rider = ?, telefono_rider = ?";
$params = [$nombre, $email, $telefono];
$types = "sss";

if ($foto_nombre) {
    $sql .= ", foto_rider = ?";
    $params[] = $foto_nombre;
    $types .= "s";
}

$sql .= " WHERE id_rider = ?";
$params[] = $id_rider;
$types .= "i";

// --- EJECUCIÓN DE LA CONSULTA ---
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error en la preparación de la consulta: " . $conn->error
    ]);
    exit();
}

$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "✅ Datos actualizados correctamente."
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "❌ Error al actualizar los datos: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
