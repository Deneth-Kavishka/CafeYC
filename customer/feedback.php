<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

checkAuth('customer');

// Get user's completed orders for feedback
$stmt = $pdo->prepare("
    SELECT DISTINCT o.id, o.order_number, o.created_at, o.total_amount,
           CASE WHEN rr.id IS NOT NULL THEN 1 ELSE 0 END as has_review
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN review_ratings rr ON o.id = rr.order_id AND rr.user_id = ?
    WHERE o.user_id = ? AND o.status = 'completed'
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'];
    $rating = $_POST['rating'];
    $review = $_POST['review'];
    
    // Check if user already reviewed this order
    $stmt = $pdo->prepare("SELECT id FROM review_ratings WHERE order_id = ? AND user_id = ?");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("
            INSERT INTO review_ratings (user_id, order_id, rating, review, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        if ($stmt->execute([$_SESSION['user_id'], $order_id, $rating, $review])) {
            $success = "Thank you for your feedback!";
            
            // Update product ratings (simplified average calculation)
            $stmt = $pdo->prepare("
                UPDATE products p 
                SET rating = (
                    SELECT AVG(rr.rating) 
                    FROM review_ratings rr 
                    JOIN order_items oi ON rr.order_id = oi.order_id 
                    WHERE oi.product_id = p.id
                ) 
                WHERE p.id IN (
                    SELECT DISTINCT oi.product_id 
                    FROM order_items oi 
                    WHERE oi.order_id = ?
                )
            ");
            $stmt->execute([$order_id]);
        } else {
            $error = "Failed to submit feedback. Please try again.";
        }
    } else {
        $error = "You have already reviewed this order.";
    }
    
    // Refresh orders list
    $stmt = $pdo->prepare("
        SELECT DISTINCT o.id, o.order_number, o.created_at, o.total_amount,
               CASE WHEN rr.id IS NOT NULL THEN 1 ELSE 0 END as has_review
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN review_ratings rr ON o.id = rr.order_id AND rr.user_id = ?
        WHERE o.user_id = ? AND o.status = 'completed'
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $orders = $stmt->fetchAll();
}

$page_title = "Feedback - CaféYC";
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/nav.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <h1 class="fw-bold text-primary mb-4">
                <i class="fas fa-star me-2"></i>Feedback & Reviews
            </h1>
            <p class="lead text-muted mb-5">Help us improve by sharing your experience with your orders</p>
        </div>
    </div>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (empty($orders)): ?>
        <div class="text-center py-5">
            <i class="fas fa-clipboard-list fa-4x text-muted mb-4"></i>
            <h3>No completed orders</h3>
            <p class="text-muted mb-4">You need to complete an order before you can leave feedback</p>
            <a href="shop.php" class="btn btn-primary btn-lg">
                <i class="fas fa-store me-2"></i>Start Shopping
            </a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <h3 class="fw-bold mb-4">Your Completed Orders</h3>
                
                <?php foreach ($orders as $order): ?>
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h6>
                            <small class="text-muted">
                                <?php echo date('M j, Y', strtotime($order['created_at'])); ?> • 
                                $<?php echo number_format($order['total_amount'], 2); ?>
                            </small>
                        </div>
                        <div>
                            <?php if ($order['has_review']): ?>
                                <span class="badge bg-success">
                                    <i class="fas fa-check me-1"></i>Reviewed
                                </span>
                            <?php else: ?>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" 
                                        data-bs-target="#feedbackModal<?php echo $order['id']; ?>">
                                    <i class="fas fa-star me-1"></i>Leave Review
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!$order['has_review']): ?>
                    <div class="card-body">
                        <p class="text-muted mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            We'd love to hear about your experience with this order!
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Feedback Modal -->
                <?php if (!$order['has_review']): ?>
                <div class="modal fade" id="feedbackModal<?php echo $order['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-star text-warning me-2"></i>
                                    Rate Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Overall Rating</label>
                                        <div class="rating-container">
                                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                                <input type="radio" id="star<?php echo $i; ?>_<?php echo $order['id']; ?>" 
                                                       name="rating" value="<?php echo $i; ?>" class="rating-input">
                                                <label for="star<?php echo $i; ?>_<?php echo $order['id']; ?>" 
                                                       class="rating-label">
                                                    <i class="fas fa-star"></i>
                                                </label>
                                            <?php endfor; ?>
                                        </div>
                                        <small class="text-muted">Click on stars to rate (5 = Excellent, 1 = Poor)</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="review<?php echo $order['id']; ?>" class="form-label fw-bold">
                                            Your Review
                                        </label>
                                        <textarea class="form-control" id="review<?php echo $order['id']; ?>" 
                                                  name="review" rows="4" required
                                                  placeholder="Tell us about your experience with this order..."></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-1"></i>Submit Review
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <div class="col-lg-4">
                <!-- Feedback Guidelines -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-lightbulb me-2"></i>Review Guidelines
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Be honest and specific about your experience
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Mention food quality, delivery time, and service
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Keep your review respectful and constructive
                            </li>
                            <li class="mb-0">
                                <i class="fas fa-check text-success me-2"></i>
                                Help other customers make informed decisions
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Contact Support -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-headset me-2"></i>Need Help?
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">Have an issue that needs immediate attention?</p>
                        <div class="d-grid">
                            <button class="btn btn-outline-primary">
                                <i class="fas fa-phone me-2"></i>Contact Support
                            </button>
                        </div>
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                Available 24/7 • Response within 1 hour
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.rating-container {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 5px;
    margin-bottom: 10px;
}

.rating-input {
    display: none;
}

.rating-label {
    cursor: pointer;
    font-size: 1.5rem;
    color: #ddd;
    transition: color 0.2s;
}

.rating-input:checked ~ .rating-label,
.rating-label:hover,
.rating-label:hover ~ .rating-label {
    color: #ffc107;
}
</style>

<?php include '../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
