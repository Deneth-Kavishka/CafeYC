<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

checkAuth('cashier');

$page_title = "Customers - CaféYC";

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_sql = '';
$params = [];
if ($search !== '') {
    $search_sql = " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?) ";
    $params = array_fill(0, 3, "%$search%");
}

// Fetch customers with stats
$stmt = $pdo->prepare("
    SELECT u.*, 
           COUNT(o.id) as total_orders,
           COALESCE(SUM(CASE WHEN o.status = 'completed' THEN o.total_amount ELSE 0 END), 0) as total_spent,
           MAX(o.created_at) as last_order_date
    FROM users u 
    LEFT JOIN orders o ON u.id = o.user_id
    WHERE u.role = 'customer'
    $search_sql
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$stmt->execute($params);
$customers = $stmt->fetchAll();

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
                    <a class="nav-link text-white active" href="customers.php">
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
            <nav class="navbar navbar-light bg-white border-bottom px-4 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Customers</h5>
                <!-- Search Bar (Order's style, top right, small, pro look) -->
                <form class="d-flex" method="get" action="customers.php" style="max-width: 250px;">
                    <input class="form-control form-control-sm rounded-pill me-2"
                           type="search"
                           name="search"
                           placeholder="Search customers..."
                           aria-label="Search"
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                           style="min-width: 120px;">
                    <button class="btn btn-sm btn-primary rounded-pill" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                    <?php if ($search): ?>
                        <a href="customers.php" class="btn btn-sm btn-outline-danger rounded-pill ms-2">Clear</a>
                    <?php endif; ?>
                </form>
            </nav>
            <div class="container-fluid p-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i>All Customers (<?php echo count($customers); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Contact</th>
                                        <th>Address</th>
                                        <th>Registration</th>
                                        <th>Orders</th>
                                        <th>Total Spent</th>
                                        <th>Last Order</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($customers as $customer): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                                                     style="width: 36px; height: 36px;">
                                                    <?php echo strtoupper(substr($customer['name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($customer['name']); ?></h6>
                                                    <small class="text-muted">ID: #<?php echo str_pad($customer['id'], 6, '0', STR_PAD_LEFT); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <div class="mb-1">
                                                    <i class="fas fa-envelope text-muted me-1"></i>
                                                    <?php echo htmlspecialchars($customer['email']); ?>
                                                </div>
                                                <?php if ($customer['phone']): ?>
                                                <div>
                                                    <i class="fas fa-phone text-muted me-1"></i>
                                                    <?php echo htmlspecialchars($customer['phone']); ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($customer['address'] ?? 'Not provided'); ?>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($customer['created_at'])); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $customer['total_orders']; ?> orders</span>
                                        </td>
                                        <td class="fw-bold">LKR <?php echo number_format($customer['total_spent'], 2); ?></td>
                                        <td>
                                            <?php if ($customer['last_order_date']): ?>
                                                <?php echo date('M j, Y', strtotime($customer['last_order_date'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">No orders</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($customer['is_active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="viewCustomer(<?php echo htmlspecialchars(json_encode($customer)); ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($customers)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">No customers found.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Details Modal -->
    <div class="modal fade" id="customerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Customer Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold">Personal Information</h6>
                            <p class="mb-1"><strong>Name:</strong> <span id="customerName"></span></p>
                            <p class="mb-1"><strong>Email:</strong> <span id="customerEmail"></span></p>
                            <p class="mb-1"><strong>Phone:</strong> <span id="customerPhone"></span></p>
                            <p class="mb-1"><strong>Address:</strong> <span id="customerAddress"></span></p>
                            <p class="mb-1"><strong>Registration:</strong> <span id="customerRegistration"></span></p>
                            <p class="mb-1"><strong>Status:</strong> <span id="customerStatus"></span></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold">Order Statistics</h6>
                            <p class="mb-1"><strong>Total Orders:</strong> <span id="customerTotalOrders"></span></p>
                            <p class="mb-1"><strong>Total Spent:</strong> <span id="customerTotalSpent"></span></p>
                            <p class="mb-1"><strong>Last Order:</strong> <span id="customerLastOrder"></span></p>
                            <p class="mb-1"><strong>Average Order:</strong> <span id="customerAvgOrder"></span></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let currentCustomerId = null;

    function viewCustomer(customer) {
        currentCustomerId = customer.id;
        document.getElementById('customerName').textContent = customer.name;
        document.getElementById('customerEmail').textContent = customer.email;
        document.getElementById('customerPhone').textContent = customer.phone || 'Not provided';
        document.getElementById('customerAddress').textContent = customer.address || 'Not provided';
        document.getElementById('customerRegistration').textContent = new Date(customer.created_at).toLocaleDateString();
        document.getElementById('customerStatus').innerHTML = customer.is_active ? 
            '<span class="badge bg-success">Active</span>' : 
            '<span class="badge bg-danger">Inactive</span>';
        document.getElementById('customerTotalOrders').textContent = customer.total_orders;
        document.getElementById('customerTotalSpent').textContent = 'LKR ' + parseFloat(customer.total_spent).toFixed(2);
        document.getElementById('customerLastOrder').textContent = customer.last_order_date ? 
            new Date(customer.last_order_date).toLocaleDateString() : 'No orders';
        const avgOrder = customer.total_orders > 0 ? (customer.total_spent / customer.total_orders) : 0;
        document.getElementById('customerAvgOrder').textContent = 'LKR ' + avgOrder.toFixed(2);
        new bootstrap.Modal(document.getElementById('customerModal')).show();
    }
    </script>
</body>
</html>
