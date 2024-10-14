<?php
require_once '../config/tcpdf-main/tcpdf.php';
require_once "../config/db.php";

$fecha_inicio = $_POST['promo_fecha_inicio'];
$fecha_fin = $_POST['promo_fecha_fin'];

$sql = "SELECT productos.nombre, 
        MONTH(ventas.fecha) AS mes, 
        SUM(ventas_productos.cantidad) AS total_vendido
        FROM ventas
        JOIN ventas_productos ON ventas.id_venta = ventas_productos.id_venta
        JOIN productos ON ventas_productos.id_producto = productos.id_producto
        WHERE productos.id_categoria = 1 
        AND ventas.fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'
        GROUP BY productos.nombre, mes
        ORDER BY productos.nombre, mes";

$result = $conn->query($sql);

// Preparar los datos de ventas por producto y mes
$data = [];
while ($row = $result->fetch_assoc()) {
    $producto = $row['nombre'];
    $mes = $row['mes'];
    $cantidad = $row['total_vendido'];
    $data[$producto][$mes] = $cantidad;
}

// Obtener todos los meses en el rango de fechas
$start_month = date('n', strtotime($fecha_inicio));
$end_month = date('n', strtotime($fecha_fin));
$meses_rango = range($start_month, $end_month);

// Inicializar temporadas
$temporadas = [];
foreach ($data as $producto => $ventas_por_mes) {
    // Obtener los meses donde no hubo ventas
    $meses_sin_ventas = array_diff($meses_rango, array_keys($ventas_por_mes));

    // Ordenar por demanda
    arsort($ventas_por_mes);
    $temporadas[$producto]['mayor_demanda'] = array_slice(array_keys($ventas_por_mes), 0, 2);

    // Cálculo de los meses con menor demanda (solo meses sin ventas)
    $meses_menor_demanda = array_keys($ventas_por_mes, 0);
    $temporadas[$producto]['menor_demanda'] = array_merge($meses_sin_ventas, $meses_menor_demanda);

    // Filtrar los meses de menor demanda dentro del rango
    $temporadas[$producto]['menor_demanda'] = array_intersect($temporadas[$producto]['menor_demanda'], $meses_rango);
}

// Generar el PDF
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Tu Nombre');
$pdf->SetTitle('Informe de Análisis de Promociones');
$pdf->SetHeaderData('', 0, 'Informe de Análisis de Promociones', "Fecha del Informe: " . date('Y-m-d'));
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(false);
$pdf->AddPage();
$pdf->Ln(10);

$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 10, 'Informe de Análisis de las Promociones', 0, 1);
$pdf->Cell(0, 10, "Periodo de fecha: $fecha_inicio a $fecha_fin", 0, 1);
$pdf->Ln(10);

$pdf->SetFont('helvetica', '', 10);
foreach ($temporadas as $producto => $temporada) {
    $pdf->Cell(0, 10, "Producto: $producto", 0, 1);
    $pdf->Cell(0, 10, "Temporada de Mayor Demanda: " . mesEspañol($temporada['mayor_demanda']), 0, 1);
    $pdf->Cell(0, 10, "Temporada de Menor Demanda: " . mesEspañol($temporada['menor_demanda']), 0, 1);
    $pdf->Ln(5);
}

$pdf->Output('informe_promociones.pdf', 'I');
$conn->close();

function mesEspañol($meses) {
    $mesesNombre = [
        1 => "Enero", 2 => "Febrero", 3 => "Marzo", 4 => "Abril",
        5 => "Mayo", 6 => "Junio", 7 => "Julio", 8 => "Agosto",
        9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre"
    ];
    $nombres = [];
    foreach ($meses as $mes) {
        $nombres[] = $mesesNombre[$mes];
    }
    return implode(" - ", $nombres);
}
?>
