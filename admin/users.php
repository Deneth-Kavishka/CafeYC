<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

checkAuth('admin');

// Handle activate/deactivate actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    if ($_POST['action'] === 'activate') {
        $stmt = $pdo->prepare("UPDATE users SET is_active = 1, deactivated_at = NULL WHERE id = ?");
        $stmt->execute([$user_id]);
    } elseif ($_POST['action'] === 'deactivate') {
        $stmt = $pdo->prepare("UPDATE users SET is_active = 0, deactivated_at = NOW() WHERE id = ?");
        $stmt->execute([$user_id]);
    }
    header("Location: users.php");
    exit;
}

// Handle registration
$register_success = null;
$register_error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_user'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $register_error = "Email already exists.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, phone, address, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $email, $hashed, $role, $phone, $address, $is_active])) {
            $register_success = "User registered successfully!";
        } else {
            $register_error = "Registration failed.";
        }
    }
}

// Handle update user
$edit_success = null;
$edit_error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $user_id = intval($_POST['edit_user_id']);
    $name = trim($_POST['edit_name']);
    $email = trim($_POST['edit_email']);
    $role = $_POST['edit_role'];
    $phone = trim($_POST['edit_phone']);
    $address = trim($_POST['edit_address']);
    $is_active = isset($_POST['edit_is_active']) ? 1 : 0;
    $new_password = trim($_POST['edit_password'] ?? '');

    // Check for duplicate email (excluding current user)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        $edit_error = "Email already exists for another user.";
    } else {
        if ($new_password !== '') {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role=?, phone=?, address=?, is_active=?, password=?, updated_at=NOW() WHERE id=?");
            $params = [$name, $email, $role, $phone, $address, $is_active, $hashed, $user_id];
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role=?, phone=?, address=?, is_active=?, updated_at=NOW() WHERE id=?");
            $params = [$name, $email, $role, $phone, $address, $is_active, $user_id];
        }
        if ($stmt->execute($params)) {
            $edit_success = "User updated successfully!";
        } else {
            $edit_error = "Failed to update user.";
        }
    }
}

