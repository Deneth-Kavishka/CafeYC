<?php
session_start();
require_once '../config/database.php';

// Get categories for filter
$stmt = $pdo->prepare("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();

// Get products with filters
$where_conditions = ["p.is_active = 1"];
$params = [];

if (isset($_GET['category']) && !empty($_GET['category'])) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $_GET['category'];
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $search_term = '%' . $_GET['search'] . '%';
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_clause = implode(' AND ', $where_conditions);

$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name,
           COALESCE(hd.discount_percentage, 0) as discount_percentage,
           CASE WHEN hd.id IS NOT NULL THEN 1 ELSE 0 END as has_deal
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN hot_deals hd ON p.id = hd.product_id AND hd.is_active = 1 AND hd.end_date > NOW()
    WHERE $where_clause 
    ORDER BY p.name
");
$stmt->execute($params);
$products = $stmt->fetchAll();

$page_title = "Shop - CaféYC";
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/nav.php'; ?>

<div class="container my-5">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="fw-bold text-primary">
                <i class="fas fa-store me-2"></i>Our Coffee Shop
            </h1>
            <p class="lead text-muted">Discover our premium selection of coffee, pastries, and more</p>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <form method="GET" class="d-flex gap-3">
                <div class="flex-grow-1">
                    <input type="text" class="form-control" name="search" placeholder="Search products..." 
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                </div>
                <select name="category" class="form-select" style="min-width: 200px;">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" 
                                <?php echo (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                </button>
                <?php if (isset($_GET['search']) || isset($_GET['category'])): ?>
                    <a href="shop.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>
        <div class="col-lg-4 text-end">
            <span class="text-muted"><?php echo count($products); ?> products found</span>
        </div>
    </div>
    
    <!-- Products Grid -->
    <?php if (empty($products)): ?>
        <div class="text-center py-5">
            <i class="fas fa-search fa-3x text-muted mb-3"></i>
            <h4>No products found</h4>
            <p class="text-muted">Try adjusting your search criteria</p>
            <a href="shop.php" class="btn btn-primary">View All Products</a>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($products as $product): ?>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card product-card h-100 shadow-sm border-0">
                        <div class="position-relative">
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                 class="card-img-top product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            
                            <?php if ($product['has_deal']): ?>
                                <div class="position-absolute top-0 end-0 m-2">
                                    <span class="badge bg-danger fs-6"><?php echo $product['discount_percentage']; ?>% OFF</span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($product['featured']): ?>
                                <div class="position-absolute top-0 start-0 m-2">
                                    <span class="badge bg-warning">
                                        <i class="fas fa-star"></i> Featured
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-body d-flex flex-column">
                            <div class="mb-2">
                                <small class="text-muted"><?php echo htmlspecialchars($product['category_name']); ?></small>
                            </div>
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text text-muted flex-grow-1"><?php echo htmlspecialchars($product['description']); ?></p>
                            
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <?php if ($product['has_deal']): ?>
                                        <div>
                                            <span class="text-decoration-line-through text-muted">$<?php echo number_format($product['price'], 2); ?></span>
                                            <span class="fs-5 fw-bold text-primary ms-1">
                                                $<?php echo number_format($product['price'] * (1 - $product['discount_percentage']/100), 2); ?>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <span class="fs-5 fw-bold text-primary">$<?php echo number_format($product['price'], 2); ?></span>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-star text-warning"></i>
                                        <span class="ms-1"><?php echo number_format($product['rating'], 1); ?></span>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button class="btn btn-primary flex-grow-1 add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                        <i class="fas fa-cart-plus me-1"></i>Add to Cart
                                    </button>
                                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#productModal<?php echo $product['id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Product Modal -->
                <div class="modal fade" id="productModal<?php echo $product['id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                             class="img-fluid rounded" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                            <?php if ($product['featured']): ?>
                                                <span class="badge bg-warning ms-1">Featured</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <p class="text-muted mb-3"><?php echo htmlspecialchars($product['description']); ?></p>
                                        
                                        <div class="mb-3">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-star text-warning me-1"></i>
                                                <span><?php echo number_format($product['rating'], 1); ?> / 5.0</span>
                                            </div>
                                            
                                            <?php if ($product['has_deal']): ?>
                                                <div class="mb-2">
                                                    <span class="text-decoration-line-through text-muted fs-5">$<?php echo number_format($product['price'], 2); ?></span>
                                                    <span class="fs-4 fw-bold text-primary ms-2">
                                                        $<?php echo number_format($product['price'] * (1 - $product['discount_percentage']/100), 2); ?>
                                                    </span>
                                                    <span class="badge bg-danger ms-2"><?php echo $product['discount_percentage']; ?>% OFF</span>
                                                </div>
                                            <?php else: ?>
                                                <div class="fs-4 fw-bold text-primary mb-2">$<?php echo number_format($product['price'], 2); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <button class="btn btn-primary btn-lg w-100 add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                            <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app.js"></script>
<script src="../assets/js/cart.js"></script>
</body>
</html>
