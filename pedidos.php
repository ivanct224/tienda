<?php
// ========================================
// ARCHIVO: pedidos.php
// Sistema de gestión de pedidos
// ========================================

include "conexion.php";
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$mensaje = "";

// Procesar acciones
if ($_POST) {
    if (isset($_POST['cancelar_pedido'])) {
        $id_pedido = $_POST['id_pedido'];
        $update = "UPDATE PEDIDO SET estado = 'cancelado' WHERE id_pedido = $id_pedido AND id_usuario = $id_usuario AND estado = 'pendiente'";
        if ($conn->query($update)) {
            $mensaje = "Pedido cancelado exitosamente";
        }
    }
    
    if (isset($_POST['reordenar'])) {
        $id_pedido = $_POST['id_pedido'];
        // Obtener productos del pedido anterior y agregarlos al carrito
        $query_items = "SELECT id_producto, cantidad FROM DETALLE_PEDIDO WHERE id_pedido = $id_pedido";
        $items = $conn->query($query_items);
        
        while ($item = $items->fetch_assoc()) {
            // Agregar cada producto al carrito actual
            $insert_carrito = "INSERT INTO CARRITO (id_usuario, id_producto, cantidad) 
                              VALUES ($id_usuario, {$item['id_producto']}, {$item['cantidad']})
                              ON DUPLICATE KEY UPDATE cantidad = cantidad + {$item['cantidad']}";
            $conn->query($insert_carrito);
        }
        $mensaje = "Productos agregados al carrito para reordenar";
    }
}

// Obtener filtros
$buscar = $_GET['buscar'] ?? '';
$estado_filtro = $_GET['estado'] ?? '';
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';

// Construir query de pedidos
$query = "SELECT p.*, 
          (SELECT COUNT(*) FROM DETALLE_PEDIDO dp WHERE dp.id_pedido = p.id_pedido) as total_productos,
          (SELECT SUM(dp.cantidad * dp.precio) FROM DETALLE_PEDIDO dp WHERE dp.id_pedido = p.id_pedido) as total_pedido
          FROM PEDIDO p 
          WHERE p.id_usuario = $id_usuario";

if ($buscar) {
    $query .= " AND p.numero_pedido LIKE '%$buscar%'";
}
if ($estado_filtro) {
    $query .= " AND p.estado = '$estado_filtro'";
}
if ($fecha_desde) {
    $query .= " AND DATE(p.fecha_pedido) >= '$fecha_desde'";
}
if ($fecha_hasta) {
    $query .= " AND DATE(p.fecha_pedido) <= '$fecha_hasta'";
}

$query .= " ORDER BY p.fecha_pedido DESC";
$resultado = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Pedido #<?php echo htmlspecialchars($pedido['numero_pedido']); ?> - Tu Tienda</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Mis Pedidos</h1>
            <p>Gestiona y consulta el estado de tus pedidos</p>
        </div>

        <!-- Mensaje de éxito/error -->
        <?php if ($mensaje): ?>
            <div class="mensaje"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <!-- Filtros de búsqueda -->
        <div class="filtros">
            <form method="GET" action="pedidos.php">
                <div class="campo">
                    <label>Buscar pedido:</label>
                    <input type="text" name="buscar" placeholder="Número de pedido..." value="<?php echo htmlspecialchars($buscar); ?>">
                </div>
                
                <div class="campo">
                    <label>Estado:</label>
                    <select name="estado">
                        <option value="">Todos los estados</option>
                        <option value="pendiente" <?php echo $estado_filtro == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="procesando" <?php echo $estado_filtro == 'procesando' ? 'selected' : ''; ?>>Procesando</option>
                        <option value="enviado" <?php echo $estado_filtro == 'enviado' ? 'selected' : ''; ?>>Enviado</option>
                        <option value="entregado" <?php echo $estado_filtro == 'entregado' ? 'selected' : ''; ?>>Entregado</option>
                        <option value="cancelado" <?php echo $estado_filtro == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                    </select>
                </div>
                
                <div class="campo">
                    <label>Desde:</label>
                    <input type="date" name="fecha_desde" value="<?php echo $fecha_desde; ?>">
                </div>
                
                <div class="campo">
                    <label>Hasta:</label>
                    <input type="date" name="fecha_hasta" value="<?php echo $fecha_hasta; ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="pedidos.php" class="btn" style="background: #6c757d; color: white;">Limpiar</a>
            </form>
        </div>

        <!-- Lista de pedidos -->
        <?php if ($resultado->num_rows > 0): ?>
            <?php while ($pedido = $resultado->fetch_assoc()): ?>
                <div class="pedido-card">
                    <div class="pedido-header">
                        <div class="pedido-info">
                            <div class="pedido-numero">#<?php echo $pedido['numero_pedido']; ?></div>
                            <div class="pedido-fecha">
                                Realizado el <?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?>
                            </div>
                        </div>
                        <span class="estado estado-<?php echo $pedido['estado']; ?>">
                            <?php echo ucfirst($pedido['estado']); ?>
                        </span>
                    </div>
                    
                    <div class="pedido-body">
                        <div class="pedido-total">
                            Total: $<?php echo number_format($pedido['total_pedido'], 2); ?>
                            <span style="font-size: 14px; font-weight: normal; color: #666;">
                                (<?php echo $pedido['total_productos']; ?> productos)
                            </span>
                        </div>
                        
                        <div class="pedido-acciones">
                            <a href="detalle_pedido.php?id=<?php echo $pedido['id_pedido']; ?>" class="btn btn-primary">
                                Ver Detalles
                            </a>
                            
                            <?php if ($pedido['codigo_seguimiento']): ?>
                                <a href="seguimiento.php?codigo=<?php echo $pedido['codigo_seguimiento']; ?>" class="btn" style="background: #7c3aed; color: white;">
                                    Rastrear Pedido
                                </a>
                            <?php endif; ?>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="id_pedido" value="<?php echo $pedido['id_pedido']; ?>">
                                <button type="submit" name="reordenar" class="btn btn-success">
                                    Reordenar
                                </button>
                            </form>
                            
                            <?php if ($pedido['estado'] == 'pendiente'): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de cancelar este pedido?')">
                                    <input type="hidden" name="id_pedido" value="<?php echo $pedido['id_pedido']; ?>">
                                    <button type="submit" name="cancelar_pedido" class="btn btn-danger">
                                        Cancelar Pedido
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="sin-pedidos">
                <h3>No se encontraron pedidos</h3>
                <p>
                    <?php if ($buscar || $estado_filtro || $fecha_desde || $fecha_hasta): ?>
                        Intenta cambiar los filtros de búsqueda
                    <?php else: ?>
                        Aún no has realizado ningún pedido
                    <?php endif; ?>
                </p>
                <br>
                <a href="index.php" class="btn btn-primary">Ir a la Tienda</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
