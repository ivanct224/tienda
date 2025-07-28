<?php
$host = "localhost";
$usuario = "root";
$contrasena = "";
$bd = "ORGANIZACION";

$conn = new mysqli($host, $usuario, $contrasena, $bd);
if ($conn->connect_error) {
    die("Error de conexion: " . $conn->connect_error);
}
?>
