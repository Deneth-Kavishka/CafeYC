<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

checkAuth('admin');

// Get dashboard statistics
$stats = [];

// Total orders
$stmt = $pdo->query("SELECT COUNT(*) as total_orders FROM orders");
$stats['total_orders'] = $stmt->fetchColumn();

// Today's orders
$stmt = $pdo->query("SELECT COUNT(*) as today_orders FROM orders WHERE DATE(created_at) = CURDATE()");
$stats['today_orders'] = $stmt->fetchColumn();

// Total revenue
$stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total_revenue FROM orders WHERE status = 'completed'");
$stats['total_revenue'] = $stmt->fetchColumn();

// Today's revenue
$stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as today_revenue FROM orders WHERE status = 'completed' AND DATE(created_at) = CURDATE()");
$stats['today_revenue'] = $stmt->fetchColumn();

// Total customers
$stmt = $pdo->query("SELECT COUNT(*) as total_customers FROM users WHERE role = 'customer'");
$stats['total_customers'] = $stmt->fetchColumn();

// Total products
$stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products WHERE is_active = 1");
$stats['total_products'] = $stmt->fetchColumn();

// Pending orders
$stmt = $pdo->query("SELECT COUNT(*) as pending_orders FROM orders WHERE status = 'pending'");
$stats['pending_orders'] = $stmt->fetchColumn();

