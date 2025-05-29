<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

checkAuth('admin');

// Get analytics data
$analytics = [];

// Revenue analytics
$stmt = $pdo->query("
    SELECT 
        DATE(created_at) as date,
        SUM(total_amount) as revenue,
        COUNT(*) as orders
    FROM orders 
    WHERE status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date
");
$daily_revenue = $stmt->fetchAll();

// Product performance
$stmt = $pdo->query("
    SELECT p.name, p.image_url,
           SUM(oi.quantity) as total_sold,
           SUM(oi.total_price) as revenue,
           (SELECT ROUND(AVG(f.rating), 1) FROM feedback f WHERE f.product_id = p.id) as avg_rating
    FROM products p
    JOIN order_items oi ON p.id = oi.product_id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'completed'
    GROUP BY p.id, p.name, p.image_url
    ORDER BY total_sold DESC
    LIMIT 10
");
$top_products = $stmt->fetchAll();

// Customer analytics
$stmt = $pdo->query("
    SELECT 
        COUNT(DISTINCT u.id) as total_customers,
        COUNT(DISTINCT CASE WHEN o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN u.id END) as active_customers,
        AVG(customer_stats.total_spent) as avg_customer_value
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id AND o.status = 'completed'
    LEFT JOIN (
        SELECT user_id, SUM(total_amount) as total_spent
        FROM orders 
        WHERE status = 'completed'
        GROUP BY user_id
    ) customer_stats ON u.id = customer_stats.user_id
    WHERE u.role = 'customer'
");
$customer_analytics = $stmt->fetch();

// Order status distribution
$stmt = $pdo->query("
    SELECT status, COUNT(*) as count
    FROM orders
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY status
");
$order_status = $stmt->fetchAll();

// Hourly sales pattern
$stmt = $pdo->query("
    SELECT 
        HOUR(created_at) as hour,
        COUNT(*) as orders,
        SUM(total_amount) as revenue
    FROM orders
    WHERE status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY HOUR(created_at)
    ORDER BY hour
");
$hourly_sales = $stmt->fetchAll();

// Category performance
$stmt = $pdo->query("
    SELECT c.name, 
           SUM(oi.quantity) as total_sold,
           SUM(oi.total_price) as revenue
    FROM categories c
    JOIN products p ON c.id = p.category_id
    JOIN order_items oi ON p.id = oi.product_id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'completed'
    GROUP BY c.id, c.name
    ORDER BY revenue DESC
");
$category_performance = $stmt->fetchAll();

$page_title = "Analytics Dashboard - CaféYC Admin";
$extra_css = ["https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css"];
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
                    <a class="nav-link text-white" href="orders.php">
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
                    <a class="nav-link text-white active" href="analytics.php">
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
                <h5 class="mb-0">Analytics Dashboard</h5>
                <div class="d-flex align-items-center">
                    <button class="btn btn-outline-primary btn-sm" onclick="exportData()">
                        <i class="fas fa-download me-1"></i>Export Report
                    </button>
                </div>
            </nav>

            <!-- Analytics Content -->
            <div class="container-fluid p-4">
                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h3 class="fw-bold mb-0"><?php echo number_format($customer_analytics['total_customers']); ?></h3>
                                        <p class="mb-0">Total Customers</p>
                                        <small class="opacity-75">
                                            <?php echo $customer_analytics['active_customers']; ?> active this month
                                        </small>
                                    </div>
                                    <i class="fas fa-users fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h3 class="fw-bold mb-0">LKR <?php echo number_format($customer_analytics['avg_customer_value'] ?? 0, 2); ?></h3>
                                        <p class="mb-0">Avg Customer Value</p>
                                        <small class="opacity-75">Lifetime value</small>
                                    </div>
                                    <i class="fas fa-dollar-sign fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h3 class="fw-bold mb-0"><?php echo count($top_products); ?></h3>
                                        <p class="mb-0">Top Products</p>
                                        <small class="opacity-75">Best performers</small>
                                    </div>
                                    <i class="fas fa-trophy fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h3 class="fw-bold mb-0"><?php echo count($category_performance); ?></h3>
                                        <p class="mb-0">Categories</p>
                                        <small class="opacity-75">Product categories</small>
                                    </div>
                                    <i class="fas fa-tags fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-line me-2"></i>Daily Revenue (Last 30 Days)
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="revenueChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-pie me-2"></i>Order Status
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="statusChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Tables -->
                <div class="row mb-4">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-star me-2"></i>Top Products
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Sold</th>
                                                <th>Revenue</th>
                                                <th>Rating</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_products as $product): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                                             class="rounded me-2" style="width: 30px; height: 30px; object-fit: cover;">
                                                        <small><?php echo htmlspecialchars($product['name']); ?></small>
                                                    </div>
                                                </td>
                                                <td><?php echo $product['total_sold']; ?></td>
                                                <td>LKR <?php echo number_format($product['revenue'], 2); ?></td>
                                                <td>
                                                    <i class="fas fa-star text-warning"></i>
                                                    <?php echo number_format($product['avg_rating'], 1); ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-tags me-2"></i>Category Performance
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th>Items Sold</th>
                                                <th>Revenue</th>
                                                <th>Share</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $total_revenue = array_sum(array_column($category_performance, 'revenue'));
                                            foreach ($category_performance as $category): 
                                            $share = $total_revenue > 0 ? ($category['revenue'] / $total_revenue) * 100 : 0;
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                                <td><?php echo $category['total_sold']; ?></td>
                                                <td>LKR <?php echo number_format($category['revenue'], 2); ?></td>
                                                <td><?php echo number_format($share, 1); ?>%</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hourly Sales Pattern -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-clock me-2"></i>Sales Pattern by Hour (Last 7 Days)
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="hourlySalesChart" height="200"></canvas>
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
    const revenueData = <?php echo json_encode($daily_revenue); ?>;
    
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: revenueData.map(item => new Date(item.date).toLocaleDateString()),
            datasets: [{
                label: 'Revenue',
                data: revenueData.map(item => item.revenue),
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
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

    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusData = <?php echo json_encode($order_status); ?>;
    
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: statusData.map(item => item.status.charAt(0).toUpperCase() + item.status.slice(1)),
            datasets: [{
                data: statusData.map(item => item.count),
                backgroundColor: [
                    '#ffc107',
                    '#0dcaf0',
                    '#198754',
                    '#dc3545'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Hourly Sales Chart
    const hourlySalesCtx = document.getElementById('hourlySalesChart').getContext('2d');
    const hourlySalesData = <?php echo json_encode($hourly_sales); ?>;
    
    // Fill missing hours with 0
    const fullHourlyData = [];
    for (let i = 0; i < 24; i++) {
        const found = hourlySalesData.find(item => parseInt(item.hour) === i);
        fullHourlyData.push({
            hour: i,
            orders: found ? found.orders : 0,
            revenue: found ? found.revenue : 0
        });
    }
    
    new Chart(hourlySalesCtx, {
        type: 'bar',
        data: {
            labels: fullHourlyData.map(item => item.hour + ':00'),
            datasets: [{
                label: 'Orders',
                data: fullHourlyData.map(item => item.orders),
                backgroundColor: 'rgba(13, 110, 253, 0.6)',
                yAxisID: 'y'
            }, {
                label: 'Revenue',
                data: fullHourlyData.map(item => item.revenue),
                type: 'line',
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false,
                    },
                    ticks: {
                        callback: function(value) {
                            return 'LKR ' + value;
                        }
                    }
                }
            }
        }
    });

    function exportData() {
        // Simple CSV export functionality
        const csvData = [
            ['Date', 'Revenue', 'Orders'],
            ...revenueData.map(item => [item.date, item.revenue, item.orders])
        ];
        
        const csv = csvData.map(row => row.join(',')).join('\n');
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.setAttribute('hidden', '');
        a.setAttribute('href', url);
        a.setAttribute('download', 'analytics-report.csv');
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
    </script>
</body>
</html>
