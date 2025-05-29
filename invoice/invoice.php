<?php
session_start();
require_once '../config/database.php';

// Get order ID and validate access
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if (!$order_id) {
    die('Invalid order.');
}

// Fetch order and user info
$stmt = $pdo->prepare("
    SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    die('Order not found.');
}

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name as product_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice - Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">CaféYC Invoice</h2>
        <button class="btn btn-primary no-print" onclick="window.print()">
            <i class="fas fa-print me-1"></i>Print / Save PDF
        </button>
    </div>
    <div class="row mb-2">
        <div class="col-6">
            <strong>CaféYC</strong><br>
            Colombo, Sri Lanka<br>
            +94 77 123 4567<br>
            info@cafeyc.com
        </div>
        <div class="col-6 text-end">
            <strong>Date:</strong> <?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?><br>
            <strong>Order #:</strong> <?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>
        </div>
    </div>
    <hr>
    <div class="row mb-2">
        <div class="col-6">
            <strong>Bill To:</strong><br>
            <?php echo htmlspecialchars($order['customer_name']); ?><br>
            <?php echo htmlspecialchars($order['customer_email']); ?><br>
            <?php echo htmlspecialchars($order['customer_phone']); ?><br>
            <?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?>
        </div>
        <div class="col-6 text-end">
            <strong>Status:</strong> <?php echo ucfirst($order['status']); ?><br>
            <strong>Payment:</strong> <?php echo ucfirst($order['payment_method']); ?>
        </div>
    </div>
    <hr>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Unit Price</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($order_items as $idx => $item): ?>
            <tr>
                <td><?php echo $idx + 1; ?></td>
                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td class="text-end"><?php echo $item['quantity']; ?></td>
                <td class="text-end">LKR <?php echo number_format($item['unit_price'], 2); ?></td>
                <td class="text-end">LKR <?php echo number_format($item['total_price'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                <td class="text-end">LKR <?php echo number_format($order['subtotal'], 2); ?></td>
            </tr>
            <tr>
                <td colspan="4" class="text-end"><strong>Tax:</strong></td>
                <td class="text-end">LKR <?php echo number_format($order['tax_amount'], 2); ?></td>
            </tr>
                <tr>
                <td colspan="4" class="text-end"><strong>Delivery:</strong></td>
                <td class="text-end">LKR <?php echo number_format($order['delivery_amount'], 2); ?></td>
            </tr>
            <tr>
                <td colspan="4" class="text-end"><strong>Discount:</strong></td>
                <td class="text-end">LKR <?php echo number_format($order['discount_amount'], 2); ?></td>
            </tr>
            <tr>
                <td colspan="4" class="text-end"><strong>Total:</strong></td>
                <td class="text-end"><strong>LKR <?php echo number_format($order['total_amount'], 2); ?></strong></td>
            </tr>
        </tfoot>
    </table>
    <div class="text-center mt-3">
        <small>Thank you for your order!</small>
    </div>
</div>
<!-- FontAwesome for print icon -->
<script src="https://kit.fontawesome.com/4e8e8e6e8e.js" crossorigin="anonymous"></script>
</body>
</html>
