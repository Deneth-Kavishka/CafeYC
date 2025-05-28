<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

checkAuth('cashier');

// Get today's statistics
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT COUNT(*) as today_orders FROM orders WHERE DATE(created_at) = ?");
$stmt->execute([$today]);
$today_orders = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as today_sales FROM orders WHERE DATE(created_at) = ? AND status != 'cancelled'");
$stmt->execute([$today]);
$today_sales = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) as pending_orders FROM orders WHERE status = 'pending'");
$stmt->execute();
$pending_orders = $stmt->fetchColumn();

// Get recent orders
$stmt = $pdo->prepare("
    SELECT o.*, u.name as customer_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE DATE(o.created_at) = ?
    ORDER BY o.created_at DESC 
    LIMIT 10
");
$stmt->execute([$today]);
$recent_orders = $stmt->fetchAll();

// Get products for quick access
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.is_active = 1 
    ORDER BY p.name 
    LIMIT 20
");
$stmt->execute();
$products = $stmt->fetchAll();

$page_title = "Cashier Dashboard - CaféYC";
?>

<?php include '../includes/header.php'; ?>

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
                    <a class="nav-link text-white active" href="dashboard.php">
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
                        <?php if ($pending_orders > 0): ?>
                            <span class="badge bg-danger ms-2"><?php echo $pending_orders; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="customers.php">
                        <i class="fas fa-users me-2"></i>Customers
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="reports.php">
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
            <!-- Top Navigation -->
            <nav class="navbar navbar-light bg-white border-bottom px-4">
                <span class="navbar-text">
                    Welcome back, <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong>
                </span>
                <div class="d-flex align-items-center">
                    <span class="text-muted me-3"><?php echo date('l, F j, Y'); ?></span>
                    <a href="../" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-eye me-1"></i>View Site
                    </a>
                </div>
            </nav>

            <!-- Dashboard Content -->
            <div class="container-fluid p-4">
                <!-- Quick Stats -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h2 class="fw-bold mb-0"><?php echo $today_orders; ?></h2>
                                        <p class="mb-0">Today's Orders</p>
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
                                        <h2 class="fw-bold mb-0">$<?php echo number_format($today_sales, 2); ?></h2>
                                        <p class="mb-0">Today's Sales</p>
                                    </div>
                                    <i class="fas fa-dollar-sign fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-warning text-white h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h2 class="fw-bold mb-0"><?php echo $pending_orders; ?></h2>
                                        <p class="mb-0">Pending Orders</p>
                                    </div>
                                    <i class="fas fa-clock fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-info text-white h-100">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="fas fa-cash-register fa-3x"></i>
                                </div>
                                <a href="pos.php" class="btn btn-light btn-lg">
                                    <i class="fas fa-plus me-2"></i>New Sale
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h3 class="fw-bold mb-4">Quick Actions</h3>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <a href="pos.php" class="card text-decoration-none h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-cash-register fa-2x text-primary mb-3"></i>
                                <h6>New Sale</h6>
                                <p class="text-muted small">Start POS</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <a href="orders.php" class="card text-decoration-none h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-list fa-2x text-primary mb-3"></i>
                                <h6>View Orders</h6>
                                <p class="text-muted small">Manage orders</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <a href="customers.php" class="card text-decoration-none h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-2x text-primary mb-3"></i>
                                <h6>Customers</h6>
                                <p class="text-muted small">Customer lookup</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <a href="reports.php" class="card text-decoration-none h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-bar fa-2x text-primary mb-3"></i>
                                <h6>Reports</h6>
                                <p class="text-muted small">Sales reports</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <a href="#" class="card text-decoration-none h-100" onclick="printDailyReport()">
                            <div class="card-body text-center">
                                <i class="fas fa-print fa-2x text-primary mb-3"></i>
                                <h6>Daily Report</h6>
                                <p class="text-muted small">Print summary</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <a href="#" class="card text-decoration-none h-100" onclick="openCashDrawer()">
                            <div class="card-body text-center">
                                <i class="fas fa-money-bill-wave fa-2x text-primary mb-3"></i>
                                <h6>Cash Drawer</h6>
                                <p class="text-muted small">Open drawer</p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="row mb-4">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-clock me-2"></i>Today's Orders
                                </h5>
                                <a href="orders.php" class="btn btn-primary btn-sm">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_orders)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                                        <h5>No orders today</h5>
                                        <p class="text-muted">Start taking orders to see them here</p>
                                        <a href="pos.php" class="btn btn-primary">Start New Sale</a>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Order #</th>
                                                    <th>Customer</th>
                                                    <th>Time</th>
                                                    <th>Amount</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_orders as $order): ?>
                                                <tr>
                                                    <td class="fw-bold">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                    <td><?php echo date('g:i A', strtotime($order['created_at'])); ?></td>
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
                                                        <a href="orders.php?view=<?php echo $order['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Product Access -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-coffee me-2"></i>Quick Add Products
                                </h5>
                            </div>
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                <?php foreach (array_slice($products, 0, 10) as $product): ?>
                                    <div class="d-flex align-items-center mb-3 p-2 border rounded">
                                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                             class="rounded me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0"><?php echo htmlspecialchars($product['name']); ?></h6>
                                            <small class="text-muted">$<?php echo number_format($product['price'], 2); ?></small>
                                        </div>
                                        <button class="btn btn-sm btn-primary quick-add-product" 
                                                data-product-id="<?php echo $product['id']; ?>">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                                <div class="text-center">
                                    <a href="pos.php" class="btn btn-outline-primary btn-sm">View All Products</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/app.js"></script>
    
    <script>
    function printDailyReport() {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Daily Sales Report - ${new Date().toDateString()}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .header { text-align: center; margin-bottom: 30px; }
                        .stats { display: flex; justify-content: space-between; margin-bottom: 20px; }
                        .stat-box { text-align: center; padding: 10px; border: 1px solid #ddd; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>CaféYC Daily Sales Report</h1>
                        <p>${new Date().toDateString()}</p>
                    </div>
                    <div class="stats">
                        <div class="stat-box">
                            <h3><?php echo $today_orders; ?></h3>
                            <p>Total Orders</p>
                        </div>
                        <div class="stat-box">
                            <h3>$<?php echo number_format($today_sales, 2); ?></h3>
                            <p>Total Sales</p>
                        </div>
                        <div class="stat-box">
                            <h3><?php echo $pending_orders; ?></h3>
                            <p>Pending Orders</p>
                        </div>
                    </div>
                    <p><strong>Cashier:</strong> <?php echo htmlspecialchars($_SESSION['name']); ?></p>
                    <p><strong>Generated:</strong> ${new Date().toLocaleString()}</p>
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }

    function openCashDrawer() {
        // In a real implementation, this would send a command to the cash drawer
        alert('Cash drawer command sent!');
    }

    // Quick add to cart functionality
    document.querySelectorAll('.quick-add-product').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            // Store in session cart or redirect to POS with product selected
            window.location.href = `pos.php?add_product=${productId}`;
        });
    });
    </script>
</body>
</html>
