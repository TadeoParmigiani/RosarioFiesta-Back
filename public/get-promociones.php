<?php
require_once '../config/db.php';

// Consulta para obtener las promociones
$query = "SELECT p.id_producto, p.nombre, p.precio, p.stock, p.estado_producto, p.img, p.descripcion
          FROM productos p
          WHERE p.es_promocion = TRUE"; 

$result = $conn->query($query);

$promociones = [];

while ($row = $result->fetch_assoc()) {
    $id_promocion = $row['id_producto'];

    // Obtener productos base relacionados con esta promoción
    $productosBaseQuery = "SELECT pp.id_producto, pp.cantidad, pr.nombre 
                           FROM promociones_productos pp
                           JOIN productos pr ON pp.id_producto = pr.id_producto
                           WHERE pp.id_promocion = $id_promocion";
    $productosBaseResult = $conn->query($productosBaseQuery);

    $productosBase = [];
    $cantidades = [];
    while ($productoBaseRow = $productosBaseResult->fetch_assoc()) {
        $productosBase[] = $productoBaseRow['nombre'];  // Obtenemos el nombre del producto
        $cantidades[] = $productoBaseRow['cantidad'];   // Obtenemos la cantidad
    }

    // Añadir la promoción y sus productos base a la respuesta
    $promociones[] = [
        'id_producto' => $row['id_producto'],
        'nombre' => $row['nombre'],
        'precio' => $row['precio'],
        'stock' => $row['stock'],
        'estado_producto' => $row['estado_producto'],
        'img' => $row['img'],
        'descripcion' => $row['descripcion'],
        'productosBase' => $productosBase,
        'cantidades' => $cantidades
    ];
}

// Devolver las promociones como un JSON
echo json_encode(['success' => true, 'promociones' => $promociones]);

$conn->close();
?>
