<?php
require_once '../config/tcpdf-main/tcpdf.php';
require_once "../config/db.php";

// Obtener datos del formulario
$fecha_inicio = $_POST['fecha_inicio'];
$fecha_fin = $_POST['fecha_fin'];

// Consulta para obtener los productos vendidos en el rango de fechas
$sql = "
    SELECT p.nombre AS producto_nombre, 
           SUM(vp.cantidad) AS unidades_vendidas, 
           SUM(vp.cantidad * vp.precio_unitario) AS ingresos
    FROM productos p
    JOIN ventas_productos vp ON p.id_producto = vp.id_producto
    JOIN ventas v ON vp.id_venta = v.id_venta
    WHERE v.fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'
    GROUP BY p.id_producto
";

$result = $conn->query($sql);
// Crear un nuevo PDF
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Administrador');
$pdf->SetTitle('Informe de Análisis de Productos');
$pdf->setPrintHeader(false); // Desactiva la cabecera
$pdf->setPrintFooter(false); // Desactiva el pie de página (si no lo deseas)

$pdf->AddPage();

// Ruta del logo
$logo = '../../RosarioFiesta-Front-master/static/img/logo rosario fiesta (1).png'; // Asegúrate de que la ruta es correcta

// Colocar el logo en la parte superior izquierda, sin deformarlo (ajustamos solo la posición)
$pdf->Image($logo, 10, 5, 30, 30);

// Ajustar el margen superior después de agregar el logo
$pdf->SetY(10);

// Generar el contenido HTML
$html = '<h1 style="text-align: center; font-size: 20px;">Informe de Análisis de Productos</h1>';
$html .= '<p style="text-align: right; font-size: 12px;">Fecha del Informe: ' . date('Y-m-d') . '</p>';
$html .= '<p style="text-align: right; font-size: 12px;">Período de fecha: ' . $fecha_inicio . ' a ' . $fecha_fin . '</p>';

// Agregar un título más destacado para el análisis de productos
$html .= '<h2>Análisis de Productos</h2>';

// Tabla con estilo
$html .= '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; width: 100%; text-align: left; font-size: 12px;">
            <thead>
                <tr style="background-color: #f2f2f2; text-align: center;">
                    <th style="padding: 10px;">Producto</th>
                    <th style="padding: 10px;">Unidades Vendidas</th>
                    <th style="padding: 10px;">Ingresos</th>
                    <th style="padding: 10px;">Contribución (%)</th>
                </tr>
            </thead>
            <tbody>';

// Variables para el total de ingresos y productos
$total_ingresos = 0;
$productos = [];

// Iterar sobre los resultados de la consulta
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
        $total_ingresos += $row['ingresos'];
    }
}

// Generar filas de la tabla
foreach ($productos as $producto) {
    $contribucion = $total_ingresos > 0 ? ($producto['ingresos'] / $total_ingresos) * 100 : 0;
    $html .= '<tr>
                <td style="padding: 8px; text-align: left;">' . htmlspecialchars($producto['producto_nombre']) . '</td>
                <td style="padding: 8px; text-align: center;">' . $producto['unidades_vendidas'] . '</td>
                <td style="padding: 8px; text-align: right;">$' . number_format($producto['ingresos'], 2) . '</td>
                <td style="padding: 8px; text-align: center;">' . number_format($contribucion, 2) . '%</td>
              </tr>';
}

$html .= '</tbody></table>';

// Total de ingresos
$html .= '<p style="font-size: 14px; font-weight: bold;">Total de ingresos en el período: $' . number_format($total_ingresos, 2) . '</p>';

// Escribir el contenido en el PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Cerrar y generar el PDF
$pdf->Output('informe_analisis_productos.pdf', 'I');

// Cerrar la conexión
$conn->close();
?>