<?php
// ========================================
// ARCHIVO: detalle_pedido.php
// Vista detallada de un pedido específico
// ========================================

include "conexion.php";
session_start();

// Verificar autenticación
if (!isset($_SESSION['id_usuario']) || !isset($_GET['id'])) {
    header("Location: pedidos.php");
    exit();
}

$id_pedido = (int)$_GET['id'];
$id_usuario = $_SESSION['id_usuario'];

// Obtener información del pedido
$query_pedido = "SELECT p.*, u.nombre as nombre_usuario, u.email 
                FROM PEDIDO p 
                JOIN USUARIO u ON p.id_usuario = u.id_usuario 
                WHERE p.id_pedido = $id_pedido AND p.id_usuario = $id_usuario";
$resultado_pedido = $conn->query($query_pedido);

if (!$resultado_pedido || $resultado_pedido->num_rows == 0) {
    header("Location: pedidos.php");
    exit();
}

$pedido = $resultado_pedido->fetch_assoc();

// Obtener productos del pedido
$query_productos = "SELECT dp.*, p.nombre, p.imagen, p.descripcion
                   FROM DETALLE_PEDIDO dp 
                   JOIN PRODUCTO p ON dp.id_producto = p.id_producto 
                   WHERE dp.id_pedido = $id_pedido
                   ORDER BY dp.id_detalle";
$productos = $conn->query($query_productos);

// Calcular totales
$subtotal = 0;
$productos_array = [];
while ($producto = $productos->fetch_assoc()) {
    $productos_array[] = $producto;
    $subtotal += ($producto['cantidad'] * $producto['precio']);
}

// Supongamos que tenemos campos adicionales en la tabla PEDIDO
$impuestos = $pedido['impuestos'] ?? ($subtotal * 0.19); // 19% IVA Chile
$envio = $pedido['costo_envio'] ?? 0;
$total = $subtotal + $impuestos + $envio;

// Funciones auxiliares
function formatearFecha($fecha) {
    $meses = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
    ];
    
    $timestamp = strtotime($fecha);
    $dia = date('d', $timestamp);
    $mes = $meses[(int)date('m', $timestamp)];
    $año = date('Y', $timestamp);
    $hora = date('H:i', $timestamp);
    
    return "$dia de $mes de $año a las $hora";
}

function formatearPrecio($precio) {
    return '$' . number_format($precio, 0, ',', '.');
}

