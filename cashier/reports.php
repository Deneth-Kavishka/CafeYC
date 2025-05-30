<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

checkAuth('cashier');
$page_title = "Sales Reports - CaféYC";

// Date ranges
$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week'));
$month_start = date('Y-m-01');

// Daily sales
$stmt = $pdo->prepare("SELECT COUNT(*) as orders, COALESCE(SUM(total_amount),0) as sales FROM orders WHERE DATE(created_at) = ? AND status != 'cancelled'");
$stmt->execute([$today]);
$daily = $stmt->fetch();

// Weekly sales
$stmt = $pdo->prepare("SELECT COUNT(*) as orders, COALESCE(SUM(total_amount),0) as sales FROM orders WHERE DATE(created_at) >= ? AND status != 'cancelled'");
$stmt->execute([$week_start]);
$weekly = $stmt->fetch();

// Monthly sales
$stmt = $pdo->prepare("SELECT COUNT(*) as orders, COALESCE(SUM(total_amount),0) as sales FROM orders WHERE DATE(created_at) >= ? AND status != 'cancelled'");
$stmt->execute([$month_start]);
$monthly = $stmt->fetch();

// Top 5 products this month
$stmt = $pdo->prepare("
    SELECT p.name, SUM(oi.quantity) as qty, SUM(oi.total_price) as total
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) >= ? AND o.status != 'cancelled'
    GROUP BY oi.product_id
    ORDER BY qty DESC
    LIMIT 5
");
$stmt->execute([$month_start]);
$top_products = $stmt->fetchAll();

// All products sales for this month (for "View All" link)
$stmt = $pdo->prepare("
    SELECT p.name, SUM(oi.quantity) as qty, SUM(oi.total_price) as total
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) >= ? AND o.status != 'cancelled'
    GROUP BY oi.product_id
    ORDER BY qty DESC
");
$stmt->execute([$month_start]);
$all_products_month = $stmt->fetchAll();

// Sales trend (last 7 days)
$stmt = $pdo->prepare("
    SELECT DATE(created_at) as day, COALESCE(SUM(total_amount),0) as sales
    FROM orders
    WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND status != 'cancelled'
    GROUP BY day
    ORDER BY day ASC
");
$stmt->execute();
$sales_trend = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Prepare datasets for chart filters

// 1. Today (hourly sales)
$stmt = $pdo->prepare("
    SELECT HOUR(created_at) as hour, COALESCE(SUM(total_amount),0) as sales
    FROM orders
    WHERE DATE(created_at) = ? AND status != 'cancelled'
    GROUP BY hour
    ORDER BY hour ASC
");
$stmt->execute([$today]);
$hourly_sales = array_fill(0, 24, 0);
foreach ($stmt->fetchAll() as $row) {
    $hourly_sales[(int)$row['hour']] = (float)$row['sales'];
}

// 2. Last 7 days (daily)
$stmt = $pdo->prepare("
    SELECT DATE(created_at) as day, COALESCE(SUM(total_amount),0) as sales
    FROM orders
    WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND status != 'cancelled'
    GROUP BY day
    ORDER BY day ASC
");
$stmt->execute();
$sales_7days = [];
$period = new DatePeriod(
    new DateTime('-6 days'),
    new DateInterval('P1D'),
    (new DateTime())->modify('+1 day')
);
foreach ($period as $dt) {
    $sales_7days[$dt->format('Y-m-d')] = 0;
}
foreach ($stmt->fetchAll() as $row) {
    $sales_7days[$row['day']] = (float)$row['sales'];
}

// 3. Last 3 months (monthly)
$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COALESCE(SUM(total_amount),0) as sales
    FROM orders
    WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 2 MONTH) AND status != 'cancelled'
    GROUP BY month
    ORDER BY month ASC
");
$stmt->execute();
$sales_3months = [];
$period = new DatePeriod(
    (new DateTime('first day of -2 month'))->setTime(0,0),
    new DateInterval('P1M'),
    (new DateTime('first day of next month'))->setTime(0,0)
);
foreach ($period as $dt) {
    $sales_3months[$dt->format('Y-m')] = 0;
}
foreach ($stmt->fetchAll() as $row) {
    $sales_3months[$row['month']] = (float)$row['sales'];
}

// 4. Last 6 months (monthly)
$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COALESCE(SUM(total_amount),0) as sales
    FROM orders
    WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH) AND status != 'cancelled'
    GROUP BY month
    ORDER BY month ASC
");
$stmt->execute();
$sales_6months = [];
$period = new DatePeriod(
    (new DateTime('first day of -5 month'))->setTime(0,0),
    new DateInterval('P1M'),
    (new DateTime('first day of next month'))->setTime(0,0)
);
foreach ($period as $dt) {
    $sales_6months[$dt->format('Y-m')] = 0;
}
foreach ($stmt->fetchAll() as $row) {
    $sales_6months[$row['month']] = (float)$row['sales'];
}

// 5. Last 12 months (monthly)
$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COALESCE(SUM(total_amount),0) as sales
    FROM orders
    WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH) AND status != 'cancelled'
    GROUP BY month
    ORDER BY month ASC
