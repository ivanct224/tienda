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
    <title>Mis Pedidos</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .filtros {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .filtros form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: end;
        }
        
        .campo {
            display: flex;
            flex-direction: column;
        }
        
        .campo label {
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        input, select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .pedido-card {
            background: white;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .pedido-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .pedido-info {
            flex: 1;
        }
        
        .pedido-numero {
            font-weight: bold;
            font-size: 16px;
            color: #333;
        }
        
        .pedido-fecha {
            color: #666;
            font-size: 14px;
        }
        
        .estado {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .estado-pendiente {
            background: #fff3cd;
            color: #856404;
        }
        
        .estado-procesando {
            background: #cce5ff;
            color: #004085;
        }
        
        .estado-enviado {
            background: #e2ccff;
            color: #5a189a;
        }
        
        .estado-entregado {
            background: #d1e7dd;
            color: #0f5132;
        }
        
        .estado-cancelado {
            background: #f8d7da;
            color: #842029;
        }
        
        .pedido-body {
            padding: 20px;
        }
        
        .pedido-total {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }
        
        .pedido-acciones {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .mensaje {
            background: #d1e7dd;
            color: #0f5132;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #badbcc;
        }
        
        .sin-pedidos {
            background: white;
            padding: 40px;
            text-align: center;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .sin-pedidos h3 {
            color: #666;
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .filtros form {
                flex-direction: column;
            }
            
            .campo {
                width: 100%;
            }
            
            .pedido-header {
                flex-direction: column;
                align-items: start;
            }
            
            .pedido-acciones {
                width: 100%;
            }
            
            .btn {
                flex: 1;
                min-width: 120px;
            }
        }
    </style>
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
