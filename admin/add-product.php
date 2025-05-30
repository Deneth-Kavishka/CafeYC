<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

checkAuth('admin');

// Get categories, brands, and suppliers for dropdowns
$stmt = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
$categories = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM brands WHERE is_active = 1 ORDER BY name");
$brands = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM suppliers WHERE is_active = 1 ORDER BY name");
$suppliers = $stmt->fetchAll();

// Check if editing existing product
$editing = false;
$product = null;
if (isset($_GET['edit'])) {
    $editing = true;
    $product_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        header('Location: products.php');
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];
    $brand_id = $_POST['brand_id'] ?: null;
    $supplier_id = $_POST['supplier_id'] ?: null;
    $price = $_POST['price'];
    $stock_quantity = $_POST['stock_quantity'];
    $featured = isset($_POST['featured']) ? 1 : 0;
    $image_url = $_POST['image_url'];
    
    try {
        if ($editing) {
            // Update existing product
            $stmt = $pdo->prepare("
                UPDATE products 
                SET name = ?, description = ?, category_id = ?, brand_id = ?, supplier_id = ?, 
                    price = ?, stock_quantity = ?, featured = ?, image_url = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $name, $description, $category_id, $brand_id, $supplier_id,
                $price, $stock_quantity, $featured, $image_url, $product['id']
            ]);
            $success = "Product updated successfully!";
        } else {
            // Create new product
            $stmt = $pdo->prepare("
                INSERT INTO products (name, description, category_id, brand_id, supplier_id, price, 
                                    stock_quantity, featured, image_url, is_active, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([
                $name, $description, $category_id, $brand_id, $supplier_id,
                $price, $stock_quantity, $featured, $image_url
            ]);
            $success = "Product created successfully!";
        }
        
        // Redirect to products page after successful operation
        header('Location: products.php?success=' . urlencode($success));
        exit;
        
    } catch (Exception $e) {
        $error = "Failed to save product. Please try again.";
    }
}

$page_title = ($editing ? "Edit Product" : "Add New Product") . " - CaféYC Admin";
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
                    <a class="nav-link text-white active" href="products.php">
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
                    <a class="nav-link text-white" href="feedbacks.php">
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
            <!-- Top Navigation -->
            <nav class="navbar navbar-light bg-white border-bottom px-4">
                <h5 class="mb-0"><?php echo $editing ? "Edit Product" : "Add New Product"; ?></h5>
                <a href="products.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Products
                </a>
            </nav>

            <!-- Form Content -->
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
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Product Information</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="name" class="form-label">Product Name *</label>
                                            <input type="text" class="form-control" id="name" name="name" required
                                                   value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="category_id" class="form-label">Category *</label>
                                            <select class="form-select" id="category_id" name="category_id" required>
                                                <option value="">Select Category</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?php echo $category['id']; ?>"
                                                            <?php echo ($product['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="brand_id" class="form-label">Brand</label>
                                            <select class="form-select" id="brand_id" name="brand_id">
                                                <option value="">Select Brand (Optional)</option>
                                                <?php foreach ($brands as $brand): ?>
                                                    <option value="<?php echo $brand['id']; ?>"
                                                            <?php echo ($product['brand_id'] ?? '') == $brand['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($brand['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="supplier_id" class="form-label">Supplier</label>
                                            <select class="form-select" id="supplier_id" name="supplier_id">
                                                <option value="">Select Supplier (Optional)</option>
                                                <?php foreach ($suppliers as $supplier): ?>
                                                    <option value="<?php echo $supplier['id']; ?>"
                                                            <?php echo ($product['supplier_id'] ?? '') == $supplier['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($supplier['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="price" class="form-label">Price (LKR) *</label>
                                            <input type="number" class="form-control" id="price" name="price" 
                                                   step="0.01" min="0" required
                                                   value="<?php echo htmlspecialchars($product['price'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="stock_quantity" class="form-label">Stock Quantity</label>
                                            <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" 
                                                   min="0" value="<?php echo htmlspecialchars($product['stock_quantity'] ?? '0'); ?>">
                                        </div>
                                        
                                        <div class="col-12 mb-3">
                                            <label for="description" class="form-label">Description *</label>
                                            <textarea class="form-control" id="description" name="description" 
                                                      rows="4" required><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="col-12 mb-3">
                                            <label for="image_url" class="form-label">Image URL *</label>
                                            <input type="url" class="form-control" id="image_url" name="image_url" required
                                                   value="<?php echo htmlspecialchars($product['image_url'] ?? ''); ?>"
                                                   placeholder="https://example.com/image.jpg">
                                            <div class="form-text">Enter a valid URL for the product image</div>
                                        </div>
                                        
                                        <div class="col-12 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="featured" name="featured"
                                                       <?php echo ($product['featured'] ?? false) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="featured">
                                                    <strong>Featured Product</strong>
                                                    <small class="text-muted d-block">Featured products appear on the homepage</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i><?php echo $editing ? "Update Product" : "Create Product"; ?>
                                        </button>
                                        <a href="products.php" class="btn btn-secondary">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <!-- Image Preview -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Image Preview</h5>
                            </div>
                            <div class="card-body text-center">
                                <img id="imagePreview" 
                                     src="<?php echo htmlspecialchars($product['image_url'] ?? 'https://via.placeholder.com/300x200?text=No+Image'); ?>" 
                                     class="img-fluid rounded" style="max-height: 200px;">
                                <div class="mt-2">
                                    <small class="text-muted">Image will be displayed as shown above</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Tips -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-lightbulb me-2"></i>Tips
                                </h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Use high-quality images (minimum 300x200px)
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Write clear, descriptive product names
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Include detailed descriptions
                                    </li>
                                    <li class="mb-0">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Set competitive pricing
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    
    <script>
    // Image preview functionality
    document.getElementById('image_url').addEventListener('input', function() {
        const imageUrl = this.value;
        const preview = document.getElementById('imagePreview');
        
        if (imageUrl) {
            preview.src = imageUrl;
            preview.onerror = function() {
                this.src = 'https://via.placeholder.com/300x200?text=Invalid+Image';
            };
        } else {
            preview.src = 'https://via.placeholder.com/300x200?text=No+Image';
        }
    });
    </script>
</body>
</html>
