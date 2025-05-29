<?php
function checkAuth($required_role = null) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /auth/login.php');
        exit;
    }
    
    if ($required_role && $_SESSION['role'] !== $required_role && $_SESSION['role'] !== 'admin') {
        header('Location: /auth/login.php?error=insufficient_permissions');
        exit;
    }
    
    return true;
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirectByRole($role) {
    switch($role) {
        case 'admin':
            return '/cafeyc/admin/dashboard.php';
        case 'cashier':
            return '/cafeyc/cashier/dashboard.php';
        case 'kitchen':
            return '/cafeyc/kitchen/dashboard.php';
        case 'inventory':
            return '/cafeyc/inventory/dashboard.php';
        case 'delivery':
            return '/cafeyc/delivery/dashboard.php';
        case 'customer':
        default:
            return '/cafeyc/customer/dashboard.php';
    }
}
?>
