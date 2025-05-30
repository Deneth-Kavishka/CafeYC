<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

checkAuth('admin');

// Filters
$rating_filter = isset($_GET['rating']) ? intval($_GET['rating']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch feedbacks with product and user info
$sql = "
    SELECT f.*, u.name as customer_name, u.email as customer_email, p.name as product_name, p.image_url
    FROM feedback f
    JOIN users u ON f.user_id = u.id
    JOIN products p ON f.product_id = p.id
    WHERE 1
";
$params = [];
if ($rating_filter > 0) {
    $sql .= " AND f.rating = ?";
    $params[] = $rating_filter;
}
if ($search !== '') {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR p.name LIKE ? OR f.comment LIKE ?)";
    $params = array_merge($params, array_fill(0, 4, "%$search%"));
}
$sql .= " ORDER BY f.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$feedbacks = $stmt->fetchAll();

// Analytics: rating distribution
$rating_stats = [];
for ($i = 5; $i >= 1; $i--) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM feedback WHERE rating = ?");
    $stmt->execute([$i]);
    $rating_stats[$i] = $stmt->fetchColumn();
}
$total_feedbacks = array_sum($rating_stats);

// Analytics: featured feedbacks
$stmt = $pdo->query("SELECT COUNT(*) FROM feedback WHERE is_featured = 1");
$featured_count = $stmt->fetchColumn();

$page_title = "Customer Feedback - CaféYC Admin";
$extra_css = ["https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css"];
?>
<?php include '../includes/header.php'; ?>

<body class="bg-light">
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar bg-dark text-white p-3" style="width: 250px; min-height: 100vh;">
            <!-- ...existing sidebar code (copy from dashboard.php, including feedbacks.php link)... -->
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
                    <a class="nav-link text-white active" href="feedbacks.php">
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
            <nav class="navbar navbar-light bg-white border-bottom px-4">
                <h5 class="mb-0">Customer Feedback Analytics</h5>
            </nav>
            <div class="container-fluid p-4">
                <!-- Analytics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card bg-primary text-white h-100">
                            <div class="card-body">
                                <h4 class="fw-bold mb-0"><?php echo $total_feedbacks; ?></h4>
                                <p class="mb-0">Total Feedbacks</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-success text-white h-100">
                            <div class="card-body">
                                <h4 class="fw-bold mb-0"><?php echo $featured_count; ?></h4>
                                <p class="mb-0">Featured Feedbacks</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <canvas id="ratingChart" height="60"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Filters -->
                <form method="get" class="row g-2 align-items-center mb-4">
                    <div class="col-auto">
                        <label class="form-label fw-semibold mb-0">Filter by Rating:</label>
                    </div>
                    <div class="col-auto">
                        <select name="rating" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="0">All</option>
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?php echo $i; ?>" <?php if($rating_filter==$i) echo 'selected'; ?>>
                                    <?php echo str_repeat('★', $i); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search customer, product, comment..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i> Search</button>
                        <?php if ($rating_filter || $search): ?>
                            <a href="feedbacks.php" class="btn btn-sm btn-outline-secondary ms-2"><i class="fas fa-times"></i> Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
                <!-- Feedback Table -->
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <strong><i class="fas fa-comments me-2"></i>Customer Feedback</strong>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>Customer</th>
                                        <th>Rating</th>
                                        <th>Comment</th>
                                        <th>Featured</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($feedbacks as $fb): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo htmlspecialchars($fb['image_url']); ?>" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                                <span><?php echo htmlspecialchars($fb['product_name']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <span class="fw-semibold"><?php echo htmlspecialchars($fb['customer_name']); ?></span><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($fb['customer_email']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star<?php if($i > $fb['rating']) echo '-o'; ?> text-warning"></i>
                                            <?php endfor; ?>
                                        </td>
                                        <td><?php echo nl2br(htmlspecialchars($fb['comment'])); ?></td>
                                        <td>
                                            <?php if ($fb['is_featured']): ?>
                                                <span class="badge bg-success">Yes</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo date('Y-m-d', strtotime($fb['created_at'])); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($feedbacks)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No feedbacks found.</td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
    // Rating Distribution Chart
    const ctx = document.getElementById('ratingChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [5, 4, 3, 2, 1].map(x => x + '★'),
            datasets: [{
                label: 'Count',
                data: [<?php echo $rating_stats[5]; ?>, <?php echo $rating_stats[4]; ?>, <?php echo $rating_stats[3]; ?>, <?php echo $rating_stats[2]; ?>, <?php echo $rating_stats[1]; ?>],
                backgroundColor: [
                    '#198754', '#0d6efd', '#ffc107', '#fd7e14', '#dc3545'
                ]
            }]
        },
        options: {
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
    </script>
</body>
</html>
