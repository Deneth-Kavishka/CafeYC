<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

checkAuth('cashier');
$page_title = "All Product Sales - CaféYC";

// Determine period filter
$period = $_GET['period'] ?? 'month';
switch ($period) {
    case 'today':
        $start = date('Y-m-d');
        $label = "Today";
        break;
    case '7days':
        $start = date('Y-m-d', strtotime('-6 days'));
        $label = "Last 7 Days";
        break;
    case '3months':
        $start = date('Y-m-d', strtotime('first day of -2 month'));
        $label = "Last 3 Months";
        break;
    case '6months':
        $start = date('Y-m-d', strtotime('first day of -5 month'));
        $label = "Last 6 Months";
        break;
    case '12months':
        $start = date('Y-m-d', strtotime('first day of -11 month'));
        $label = "Last 12 Months";
        break;
    default:
        $start = date('Y-m-01');
        $label = "This Month";
        $period = 'month';
}

// Search filter
$search = trim($_GET['search'] ?? '');
$search_sql = '';
$params = [$start];
if ($search !== '') {
    $search_sql = " AND p.name LIKE ? ";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare("
    SELECT p.name, SUM(oi.quantity) as qty, SUM(oi.total_price) as total
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) >= ? AND o.status != 'cancelled'
    $search_sql
    GROUP BY oi.product_id
    ORDER BY qty DESC
");
$stmt->execute($params);
$products = $stmt->fetchAll();

include '../includes/header.php';
?>
<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0 d-inline align-middle">
                    <i class="fas fa-list me-2"></i>All Product Sales
                    <small class="text-muted">(<?php echo $label; ?>)</small>
                </h3>
            </div>
            <form method="get" class="d-flex align-items-center gap-2" style="min-width: 350px;">
                <label for="period" class="me-2 mb-0 fw-semibold">Period:</label>
                <select name="period" id="period" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="today" <?php if($period=='today') echo 'selected'; ?>>Today</option>
                    <option value="7days" <?php if($period=='7days') echo 'selected'; ?>>Last 7 Days</option>
                    <option value="month" <?php if($period=='month') echo 'selected'; ?>>This Month</option>
                    <option value="3months" <?php if($period=='3months') echo 'selected'; ?>>Last 3 Months</option>
                    <option value="6months" <?php if($period=='6months') echo 'selected'; ?>>Last 6 Months</option>
                    <option value="12months" <?php if($period=='12months') echo 'selected'; ?>>Last 12 Months</option>
                </select>
                <input type="text" name="search" class="form-control form-control-sm ms-2" placeholder="Search product..." value="<?php echo htmlspecialchars($search); ?>" style="width: 160px;">
                <button type="submit" class="btn btn-primary btn-sm ms-1"><i class="fas fa-search"></i></button>
            </form>
        </div>
        <div class="card shadow-sm">
            <div class="card-body">
                <!-- Back to Sales Report button placed before the table -->
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <a href="reports.php" class="btn btn-outline-primary btn-sm back-btn">
                        <i class="fas fa-arrow-left me-1"></i>Back to Sales Report
                    </a>
                    <a href="products-sales-download.php?period=<?php echo urlencode($period); ?>&search=<?php echo urlencode($search); ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-download me-1"></i>Download Full Report
                    </a>
                </div>
                <style>
                    .back-btn:hover, .back-btn:focus {
                        background-color: #198754 !important; /* Bootstrap green */
                        color: #fff !important;
                        border-color: #198754 !important;
                    }
                </style>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th style="width:60px;">#</th>
                                <th>Product</th>
                                <th class="text-end">Quantity Sold</th>
                                <th class="text-end">Total Sales (LKR)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $idx => $prod): ?>
                            <tr>
                                <td class="fw-bold"><?php echo $idx + 1; ?></td>
                                <td><?php echo htmlspecialchars($prod['name']); ?></td>
                                <td class="text-end"><?php echo $prod['qty']; ?></td>
                                <td class="text-end text-success fw-semibold">LKR <?php echo number_format($prod['total'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">No product sales found for this period.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
