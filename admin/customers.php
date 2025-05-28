<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

checkAuth('admin');

// Handle customer actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'toggle_status':
                $customer_id = $_POST['customer_id'];
                $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ? AND role = 'customer'");
                if ($stmt->execute([$customer_id])) {
                    $success = "Customer status updated successfully!";
                } else {
                    $error = "Failed to update customer status.";
                }
                break;
        }
    }
}

// Get customers with order statistics
$stmt = $pdo->prepare("
    SELECT u.*, 
           COUNT(o.id) as total_orders,
           COALESCE(SUM(CASE WHEN o.status = 'completed' THEN o.total_amount ELSE 0 END), 0) as total_spent,
           MAX(o.created_at) as last_order_date
    FROM users u 
    LEFT JOIN orders o ON u.id = o.user_id
    WHERE u.role = 'customer'
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$stmt->execute();
$customers = $stmt->fetchAll();

$page_title = "Customers Management - CaféYC Admin";
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
                    <a class="nav-link text-white active" href="customers.php">
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
                <h5 class="mb-0">Customers Management</h5>
                <div class="d-flex align-items-center">
                    <span class="text-muted me-3">Total Customers: <?php echo count($customers); ?></span>
                </div>
            </nav>

            <!-- Customers Content -->
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

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i>All Customers
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Contact</th>
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
                                                     style="width: 40px; height: 40px;">
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
                                        <td><?php echo date('M j, Y', strtotime($customer['created_at'])); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $customer['total_orders']; ?> orders</span>
                                        </td>
                                        <td class="fw-bold">$<?php echo number_format($customer['total_spent'], 2); ?></td>
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
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewCustomer(<?php echo htmlspecialchars(json_encode($customer)); ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" 
                                                            title="Toggle Status">
                                                        <i class="fas fa-toggle-<?php echo $customer['is_active'] ? 'on' : 'off'; ?>"></i>
                                                    </button>
                                                </form>
                                            </div>
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
                    <button type="button" class="btn btn-primary" onclick="viewCustomerOrders()">View Orders</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    
    <script>
    let currentCustomerId = null;

    function viewCustomer(customer) {
        currentCustomerId = customer.id;
        
        document.getElementById('customerName').textContent = customer.name;
        document.getElementById('customerEmail').textContent = customer.email;
        document.getElementById('customerPhone').textContent = customer.phone || 'Not provided';
        document.getElementById('customerRegistration').textContent = new Date(customer.created_at).toLocaleDateString();
        document.getElementById('customerStatus').innerHTML = customer.is_active ? 
            '<span class="badge bg-success">Active</span>' : 
            '<span class="badge bg-danger">Inactive</span>';
        
        document.getElementById('customerTotalOrders').textContent = customer.total_orders;
        document.getElementById('customerTotalSpent').textContent = '$' + parseFloat(customer.total_spent).toFixed(2);
        document.getElementById('customerLastOrder').textContent = customer.last_order_date ? 
            new Date(customer.last_order_date).toLocaleDateString() : 'No orders';
        
        const avgOrder = customer.total_orders > 0 ? (customer.total_spent / customer.total_orders) : 0;
        document.getElementById('customerAvgOrder').textContent = '$' + avgOrder.toFixed(2);
        
        new bootstrap.Modal(document.getElementById('customerModal')).show();
    }

    function viewCustomerOrders() {
        if (currentCustomerId) {
            window.open('orders.php?customer_id=' + currentCustomerId, '_blank');
        }
    }
    </script>
</body>
</html>
