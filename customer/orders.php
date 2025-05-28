<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

checkAuth('customer');

// Get user orders
$stmt = $pdo->prepare("
    SELECT o.*, ca.address as delivery_address 
    FROM orders o 
    LEFT JOIN customer_addresses ca ON o.delivery_address_id = ca.id 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// Get order details if viewing specific order
$order_details = null;
if (isset($_GET['view'])) {
    $order_id = $_GET['view'];
    
    $stmt = $pdo->prepare("
        SELECT o.*, ca.address as delivery_address 
        FROM orders o 
        LEFT JOIN customer_addresses ca ON o.delivery_address_id = ca.id 
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order_details = $stmt->fetch();
    
    if ($order_details) {
        $stmt = $pdo->prepare("
            SELECT oi.*, p.name as product_name, p.image_url 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll();
    }
}

$page_title = "My Orders - CaféYC";
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/nav.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <h1 class="fw-bold text-primary mb-4">
                <i class="fas fa-list-alt me-2"></i>My Orders
            </h1>
            
            <?php if (isset($_GET['success']) && $_GET['success'] === 'order_placed'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Order placed successfully!</strong> 
                    <?php if (isset($_GET['order_id'])): ?>
                        Order #<?php echo str_pad($_GET['order_id'], 6, '0', STR_PAD_LEFT); ?> has been confirmed.
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (empty($orders)): ?>
        <div class="text-center py-5">
            <i class="fas fa-clipboard-list fa-4x text-muted mb-4"></i>
            <h3>No orders yet</h3>
            <p class="text-muted mb-4">You haven't placed any orders. Start exploring our menu!</p>
            <a href="shop.php" class="btn btn-primary btn-lg">
                <i class="fas fa-store me-2"></i>Start Shopping
            </a>
        </div>
    <?php else: ?>
        <?php if ($order_details): ?>
            <!-- Order Details View -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Order #<?php echo str_pad($order_details['id'], 6, '0', STR_PAD_LEFT); ?></h5>
                    <a href="orders.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Back to Orders
                    </a>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold">Order Information</h6>
                            <p class="mb-1"><strong>Order Number:</strong> <?php echo htmlspecialchars($order_details['order_number']); ?></p>
                            <p class="mb-1"><strong>Date:</strong> <?php echo date('M j, Y g:i A', strtotime($order_details['created_at'])); ?></p>
                            <p class="mb-1"><strong>Status:</strong> 
                                <?php
                                $status_class = match($order_details['status']) {
                                    'pending' => 'warning',
                                    'processing' => 'info', 
                                    'completed' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'secondary'
                                };
                                ?>
                                <span class="badge bg-<?php echo $status_class; ?>">
                                    <?php echo ucfirst($order_details['status']); ?>
                                </span>
                            </p>
                            <p class="mb-1"><strong>Payment:</strong> <?php echo ucfirst($order_details['payment_method']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold">Delivery Information</h6>
                            <p class="mb-1"><strong>Address:</strong><br><?php echo htmlspecialchars($order_details['delivery_address']); ?></p>
                            <?php if ($order_details['delivery_notes']): ?>
                                <p class="mb-1"><strong>Notes:</strong> <?php echo htmlspecialchars($order_details['delivery_notes']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <h6 class="fw-bold mb-3">Order Items</h6>
                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                 class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;" 
                                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                            <span><?php echo htmlspecialchars($item['product_name']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                                    <td class="text-end">$<?php echo number_format($item['total_price'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                    <td class="text-end">$<?php echo number_format($order_details['subtotal'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Tax:</strong></td>
                                    <td class="text-end">$<?php echo number_format($order_details['tax_amount'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Delivery Fee:</strong></td>
                                    <td class="text-end">$<?php echo number_format($order_details['delivery_fee'], 2); ?></td>
                                </tr>
                                <tr class="table-light">
                                    <td colspan="3" class="text-end"><strong>Total Amount:</strong></td>
                                    <td class="text-end"><strong>$<?php echo number_format($order_details['total_amount'], 2); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="text-end mt-4">
                        <a href="../invoice/generate.php?order_id=<?php echo $order_details['id']; ?>" 
                           class="btn btn-outline-primary" target="_blank">
                            <i class="fas fa-download me-1"></i>Download Invoice
                        </a>
                        <?php if ($order_details['status'] === 'completed'): ?>
                            <a href="feedback.php?order_id=<?php echo $order_details['id']; ?>" 
                               class="btn btn-primary ms-2">
                                <i class="fas fa-star me-1"></i>Rate Order
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Orders List -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td class="fw-bold">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                    <td><?php echo $order['total_items']; ?> items</td>
                                    <td class="fw-bold">$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <?php
                                        $status_class = match($order['status']) {
                                            'pending' => 'warning',
                                            'processing' => 'info',
                                            'completed' => 'success',
                                            'cancelled' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $status_class; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?view=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="../invoice/generate.php?order_id=<?php echo $order['id']; ?>" 
                                           class="btn btn-sm btn-outline-secondary" target="_blank">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
