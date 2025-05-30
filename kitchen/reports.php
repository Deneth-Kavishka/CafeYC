<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

checkAuth('kitchen');

// Date range filter logic
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'today';
switch ($filter) {
    case '7days':
        $from = date('Y-m-d', strtotime('-6 days'));
        $to = date('Y-m-d');
        $label = "Last 7 Days";
        break;
    case '30days':
        $from = date('Y-m-d', strtotime('-29 days'));
        $to = date('Y-m-d');
        $label = "Last 30 Days";
        break;
    case '3months':
        $from = date('Y-m-d', strtotime('-3 months'));
        $to = date('Y-m-d');
        $label = "Last 3 Months";
        break;
    case '6months':
        $from = date('Y-m-d', strtotime('-6 months'));
        $to = date('Y-m-d');
        $label = "Last 6 Months";
        break;
    case 'year':
        $from = date('Y-m-d', strtotime('-1 year'));
        $to = date('Y-m-d');
        $label = "Last Year";
        break;
    default:
        $from = date('Y-m-d');
        $to = date('Y-m-d');
        $label = "Today";
        break;
}

// Get summary stats
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_orders,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_orders,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) AS processing_orders,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_orders,
        AVG(CASE WHEN status = 'completed' THEN TIMESTAMPDIFF(MINUTE, created_at, updated_at) END) AS avg_prep_time
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$stmt->execute([$from, $to]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get completed orders for range
$stmt = $pdo->prepare("
    SELECT o.*, u.name AS customer_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.status = 'completed' AND DATE(o.created_at) BETWEEN ? AND ?
    ORDER BY o.updated_at DESC
");
$stmt->execute([$from, $to]);
$completed_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get most popular items in range
$stmt = $pdo->prepare("
    SELECT p.name, SUM(oi.quantity) AS total_qty
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.status = 'completed'
    GROUP BY oi.product_id
    ORDER BY total_qty DESC
    LIMIT 5
");
$stmt->execute([$from, $to]);
$popular_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get queue order count for sidebar badge
$queue_count = 0;
$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','processing')");
$queue_count = $stmt->fetchColumn();

$page_title = "Kitchen Reports - CaféYC";
?>
<?php include '../includes/header.php'; ?>
<body class="bg-light">
    <div class="d-flex">
        <!-- Sidebar (same as dashboard) -->
        <nav class="sidebar bg-dark text-white p-3" style="width: 250px; min-height: 100vh;">
            <div class="text-center mb-4">
                <h4 class="fw-bold">
                    <i class="fas fa-utensils me-2"></i>CaféYC Kitchen
                </h4>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link text-white<?php if(basename($_SERVER['PHP_SELF']) == 'dashboard.php') echo ' active'; ?>" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white<?php if(basename($_SERVER['PHP_SELF']) == 'orders.php') echo ' active'; ?>" href="orders.php">
                        <i class="fas fa-list me-2"></i>Order Queue
                        <?php if ($queue_count > 0): ?>
                            <span class="badge bg-danger ms-2"><?php echo $queue_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white<?php if(basename($_SERVER['PHP_SELF']) == 'menu.php') echo ' active'; ?>" href="menu.php">
                        <i class="fas fa-book me-2"></i>Menu Items
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white<?php if(basename($_SERVER['PHP_SELF']) == 'reports.php') echo ' active'; ?>" href="reports.php">
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
            <div class="container py-4">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <h2 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Kitchen Reports</h2>
                    <form method="get" class="d-flex align-items-center gap-2">
                        <label class="me-2 fw-semibold text-secondary">Filter:</label>
                        <select name="filter" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="today" <?php if($filter=='today') echo 'selected'; ?>>Today</option>
                            <option value="7days" <?php if($filter=='7days') echo 'selected'; ?>>Last 7 Days</option>
                            <option value="30days" <?php if($filter=='30days') echo 'selected'; ?>>Last 30 Days</option>
                            <option value="3months" <?php if($filter=='3months') echo 'selected'; ?>>Last 3 Months</option>
                            <option value="6months" <?php if($filter=='6months') echo 'selected'; ?>>Last 6 Months</option>
                            <option value="year" <?php if($filter=='year') echo 'selected'; ?>>Last Year</option>
                        </select>
                    </form>
                </div>
                <div class="mb-3 text-end text-muted">
                    <span class="badge bg-light text-dark border"><?php echo $label; ?> (<?php echo $from; ?> to <?php echo $to; ?>)</span>
                </div>
                <div class="row mb-4 g-3">
                    <div class="col-md-3">
                        <div class="card text-center shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="card-title text-primary"><i class="fas fa-receipt me-1"></i> Total Orders</h5>
                                <h2 class="fw-bold"><?php echo $stats['total_orders'] ?? 0; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="card-title text-success"><i class="fas fa-check-circle me-1"></i> Completed</h5>
                                <h2 class="fw-bold"><?php echo $stats['completed_orders'] ?? 0; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="card-title text-warning"><i class="fas fa-clock me-1"></i> Pending</h5>
                                <h2 class="fw-bold"><?php echo $stats['pending_orders'] ?? 0; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="card-title text-info"><i class="fas fa-fire me-1"></i> In Progress</h5>
                                <h2 class="fw-bold"><?php echo $stats['processing_orders'] ?? 0; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mb-4 g-3">
                    <div class="col-md-3">
                        <div class="card text-center shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="card-title text-danger"><i class="fas fa-times-circle me-1"></i> Cancelled</h5>
                                <h2 class="fw-bold"><?php echo $stats['cancelled_orders'] ?? 0; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="card-title text-secondary"><i class="fas fa-stopwatch me-1"></i> Avg Prep Time</h5>
                                <h2 class="fw-bold"><?php echo is_null($stats['avg_prep_time']) ? '-' : round($stats['avg_prep_time']); ?> <span class="fs-6">min</span></h2>
                            </div>
                        </div>
                    </div>
                <!--    <div class="col-md-6">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="card-title mb-3"><i class="fas fa-star me-2"></i>Top 5 Popular Items</h5>
                                <?php if (empty($popular_items)): ?>
                                    <div class="text-muted">No data</div>
                                <?php else: ?>
                                    <ol class="mb-0">
                                        <?php foreach ($popular_items as $item): ?>
                                            <li>
                                                <span class="fw-semibold"><?php echo htmlspecialchars($item['name']); ?></span>
                                                <span class="text-muted">(<?php echo $item['total_qty']; ?> sold)</span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ol>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>-->
                </div>
                <div class="card shadow-sm mb-4 border-0">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Completed Orders<?php echo $label !== "Today" ? " ($label)" : " Today"; ?></h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($completed_orders)): ?>
                            <div class="p-4 text-center text-muted">No completed orders in this period.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Customer</th>
                                            <th>Order Time</th>
                                            <th>Completed At</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($completed_orders as $order): ?>
                                            <tr>
                                                <td><?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                <td><?php echo date('Y-m-d g:i A', strtotime($order['created_at'])); ?></td>
                                                <td><?php echo date('Y-m-d g:i A', strtotime($order['updated_at'])); ?></td>
                                                <td><?php echo $order['delivery_notes'] ? htmlspecialchars($order['delivery_notes']) : '-'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
