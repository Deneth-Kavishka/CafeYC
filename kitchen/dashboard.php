<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

checkAuth('kitchen');

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $order_id = $_POST['order_id'];
        $status = $_POST['status'];
        
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt->execute([$status, $order_id])) {
            $success = "Order status updated successfully!";
        }
    }
}

// Get pending and processing orders
$stmt = $pdo->prepare("
    SELECT o.*, u.name as customer_name,
           GROUP_CONCAT(
               CONCAT(oi.quantity, 'x ', p.name) 
               ORDER BY oi.id 
               SEPARATOR ', '
           ) as items_summary
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.status IN ('pending', 'processing')
    GROUP BY o.id
    ORDER BY o.created_at ASC
");
$stmt->execute();
$active_orders = $stmt->fetchAll();

// Get today's completed orders
$stmt = $pdo->prepare("
    SELECT COUNT(*) as completed_orders 
    FROM orders 
    WHERE status = 'completed' AND DATE(created_at) = CURDATE()
");
$stmt->execute();
$completed_today = $stmt->fetchColumn();

// Get kitchen statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
        COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_count,
        AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_prep_time
    FROM orders 
    WHERE DATE(created_at) = CURDATE()
");
$stmt->execute();
$kitchen_stats = $stmt->fetch();

$page_title = "Kitchen Dashboard - CaféYC";
?>

<?php include '../includes/header.php'; ?>

<body class="bg-light">
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar bg-dark text-white p-3" style="width: 250px; min-height: 100vh;">
            <div class="text-center mb-4">
                <h4 class="fw-bold">
                    <i class="fas fa-utensils me-2"></i>CaféYC Kitchen
                </h4>
            </div>
            
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link text-white active" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="orders.php">
                        <i class="fas fa-list me-2"></i>Order Queue
                        <?php if ($kitchen_stats['pending_count'] + $kitchen_stats['processing_count'] > 0): ?>
                            <span class="badge bg-danger ms-2">
                                <?php echo $kitchen_stats['pending_count'] + $kitchen_stats['processing_count']; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="menu.php">
                        <i class="fas fa-book me-2"></i>Menu Items
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="reports.php">
                        <i class="fas fa-chart-bar me-2"></i>Kitchen Reports
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
                <span class="navbar-text">
                    Welcome back, <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong>
                </span>
                <div class="d-flex align-items-center">
                    <span class="text-muted me-3"><?php echo date('l, F j, Y g:i A'); ?></span>
                    <button class="btn btn-outline-primary btn-sm" onclick="refreshOrders()">
                        <i class="fas fa-sync-alt me-1"></i>Refresh
                    </button>
                </div>
            </nav>

            <!-- Dashboard Content -->
            <div class="container-fluid p-4">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Kitchen Statistics -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-warning text-dark h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h2 class="fw-bold mb-0"><?php echo $kitchen_stats['pending_count']; ?></h2>
                                        <p class="mb-0">Pending Orders</p>
                                    </div>
                                    <i class="fas fa-clock fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-info text-white h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h2 class="fw-bold mb-0"><?php echo $kitchen_stats['processing_count']; ?></h2>
                                        <p class="mb-0">In Progress</p>
                                    </div>
                                    <i class="fas fa-fire fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h2 class="fw-bold mb-0"><?php echo $completed_today; ?></h2>
                                        <p class="mb-0">Completed Today</p>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h2 class="fw-bold mb-0"><?php echo round($kitchen_stats['avg_prep_time'] ?? 0); ?></h2>
                                        <p class="mb-0">Avg Prep Time (min)</p>
                                    </div>
                                    <i class="fas fa-stopwatch fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Orders Queue -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-fire me-2"></i>Active Orders Queue
                                </h5>
                                <div>
                                    <span class="text-muted me-3">Auto-refresh in <span id="countdown">30</span>s</span>
                                    <button class="btn btn-outline-primary btn-sm" onclick="refreshOrders()">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($active_orders)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-coffee fa-4x text-muted mb-4"></i>
                                        <h4>All caught up!</h4>
                                        <p class="text-muted">No orders in the queue right now</p>
                                    </div>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($active_orders as $order): ?>
                                            <div class="col-lg-4 col-md-6 mb-4">
                                                <div class="card border-<?php echo $order['status'] === 'pending' ? 'warning' : 'info'; ?> h-100">
                                                    <div class="card-header bg-<?php echo $order['status'] === 'pending' ? 'warning' : 'info'; ?> text-<?php echo $order['status'] === 'pending' ? 'dark' : 'white'; ?>">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <h6 class="mb-0">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h6>
                                                            <span class="badge bg-<?php echo $order['status'] === 'pending' ? 'dark' : 'light'; ?> text-<?php echo $order['status'] === 'pending' ? 'white' : 'dark'; ?>">
                                                                <?php echo ucfirst($order['status']); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="mb-3">
                                                            <strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <strong>Order Time:</strong> 
                                                            <?php 
                                                            $order_time = new DateTime($order['created_at']);
                                                            echo $order_time->format('g:i A');
                                                            $now = new DateTime();
                                                            $diff = $now->diff($order_time);
                                                            $minutes = $diff->i + ($diff->h * 60);
                                                            if ($minutes > 0) {
                                                                echo " <span class='text-muted'>({$minutes} min ago)</span>";
                                                            }
                                                            ?>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <strong>Items:</strong>
                                                            <div class="mt-1">
                                                                <?php echo htmlspecialchars($order['items_summary']); ?>
                                                            </div>
                                                        </div>
                                                        
                                                        <?php if ($order['delivery_notes']): ?>
                                                            <div class="mb-3">
                                                                <strong>Notes:</strong>
                                                                <div class="text-muted small">
                                                                    <?php echo htmlspecialchars($order['delivery_notes']); ?>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="card-footer">
                                                        <div class="d-flex gap-2">
                                                            <?php if ($order['status'] === 'pending'): ?>
                                                                <form method="POST" class="flex-grow-1">
                                                                    <input type="hidden" name="action" value="update_status">
                                                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                                    <input type="hidden" name="status" value="processing">
                                                                    <button type="submit" class="btn btn-info w-100">
                                                                        <i class="fas fa-play me-1"></i>Start Cooking
                                                                    </button>
                                                                </form>
                                                            <?php else: ?>
                                                                <form method="POST" class="flex-grow-1">
                                                                    <input type="hidden" name="action" value="update_status">
                                                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                                    <input type="hidden" name="status" value="completed">
                                                                    <button type="submit" class="btn btn-success w-100">
                                                                        <i class="fas fa-check me-1"></i>Mark Ready
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                            <button class="btn btn-outline-primary" 
                                                                    onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    <!-- Order details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/app.js"></script>
    
    <script>
    let countdownTimer;
    let countdownValue = 30;

    function startCountdown() {
        countdownValue = 30;
        document.getElementById('countdown').textContent = countdownValue;
        
        countdownTimer = setInterval(() => {
            countdownValue--;
            document.getElementById('countdown').textContent = countdownValue;
            
            if (countdownValue <= 0) {
                refreshOrders();
            }
        }, 1000);
    }

    function refreshOrders() {
        clearInterval(countdownTimer);
        location.reload();
    }

    function viewOrderDetails(orderId) {
        // Fetch order details via AJAX
        fetch(`../api/orders.php?action=get_order&id=${orderId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayOrderDetails(data.order);
                } else {
                    alert('Failed to load order details');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading order details');
            });
    }

    function displayOrderDetails(order) {
        const content = `
            <div class="row">
                <div class="col-md-6">
                    <h6 class="fw-bold">Order Information</h6>
                    <p><strong>Order #:</strong> ${String(order.id).padStart(6, '0')}</p>
                    <p><strong>Customer:</strong> ${order.customer_name}</p>
                    <p><strong>Order Time:</strong> ${new Date(order.created_at).toLocaleString()}</p>
                    <p><strong>Status:</strong> <span class="badge bg-info">${order.status}</span></p>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-bold">Order Items</h6>
                    <div class="list-group list-group-flush">
                        ${order.items.map(item => `
                            <div class="list-group-item d-flex justify-content-between">
                                <span>${item.quantity}x ${item.product_name}</span>
                                <span>$${parseFloat(item.unit_price).toFixed(2)}</span>
                            </div>
                        `).join('')}
                    </div>
                    <div class="mt-2">
                        <strong>Total: $${parseFloat(order.total_amount).toFixed(2)}</strong>
                    </div>
                </div>
            </div>
            ${order.delivery_notes ? `
                <div class="row mt-3">
                    <div class="col-12">
                        <h6 class="fw-bold">Special Instructions</h6>
                        <p class="text-muted">${order.delivery_notes}</p>
                    </div>
                </div>
            ` : ''}
        `;
        
        document.getElementById('orderDetailsContent').innerHTML = content;
        new bootstrap.Modal(document.getElementById('orderDetailsModal')).show();
    }

    // Start countdown on page load
    document.addEventListener('DOMContentLoaded', function() {
        startCountdown();
        
        // Add sound notification for new orders (if supported)
        if ('Notification' in window) {
            Notification.requestPermission();
        }
    });

    // Auto-refresh page every 30 seconds
    setTimeout(() => {
        refreshOrders();
    }, 30000);
    </script>
</body>
</html>
