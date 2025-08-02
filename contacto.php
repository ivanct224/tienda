<?php
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contáctenos</title>
</head>
<body>
    <h2>Contáctenos</h2>
    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $nombre = htmlspecialchars($_POST['nombre']);
        $correo = htmlspecialchars($_POST['correo']);
        $mensaje = htmlspecialchars($_POST['mensaje']);

        echo "<h3>Gracias por contactarnos, $nombre.</h3>";
        echo "<p><strong>Correo:</strong> $correo</p>";
        echo "<p><strong>Mensaje:</strong> $mensaje</p>";
    } else {
    ?>
    <form method="post" action="">
        <label for="nombre">Nombre:</label><br>
        <input type="text" id="nombre" name="nombre" required><br><br>
        <label for="correo">Correo electrónico:</label><br>
        <input type="email" id="correo" name="correo" required><br><br>
        <label for="mensaje">Mensaje:</label><br>
        <textarea id="mensaje" name="mensaje" rows="5" required></textarea><br><br>
        <input type="submit" value="Enviar">
    </form>
    <?php } ?>