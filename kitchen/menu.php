<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

checkAuth('kitchen');

// Handle activate/deactivate actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    if ($_POST['action'] === 'activate') {
        $stmt = $pdo->prepare("UPDATE products SET is_active = 1 WHERE id = ?");
        $stmt->execute([$product_id]);
    } elseif ($_POST['action'] === 'deactivate') {
        $stmt = $pdo->prepare("UPDATE products SET is_active = 0 WHERE id = ?");
        $stmt->execute([$product_id]);
    }
    header("Location: menu.php");
    exit;
}

// Fetch all menu items with category name
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY c.name, p.name
");
$stmt->execute();
$menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch queue order count for sidebar badge
$queue_count = 0;
$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','processing')");
$queue_count = $stmt->fetchColumn();

$page_title = "Menu Items - CaféYC";
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
                    <a class="nav-link text-white" href="reports.php">
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
                    <h2 class="mb-0"><i class="fas fa-book me-2"></i>Menu Items</h2>
                </div>
                <?php if (empty($menu_items)): ?>
                    <div class="alert alert-info">No menu items found.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle shadow-sm rounded" style="background: #fff;">
                            <thead class="table-primary">
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Price (LKR)</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($menu_items as $idx => $item): ?>
                                    <tr>
                                        <td><?php echo $idx + 1; ?></td>
                                        <td class="fw-bold"><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td>
                                            <span>
                                                <?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['description']); ?></td>
                                        <td>
                                            <span class="fw-semibold text-success">LKR <?php echo number_format($item['price'], 2); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo (!empty($item['is_active']) && $item['is_active']) ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo (!empty($item['is_active']) && $item['is_active']) ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" name="action" value="<?php echo (!empty($item['is_active']) && $item['is_active']) ? 'deactivate' : 'activate'; ?>"
                                                    class="btn btn-sm <?php echo (!empty($item['is_active']) && $item['is_active']) ? 'btn-outline-danger' : 'btn-outline-success'; ?>"
                                                    title="Toggle Active/Inactive">
                                                    <i class="fas <?php echo (!empty($item['is_active']) && $item['is_active']) ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i>
                                                </button>
                                            </form>
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
</body>
</html>
