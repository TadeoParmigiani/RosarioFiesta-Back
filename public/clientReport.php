<?php
require_once '../config/tcpdf-main/tcpdf.php';
require_once "../config/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = "SELECT id_cliente, CONCAT(nombre, ' ', apellido) AS nombre FROM clientes";
    $result = $conn->query($query);

    $clientes = [];
    while ($row = $result->fetch_assoc()) {
        $clientes[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($clientes);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientes = $_POST['clientes'];

    $pdf = new TCPDF();
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Rosario Fiesta');
    $pdf->SetTitle('Informe de Análisis de Clientes');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->AddPage();

    $logo = '../../RosarioFiesta-Front-master/static/img/logo rosario fiesta (1).png';
    $pdf->Image($logo, 10, 10, 30, 30);

    $pdf->SetFont('Helvetica', 'B', 16);
    $pdf->SetY(15);
    $pdf->Cell(0, 10, 'Informe de Análisis de Clientes', 0, 1, 'C');
    $pdf->Ln(15);

    $pdf->SetFont('Helvetica', '', 12);
    $pdf->Cell(0, 10, 'Fecha del Informe: ' . date('Y-m-d'), 0, 1, 'R');
    $pdf->Ln(10);

    foreach ($clientes as $cliente_id) {
        $query = "
            SELECT 
                c.nombre AS nombre_cliente,
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

        if ($result->num_rows === 0) {
            $pdf->SetFont('Helvetica', '', 12);
            $pdf->SetFillColor(255, 220, 220);
            $pdf->MultiCell(0, 10, 'No hay datos para el cliente con ID: ' . $cliente_id, 0, 'L', 1);
            $pdf->Ln(10);
            continue;
        }

        $nombre_cliente = '';
        $productos_comprados = [];
        $cantidad_por_producto = [];
        $total_invertido = 0;

        while ($row = $result->fetch_assoc()) {
            $nombre_cliente = $row['nombre_cliente'];
            $producto = $row['nombre_producto'];
            $cantidad = $row['cantidad'];
            $total_producto = $row['total_producto'];

            $productos_comprados[] = "$producto ($cantidad unidades)";
            $cantidad_por_producto[$producto] = $cantidad;
            $total_invertido += $total_producto;
        }

        arsort($cantidad_por_producto);
        $preferencia = array_key_first($cantidad_por_producto);

        // Marco para el cliente
        $pdf->SetFont('Helvetica', 'B', 14);
        $pdf->SetFillColor(230, 230, 250);
        $pdf->SetDrawColor(150, 150, 150);
        $pdf->SetLineWidth(0.5);
        $pdf->MultiCell(0, 10, "Cliente: $nombre_cliente", 1, 'C', 1);
        $pdf->Ln(5);

        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Productos comprados:', 0, 1, 'L');
        $pdf->SetFont('Helvetica', '', 12);
        foreach ($productos_comprados as $producto) {
            $pdf->Cell(10); // Sangría
            $pdf->Cell(0, 8, '- ' . $producto, 0, 1, 'L');
        }

        $pdf->Ln(5);
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Preferencia:', 0, 1, 'L');
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 8, $preferencia, 0, 1, 'L');

        $pdf->Ln(5);
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Total invertido:', 0, 1, 'L');
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 8, '$' . number_format($total_invertido, 2), 0, 1, 'L');

        $pdf->Ln(10);
    }

    $pdf->Output('informe_analisis_clientes.pdf', 'I');
    $conn->close();
}
?>
