<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

checkAuth('admin');

// Handle supplier actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = $_POST['name'];
                $contact_person = $_POST['contact_person'];
                $email = $_POST['email'];
                $phone = $_POST['phone'];
                $address = $_POST['address'];
                
                $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact_person, email, phone, address, is_active, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
                if ($stmt->execute([$name, $contact_person, $email, $phone, $address])) {
                    $success = "Supplier added successfully!";
                } else {
                    $error = "Failed to add supplier.";
                }
                break;
                
            case 'edit':
                $id = $_POST['supplier_id'];
                $name = $_POST['name'];
                $contact_person = $_POST['contact_person'];
                $email = $_POST['email'];
                $phone = $_POST['phone'];
                $address = $_POST['address'];
                
                $stmt = $pdo->prepare("UPDATE suppliers SET name = ?, contact_person = ?, email = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?");
                if ($stmt->execute([$name, $contact_person, $email, $phone, $address, $id])) {
                    $success = "Supplier updated successfully!";
                } else {
                    $error = "Failed to update supplier.";
                }
                break;
                
            case 'delete':
                $id = $_POST['supplier_id'];
                $stmt = $pdo->prepare("UPDATE suppliers SET is_active = 0 WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $success = "Supplier deleted successfully!";
                } else {
                    $error = "Failed to delete supplier.";
                }
                break;
        }
    }
}

// Get suppliers with product counts
$stmt = $pdo->prepare("
    SELECT s.*, COUNT(p.id) as product_count 
    FROM suppliers s 
    LEFT JOIN products p ON s.id = p.supplier_id AND p.is_active = 1 
    WHERE s.is_active = 1 
    GROUP BY s.id 
    ORDER BY s.name
");
$stmt->execute();
$suppliers = $stmt->fetchAll();

$page_title = "Suppliers Management - CaféYC Admin";
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
                    <a class="nav-link text-white active" href="suppliers.php">
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
                <h5 class="mb-0">Suppliers Management</h5>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                    <i class="fas fa-plus me-2"></i>Add New Supplier
                </button>
            </nav>

            <!-- Suppliers Content -->
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

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-truck me-2"></i>All Suppliers (<?php echo count($suppliers); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Supplier Name</th>
                                        <th>Contact Person</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Products</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($suppliers as $supplier): ?>
                                    <tr>
                                        <td>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($supplier['name']); ?></h6>
                                            <small class="text-muted"><?php echo htmlspecialchars($supplier['address']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($supplier['contact_person']); ?></td>
                                        <td><?php echo htmlspecialchars($supplier['email']); ?></td>
                                        <td><?php echo htmlspecialchars($supplier['phone']); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $supplier['product_count']; ?> products</span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="editSupplier(<?php echo htmlspecialchars(json_encode($supplier)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="confirmDelete(<?php echo $supplier['id']; ?>, '<?php echo htmlspecialchars($supplier['name']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Supplier Modal -->
    <div class="modal fade" id="addSupplierModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="addName" class="form-label">Supplier Name</label>
                                <input type="text" class="form-control" id="addName" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="addContactPerson" class="form-label">Contact Person</label>
                                <input type="text" class="form-control" id="addContactPerson" name="contact_person" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="addEmail" class="form-label">Email</label>
                                <input type="email" class="form-control" id="addEmail" name="email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="addPhone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="addPhone" name="phone" required>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="addAddress" class="form-label">Address</label>
                                <textarea class="form-control" id="addAddress" name="address" rows="3" required></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Supplier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Supplier Modal -->
    <div class="modal fade" id="editSupplierModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="supplier_id" id="editSupplierId">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editName" class="form-label">Supplier Name</label>
                                <input type="text" class="form-control" id="editName" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editContactPerson" class="form-label">Contact Person</label>
                                <input type="text" class="form-control" id="editContactPerson" name="contact_person" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editEmail" class="form-label">Email</label>
                                <input type="email" class="form-control" id="editEmail" name="email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editPhone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="editPhone" name="phone" required>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="editAddress" class="form-label">Address</label>
                                <textarea class="form-control" id="editAddress" name="address" rows="3" required></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Supplier</button>
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
                    <p>Are you sure you want to delete the supplier "<span id="deleteSupplierName"></span>"?</p>
                    <p class="text-muted">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="supplier_id" id="deleteSupplierId">
                        <button type="submit" class="btn btn-danger">Delete Supplier</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    
    <script>
    function editSupplier(supplier) {
        document.getElementById('editSupplierId').value = supplier.id;
        document.getElementById('editName').value = supplier.name;
        document.getElementById('editContactPerson').value = supplier.contact_person;
        document.getElementById('editEmail').value = supplier.email;
        document.getElementById('editPhone').value = supplier.phone;
        document.getElementById('editAddress').value = supplier.address;
        new bootstrap.Modal(document.getElementById('editSupplierModal')).show();
    }

    function confirmDelete(supplierId, supplierName) {
        document.getElementById('deleteSupplierId').value = supplierId;
        document.getElementById('deleteSupplierName').textContent = supplierName;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
    </script>
</body>
</html>
