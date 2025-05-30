<footer class="bg-dark text-white py-5 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <h5 class="fw-bold">CaféYC</h5>
                <p class="text-white">Premium coffee experience with exceptional service and quality products.</p>
                <div class="d-flex gap-3">
                    <a href="#" class="text-white"><i class="fab fa-facebook fa-lg"></i></a>
                    <a href="#" class="text-white"><i class="fab fa-instagram fa-lg"></i></a>
                    <a href="#" class="text-white"><i class="fab fa-twitter fa-lg"></i></a>
                </div>
            </div>
            <div class="col-lg-2 mb-4">
                <h6 class="fw-bold">Quick Links</h6>
                <ul class="list-unstyled">
                    <li><a href="/cafeyc/" class="text-white text-decoration-none">Home</a></li>
                    <li><a href="/cafeyc/customer/shop.php" class="text-white text-decoration-none">Shop</a></li>
                    <li>
                        <a href="/cafeyc/customer/orders.php" class="text-white text-decoration-none" id="ordersLink">Orders</a>
                    </li>
                    <li>
                        <a href="/cafeyc/customer/feedback.php" class="text-white text-decoration-none" id="feedbackLink">Feedback</a>
                    </li>
                </ul>
            </div>
            <div class="col-lg-3 mb-4">
                <h6 class="fw-bold">Contact Info</h6>
                <ul class="list-unstyled text-white">
                    <li><i class="fas fa-map-marker-alt me-2"></i>Mathale Town, Sri lanka</li>
                    <li><i class="fas fa-phone me-2"></i>+94 (76) 123-4567</li>
                    <li><i class="fas fa-envelope me-2"></i>info@cafeyc.com</li>
                </ul>
            </div>
            <div class="col-lg-3 mb-4">
                <h6 class="fw-bold">Opening Hours</h6>
                <ul class="list-unstyled text-white">
                    <li>Monday - Friday: 6:00 AM - 10:00 PM</li>
                    <li>Saturday: 7:00 AM - 11:00 PM</li>
                    <li>Sunday: 8:00 AM - 9:00 PM</li>
                </ul>
            </div>
        </div>
        <hr class="my-4">
        <div class="text-center">
            <p class="mb-0 text-white">&copy; 2025 CaféYC. All rights reserved.</p>
        </div>
    </div>
</footer>
<!-- Login required popup -->
<div id="loginRequiredMsg" class="position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 9999; display: none; min-width: 320px;">
    <div class="alert alert-warning alert-dismissible fade show shadow" role="alert">
        <strong>First you need to Login</strong>
        <button type="button" class="btn-close" onclick="hideLoginMsg()"></button>
    </div>
</div>
<script>
    // Pass PHP login status to JS
    var isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

    function showLoginMsg() {
        var msg = document.getElementById('loginRequiredMsg');
        if (msg) {
            msg.style.display = 'block';
            setTimeout(hideLoginMsg, 1000);
        }
    }
    function hideLoginMsg() {
        var msg = document.getElementById('loginRequiredMsg');
        if (msg) msg.style.display = 'none';
    }

    function handleProtectedLink(e, url) {
        if (!isLoggedIn) {
            e.preventDefault();
            showLoginMsg();
            setTimeout(function() {
                window.location.href = "/cafeyc/auth/login.php?msg=" + encodeURIComponent("First you need to Login");
            }, 500);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        var ordersLink = document.getElementById('ordersLink');
        var feedbackLink = document.getElementById('feedbackLink');
        if (ordersLink) {
            ordersLink.addEventListener('click', function(e) {
                handleProtectedLink(e, '/cafeyc/customer/orders.php');
            });
        }
        if (feedbackLink) {
            feedbackLink.addEventListener('click', function(e) {
                handleProtectedLink(e, '/cafeyc/customer/feedback.php');
            });
        }

        // Show popup if redirected from login.php with msg param
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('msg') === 'First you need to Login') {
            showLoginMsg();
        }
    });
</script>
