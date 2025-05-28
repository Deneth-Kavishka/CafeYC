<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

checkAuth('customer');

$cart_items = [];
$total = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    
    $stmt = $pdo->prepare("
        SELECT p.*, 
               COALESCE(hd.discount_percentage, 0) as discount_percentage,
               CASE WHEN hd.id IS NOT NULL THEN 1 ELSE 0 END as has_deal
        FROM products p 
        LEFT JOIN hot_deals hd ON p.id = hd.product_id AND hd.is_active = 1 AND hd.end_date > NOW()
        WHERE p.id IN ($placeholders)
    ");
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        $quantity = $_SESSION['cart'][$product['id']];
        $price = $product['has_deal'] 
            ? $product['price'] * (1 - $product['discount_percentage']/100)
            : $product['price'];
        
        $cart_items[] = [
            'product' => $product,
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' => $price * $quantity
        ];
        
        $total += $price * $quantity;
    }
}

$page_title = "Shopping Cart - CaféYC";
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/nav.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <h1 class="fw-bold text-primary mb-4">
                <i class="fas fa-shopping-cart me-2"></i>Shopping Cart
            </h1>
        </div>
    </div>
    
    <?php if (empty($cart_items)): ?>
        <div class="text-center py-5">
            <i class="fas fa-shopping-cart fa-4x text-muted mb-4"></i>
            <h3>Your cart is empty</h3>
            <p class="text-muted mb-4">Add some delicious items to get started!</p>
            <a href="shop.php" class="btn btn-primary btn-lg">
                <i class="fas fa-store me-2"></i>Continue Shopping
            </a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Cart Items (<?php echo array_sum($_SESSION['cart']); ?> items)</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item border-bottom p-4" data-product-id="<?php echo $item['product']['id']; ?>">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <img src="<?php echo htmlspecialchars($item['product']['image_url']); ?>" 
                                             class="img-fluid rounded" alt="<?php echo htmlspecialchars($item['product']['name']); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($item['product']['name']); ?></h6>
                                        <p class="text-muted small mb-1"><?php echo htmlspecialchars($item['product']['description']); ?></p>
                                        <?php if ($item['product']['has_deal']): ?>
                                            <span class="badge bg-danger">
                                                <?php echo $item['product']['discount_percentage']; ?>% OFF
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="text-center">
                                            <?php if ($item['product']['has_deal']): ?>
                                                <div class="text-decoration-line-through text-muted small">
                                                    $<?php echo number_format($item['product']['price'], 2); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="fw-bold">$<?php echo number_format($item['price'], 2); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="d-flex align-items-center justify-content-center">
                                            <button class="btn btn-sm btn-outline-secondary update-quantity" 
                                                    data-action="decrease" data-product-id="<?php echo $item['product']['id']; ?>">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <span class="mx-3 fw-bold"><?php echo $item['quantity']; ?></span>
                                            <button class="btn btn-sm btn-outline-secondary update-quantity" 
                                                    data-action="increase" data-product-id="<?php echo $item['product']['id']; ?>">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <div class="fw-bold text-primary mb-2">$<?php echo number_format($item['subtotal'], 2); ?></div>
                                        <button class="btn btn-sm btn-outline-danger remove-from-cart" 
                                                data-product-id="<?php echo $item['product']['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="mt-3">
                    <a href="shop.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-1"></i>Continue Shopping
                    </a>
                    <button class="btn btn-outline-danger ms-2" id="clear-cart">
                        <i class="fas fa-trash me-1"></i>Clear Cart
                    </button>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <span>Subtotal:</span>
                            <span class="fw-bold">$<?php echo number_format($total, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Tax (8%):</span>
                            <span>$<?php echo number_format($total * 0.08, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Delivery Fee:</span>
                            <span>$2.99</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="fw-bold fs-5">Total:</span>
                            <span class="fw-bold fs-5 text-primary">$<?php echo number_format($total + ($total * 0.08) + 2.99, 2); ?></span>
                        </div>
                        
                        <div class="d-grid">
                            <a href="checkout.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                            </a>
                        </div>
                        
                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                Secure checkout guaranteed
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Recommended Products -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">You might also like</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get recommended products
                        $stmt = $pdo->prepare("SELECT * FROM products WHERE featured = 1 AND is_active = 1 LIMIT 3");
                        $stmt->execute();
                        $recommended = $stmt->fetchAll();
                        ?>
                        
                        <?php foreach ($recommended as $product): ?>
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                     class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($product['name']); ?></h6>
                                    <small class="text-muted">$<?php echo number_format($product['price'], 2); ?></small>
                                </div>
                                <button class="btn btn-sm btn-outline-primary add-to-cart" 
                                        data-product-id="<?php echo $product['id']; ?>">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app.js"></script>
<script src="../assets/js/cart.js"></script>
</body>
</html>
