<?php
require_once '../config/tcpdf-main/tcpdf.php';
require_once "../config/db.php";

// Obtener lista de clientes para el select
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $query = "SELECT id_cliente, CONCAT(nombre, ' ', apellido) AS nombre FROM clientes";
    $result = $conn->query($query);

    $clientes = [];
    while ($row = $result->fetch_assoc()) {
        $clientes[] = $row;
    }
    echo json_encode($clientes);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los IDs de clientes seleccionados
    $clientes = $_POST['clientes'];

    // Crear un nuevo PDF
    $pdf = new TCPDF();
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Rosario Fiesta');
    $pdf->AddPage();

    // Encabezado del informe
    $pdf->SetFont('Helvetica', 'B', 16);
    $pdf->SetTitle('Informe de Análisis de Clientes');
    $pdf->Cell(0, 10, 'Informe de Análisis de Clientes', 0, 1, 'C');
    $pdf->Ln(10);

    // Fecha del informe
    $pdf->SetFont('Helvetica', '', 12);
    $pdf->Cell(0, 10, 'Fecha del Informe: ' . date('Y-m-d'), 0, 1);
    $pdf->Ln(10);

    // Análisis de Clientes
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Análisis de Clientes:', 0, 1);
    $pdf->SetFont('Helvetica', '', 12);

    foreach ($clientes as $cliente_id) {
        // Consulta para obtener datos del cliente filtrando por el ID
        $query = "
            SELECT c.nombre AS nombre_cliente,
                   p.nombre AS nombre_producto,
                   SUM(vp.cantidad) AS cantidad,
                   SUM(vp.cantidad * vp.precio_unitario) AS total_producto
            FROM clientes c
            JOIN ventas v ON c.id_cliente = v.id_cliente
            JOIN ventas_productos vp ON v.id_venta = vp.id_venta
            JOIN productos p ON vp.id_producto = p.id_producto
            WHERE c.id_cliente = ?
            GROUP BY c.nombre, p.nombre
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $cliente_id);
        $stmt->execute();
        $result = $stmt->get_result();

        // Detalles del cliente
        $total_invertido = 0;
        $productos_comprados = [];
        $cantidad_por_producto = [];
        $preferencia = '';

        if ($result->num_rows > 0) {
            while ($cliente_data = $result->fetch_assoc()) {
                $nombre_cliente = $cliente_data['nombre_cliente'];
                $producto = $cliente_data['nombre_producto'];
                $cantidad = $cliente_data['cantidad'];
                $total_producto = $cliente_data['total_producto'];

                // Acumular datos
                $productos_comprados[] = $producto . ' (' . $cantidad . ' unidades)';
                $total_invertido += $total_producto;

                // Contar cantidad por producto
                if (!isset($cantidad_por_producto[$producto])) {
                    $cantidad_por_producto[$producto] = 0;
                }
                $cantidad_por_producto[$producto] += $cantidad;
            }

            // Determinar la preferencia (mayor cantidad comprada)
            $max_cantidad = 0;
            foreach ($cantidad_por_producto as $producto => $cantidad) {
                if ($cantidad > $max_cantidad) {
                    $max_cantidad = $cantidad;
                    $preferencia = $producto;
                }
            }
        } else {
            $pdf->Cell(0, 10, 'No hay datos para el cliente con ID: ' . $cliente_id, 0, 1);
            $pdf->Ln(10);
            continue;
        }

        // Detalles del cliente en el PDF
        $pdf->Cell(0, 10, 'Cliente: ' . $nombre_cliente, 0, 1);
        
        // Imprimir cada producto en una nueva línea
        $pdf->Cell(0, 10, 'Productos comprados:', 0, 1);
        foreach ($productos_comprados as $producto) {
            $pdf->Cell(0, 10, '- ' . $producto, 0, 1);
        }

        $pdf->Cell(0, 10, 'Preferencias: Mayor interés en ' . $preferencia, 0, 1);
        $pdf->Cell(0, 10, 'Total invertido: $' . number_format($total_invertido, 2), 0, 1);
        $pdf->Ln(10);
    }

    // Cerrar y mostrar el PDF
    $pdf->Output('informe_analisis_clientes.pdf', 'I');
    $conn->close();
}
?>
