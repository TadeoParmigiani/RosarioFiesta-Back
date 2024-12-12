<?php

require_once '../config/db.php'; // Incluye el archivo de conexión

$query = "SELECT ventas.id_venta, ventas.fecha,  clientes.id_cliente, CONCAT (clientes.nombre, ' ', clientes.apellido) AS cliente, ventas.metodo_pago, ventas.total, ventas.estado
          FROM ventas
          JOIN clientes ON ventas.id_cliente = clientes.id_cliente ORDER BY ventas.fecha DESC";
$result = $conn->query($query);

$ventas = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $venta = $row;
        
        // Obtener productos asociados a esta venta
        $id_venta = $venta['id_venta'];
        $productosQuery = "SELECT productos.nombre, ventas_productos.cantidad, ventas_productos.precio_unitario 
                           FROM ventas_productos 
                           JOIN productos ON ventas_productos.id_producto = productos.id_producto 
                           WHERE ventas_productos.id_venta = $id_venta";
        $productosResult = $conn->query($productosQuery);
        
        $productos = [];
        while ($producto = $productosResult->fetch_assoc()) {
            $productos[] = $producto;
        }
        
        $venta['productos'] = $productos;
        $ventas[] = $venta;
    }
}

// Devuelve los resultados en formato JSON
echo json_encode($ventas);
?>
