<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

checkAuth('cashier');
$page_title = "POS System - CaféYC";

// Add this block to fetch pending orders count
$stmt = $pdo->prepare("SELECT COUNT(*) as pending_orders FROM orders WHERE status = 'pending'");
$stmt->execute();
$pending_orders = $stmt->fetchColumn();
?>

<?php include '../includes/header.php'; ?>


<body class="bg-light">
    <div class="d-flex">
        <!-- Sidebar -->
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
        <div class="flex-grow-1 d-flex align-items-center justify-content-center" style="height: 100vh; position: relative;">
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.15; z-index: 0; pointer-events: none;">
                <h1 style="font-size: 6rem; font-weight: bold; color:rgba(89, 138, 212, 0.75); text-align: center; user-select: none;">
                    FUTURE DEVELOPMENT
                </h1>
            </div>
            <div style="z-index: 1;">
                <b><h2 class="text-center text-muted">POS System</h2>
                <p class="text-center text-muted">This feature is under future development.</p></b>
            </div>
        </div>
    </div>
</body>
</html>
