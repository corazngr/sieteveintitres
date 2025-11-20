<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}

require_once("../conexion.php");

try {
    $conn->query("
        UPDATE membresias 
        SET estado = 'Vencida' 
        WHERE estado IN ('Activa', 'Por Vencer') 
        AND (
                fecha_fin < CURDATE() 
                OR 
                (clases_restantes IS NOT NULL AND clases_restantes = 0)
            )
    ");
    $search = $_GET['search'] ?? '';
    $role = $_GET['role'] ?? 'all';
    $status = $_GET['status'] ?? 'all'; // Esto será 'active' (1) o 'inactive' (0)

    $sql_usuarios = "
    SELECT 
        u.id_usuario, u.tipo_usuario,
            CASE WHEN u.tipo_usuario = 'rider' THEN r.id_rider WHEN u.tipo_usuario = 'coach' THEN c.id_coach WHEN u.tipo_usuario = 'admin' THEN a.id_admin END AS specific_id,
            CASE WHEN u.tipo_usuario = 'rider' THEN r.nombre_rider WHEN u.tipo_usuario = 'coach' THEN c.nombre_coach WHEN u.tipo_usuario = 'admin' THEN a.nombre_admin END AS nombre,
            CASE WHEN u.tipo_usuario = 'rider' THEN r.email_rider WHEN u.tipo_usuario = 'coach' THEN c.email_coach WHEN u.tipo_usuario = 'admin' THEN a.email_admin END AS email,
            CASE WHEN u.tipo_usuario = 'rider' THEN r.telefono_rider WHEN u.tipo_usuario = 'coach' THEN c.telefono_coach WHEN u.tipo_usuario = 'admin' THEN a.telefono_admin END AS telefono,
            CASE WHEN u.tipo_usuario = 'rider' THEN r.foto_rider WHEN u.tipo_usuario = 'coach' THEN c.foto_coach ELSE NULL END AS foto,

            CASE 
                WHEN u.tipo_usuario = 'coach' THEN c.estatus -- Asumo que 'c.estatus' era el de membresía, si no, ajústalo.
                WHEN u.tipo_usuario = 'rider' THEN 
                    IF(
                        (SELECT COUNT(*) 
                         FROM membresias m 
                         WHERE m.id_rider = r.id_rider 
                           AND m.estado IN ('Activa', 'Por Vencer')) > 0, 
                        'Activo', 
                        'Inactivo'
                    ) 
                ELSE 'Activo' -- Para Admins
            END AS estado_membresia,

            CASE 
                WHEN u.tipo_usuario = 'rider' THEN 
                IF(
                    (
                    SELECT COUNT(*) 
                    FROM membresias m 
                    WHERE m.id_rider = r.id_rider 
                    AND m.estado IN ('Activa', 'Por Vencer')
                    AND (
                        (m.fecha_fin >= CURDATE() AND m.clases_restantes IS NULL)
                        OR
                        (m.clases_restantes > 0)
                    )
                    ) > 0, 
                    1, -- Activo (1)
                    0  -- Inactivo (0)
                    ) 
                WHEN u.tipo_usuario = 'coach' THEN c.esta_activo
                ELSE 1 -- Para Admins
            END AS esta_activo

        FROM usuarios u
        LEFT JOIN riders r ON u.id_rider = r.id_rider
        LEFT JOIN coaches c ON u.id_coach = c.id_coach
        LEFT JOIN admin a ON u.id_admin = a.id_admin
        WHERE (r.id_rider IS NOT NULL OR c.id_coach IS NOT NULL OR a.id_admin IS NOT NULL)";

    $params = [];
    $types = "";
    if (!empty($search)) {
        $sql_usuarios .= " AND ( (u.tipo_usuario = 'rider' AND (r.nombre_rider LIKE ? OR r.email_rider LIKE ?)) OR (u.tipo_usuario = 'coach' AND (c.nombre_coach LIKE ? OR c.email_coach LIKE ?)) OR (u.tipo_usuario = 'admin' AND (a.nombre_admin LIKE ? OR a.email_admin LIKE ?)) )";
        $searchTerm = "%{$search}%";
        array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        $types .= "ssssss";
    }
    if ($role !== 'all') {
        $sql_usuarios .= " AND u.tipo_usuario = ?";
        $params[] = $role;
        $types .= "s";
    }

    if ($status !== 'all') {
        $db_status_value = ($status === 'active') ? 1 : 0; // Convertimos 'active' a 1, 'inactive' a 0
        $sql_usuarios .= " HAVING esta_activo = ?"; 
        $params[] = $db_status_value;
        $types .= "i"; // 'i' de integer
    }

    $sql_usuarios .= " ORDER BY nombre ASC";
    $stmt_usuarios = $conn->prepare($sql_usuarios);
    if (!empty($params)) {
        $stmt_usuarios->bind_param($types, ...$params);
    }
    $stmt_usuarios->execute();
    $usuarios = $stmt_usuarios->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_usuarios->close();

    $sql_tipos = "SELECT id_tipo_membresia, nombre, precio, periodo FROM tipos_membresia WHERE estatus = 'Activo' ORDER BY nombre ASC";
    $tipos_membresia = $conn->query($sql_tipos)->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $usuarios,
        'tipos_membresia' => $tipos_membresia 
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al obtener datos: ' . $e->getMessage()]);
}

$conn->close();
?>