");
$stmt->execute();
$sales_12months = [];
$period = new DatePeriod(
    (new DateTime('first day of -11 month'))->setTime(0,0),
    new DateInterval('P1M'),
    (new DateTime('first day of next month'))->setTime(0,0)
);
foreach ($period as $dt) {
    $sales_12months[$dt->format('Y-m')] = 0;
}
foreach ($stmt->fetchAll() as $row) {
    $sales_12months[$row['month']] = (float)$row['sales'];
}

include '../includes/header.php';
?>

<body class="bg-light">
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar bg-dark text-white p-3" style="width: 250px; min-height: 100vh;">
            <div class="text-center mb-4">
                <h4 class="fw-bold">
                    <i class="fas fa-coffee me-2"></i>CaféYC Cashier
                </h4>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="pos.php">
                        <i class="fas fa-cash-register me-2"></i>POS System
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="orders.php">
                        <i class="fas fa-shopping-bag me-2"></i>Orders
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="customers.php">
                        <i class="fas fa-users me-2"></i>Customers
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white active" href="reports.php">
                        <i class="fas fa-chart-bar me-2"></i>Sales Reports
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
            <nav class="navbar navbar-light bg-white border-bottom px-4">
                <h5 class="mb-0">Sales Reports</h5>
            </nav>
            <div class="container-fluid p-4">
                <div class="row mb-4">
                    <div class="col-lg-4 col-md-6 mb-3">
                        <div class="card border-primary">
                            <div class="card-body">
                                <h6 class="text-primary">Today</h6>
                                <h2 class="fw-bold mb-0">LKR <?php echo number_format($daily['sales'], 2); ?></h2>
                                <p class="mb-0 text-muted"><?php echo $daily['orders']; ?> Orders</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-3">
                        <div class="card border-success">
                            <div class="card-body">
                                <h6 class="text-success">This Week</h6>
                                <h2 class="fw-bold mb-0">LKR <?php echo number_format($weekly['sales'], 2); ?></h2>
                                <p class="mb-0 text-muted"><?php echo $weekly['orders']; ?> Orders</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-3">
                        <div class="card border-info">
                            <div class="card-body">
                                <h6 class="text-info">This Month</h6>
                                <h2 class="fw-bold mb-0">LKR <?php echo number_format($monthly['sales'], 2); ?></h2>
                                <p class="mb-0 text-muted"><?php echo $monthly['orders']; ?> Orders</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Sales Trend Chart -->
                <div class="row mb-4">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Sales Trend</h6>
                                <select id="trendFilter" class="form-select form-select-sm" style="width:auto;">
                                    <option value="today">Today (Hourly)</option>
                                    <option value="7days" selected>Last 7 Days</option>
                                    <option value="3months">Last 3 Months</option>
                                    <option value="6months">Last 6 Months</option>
                                    <option value="12months">Last 12 Months</option>
                                </select>
                            </div>
                            <div class="card-body">
                                <canvas id="salesTrendChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                    <!-- Top Products -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>
                                    <h6 class="mb-0 d-inline"><i class="fas fa-star me-2"></i>Top 5 Products (This Month)</h6>
                                </span>
                                <a href="products-sales.php?period=month" id="viewAllProductsLink" class="small" target="_self">View All</a>
                            </div>
                            <div class="card-body">
                                <ul class="list-group">
                                    <?php foreach ($top_products as $prod): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?php echo htmlspecialchars($prod['name']); ?>
                                        <span class="badge bg-primary rounded-pill"><?php echo $prod['qty']; ?> sold</span>
                                    </li>
                                    <?php endforeach; ?>
                                    <?php if (empty($top_products)): ?>
                                    <li class="list-group-item text-muted text-center">No sales yet.</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Monthly Summary Table -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-table me-2"></i>Monthly Sales Summary</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Orders</th>
                                    <th>Sales (LKR)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Get daily sales for this month
                                $stmt = $pdo->prepare("
                                    SELECT DATE(created_at) as day, COUNT(*) as orders, COALESCE(SUM(total_amount),0) as sales
                                    FROM orders
                                    WHERE DATE(created_at) >= ? AND status != 'cancelled'
                                    GROUP BY day
                                    ORDER BY day DESC
                                ");
                                $stmt->execute([$month_start]);
                                $month_days = $stmt->fetchAll();
                                foreach ($month_days as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['day']); ?></td>
                                    <td><?php echo $row['orders']; ?></td>
                                    <td><?php echo number_format($row['sales'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($month_days)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No sales data for this month.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Chart.js for sales trend -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Prepare all datasets for chart filters
    const trendData = {
        today: {
            labels: <?php echo json_encode(array_map(fn($h) => sprintf('%02d:00', $h), range(0,23))); ?>,
            data: <?php echo json_encode(array_values($hourly_sales)); ?>
        },
        '7days': {
            labels: <?php echo json_encode(array_keys($sales_7days)); ?>,
            data: <?php echo json_encode(array_values($sales_7days)); ?>
        },
        '3months': {
            labels: <?php echo json_encode(array_keys($sales_3months)); ?>,
            data: <?php echo json_encode(array_values($sales_3months)); ?>
        },
        '6months': {
            labels: <?php echo json_encode(array_keys($sales_6months)); ?>,
            data: <?php echo json_encode(array_values($sales_6months)); ?>
        },
        '12months': {
            labels: <?php echo json_encode(array_keys($sales_12months)); ?>,
            data: <?php echo json_encode(array_values($sales_12months)); ?>
        }
    };

    let currentType = '7days';
    const ctx = document.getElementById('salesTrendChart').getContext('2d');
    let salesTrendChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: trendData[currentType].labels,
            datasets: [
                {
                    type: 'bar',
                    label: 'Sales (LKR)',
                    data: trendData[currentType].data,
                    backgroundColor: 'rgba(0,123,255,0.3)',
                    borderColor: '#007bff',
                    borderWidth: 1
                },
                {
                    type: 'line',
                    label: 'Sales (LKR)',
                    data: trendData[currentType].data,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0,123,255,0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 4,
                    pointBackgroundColor: '#007bff'
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    document.getElementById('trendFilter').addEventListener('change', function() {
        currentType = this.value;
        salesTrendChart.data.labels = trendData[currentType].labels;
        salesTrendChart.data.datasets[0].data = trendData[currentType].data;
        salesTrendChart.data.datasets[1].data = trendData[currentType].data;
        salesTrendChart.update();
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
