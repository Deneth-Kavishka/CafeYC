<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

checkAuth('admin');

// Handle brand actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = $_POST['name'];
                $description = $_POST['description'];
                $stmt = $pdo->prepare("INSERT INTO brands (name, description, is_active, created_at) VALUES (?, ?, 1, NOW())");
                if ($stmt->execute([$name, $description])) {
                    $success = "Brand added successfully!";
                } else {
                    $error = "Failed to add brand.";
                }
                break;
                
            case 'edit':
                $id = $_POST['brand_id'];
                $name = $_POST['name'];
                $description = $_POST['description'];
                $stmt = $pdo->prepare("UPDATE brands SET name = ?, description = ?, updated_at = NOW() WHERE id = ?");
                if ($stmt->execute([$name, $description, $id])) {
                    $success = "Brand updated successfully!";
                } else {
                    $error = "Failed to update brand.";
                }
                break;
                
            case 'delete':
                $id = $_POST['brand_id'];
                $stmt = $pdo->prepare("UPDATE brands SET is_active = 0 WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $success = "Brand deleted successfully!";
                } else {
                    $error = "Failed to delete brand.";
                }
                break;
        }
    }
}

// Get brands with product counts
$stmt = $pdo->prepare("
    SELECT b.*, COUNT(p.id) as product_count 
    FROM brands b 
    LEFT JOIN products p ON b.id = p.brand_id AND p.is_active = 1 
    WHERE b.is_active = 1 
    GROUP BY b.id 
    ORDER BY b.name
");
$stmt->execute();
$brands = $stmt->fetchAll();

$page_title = "Brands Management - CaféYC Admin";
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
                    <a class="nav-link text-white active" href="brands.php">
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
                <h5 class="mb-0">Brands Management</h5>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBrandModal">
                    <i class="fas fa-plus me-2"></i>Add New Brand
                </button>
            </nav>

            <!-- Brands Content -->
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
                            <i class="fas fa-star me-2"></i>All Brands (<?php echo count($brands); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Brand Name</th>
                                        <th>Description</th>
                                        <th>Products</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($brands as $brand): ?>
                                    <tr>
                                        <td>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($brand['name']); ?></h6>
                                        </td>
                                        <td><?php echo htmlspecialchars($brand['description']); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $brand['product_count']; ?> products</span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($brand['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="editBrand(<?php echo htmlspecialchars(json_encode($brand)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="confirmDelete(<?php echo $brand['id']; ?>, '<?php echo htmlspecialchars($brand['name']); ?>')">
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

    <!-- Add Brand Modal -->
    <div class="modal fade" id="addBrandModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="addName" class="form-label">Brand Name</label>
                            <input type="text" class="form-control" id="addName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="addDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="addDescription" name="description" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Brand</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Brand Modal -->
    <div class="modal fade" id="editBrandModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="brand_id" id="editBrandId">
                        <div class="mb-3">
                            <label for="editName" class="form-label">Brand Name</label>
                            <input type="text" class="form-control" id="editName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editDescription" name="description" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Brand</button>
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
                    <p>Are you sure you want to delete the brand "<span id="deleteBrandName"></span>"?</p>
                    <p class="text-muted">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="brand_id" id="deleteBrandId">
                        <button type="submit" class="btn btn-danger">Delete Brand</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    
    <script>
    function editBrand(brand) {
        document.getElementById('editBrandId').value = brand.id;
        document.getElementById('editName').value = brand.name;
        document.getElementById('editDescription').value = brand.description;
        new bootstrap.Modal(document.getElementById('editBrandModal')).show();
    }

    function confirmDelete(brandId, brandName) {
        document.getElementById('deleteBrandId').value = brandId;
        document.getElementById('deleteBrandName').textContent = brandName;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
    </script>
</body>
</html>
