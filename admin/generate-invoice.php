<?php
session_start();
require_once '../config.php';
require_once '../vendor/autoload.php'; // Make sure TCPDF is installed via Composer

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit();
}

$order_id = $_GET['id'];

try {
    // Get order details
    $stmt = $conn->prepare("
        SELECT 
            o.*, 
            u.full_name as customer_name,
            u.phone as customer_phone,
            u.email as customer_email,
            a.address_line1,
            a.address_line2,
            a.city,
            a.state,
            a.pincode
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        LEFT JOIN addresses a ON o.address_id = a.id
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        header('Location: orders.php');
        exit();
    }

    // Get order items
    $stmt = $conn->prepare("
        SELECT 
            p.name,
            oi.quantity,
            p.calories,
            p.protein,
            p.carbs,
            p.fat,
            p.fiber,
            (p.price * oi.quantity) as price
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();

    // Create new PDF document
    $pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('BighatMeal Admin Panel');
    $pdf->SetAuthor('BighatMeal');
    $pdf->SetTitle('Order Invoice');
    $pdf->SetSubject('Order Invoice');
    $pdf->SetKeywords('BighatMeal, Invoice, Order');

    // Add a page
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->Cell(0, 10, 'BighatMeal', 0, 1, 'C');
    $pdf->Ln(10);

    // Invoice Header
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'INVOICE', 0, 1, 'C');
    $pdf->Ln(5);

    // Invoice Details
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Invoice Number: ' . $order['invoice_number'], 0, 1);
    $pdf->Cell(0, 10, 'Date: ' . date('d/m/Y', strtotime($order['created_at'])), 0, 1);
    $pdf->Ln(5);

    // Customer Details
    $pdf->Cell(0, 10, 'Customer Details:', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 10, 'Name: ' . $order['customer_name'], 0, 1);
    $pdf->Cell(0, 10, 'Phone: ' . $order['customer_phone'], 0, 1);
    $pdf->Cell(0, 10, 'Email: ' . $order['customer_email'], 0, 1);
    $pdf->Cell(0, 10, 'Address: ' . $order['address_line1'] . ', ' . $order['city'] . ', ' . $order['state'] . ' - ' . $order['pincode'], 0, 1);
    $pdf->Ln(10);

    // Order Items Table
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(60, 10, 'Item', 1, 0, 'C');
    $pdf->Cell(30, 10, 'Quantity', 1, 0, 'C');
    $pdf->Cell(30, 10, 'Calories', 1, 0, 'C');
    $pdf->Cell(30, 10, 'Protein', 1, 0, 'C');
    $pdf->Cell(30, 10, 'Carbs', 1, 0, 'C');
    $pdf->Cell(30, 10, 'Fat', 1, 0, 'C');
    $pdf->Cell(30, 10, 'Fiber', 1, 0, 'C');
    $pdf->Cell(30, 10, 'Price', 1, 1, 'C');

    $pdf->SetFont('helvetica', '', 10);
    foreach ($items as $item) {
        $pdf->Cell(60, 10, $item['name'], 1, 0, 'L');
        $pdf->Cell(30, 10, $item['quantity'] . 'g', 1, 0, 'C');
        $pdf->Cell(30, 10, $item['calories'] . ' cal', 1, 0, 'C');
        $pdf->Cell(30, 10, $item['protein'] . 'g', 1, 0, 'C');
        $pdf->Cell(30, 10, $item['carbs'] . 'g', 1, 0, 'C');
        $pdf->Cell(30, 10, $item['fat'] . 'g', 1, 0, 'C');
        $pdf->Cell(30, 10, $item['fiber'] . 'g', 1, 0, 'C');
        $pdf->Cell(30, 10, '₹' . number_format($item['price'], 2), 1, 1, 'R');
    }

    // Total
    $pdf->Cell(240, 10, 'Total: ₹' . number_format($order['total_amount'], 2), 1, 1, 'R');

    // Output the PDF
    $pdf->Output('I', 'Invoice_' . $order['order_number'] . '.pdf');

} catch(PDOException $e) {
    header('Location: orders.php');
    exit();
}
?>
