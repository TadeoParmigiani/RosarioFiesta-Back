<?php
// Incluir el archivo de configuración para establecer la conexión con la base de datos
require_once '../config/db.php';

// Obtener los datos de los productos y las cantidades desde la solicitud POST
$data = json_decode(file_get_contents('php://input'), true);

// Verificar que los datos contienen los productos y cantidades
if (empty($data['ids']) || empty($data['cantidades'])) {
    echo json_encode(['success' => false, 'message' => 'No se recibieron productos o cantidades para verificar stock.']);
    exit;
}

// Obtener los IDs de los productos y las cantidades
$idsProductos = $data['ids'];
$cantidadesProductos = $data['cantidades'];

// Preparar la consulta para obtener el stock de los productos seleccionados
$placeholders = implode(',', array_fill(0, count($idsProductos), '?'));
$query = "SELECT id_producto, nombre, stock FROM productos WHERE id_producto IN ($placeholders)";

// Preparar y ejecutar la consulta
$stmt = $conn->prepare($query);
$typeStr = str_repeat('i', count($idsProductos)); // tipo de dato entero (integer)
$stmt->bind_param($typeStr, ...$idsProductos);
$stmt->execute();

// Recuperar los resultados
$result = $stmt->get_result();
$productos = [];
while ($producto = $result->fetch_assoc()) {
    $productos[$producto['id_producto']] = $producto;
}

// Verificar si algún producto no tiene stock suficiente
$productosSinStock = [];
foreach ($idsProductos as $index => $idProducto) {
    if (isset($productos[$idProducto])) {
        // Verificar si la cantidad solicitada es mayor que el stock disponible
        if ($productos[$idProducto]['stock'] < $cantidadesProductos[$index]) {
            $productosSinStock[] = $productos[$idProducto];
        }
    }
}

// Si hay productos sin stock suficiente, devolver un error
if (count($productosSinStock) > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Algunos productos no tienen suficiente stock.',
        'productosSinStock' => $productosSinStock
    ]);
    exit; // Detener la ejecución si hay productos sin stock suficiente
}

// Si todo está correcto, devolver éxito
echo json_encode([
    'success' => true,
    'message' => 'Stock verificado con éxito.',
    'productosSinStock' => []
]);
?>
