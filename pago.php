<!-- pago.php -->
<?php session_start(); ?>
<h2>Resumen de Compra</h2>
<p>Total a pagar: $<?php echo $_SESSION['total']; ?></p>

<form action="procesar_pago.php" method="POST">
  <label>Nombre del titular:</label>
  <input type="text" name="nombre" required><br>

  <label>Número de tarjeta:</label>
  <input type="text" name="tarjeta" maxlength="16" required><br>

  <label>Fecha de vencimiento:</label>
  <input type="text" name="vencimiento" placeholder="MM/AA" required><br>

  <label>CVV:</label>
  <input type="text" name="cvv" maxlength="3" required><br>

  <button type="submit">Pagar</button>
</form>
