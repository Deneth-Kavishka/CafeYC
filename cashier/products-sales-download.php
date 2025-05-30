<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

checkAuth('cashier');

// Determine period filter
$period = $_GET['period'] ?? 'month';
switch ($period) {
    case 'today':
        $start = date('Y-m-d');
        $label = "Today";
        break;
    case '7days':
        $start = date('Y-m-d', strtotime('-6 days'));
        $label = "Last 7 Days";
        break;
    case '3months':
        $start = date('Y-m-d', strtotime('first day of -2 month'));
        $label = "Last 3 Months";
        break;
    case '6months':
        $start = date('Y-m-d', strtotime('first day of -5 month'));
        $label = "Last 6 Months";
        break;
    case '12months':
        $start = date('Y-m-d', strtotime('first day of -11 month'));
        $label = "Last 12 Months";
        break;
    default:
        $start = date('Y-m-01');
        $label = "This Month";
        $period = 'month';
}

// Search filter
$search = trim($_GET['search'] ?? '');
$search_sql = '';
$params = [$start];
if ($search !== '') {
    $search_sql = " AND p.name LIKE ? ";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare("
    SELECT p.name, SUM(oi.quantity) as qty, SUM(oi.total_price) as total
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) >= ? AND o.status != 'cancelled'
    $search_sql
    GROUP BY oi.product_id
    ORDER BY qty DESC
");
$stmt->execute($params);
$products = $stmt->fetchAll();

// Output CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=product-sales-report-' . strtolower(str_replace(' ', '-', $label)) . '-' . date('Ymd_His') . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['#', 'Product', 'Quantity Sold', 'Total Sales (LKR)']);

foreach ($products as $idx => $prod) {
    fputcsv($output, [
        $idx + 1,
        $prod['name'],
        $prod['qty'],
        number_format($prod['total'], 2)
    ]);
}
fclose($output);
exit;