// Fetch all system users (not customers)
$stmt = $pdo->prepare("SELECT * FROM users WHERE role != 'customer' ORDER BY role, name");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "System Users - CaféYC";
?>
<?php include '../includes/header.php'; ?>
<body class="bg-light">
    <div class="d-flex">
        <!-- Sidebar (same as admin dashboard) -->
        <nav class="sidebar bg-dark text-white p-3" style="width: 250px; min-height: 100vh;">
            <div class="text-center mb-4">
                <h4 class="fw-bold">
                    <i class="fas fa-coffee me-2"></i>CaféYC Admin
                </h4>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link text-white<?php if(basename($_SERVER['PHP_SELF']) == 'dashboard.php') echo ' active'; ?>" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white<?php if(basename($_SERVER['PHP_SELF']) == 'orders.php') echo ' active'; ?>" href="orders.php">
                        <i class="fas fa-shopping-bag me-2"></i>Orders
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white<?php if(basename($_SERVER['PHP_SELF']) == 'products.php') echo ' active'; ?>" href="products.php">
                        <i class="fas fa-box me-2"></i>Products
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white<?php if(basename($_SERVER['PHP_SELF']) == 'categories.php') echo ' active'; ?>" href="categories.php">
                        <i class="fas fa-tags me-2"></i>Categories
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white<?php if(basename($_SERVER['PHP_SELF']) == 'brands.php') echo ' active'; ?>" href="brands.php">
                        <i class="fas fa-star me-2"></i>Brands
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white<?php if(basename($_SERVER['PHP_SELF']) == 'suppliers.php') echo ' active'; ?>" href="suppliers.php">
                        <i class="fas fa-truck me-2"></i>Suppliers
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white<?php if(basename($_SERVER['PHP_SELF']) == 'customers.php') echo ' active'; ?>" href="customers.php">
                        <i class="fas fa-users me-2"></i>Customers
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white<?php if(basename($_SERVER['PHP_SELF']) == 'sliders.php') echo ' active'; ?>" href="sliders.php">
                        <i class="fas fa-images me-2"></i>Sliders
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white<?php if(basename($_SERVER['PHP_SELF']) == 'hot-deals.php') echo ' active'; ?>" href="hot-deals.php">
                        <i class="fas fa-fire me-2"></i>Hot Deals
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white<?php if(basename($_SERVER['PHP_SELF']) == 'analytics.php') echo ' active'; ?>" href="analytics.php">
                        <i class="fas fa-chart-bar me-2"></i>Analytics
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white<?php if(basename($_SERVER['PHP_SELF']) == 'users.php') echo ' active'; ?>" href="users.php">
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
            <div class="container py-4">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <h2 class="mb-0"><i class="fas fa-user-cog me-2"></i>System Users</h2>
                </div>
                <?php if ($register_success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($register_success); ?></div>
                <?php elseif ($register_error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($register_error); ?></div>
                <?php endif; ?>
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <strong><i class="fas fa-user-plus me-2"></i>Register New System User</strong>
                    </div>
                    <div class="card-body">
                        <form method="post" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" required maxlength="100">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required maxlength="100">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Role</label>
                                <select name="role" class="form-select" required>
                                    <option value="admin">Admin</option>
                                    <option value="kitchen">Kitchen</option>
                                    <option value="cashier">Cashier</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required minlength="6">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Contact Number</label>
                                <input type="text" name="phone" class="form-control" maxlength="30">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-control" maxlength="255">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" name="register_user" class="btn btn-success px-4">
                                    <i class="fas fa-user-plus me-1"></i>Register
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <strong><i class="fas fa-users me-2"></i>Manage System Users</strong>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($edit_success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($edit_success); ?></div>
                        <?php elseif ($edit_error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($edit_error); ?></div>
                        <?php endif; ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Registered</th>
                                        <th>Deactivated</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $idx => $user): ?>
                                        <tr>
                                            <td><?php echo $idx + 1; ?></td>
                                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    echo $user['role'] === 'admin' ? 'primary' : ($user['role'] === 'kitchen' ? 'info' : 'warning text-dark');
                                                ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $user['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo date('Y-m-d', strtotime($user['created_at'] ?? 'now')); ?>
                                            </td>
                                            <td>
                                                <?php echo !$user['is_active'] && !empty($user['deactivated_at']) ? date('Y-m-d', strtotime($user['deactivated_at'])) : '-'; ?>
                                            </td>
                                            <td>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" name="action" value="<?php echo $user['is_active'] ? 'deactivate' : 'activate'; ?>"
                                                        class="btn btn-sm <?php echo $user['is_active'] ? 'btn-outline-danger' : 'btn-outline-success'; ?>"
                                                        title="Toggle Active/Inactive">
                                                        <i class="fas <?php echo $user['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i>
                                                    </button>
                                                </form>
                                                <!-- Edit icon button -->
                                                <button type="button" class="btn btn-sm btn-outline-primary ms-1" title="Edit"
                                                    data-user='<?php echo json_encode($user, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>'
                                                    onclick="showEditUserModal(this)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">No system users found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Edit User Modal -->
                <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <form method="post" class="modal-content" autocomplete="off">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body row g-3">
                                <input type="hidden" name="edit_user_id" id="edit_user_id">
                                <div class="col-12">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="edit_name" id="edit_name" class="form-control" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="edit_email" id="edit_email" class="form-control" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Role</label>
                                    <select name="edit_role" id="edit_role" class="form-select" required>
                                        <option value="admin">Admin</option>
                                        <option value="kitchen">Kitchen</option>
                                        <option value="cashier">Cashier</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Contact Number</label>
                                    <input type="text" name="edit_phone" id="edit_phone" class="form-control">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Address</label>
                                    <input type="text" name="edit_address" id="edit_address" class="form-control">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Change Password <small class="text-muted">(leave blank to keep current)</small></label>
                                    <input type="password" name="edit_password" id="edit_password" class="form-control" minlength="6" autocomplete="new-password">
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="edit_is_active" id="edit_is_active" value="1">
                                        <label class="form-check-label" for="edit_is_active">Active</label>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="update_user" class="btn btn-primary">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- Ensure Bootstrap JS is loaded -->
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
                <script>
                function showEditUserModal(btn) {
                    var userStr = btn.getAttribute('data-user');
                    try {
                        var user = JSON.parse(userStr);
                    } catch (e) {
                        alert('Could not load user data for editing.');
                        return;
                    }
                    document.getElementById('edit_user_id').value = user.id || '';
                    document.getElementById('edit_name').value = user.name || '';
                    document.getElementById('edit_email').value = user.email || '';
                    document.getElementById('edit_role').value = user.role || '';
                    document.getElementById('edit_phone').value = user.phone || '';
                    document.getElementById('edit_address').value = user.address || '';
                    document.getElementById('edit_password').value = '';
                    document.getElementById('edit_is_active').checked = user.is_active == 1;
                    // Remove any previous modal-backdrop if stuck
                    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                    // Show modal (Bootstrap 5)
                    var modalEl = document.getElementById('editUserModal');
                    if (typeof bootstrap !== "undefined" && bootstrap.Modal) {
                        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                        modal.show();
                    } else {
                        alert('Bootstrap JS is not loaded. Please include Bootstrap 5 JS before </body>.');
                    }
                }
                </script>
            </div>
        </div>
    </div>
</body>
</html>
