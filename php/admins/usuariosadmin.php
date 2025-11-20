<?php
// Habilitar la visualización de errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("../conexion.php");

// Variable para almacenar usuarios
$usuarios = [];

try {
    // 1. Verificamos que la conexión exista
    if (!$conexion) {
        die("❌ Error: La variable de conexión no está disponible. Revisa tu archivo 'conexion.php'.");
    }

    $sql = "
        SELECT 
            u.id_usuario,
            u.tipo_usuario,
            
            CASE 
                WHEN u.tipo_usuario = 'rider' THEN r.nombre_rider
                WHEN u.tipo_usuario = 'coach' THEN c.nombre_coach
                WHEN u.tipo_usuario = 'admin' THEN a.nombre_admin
            END AS nombre,
            
            CASE 
                WHEN u.tipo_usuario = 'rider' THEN r.email_rider
                WHEN u.tipo_usuario = 'coach' THEN c.email_coach
                WHEN u.tipo_usuario = 'admin' THEN a.email_admin
            END AS email,
            
            CASE 
                WHEN u.tipo_usuario = 'rider' THEN r.telefono_rider
                WHEN u.tipo_usuario = 'coach' THEN c.telefono_coach
                WHEN u.tipo_usuario = 'admin' THEN a.telefono_admin
            END AS telefono,
            
            CASE 
                WHEN u.tipo_usuario = 'rider' THEN r.foto_rider
                WHEN u.tipo_usuario = 'coach' THEN c.foto_coach
                ELSE '/sieteveintitres/images/icono-default.jpg' 
            END AS foto,
            
            CASE 
                WHEN u.tipo_usuario = 'coach' THEN c.estatus
                ELSE 'Activo'
            END AS estatus
            
        FROM usuarios u
        LEFT JOIN riders r ON u.id_rider = r.id_rider
        LEFT JOIN coaches c ON u.id_coach = c.id_coach
        LEFT JOIN admin a ON u.id_admin = a.id_admin
        WHERE 
            (r.id_rider IS NOT NULL OR c.id_coach IS NOT NULL OR a.id_admin IS NOT NULL)
        ORDER BY nombre ASC
    ";

    $consulta = $conexion->prepare($sql);
    $consulta->execute();
    $usuarios = $consulta->fetchAll(PDO::FETCH_ASSOC);

    echo '<pre>';
    var_dump($usuarios);
    echo '</pre>';


} catch (PDOException $e) {
    // Si hay un error en la consulta SQL, lo mostrará aquí.
    die("❌ Error al obtener usuarios: " . $e->getMessage());
}
?>