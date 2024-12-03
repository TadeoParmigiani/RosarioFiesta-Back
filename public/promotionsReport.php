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

// Crear el PDF
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Administrador');
$pdf->SetTitle('Informe de Análisis de Promociones');

// Desactivar el pie de página y encabezado predeterminados
$pdf->setPrintHeader(false); 
$pdf->setPrintFooter(false);

// Agregar una nueva página
$pdf->AddPage();

// Ruta del logo
$logo = '../../RosarioFiesta-Front-master/static/img/logo rosario fiesta (1).png'; // Asegúrate de que la ruta es correcta

// Colocar el logo en la parte superior izquierda, sin deformarlo (ajustamos solo la posición)
$pdf->Image($logo, 10, 5, 30, 30);

// Ajustar el margen superior después de agregar el logo
$pdf->SetY(10);

// Fuente general para el informe
$pdf->SetFont('helvetica', '', 14);

// Título principal del informe en color negro
$pdf->SetFont('helvetica', 'B', 20);
$pdf->SetTextColor(0, 0, 0,); // Negro
$pdf->Cell(0, 10, 'Informe de Análisis de Promociones', 0, 1, 'C');
$pdf->Ln(5); // Espacio después del título

// Subtítulo con la fecha en negro
$pdf->SetFont('helvetica', 'I', 14); // Fuente itálica para el subtítulo
$pdf->Cell(0, 10, "Fecha del Informe: " . date('Y-m-d'), 0, 1, 'R');
$pdf->Ln(10); // Espacio después del subtítulo

// Título de la sección en negrita, color negro
$pdf->SetFont('helvetica', 'B', 14);  // Negrita para el título de la sección
$pdf->Cell(0, 10, 'Detalles del Informe', 0, 1, 'L');
$pdf->Ln(5); // Espacio antes de comenzar con los detalles de las promociones

// Información general del periodo
$pdf->SetFont('helvetica', '', 12);  // Fuente normal para la descripción
$pdf->Cell(0, 10, "Periodo de fecha: $fecha_inicio a $fecha_fin", 0, 1, 'L');
$pdf->Ln(10); // Espacio antes de la tabla

// Crear tabla para los detalles de las promociones
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(60, 10, 'Producto', 1, 0, 'C', 0, '', 1); // Columna Producto
$pdf->Cell(60, 10, 'Temporada de Mayor Demanda', 1, 0, 'C', 0, '', 1); // Temporada de mayor demanda
$pdf->Cell(60, 10, 'Temporada de Menor Demanda', 1, 1, 'C', 0, '', 1); // Temporada de menor demanda
$pdf->SetFont('helvetica', '', 10);

// Iterar sobre las temporadas y mostrar los detalles en la tabla
foreach ($temporadas as $producto => $temporada) {
    $pdf->Cell(60, 10, $producto, 1, 0, 'C');
    $pdf->Cell(60, 10, mesEspañol($temporada['mayor_demanda']), 1, 0, 'C');
    $pdf->Cell(60, 10, mesEspañol($temporada['menor_demanda']), 1, 1, 'C');
}

// Finalizar la tabla y agregar un espacio después
$pdf->Ln(10);

// Finalizar el PDF y enviarlo al navegador
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