function obtenerIconoEstado($estado) {
    $iconos = [
        'pendiente' => 'fas fa-clock',
        'procesando' => 'fas fa-cog fa-spin',
        'enviado' => 'fas fa-truck',
        'entregado' => 'fas fa-check-circle',
        'cancelado' => 'fas fa-times-circle'
    ];
    return $iconos[$estado] ?? 'fas fa-question-circle';
}
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
        <!-- Header con navegación -->
        <div class="header">
            <div class="d-flex align-items-center gap-2 mb-2">
                <a href="pedidos.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Volver a Pedidos
                </a>
            </div>
            <h1>
                <i class="fas fa-receipt"></i> 
                Pedido #<?php echo htmlspecialchars($pedido['numero_pedido']); ?>
            </h1>
            <div class="d-flex align-items-center gap-2">
                <span>Estado actual:</span>
                <span class="estado estado-<?php echo $pedido['estado']; ?>">
                    <i class="<?php echo obtenerIconoEstado($pedido['estado']); ?>"></i>
                    <?php echo ucfirst($pedido['estado']); ?>
                </span>
            </div>
        </div>

        <!-- Layout de 2 columnas -->
        <div class="grid-2col">
            <!-- Columna principal - Productos -->
            <div>
                <!-- Información del estado -->
                <div class="pedido-card">
                    <div class="pedido-header">
                        <h3><i class="fas fa-info-circle"></i> Información del Pedido</h3>
                    </div>
                    <div class="pedido-body">
                        <div class="d-flex justify-content-between mb-2">
                            <strong><i class="fas fa-calendar"></i> Fecha de pedido:</strong>
                            <span><?php echo formatearFecha($pedido['fecha_pedido']); ?></span>
                        </div>
                        
                        <?php if (!empty($pedido['fecha_estimada_entrega'])): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <strong><i class="fas fa-truck"></i> Entrega estimada:</strong>
                            <span><?php echo formatearFecha($pedido['fecha_estimada_entrega']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($pedido['codigo_seguimiento'])): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <strong><i class="fas fa-barcode"></i> Código de seguimiento:</strong>
                            <code><?php echo htmlspecialchars($pedido['codigo_seguimiento']); ?></code>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($pedido['notas'])): ?>
                        <div class="mt-3">
                            <strong><i class="fas fa-sticky-note"></i> Notas:</strong>
                            <p class="text-muted mt-1"><?php echo nl2br(htmlspecialchars($pedido['notas'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Lista de productos -->
                <div class="pedido-card">
                    <div class="pedido-header">
                        <h3><i class="fas fa-box"></i> Productos Pedidos (<?php echo count($productos_array); ?>)</h3>
                    </div>
                    <div class="pedido-body">
                        <?php foreach ($productos_array as $producto): ?>
                            <div class="producto-item">
                                <img src="imagenes/<?php echo htmlspecialchars($producto['imagen']); ?>" 
                                     alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                     class="producto-imagen"
                                     onerror="this.src='imagenes/no-image.jpg'">
                                
                                <div class="producto-info">
                                    <h4><?php echo htmlspecialchars($producto['nombre']); ?></h4>
                                    
                                    <?php if (!empty($producto['descripcion'])): ?>
                                        <p class="text-muted"><?php echo htmlspecialchars(substr($producto['descripcion'], 0, 100)); ?>...</p>
                                    <?php endif; ?>
                                    
                                    <p><strong>Cantidad:</strong> <?php echo $producto['cantidad']; ?> unidades</p>
                                    <p class="precio-unitario">
                                        <strong>Precio unitario:</strong> <?php echo formatearPrecio($producto['precio']); ?>
                                    </p>
                                </div>
                                
                                <div class="producto-precio">
                                    <div class="precio-total">
                                        <?php echo formatearPrecio($producto['cantidad'] * $producto['precio']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Acciones disponibles -->
                <div class="pedido-card">
                    <div class="pedido-header">
                        <h3><i class="fas fa-tools"></i> Acciones Disponibles</h3>
                    </div>
                    <div class="pedido-body">
                        <div class="pedido-acciones">
                            <?php if (!empty($pedido['codigo_seguimiento'])): ?>
                                <a href="seguimiento.php?codigo=<?php echo $pedido['codigo_seguimiento']; ?>" class="btn btn-primary">
                                    <i class="fas fa-map-marker-alt"></i> Rastrear Envío
                                </a>
                            <?php endif; ?>
                            
                            <form method="POST" action="pedidos.php" style="display: inline;">
                                <input type="hidden" name="id_pedido" value="<?php echo $pedido['id_pedido']; ?>">
                                <button type="submit" name="reordenar" class="btn btn-success">
                                    <i class="fas fa-redo"></i> Reordenar Productos
                                </button>
                            </form>
                            
                            <a href="factura.php?id=<?php echo $pedido['id_pedido']; ?>" class="btn btn-secondary" target="_blank">
                                <i class="fas fa-file-pdf"></i> Descargar Factura
                            </a>
                            
                            <?php if ($pedido['estado'] == 'pendiente'): ?>
                                <form method="POST" action="pedidos.php" style="display: inline;" 
                                      onsubmit="return confirm('¿Estás seguro de que deseas cancelar este pedido?')">
                                    <input type="hidden" name="id_pedido" value="<?php echo $pedido['id_pedido']; ?>">
                                    <button type="submit" name="cancelar_pedido" class="btn btn-danger">
                                        <i class="fas fa-times"></i> Cancelar Pedido
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar - Información adicional -->
            <div class="sidebar">
                <!-- Resumen de costos -->
                <div class="info-box">
                    <div class="info-box-header">
                        <h3><i class="fas fa-calculator"></i> Resumen del Pedido</h3>
                    </div>
                    <div class="info-box-body">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Subtotal:</span>
                            <span><?php echo formatearPrecio($subtotal); ?></span>
                        </div>
                        
                        <?php if ($impuestos > 0): ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span>IVA (19%):</span>
                            <span><?php echo formatearPrecio($impuestos); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between mb-1">
                            <span>Envío:</span>
                            <span><?php echo $envio > 0 ? formatearPrecio($envio) : 'Gratis'; ?></span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between font-weight-bold" style="font-size: 18px;">
                            <span>Total:</span>
                            <span><?php echo formatearPrecio($total); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Dirección de envío -->
                <?php if (!empty($pedido['direccion_envio'])): ?>
                <div class="info-box">
                    <div class="info-box-header">
                        <h3><i class="fas fa-map-marker-alt"></i> Dirección de Envío</h3>
                    </div>
                    <div class="info-box-body">
                        <p><strong><?php echo htmlspecialchars($pedido['nombre_usuario']); ?></strong></p>
                        <p><?php echo nl2br(htmlspecialchars($pedido['direccion_envio'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Método de pago -->
                <?php if (!empty($pedido['metodo_pago'])): ?>
                <div class="info-box">
                    <div class="info-box-header">
                        <h3><i class="fas fa-credit-card"></i> Método de Pago</h3>
                    </div>
                    <div class="info-box-body">
                        <p><?php echo htmlspecialchars($pedido['metodo_pago']); ?></p>
                        
                        <?php if (!empty($pedido['referencia_pago'])): ?>
                            <p class="text-muted mt-1">
                                <strong>Referencia:</strong> <?php echo htmlspecialchars($pedido['referencia_pago']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Soporte al cliente -->
                <div class="info-box">
                    <div class="info-box-header">
                        <h3><i class="fas fa-headset"></i> ¿Necesitas Ayuda?</h3>
                    </div>
                    <div class="info-box-body">
                        <p class="mb-2">Si tienes alguna pregunta sobre tu pedido, contáctanos:</p>
                        
                        <div class="d-flex align-items-center gap-1 mb-2">
                            <i class="fas fa-phone text-muted"></i>
                            <a href="tel:+56912345678" class="btn-link">+56 9 1234 5678</a>
                        </div>
                        
                        <div class="d-flex align-items-center gap-1 mb-2">
                            <i class="fas fa-envelope text-muted"></i>
                            <a href="mailto:soporte@tutienda.cl" class="btn-link">soporte@tutienda.cl</a>
                        </div>
                        
                        <div class="d-flex align-items-center gap-1">
                            <i class="fas fa-comments text-muted"></i>
                            <a href="chat.php?pedido=<?php echo $pedido['id_pedido']; ?>" class="btn-link">Chat en línea</a>
                        </div>
                    </div>
                </div>

                <!-- Historial del pedido (si está disponible) -->
                <?php
                // Consulta opcional para historial de estados
                $query_historial = "SELECT * FROM HISTORIAL_PEDIDO WHERE id_pedido = $id_pedido ORDER BY fecha_cambio DESC";
                $historial = $conn->query($query_historial);
                
                if ($historial && $historial->num_rows > 0):
                ?>
                <div class="info-box">
                    <div class="info-box-header">
                        <h3><i class="fas fa-history"></i> Historial del Pedido</h3>
                    </div>
                    <div class="info-box-body">
                        <?php while ($entrada = $historial->fetch_assoc()): ?>
                            <div class="mb-2 pb-2" style="border-bottom: 1px solid #f1f3f4;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="estado estado-<?php echo $entrada['estado']; ?> btn-small">
                                        <?php echo ucfirst($entrada['estado']); ?>
                                    </span>
                                    <small class="text-muted">
                                        <?php echo date('d/m H:i', strtotime($entrada['fecha_cambio'])); ?>
                                    </small>
                                </div>
                                <?php if (!empty($entrada['observaciones'])): ?>
                                    <p class="text-muted mt-1" style="font-size: 12px;">
                                        <?php echo htmlspecialchars($entrada['observaciones']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Estilos adicionales específicos para esta página -->
    <style>
        .btn-link {
            color: #007bff;
            text-decoration: none;
            font-size: 14px;
        }
        
        .btn-link:hover {
            text-decoration: underline;
        }
        
        code {
            background-color: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            border: 1px solid #e9ecef;
        }
    </style>
</body>
</html>
