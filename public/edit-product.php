<?php
require_once '../config/db.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idProducto = $_POST['id_producto'];
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $stock = $_POST['stock'];
    $estado = $_POST['estado'];
    $descripcion = $_POST['descripcion'];
    $idCategoria = $_POST['id_categoria'];
    $img = $_POST['img'];

    if ($conn) {
        // Comienza la transacción
        $conn->begin_transaction();
        
        try {
            // Actualizar el producto base
            $sql = "UPDATE productos SET nombre = ?, precio = ?, stock = ?, estado_producto = ?, descripcion = ?, id_categoria = ?, img = ? WHERE id_producto = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sdissisi', $nombre, $precio, $stock, $estado, $descripcion, $idCategoria, $img, $idProducto);

            if (!$stmt->execute()) {
                throw new Exception('Error al actualizar el producto.');
            }

            // Obtener las promociones que contienen el producto base
            $sql_promociones = "SELECT id_promocion, cantidad FROM promociones_productos WHERE id_producto = ?";
            $stmt_promociones = $conn->prepare($sql_promociones);
            $stmt_promociones->bind_param("i", $idProducto);
            $stmt_promociones->execute();
            $result_promociones = $stmt_promociones->get_result();

            while ($promocion = $result_promociones->fetch_assoc()) {
                $id_promocion = $promocion['id_promocion'];
                $cantidad_base = $promocion['cantidad'];

                // Calcular el nuevo stock de la promoción basado en el stock del producto base
                $nuevo_stock_promocion = floor($stock / $cantidad_base);

                // Actualizar el stock de la promoción
                $sql_actualizar_promocion = "UPDATE productos SET stock = ? WHERE id_producto = ?";
                $stmt_actualizar_promocion = $conn->prepare($sql_actualizar_promocion);
                $stmt_actualizar_promocion->bind_param("ii", $nuevo_stock_promocion, $id_promocion);

                if (!$stmt_actualizar_promocion->execute()) {
                    throw new Exception('Error al actualizar el stock de la promoción.');
                }
                $stmt_actualizar_promocion->close();
            }

            // Confirmar los cambios en la base de datos
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Producto y promociones actualizadas correctamente.']);

            $stmt_promociones->close();
            $stmt->close();
        } catch (Exception $e) {
            // Si hay un error, revertir la transacción
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error en la conexión a la base de datos.']);
    }
}
?>
