<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

checkAuth('admin');

// Handle hot deal actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $product_id = $_POST['product_id'];
                $discount_percentage = $_POST['discount_percentage'];
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                
                $stmt = $pdo->prepare("INSERT INTO hot_deals (product_id, discount_percentage, start_date, end_date, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())");
                if ($stmt->execute([$product_id, $discount_percentage, $start_date, $end_date])) {
                    $success = "Hot deal added successfully!";
                } else {
                    $error = "Failed to add hot deal.";
                }
                break;
                
            case 'edit':
                $id = $_POST['deal_id'];
                $product_id = $_POST['product_id'];
                $discount_percentage = $_POST['discount_percentage'];
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                
                $stmt = $pdo->prepare("UPDATE hot_deals SET product_id = ?, discount_percentage = ?, start_date = ?, end_date = ?, updated_at = NOW() WHERE id = ?");
                if ($stmt->execute([$product_id, $discount_percentage, $start_date, $end_date, $id])) {
                    $success = "Hot deal updated successfully!";
                } else {
                    $error = "Failed to update hot deal.";
                }
                break;
                
            case 'toggle_status':
                $id = $_POST['deal_id'];
                $stmt = $pdo->prepare("UPDATE hot_deals SET is_active = NOT is_active WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $success = "Hot deal status updated successfully!";
                } else {
                    $error = "Failed to update hot deal status.";
                }
                break;
                
            case 'delete':
                $id = $_POST['deal_id'];
                $stmt = $pdo->prepare("DELETE FROM hot_deals WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $success = "Hot deal deleted successfully!";
                } else {
                    $error = "Failed to delete hot deal.";
                }
                break;
        }
    }
}

// Get hot deals with product information
$stmt = $pdo->prepare("
    SELECT hd.*, p.name as product_name, p.image_url, p.price,
           CASE WHEN hd.end_date > NOW() THEN 1 ELSE 0 END as is_valid
    FROM hot_deals hd 
    JOIN products p ON hd.product_id = p.id 
    ORDER BY hd.created_at DESC
");
$stmt->execute();
$hot_deals = $stmt->fetchAll();

// Get products for dropdown
$stmt = $pdo->prepare("SELECT id, name, price FROM products WHERE is_active = 1 ORDER BY name");
$stmt->execute();
$products = $stmt->fetchAll();

$page_title = "Hot Deals Management - CaféYC Admin";
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
                    <a class="nav-link text-white active" href="hot-deals.php">
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
                <h5 class="mb-0">Hot Deals Management</h5>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDealModal">
                    <i class="fas fa-plus me-2"></i>Add New Deal
                </button>
            </nav>

            <!-- Hot Deals Content -->
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
                            <i class="fas fa-fire me-2"></i>All Hot Deals (<?php echo count($hot_deals); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Original Price</th>
                                        <th>Discount</th>
                                        <th>Sale Price</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($hot_deals as $deal): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo htmlspecialchars($deal['image_url']); ?>" 
                                                     class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;" 
                                                     alt="<?php echo htmlspecialchars($deal['product_name']); ?>">
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($deal['product_name']); ?></h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="fw-bold">$<?php echo number_format($deal['price'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-danger fs-6"><?php echo $deal['discount_percentage']; ?>% OFF</span>
                                        </td>
                                        <td class="fw-bold text-success">
                                            $<?php echo number_format($deal['price'] * (1 - $deal['discount_percentage']/100), 2); ?>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <div><strong>Start:</strong> <?php echo date('M j, Y', strtotime($deal['start_date'])); ?></div>
                                                <div><strong>End:</strong> <?php echo date('M j, Y', strtotime($deal['end_date'])); ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($deal['is_active'] && $deal['is_valid']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php elseif ($deal['is_active'] && !$deal['is_valid']): ?>
                                                <span class="badge bg-warning">Expired</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="editDeal(<?php echo htmlspecialchars(json_encode($deal)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="deal_id" value="<?php echo $deal['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-warning">
                                                        <i class="fas fa-toggle-<?php echo $deal['is_active'] ? 'on' : 'off'; ?>"></i>
                                                    </button>
                                                </form>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="confirmDelete(<?php echo $deal['id']; ?>, '<?php echo htmlspecialchars($deal['product_name']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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

    <!-- Add Deal Modal -->
    <div class="modal fade" id="addDealModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Hot Deal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="addProductId" class="form-label">Product</label>
                                <select class="form-select" id="addProductId" name="product_id" required>
                                    <option value="">Select Product</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?php echo $product['id']; ?>">
                                            <?php echo htmlspecialchars($product['name']); ?> - $<?php echo number_format($product['price'], 2); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="addDiscountPercentage" class="form-label">Discount Percentage</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="addDiscountPercentage" 
                                           name="discount_percentage" min="1" max="90" required>
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="addStartDate" class="form-label">Start Date</label>
                                <input type="datetime-local" class="form-control" id="addStartDate" name="start_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="addEndDate" class="form-label">End Date</label>
                                <input type="datetime-local" class="form-control" id="addEndDate" name="end_date" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Deal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Deal Modal -->
    <div class="modal fade" id="editDealModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Hot Deal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="deal_id" id="editDealId">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editProductId" class="form-label">Product</label>
                                <select class="form-select" id="editProductId" name="product_id" required>
                                    <option value="">Select Product</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?php echo $product['id']; ?>">
                                            <?php echo htmlspecialchars($product['name']); ?> - $<?php echo number_format($product['price'], 2); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editDiscountPercentage" class="form-label">Discount Percentage</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="editDiscountPercentage" 
                                           name="discount_percentage" min="1" max="90" required>
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editStartDate" class="form-label">Start Date</label>
                                <input type="datetime-local" class="form-control" id="editStartDate" name="start_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editEndDate" class="form-label">End Date</label>
                                <input type="datetime-local" class="form-control" id="editEndDate" name="end_date" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Deal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the hot deal for "<span id="deleteDealProduct"></span>"?</p>
                    <p class="text-muted">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="deal_id" id="deleteDealId">
                        <button type="submit" class="btn btn-danger">Delete Deal</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    
    <script>
    function editDeal(deal) {
        document.getElementById('editDealId').value = deal.id;
        document.getElementById('editProductId').value = deal.product_id;
        document.getElementById('editDiscountPercentage').value = deal.discount_percentage;
        
        // Format dates for datetime-local input
        const startDate = new Date(deal.start_date);
        const endDate = new Date(deal.end_date);
        
        document.getElementById('editStartDate').value = formatDateTimeLocal(startDate);
        document.getElementById('editEndDate').value = formatDateTimeLocal(endDate);
        
        new bootstrap.Modal(document.getElementById('editDealModal')).show();
    }

    function confirmDelete(dealId, productName) {
        document.getElementById('deleteDealId').value = dealId;
        document.getElementById('deleteDealProduct').textContent = productName;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }

    function formatDateTimeLocal(date) {
        const offset = date.getTimezoneOffset();
        const localDate = new Date(date.getTime() - (offset * 60 * 1000));
        return localDate.toISOString().slice(0, 16);
    }

    // Set default start date to now
    document.addEventListener('DOMContentLoaded', function() {
        const now = new Date();
        document.getElementById('addStartDate').value = formatDateTimeLocal(now);
        
        // Set default end date to 7 days from now
        const weekFromNow = new Date(now.getTime() + (7 * 24 * 60 * 60 * 1000));
        document.getElementById('addEndDate').value = formatDateTimeLocal(weekFromNow);
    });
    </script>
</body>
</html>
