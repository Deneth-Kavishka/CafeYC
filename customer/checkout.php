<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

checkAuth('customer');

// Redirect if cart is empty
if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

// Get user addresses
$stmt = $pdo->prepare("SELECT * FROM customer_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$addresses = $stmt->fetchAll();

// Calculate totals
$cart_items = [];
$subtotal = 0;

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
    
    $subtotal += $price * $quantity;
}

$tax = $subtotal * 0.08;
$delivery_fee = 2.99;
$total = $subtotal + $tax + $delivery_fee;

// Process order
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address_id = $_POST['address_id'] ?? null;
    $payment_method = $_POST['payment_method'];
    $delivery_notes = $_POST['delivery_notes'] ?? '';
    
    if (!$address_id && !empty($_POST['new_address'])) {
        // Add new address
        $stmt = $pdo->prepare("INSERT INTO customer_addresses (user_id, address, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], $_POST['new_address']]);
        $address_id = $pdo->lastInsertId();
    }
    
    if ($address_id) {
        try {
            $pdo->beginTransaction();
            
            // Create order
            $order_number = 'ORD' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, order_number, total_items, subtotal, tax_amount, delivery_fee, total_amount, 
                                   delivery_address_id, payment_method, delivery_notes, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([
                $_SESSION['user_id'], 
                $order_number,
                array_sum($_SESSION['cart']),
                $subtotal,
                $tax,
                $delivery_fee,
                $total,
                $address_id,
                $payment_method,
                $delivery_notes
            ]);
            
            $order_id = $pdo->lastInsertId();
            
            // Add order items
            foreach ($cart_items as $item) {
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $order_id,
                    $item['product']['id'],
                    $item['quantity'],
                    $item['price'],
                    $item['subtotal']
                ]);
            }
            
            $pdo->commit();
            
            // Clear cart
            unset($_SESSION['cart']);
            
            header("Location: orders.php?success=order_placed&order_id=$order_id");
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Order placement failed. Please try again.";
        }
    } else {
        $error = "Please select or add a delivery address.";
    }
}

$page_title = "Checkout - CaféYC";
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/nav.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <h1 class="fw-bold text-primary mb-4">
                <i class="fas fa-credit-card me-2"></i>Checkout
            </h1>
        </div>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="row">
            <div class="col-lg-8">
                <!-- Delivery Address -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-map-marker-alt me-2"></i>Delivery Address
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($addresses)): ?>
                            <div class="mb-3">
                                <label for="new_address" class="form-label">Delivery Address</label>
                                <textarea class="form-control" id="new_address" name="new_address" rows="3" required 
                                         placeholder="Enter your complete delivery address"></textarea>
                            </div>
                        <?php else: ?>
                            <?php foreach ($addresses as $index => $address): ?>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="address_id" value="<?php echo $address['id']; ?>" 
                                           id="address<?php echo $address['id']; ?>" <?php echo $index === 0 ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="address<?php echo $address['id']; ?>">
                                        <div class="fw-bold">
                                            <?php echo htmlspecialchars($address['address']); ?>
                                            <?php if ($address['is_default']): ?>
                                                <span class="badge bg-primary ms-2">Default</span>
                                            <?php endif; ?>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="address_id" value="" id="new_address_option">
                                <label class="form-check-label" for="new_address_option">
                                    <strong>Use a new address</strong>
                                </label>
                            </div>
                            
                            <div id="new_address_field" class="mt-3" style="display: none;">
                                <textarea class="form-control" name="new_address" rows="3" 
                                         placeholder="Enter your complete delivery address"></textarea>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Payment Method -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-credit-card me-2"></i>Payment Method
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="payment_method" value="cash" id="cash" checked>
                            <label class="form-check-label" for="cash">
                                <i class="fas fa-money-bill-wave text-success me-2"></i>
                                <strong>Cash on Delivery</strong>
                                <small class="text-muted d-block">Pay when your order arrives</small>
                            </label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="payment_method" value="card" id="card">
                            <label class="form-check-label" for="card">
                                <i class="fas fa-credit-card text-primary me-2"></i>
                                <strong>Credit/Debit Card</strong>
                                <small class="text-muted d-block">Pay securely online</small>
                            </label>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" value="wallet" id="wallet">
                            <label class="form-check-label" for="wallet">
                                <i class="fas fa-wallet text-info me-2"></i>
                                <strong>Digital Wallet</strong>
                                <small class="text-muted d-block">PayPal, Apple Pay, Google Pay</small>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Delivery Notes -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-sticky-note me-2"></i>Delivery Notes
                        </h5>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" name="delivery_notes" rows="3" 
                                 placeholder="Any special instructions for delivery? (Optional)"></textarea>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Order Summary -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <!-- Order Items -->
                        <div class="mb-3">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($item['product']['name']); ?></h6>
                                        <small class="text-muted">
                                            $<?php echo number_format($item['price'], 2); ?> × <?php echo $item['quantity']; ?>
                                        </small>
                                    </div>
                                    <div class="fw-bold">$<?php echo number_format($item['subtotal'], 2); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <hr>
                        
                        <!-- Totals -->
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>$<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax (8%):</span>
                            <span>$<?php echo number_format($tax, 2); ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span>Delivery Fee:</span>
                            <span>$<?php echo number_format($delivery_fee, 2); ?></span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-4">
                            <span class="fw-bold fs-5">Total:</span>
                            <span class="fw-bold fs-5 text-primary">$<?php echo number_format($total, 2); ?></span>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-check me-2"></i>Place Order
                            </button>
                            <a href="cart.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Back to Cart
                            </a>
                        </div>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                Your order is secure and protected
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app.js"></script>
<script>
document.getElementById('new_address_option').addEventListener('change', function() {
    document.getElementById('new_address_field').style.display = this.checked ? 'block' : 'none';
});

// Hide new address field when other addresses are selected
document.querySelectorAll('input[name="address_id"]:not(#new_address_option)').forEach(function(radio) {
    radio.addEventListener('change', function() {
        if (this.checked) {
            document.getElementById('new_address_field').style.display = 'none';
        }
    });
});
</script>
</body>
</html>
