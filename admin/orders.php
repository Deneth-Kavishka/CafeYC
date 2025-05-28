<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

checkAuth('admin');

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $order_id = $_POST['order_id'];
        $status = $_POST['status'];
        
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt->execute([$status, $order_id])) {
            $success = "Order status updated successfully!";
        } else {
            $error = "Failed to update order status.";
        }
    }
}

// Get orders with customer information
$stmt = $pdo->prepare("
    SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone,
           ca.address as delivery_address
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    LEFT JOIN customer_addresses ca ON o.delivery_address_id = ca.id
    ORDER BY o.created_at DESC
");
$stmt->execute();
$orders = $stmt->fetchAll();

// Get order details if viewing specific order
$order_details = null;
if (isset($_GET['view'])) {
    $order_id = $_GET['view'];
    
    $stmt = $pdo->prepare("
        SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone,
               ca.address as delivery_address
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        LEFT JOIN customer_addresses ca ON o.delivery_address_id = ca.id
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
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

$page_title = "Orders Management - CaféYC Admin";
?>

<?php include '../includes/header.php'; ?>

<body class="bg-light">
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar bg-dark text-white p-3" style="width: 250px; min-height: 100vh;">
            <div class="text-center mb-4">
                <h4 class="fw-bold">
                    <i class="fas fa-coffee me-2"></i>CaféYC Admin
                </h4>
            </div>
            
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white active" href="orders.php">
                        <i class="fas fa-shopping-bag me-2"></i>Orders
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="products.php">
                        <i class="fas fa-box me-2"></i>Products
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="categories.php">
                        <i class="fas fa-tags me-2"></i>Categories
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="brands.php">
                        <i class="fas fa-star me-2"></i>Brands
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="suppliers.php">
                        <i class="fas fa-truck me-2"></i>Suppliers
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="customers.php">
                        <i class="fas fa-users me-2"></i>Customers
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="sliders.php">
                        <i class="fas fa-images me-2"></i>Sliders
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="hot-deals.php">
                        <i class="fas fa-fire me-2"></i>Hot Deals
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="analytics.php">
                        <i class="fas fa-chart-bar me-2"></i>Analytics
                    </a>
                </li>
                <li class="nav-item mt-auto">
                    <a class="nav-link text-white" href="../auth/logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="flex-grow-1">
            <!-- Top Navigation -->
            <nav class="navbar navbar-light bg-white border-bottom px-4">
                <h5 class="mb-0">Orders Management</h5>
                <?php if ($order_details): ?>
                    <a href="orders.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Orders
                    </a>
                <?php endif; ?>
            </nav>

            <!-- Orders Content -->
            <div class="container-fluid p-4">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($order_details): ?>
                    <!-- Order Details View -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Order #<?php echo str_pad($order_details['id'], 6, '0', STR_PAD_LEFT); ?></h5>
                            <div>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?php echo $order_details['id']; ?>">
                                    <select name="status" class="form-select form-select-sm d-inline-block me-2" style="width: auto;" onchange="this.form.submit()">
                                        <option value="pending" <?php echo $order_details['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="processing" <?php echo $order_details['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="completed" <?php echo $order_details['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo $order_details['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </form>
                                <a href="../invoice/generate.php?order_id=<?php echo $order_details['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="fas fa-print me-1"></i>Invoice
                                </a>
                            </div>
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
                                    <h6 class="fw-bold">Customer Information</h6>
                                    <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($order_details['customer_name']); ?></p>
                                    <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order_details['customer_email']); ?></p>
                                    <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($order_details['customer_phone']); ?></p>
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
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Orders List -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-shopping-bag me-2"></i>All Orders (<?php echo count($orders); ?>)
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Customer</th>
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
                                            <td>
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($order['customer_name']); ?></h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                                </div>
                                            </td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
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
                                                <div class="btn-group">
                                                    <a href="?view=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="../invoice/generate.php?order_id=<?php echo $order['id']; ?>" 
                                                       class="btn btn-sm btn-outline-secondary" target="_blank">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>
