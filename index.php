<?php
session_start();
require_once 'config/database.php';

// Get featured products and hot deals
$stmt = $pdo->prepare("SELECT * FROM products WHERE featured = 1 LIMIT 8");
$stmt->execute();
$featured_products = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT p.*, hd.discount_percentage FROM hot_deals hd 
                      JOIN products p ON hd.product_id = p.id 
                      WHERE hd.is_active = 1 AND hd.end_date > NOW() LIMIT 4");
$stmt->execute();
$hot_deals = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM sliders WHERE is_active = 1 ORDER BY sort_order");
$stmt->execute();
$sliders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CaféYC - Premium Coffee Experience</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <!-- Hero Slider -->
    <div id="heroCarousel" class="carousel slide mb-5" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <?php foreach($sliders as $index => $slider): ?>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?php echo $index; ?>" 
                        <?php echo $index === 0 ? 'class="active"' : ''; ?>></button>
            <?php endforeach; ?>
        </div>
        <div class="carousel-inner">
            <?php foreach($sliders as $index => $slider): ?>
                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                    <div class="hero-slide" style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('<?php echo $slider['image_url']; ?>')">
                        <div class="container">
                            <div class="row align-items-center min-vh-50">
                                <div class="col-lg-6">
                                    <h1 class="display-4 text-white fw-bold mb-4"><?php echo htmlspecialchars($slider['title']); ?></h1>
                                    <p class="lead text-white mb-4"><?php echo htmlspecialchars($slider['description']); ?></p>
                                    <a href="/cafeyc/customer/shop.php" class="btn btn-primary btn-lg">Shop Now</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Hot Deals Section -->
    <?php if(!empty($hot_deals)): ?>
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12 text-center">
                    <h2 class="display-5 fw-bold text-primary">
                        <i class="fas fa-fire text-danger"></i> Hot Deals
                    </h2>
                    <p class="lead">Limited time offers you can't miss!</p>
                </div>
            </div>
            <div class="row">
                <?php foreach($hot_deals as $deal): ?>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card product-card h-100 shadow-sm border-0">
                        <div class="position-relative">
                            <img src="<?php echo htmlspecialchars($deal['image_url']); ?>" 
                                 class="card-img-top product-image" alt="<?php echo htmlspecialchars($deal['name']); ?>">
                            <div class="position-absolute top-0 end-0 m-2">
                                <span class="badge bg-danger fs-6"><?php echo $deal['discount_percentage']; ?>% OFF</span>
                            </div>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($deal['name']); ?></h5>
                            <p class="card-text text-muted flex-grow-1"><?php echo htmlspecialchars($deal['description']); ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-decoration-line-through text-muted">LKR <?php echo number_format($deal['price'], 2); ?></span>
                                    <span class="fs-5 fw-bold text-primary ms-2">
                                        LKR <?php echo number_format($deal['price'] * (1 - $deal['discount_percentage']/100), 2); ?>
                                    </span>
                                </div>
                                <button class="btn btn-primary btn-sm add-to-cart" data-product-id="<?php echo $deal['id']; ?>">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Featured Products -->
    <section class="py-5">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12 text-center">
                    <h2 class="display-5 fw-bold text-primary">Featured Products</h2>
                    <p class="lead">Discover our most popular café favorites</p>
                </div>
            </div>
            <div class="row">
                <?php foreach($featured_products as $product): ?>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card product-card h-100 shadow-sm border-0">
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                             class="card-img-top product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text text-muted flex-grow-1"><?php echo htmlspecialchars($product['description']); ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fs-5 fw-bold text-primary">LKR<?php echo number_format($product['price'], 2); ?></span>
                                <button class="btn btn-primary btn-sm add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-4">
                <a href="customer/shop.php" class="btn btn-outline-primary btn-lg">View All Products</a>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="py-5 bg-primary text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="display-5 fw-bold mb-4">Welcome to CaféYC</h2>
                    <p class="lead mb-4">
                        Experience the finest coffee culture with our premium selection of handcrafted beverages, 
                        fresh pastries, and exceptional service. From early morning espressos to late-night lattes, 
                        we're here to fuel your passion.
                    </p>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-coffee fa-2x me-3"></i>
                                <div>
                                    <h5>Premium Coffee</h5>
                                    <p class="mb-0">Ethically sourced beans</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-clock fa-2x me-3"></i>
                                <div>
                                    <h5>Quick Service</h5>
                                    <p class="mb-0">Fast & efficient</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="https://images.unsplash.com/photo-1554118811-1e0d58224f24?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                         class="img-fluid rounded" alt="Café Interior">
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
    <script src="assets/js/cart.js"></script>
</body>
</html>