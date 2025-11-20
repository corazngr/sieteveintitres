<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// 1. Respuesta por defecto
$response = [
    'loggedIn' => false,
    'tipo_usuario' => null,
    'nombre' => null,
    'tieneMembresiaActiva' => false
];

try {
    require_once("../conexion.php"); // Conectamos a la BD

    // 2. CORRECCIÓN: Verificamos 'tipo_usuario' primero, es más general
    if (isset($_SESSION['tipo_usuario'])) {
        
        $response['loggedIn'] = true;
        $response['tipo_usuario'] = $_SESSION['tipo_usuario'];
        // Asumimos que 'nombre_usuario' se guarda al iniciar sesión
        $response['nombre'] = $_SESSION['nombre_usuario'] ?? 'Usuario'; 

        // 3. Si el usuario es 'rider', verificamos su 'id_rider' y membresía
        if ($response['tipo_usuario'] === 'rider' && isset($_SESSION['id_rider'])) {
            
            $id_rider = $_SESSION['id_rider'];
            date_default_timezone_set('America/Mexico_City');
            $hoy = date('Y-m-d');

            // --- Lógica de autolimpieza de membresías (Recomendado) ---
            
            // Vencidas por fecha
            $conn->query("UPDATE membresias SET estado = 'Vencida' 
                          WHERE id_rider = $id_rider 
                          AND estado IN ('Activa', 'Por Vencer') 
                          AND fecha_fin < '$hoy'");
            
            // Vencidas por clases
            $conn->query("UPDATE membresias SET estado = 'Vencida' 
                          WHERE id_rider = $id_rider 
                          AND estado = 'Activa' 
                          AND clases_restantes IS NOT NULL 
                          AND clases_restantes <= 0");

            // --- Fin de autolimpieza ---

            // 4. Consulta de membresía MÁS ROBUSTA (como la de reservar_clase.php)
            $sql = "SELECT COUNT(*) as conteo 
                    FROM membresias 
                    WHERE id_rider = ? 
                      AND estado IN ('Activa', 'Por Vencer') 
                      AND fecha_fin >= CURDATE()
                      AND (clases_restantes IS NULL OR clases_restantes > 0)"; // Añade chequeo de clases
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) throw new Exception("Error al preparar la consulta de membresía.");

            $stmt->bind_param("i", $id_rider);
            $stmt->execute();
            $resultado = $stmt->get_result()->fetch_assoc();
            
            if ($resultado['conteo'] > 0) {
                $response['tieneMembresiaActiva'] = true;
            }
            $stmt->close();
        }
        // Si es 'admin' o 'coach', loggedIn será 'true' pero tieneMembresiaActiva será 'false', lo cual es correcto.
    }
    
    $conn->close();

} catch (Exception $e) {
    // Si algo falla, lo registramos y enviamos un JSON de error limpio
    $response['message'] = $e->getMessage();
    http_response_code(500); // Indicamos que fue un error del servidor
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>