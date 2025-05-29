<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

checkAuth('customer');

// Get customer statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total_orders FROM orders WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$total_orders = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) as pending_orders FROM orders WHERE user_id = ? AND status IN ('pending', 'processing')");
$stmt->execute([$_SESSION['user_id']]);
$pending_orders = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT SUM(total_amount) as total_spent FROM orders WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$_SESSION['user_id']]);
$total_spent = $stmt->fetchColumn() ?? 0;

// Get recent orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$recent_orders = $stmt->fetchAll();

$page_title = "Customer Dashboard - CaféYC";
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/nav.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <h1 class="fw-bold text-primary mb-4">
                <i class="fas fa-tachometer-alt me-2"></i>Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!
            </h1>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-5">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h2 class="fw-bold mb-0"><?php echo $total_orders; ?></h2>
                            <p class="mb-0">Total Orders</p>
                        </div>
                        <i class="fas fa-shopping-bag fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-4">
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
        
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h2 class="fw-bold mb-0">LKR <?php echo number_format($total_spent, 2); ?></h2>
                            <p class="mb-0">Total Spent</p>
                        </div>
                        <i class="fas fa-dollar-sign fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card bg-info text-white h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-coffee fa-3x"></i>
                    </div>
                    <a href="shop.php" class="btn btn-light btn-lg">
                        <i class="fas fa-shopping-cart me-2"></i>Shop Now
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mb-5">
        <div class="col-12">
            <h3 class="fw-bold mb-4">Quick Actions</h3>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <a href="shop.php" class="card text-decoration-none h-100">
                <div class="card-body text-center">
                    <i class="fas fa-store fa-2x text-primary mb-3"></i>
                    <h5>Browse Products</h5>
                    <p class="text-muted">Explore our menu</p>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <a href="orders.php" class="card text-decoration-none h-100">
                <div class="card-body text-center">
                    <i class="fas fa-list fa-2x text-primary mb-3"></i>
                    <h5>View Orders</h5>
                    <p class="text-muted">Track your orders</p>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <a href="cart.php" class="card text-decoration-none h-100">
                <div class="card-body text-center">
                    <i class="fas fa-shopping-cart fa-2x text-primary mb-3"></i>
                    <h5>Shopping Cart</h5>
                    <p class="text-muted">Review your cart</p>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <a href="feedback.php" class="card text-decoration-none h-100">
                <div class="card-body text-center">
                    <i class="fas fa-star fa-2x text-primary mb-3"></i>
                    <h5>Give Feedback</h5>
                    <p class="text-muted">Rate your experience</p>
                </div>
            </a>
        </div>
    </div>
    
    <!-- Recent Orders -->
    <?php if (!empty($recent_orders)): ?>
    <div class="row">
        <div class="col-12">
            <h3 class="fw-bold mb-4">Recent Orders</h3>
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
                                <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td class="fw-bold">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                    <td><?php echo $order['total_items']; ?> items</td>
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
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center">
                        <a href="orders.php" class="btn btn-primary">View All Orders</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
