<?php
// conexion.php

$servidor = "localhost";
$usuario = "root";
$password = "12345";
$base_de_datos = "siete_veintitres";
$puerto = 3306;

$conn = new mysqli($servidor, $usuario, $password, $base_de_datos, $puerto);

if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>