// Get recent orders
$stmt = $pdo->prepare("
    SELECT o.*, u.name as customer_name, 
        (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) as total_items
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 10
");
$stmt->execute();
$recent_orders = $stmt->fetchAll();

// Fetch order items for each recent order (with product name)
$order_items_map = [];
if ($recent_orders) {
    $order_ids = array_column($recent_orders, 'id');
    $in = str_repeat('?,', count($order_ids) - 1) . '?';
    $stmt = $pdo->prepare("
        SELECT oi.order_id, p.name as product_name, oi.quantity
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id IN ($in)
    ");
    $stmt->execute($order_ids);
    foreach ($stmt->fetchAll() as $item) {
        $order_items_map[$item['order_id']][] = $item;
    }
}

// Get top products
$stmt = $pdo->prepare("
    SELECT p.name, p.image_url, SUM(oi.quantity) as total_sold, SUM(oi.total_price) as total_revenue
    FROM products p
    JOIN order_items oi ON p.id = oi.product_id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'completed'
    GROUP BY p.id, p.name, p.image_url
    ORDER BY total_sold DESC
    LIMIT 5
");
$stmt->execute();
$top_products = $stmt->fetchAll();

// Get monthly revenue data for chart
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        SUM(total_amount) as revenue
    FROM orders 
    WHERE status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month
");
$stmt->execute();
$monthly_revenue = $stmt->fetchAll();

// Add filter logic for revenue chart
$revenue_filter = isset($_GET['revenue_filter']) ? $_GET['revenue_filter'] : 'monthly';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

$custom_range = ($from_date && $to_date);

if ($custom_range) {
    $revenue_label = "Revenue (" . htmlspecialchars($from_date) . " to " . htmlspecialchars($to_date) . ")";
    $revenue_sql = "
        SELECT DATE(created_at) as period, SUM(total_amount) as revenue
        FROM orders
        WHERE status = 'completed' AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY period
    ";
    $stmt = $pdo->prepare($revenue_sql);
    $stmt->execute([$from_date, $to_date]);
    $revenue_chart_data = $stmt->fetchAll();
    $chart_type = 'bar';
} else {
    switch ($revenue_filter) {
        case 'daily':
            $revenue_label = "Daily Revenue (Last 30 Days)";
            $revenue_sql = "
                SELECT DATE(created_at) as period, SUM(total_amount) as revenue
                FROM orders
                WHERE status = 'completed' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
                GROUP BY DATE(created_at)
                ORDER BY period
            ";
            $chart_type = 'bar';
            break;
        case 'weekly':
            $revenue_label = "Weekly Revenue (Last 12 Weeks)";
            $revenue_sql = "
                SELECT DATE_FORMAT(created_at, '%x-%v') as period, SUM(total_amount) as revenue
                FROM orders
                WHERE status = 'completed' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 WEEK)
                GROUP BY DATE_FORMAT(created_at, '%x-%v')
                ORDER BY period
            ";
            $chart_type = 'line';
            break;
        case 'annually':
            $revenue_label = "Annual Revenue (Last 5 Years)";
            $revenue_sql = "
                SELECT DATE_FORMAT(created_at, '%Y') as period, SUM(total_amount) as revenue
                FROM orders
                WHERE status = 'completed' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR)
                GROUP BY DATE_FORMAT(created_at, '%Y')
                ORDER BY period
            ";
            $chart_type = 'line';
            break;
        case 'monthly':
        default:
            $revenue_label = "Monthly Revenue (Last 12 Months)";
            $revenue_sql = "
                SELECT DATE_FORMAT(created_at, '%Y-%m') as period, SUM(total_amount) as revenue
                FROM orders
                WHERE status = 'completed' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY period
            ";
            $chart_type = 'line';
            break;
    }
    $stmt = $pdo->prepare($revenue_sql);
    $stmt->execute();
    $revenue_chart_data = $stmt->fetchAll();
}

$page_title = "Admin Dashboard - CaféYC";
$extra_css = [
    "https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css"
];
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
                    <a class="nav-link text-white active" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="orders.php">
                        <i class="fas fa-shopping-bag me-2"></i>Orders
                        <?php if ($stats['pending_orders'] > 0): ?>
                            <span class="badge bg-danger ms-2"><?php echo $stats['pending_orders']; ?></span>
                        <?php endif; ?>
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
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="users.php">
                        <i class="fas fa-user-cog me-2"></i>System Users
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="feedbacks.php">
                        <i class="fas fa-comments me-2"></i>Customer Feedback
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
                    <span class="text-muted me-3"><?php echo date('l, F j, Y'); ?></span>
                    <a href="../" class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener">
                        <i class="fas fa-eye me-1"></i>View Site
                    </a>
                </div>
            </nav>
            <!-- Dashboard Content -->
            <div class="container-fluid p-4">
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h2 class="fw-bold mb-0"><?php echo number_format($stats['total_orders']); ?></h2>
                                        <p class="mb-0">Total Orders</p>
                                        <small class="opacity-75">
                                            <?php echo $stats['today_orders']; ?> today
                                        </small>
                                    </div>
                                    <i class="fas fa-shopping-bag fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h2 class="fw-bold mb-0">LKR <?php echo number_format($stats['total_revenue'], 0); ?></h2>
                                        <p class="mb-0">Total Revenue</p>
                                        <small class="opacity-75">
                                            LKR <?php echo number_format($stats['today_revenue'], 2); ?> today
                                        </small>
                                    </div>
                                    <i class="fas fa-dollar-sign fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-info text-white h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h2 class="fw-bold mb-0"><?php echo number_format($stats['total_customers']); ?></h2>
                                        <p class="mb-0">Customers</p>
                                        <small class="opacity-75">Registered users</small>
                                    </div>
                                    <i class="fas fa-users fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-warning text-white h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h2 class="fw-bold mb-0"><?php echo number_format($stats['total_products']); ?></h2>
                                        <p class="mb-0">Products</p>
                                        <small class="opacity-75">Active items</small>
                                    </div>
                                    <i class="fas fa-box fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-lg-8">
                        <div class="card h-100">
                            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div>
                                    <h5 class="mb-0">
                                        <i class="fas fa-chart-line me-2"></i><?php echo $revenue_label; ?>
                                    </h5>
                                </div>
                                <form method="get" class="d-flex align-items-center gap-2 flex-wrap" style="font-size: 0.95rem;">
                                    <label class="me-2 fw-semibold text-secondary mb-0">View:</label>
                                    <select name="revenue_filter" class="form-select form-select-sm" style="width:120px;" onchange="this.form.submit()">
                                        <option value="daily" <?php if($revenue_filter=='daily') echo 'selected'; ?>>Daily</option>
                                        <option value="weekly" <?php if($revenue_filter=='weekly') echo 'selected'; ?>>Weekly</option>
                                        <option value="monthly" <?php if($revenue_filter=='monthly') echo 'selected'; ?>>Monthly</option>
                                        <option value="annually" <?php if($revenue_filter=='annually') echo 'selected'; ?>>Annually</option>
                                    </select>
                                    <span class="mx-2 text-secondary" style="font-size:0.9em;">or</span>
                                    <input type="date" name="from_date" class="form-control form-control-sm" style="width:140px;" value="<?php echo htmlspecialchars($from_date); ?>" max="<?php echo date('Y-m-d'); ?>">
                                    <span class="mx-1" style="font-size:0.9em;">to</span>
                                    <input type="date" name="to_date" class="form-control form-control-sm" style="width:140px;" value="<?php echo htmlspecialchars($to_date); ?>" max="<?php echo date('Y-m-d'); ?>">
                                    <button type="submit" class="btn btn-sm btn-primary ms-2 px-3" style="font-size:0.95em;">Apply</button>
                                    <?php if ($custom_range): ?>
                                        <a href="dashboard.php" class="btn btn-sm btn-outline-secondary ms-2 px-3" style="font-size:0.95em;">Clear</a>
                                    <?php endif; ?>
                                </form>
                            </div>
                            <div class="card-body">
                                <canvas id="revenueChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-trophy me-2"></i>Top Products
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($top_products as $product): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                         class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($product['name']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo $product['total_sold']; ?> sold • 
                                            LKR <?php echo number_format($product['total_revenue'], 2); ?>
                                        </small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-clock me-2"></i>Recent Orders
                                </h5>
                                <a href="orders.php" class="btn btn-primary btn-sm">View All</a>
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
                                                <th>Qty</th>
                                                <th>Discount</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td class="fw-bold">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                <td><?php echo date('M j, g:i A', strtotime($order['created_at'])); ?></td>
                                                <td>
                                                    <?php
                                                    if (!empty($order_items_map[$order['id']])) {
                                                        foreach ($order_items_map[$order['id']] as $item) {
                                                            echo htmlspecialchars($item['product_name']) . '</span><br>';
                                                        }
                                                    } else {
                                                        echo '<span class="text-muted">-</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo $order['total_items']; ?></td>
                                                <td>
                                                    <?php echo 'LKR ' . number_format($order['discount_amount'], 2); ?>
                                                </td>
                                                <td class="fw-bold">LKR <?php echo number_format($order['total_amount'], 2); ?></td>
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
                                                    <a href="orders.php?view=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    
    <script>
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const revenueData = <?php echo json_encode($revenue_chart_data); ?>;
    const filterType = "<?php echo $revenue_filter; ?>";
    const chartType = "<?php echo $chart_type; ?>";
    let labels = [];
    if ("<?php echo $custom_range ? '1' : ''; ?>" === "1") {
        labels = revenueData.map(item => {
            const date = new Date(item.period);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        });
    } else if (filterType === 'daily') {
        labels = revenueData.map(item => {
            const date = new Date(item.period);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });
    } else if (filterType === 'weekly') {
        labels = revenueData.map(item => {
            const [year, week] = item.period.split('-');
            return 'W' + week + ' ' + year;
        });
    } else if (filterType === 'annually') {
        labels = revenueData.map(item => item.period);
    } else {
        // monthly
        labels = revenueData.map(item => {
            const date = new Date(item.period + '-01');
            return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        });
    }

    new Chart(revenueCtx, {
        type: chartType,
        data: {
            labels: labels,
            datasets: [{
                label: 'Revenue',
                data: revenueData.map(item => item.revenue),
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.3)',
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'LKR ' + Number(context.parsed.y).toLocaleString();
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'LKR ' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
    </script>
</body>
</html>
