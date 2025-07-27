<?php
session_start();

$nombre = $_POST['nombre'];
$tarjeta = $_POST['tarjeta'];
$vencimiento = $_POST['vencimiento'];
$cvv = $_POST['cvv'];
$total = $_SESSION['total']; // Total guardado desde el carrito

// Validación básica
if(strlen($tarjeta) == 16 && strlen($cvv) == 3){
    // Simular "procesamiento de pago"
    // Aquí podrías guardar la compra en la BD o enviar a una API de pago real

    echo "<h2>Pago realizado con éxito</h2>";
    echo "<p>Gracias por tu compra, $nombre.</p>";
    session_destroy(); // Vaciar carrito
} else {
    echo "<h2>Error en los datos de la tarjeta.</h2>";
    echo "<a href='pago.php'>Volver al pago</a>";
}
?>
