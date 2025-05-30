<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

checkAuth('admin');

// Handle slider actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $title = $_POST['title'];
                $description = $_POST['description'];
                $image_url = $_POST['image_url'];
                $sort_order = $_POST['sort_order'];
                
                $stmt = $pdo->prepare("INSERT INTO sliders (title, description, image_url, sort_order, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())");
                if ($stmt->execute([$title, $description, $image_url, $sort_order])) {
                    $success = "Slider added successfully!";
                } else {
                    $error = "Failed to add slider.";
                }
                break;
                
            case 'edit':
                $id = $_POST['slider_id'];
                $title = $_POST['title'];
                $description = $_POST['description'];
                $image_url = $_POST['image_url'];
                $sort_order = $_POST['sort_order'];
                
                $stmt = $pdo->prepare("UPDATE sliders SET title = ?, description = ?, image_url = ?, sort_order = ?, updated_at = NOW() WHERE id = ?");
                if ($stmt->execute([$title, $description, $image_url, $sort_order, $id])) {
                    $success = "Slider updated successfully!";
                } else {
                    $error = "Failed to update slider.";
                }
                break;
                
            case 'toggle_status':
                $id = $_POST['slider_id'];
                $stmt = $pdo->prepare("UPDATE sliders SET is_active = NOT is_active WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $success = "Slider status updated successfully!";
                } else {
                    $error = "Failed to update slider status.";
                }
                break;
                
            case 'delete':
                $id = $_POST['slider_id'];
                $stmt = $pdo->prepare("DELETE FROM sliders WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $success = "Slider deleted successfully!";
                } else {
                    $error = "Failed to delete slider.";
                }
                break;
        }
    }
}

// Get sliders
$stmt = $pdo->prepare("SELECT * FROM sliders ORDER BY sort_order, created_at DESC");
$stmt->execute();
$sliders = $stmt->fetchAll();

$page_title = "Sliders Management - CaféYC Admin";
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
                    <a class="nav-link text-white active" href="sliders.php">
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
                    <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="users.php">
                        <i class="fas fa-user-cog me-2"></i>System Users
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="feedbacks.php">
                        <i class="fas fa-comments me-2"></i>Customer Feedback
                    </a>
                </li>
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
                <h5 class="mb-0">Sliders Management</h5>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSliderModal">
                    <i class="fas fa-plus me-2"></i>Add New Slider
                </button>
            </nav>

            <!-- Sliders Content -->
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

                <div class="row">
                    <?php foreach ($sliders as $slider): ?>
                    <div class="col-lg-6 col-xl-4 mb-4">
                        <div class="card h-100">
                            <div class="position-relative">
                                <img src="<?php echo htmlspecialchars($slider['image_url']); ?>" 
                                     class="card-img-top" style="height: 200px; object-fit: cover;" 
                                     alt="<?php echo htmlspecialchars($slider['title']); ?>">
                                <div class="position-absolute top-0 end-0 m-2">
                                    <?php if ($slider['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </div>
                                <div class="position-absolute top-0 start-0 m-2">
                                    <span class="badge bg-primary">Order: <?php echo $slider['sort_order']; ?></span>
                                </div>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($slider['title']); ?></h5>
                                <p class="card-text flex-grow-1"><?php echo htmlspecialchars($slider['description']); ?></p>
                                <div class="d-flex gap-2 mt-auto">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="editSlider(<?php echo htmlspecialchars(json_encode($slider)); ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="slider_id" value="<?php echo $slider['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-warning">
                                            <i class="fas fa-toggle-<?php echo $slider['is_active'] ? 'on' : 'off'; ?>"></i>
                                        </button>
                                    </form>
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="confirmDelete(<?php echo $slider['id']; ?>, '<?php echo htmlspecialchars($slider['title']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($sliders)): ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="fas fa-images fa-4x text-muted mb-3"></i>
                            <h4>No sliders found</h4>
                            <p class="text-muted">Add your first slider to get started</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSliderModal">
                                <i class="fas fa-plus me-2"></i>Add Slider
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Slider Modal -->
    <div class="modal fade" id="addSliderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Slider</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="addTitle" class="form-label">Title</label>
                                <input type="text" class="form-control" id="addTitle" name="title" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="addSortOrder" class="form-label">Sort Order</label>
                                <input type="number" class="form-control" id="addSortOrder" name="sort_order" value="1" min="1" required>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="addDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="addDescription" name="description" rows="3" required></textarea>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="addImageUrl" class="form-label">Image URL</label>
                                <input type="url" class="form-control" id="addImageUrl" name="image_url" required 
                                       placeholder="https://example.com/image.jpg">
                                <div class="form-text">Use high-quality images (recommended size: 1200x400px)</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Slider</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Slider Modal -->
    <div class="modal fade" id="editSliderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Slider</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="slider_id" id="editSliderId">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="editTitle" class="form-label">Title</label>
                                <input type="text" class="form-control" id="editTitle" name="title" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="editSortOrder" class="form-label">Sort Order</label>
                                <input type="number" class="form-control" id="editSortOrder" name="sort_order" min="1" required>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="editDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="editDescription" name="description" rows="3" required></textarea>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="editImageUrl" class="form-label">Image URL</label>
                                <input type="url" class="form-control" id="editImageUrl" name="image_url" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Slider</button>
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
                    <p>Are you sure you want to delete the slider "<span id="deleteSliderTitle"></span>"?</p>
                    <p class="text-muted">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="slider_id" id="deleteSliderId">
                        <button type="submit" class="btn btn-danger">Delete Slider</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    
    <script>
    function editSlider(slider) {
        document.getElementById('editSliderId').value = slider.id;
        document.getElementById('editTitle').value = slider.title;
        document.getElementById('editDescription').value = slider.description;
        document.getElementById('editImageUrl').value = slider.image_url;
        document.getElementById('editSortOrder').value = slider.sort_order;
        new bootstrap.Modal(document.getElementById('editSliderModal')).show();
    }

    function confirmDelete(sliderId, sliderTitle) {
        document.getElementById('deleteSliderId').value = sliderId;
        document.getElementById('deleteSliderTitle').textContent = sliderTitle;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
    </script>
</body>
</html>
