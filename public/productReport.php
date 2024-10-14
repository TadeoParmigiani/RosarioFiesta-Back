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
$pdf->SetHeaderData('', 0, 'Informe de Análisis de Productos', 'Fecha del Informe: ' . date('Y-m-d') );
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(false);
$pdf->AddPage();
$pdf->Ln(10);
// Contenido del PDF
$html = '<h1>Informe de Análisis de Productos</h1>';
$html .= '<p>Período de fecha: ' . $fecha_inicio . ' a ' . $fecha_fin . '</p>';
$html .= '<h2>Análisis de Productos:</h2>';
$html .= '<table border="1" cellpadding="4">
            <tr>
                <th>Producto</th>
                <th>Unidades Vendidas</th>
                <th>Ingresos</th>
                <th>Contribución</th>
            </tr>';

$total_ingresos = 0;
$productos = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row; // Almacena los datos de los productos
        $total_ingresos += $row['ingresos']; // Acumula el total de ingresos
    }
}

// Calcula la contribución de cada producto y genera las filas de la tabla
foreach ($productos as $producto) {
    $contribucion = ($producto['ingresos'] / $total_ingresos) * 100;
    $html .= '<tr>
                <td>' . $producto['producto_nombre'] . '</td>
                <td>' . $producto['unidades_vendidas'] . '</td>
                <td>$' . number_format($producto['ingresos'], 2) . '</td>
                <td>' . number_format($contribucion, 2) . '%</td>
              </tr>';
}

$html .= '</table>';
$html .= '<p>Total de ingresos en el período: $' . number_format($total_ingresos, 2) . '</p>';

// Imprimir el contenido
$pdf->writeHTML($html, true, false, true, false, '');

// Cerrar y generar el PDF
$pdf->Output('informe_analisis_productos.pdf', 'I');

// Cerrar la conexión
$conn->close();
?>
