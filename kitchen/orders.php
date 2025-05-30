<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

checkAuth('kitchen');

// Fetch all orders (not just queue)
$stmt = $pdo->prepare("
    SELECT o.*, u.name as customer_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
");
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch items for each order
$order_items = [];
if ($orders) {
    $order_ids = array_column($orders, 'id');
    $in_query = implode(',', array_fill(0, count($order_ids), '?'));
    $stmt = $pdo->prepare("
        SELECT oi.order_id, oi.quantity, p.name as product_name, oi.unit_price
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id IN ($in_query)
        ORDER BY oi.order_id, oi.id
    ");
    $stmt->execute($order_ids);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $order_items[$item['order_id']][] = $item;
    }
}

$page_title = "Order Table - CaféYC";
?>
<?php include '../includes/header.php'; ?>
<body class="bg-light">
    <div class="d-flex">
        <!-- Kitchen Side Panel (same as dashboard) -->
        <nav class="sidebar bg-dark text-white p-3" style="width: 250px; min-height: 100vh;">
            <div class="text-center mb-4">
                <h4 class="fw-bold">
                    <i class="fas fa-utensils me-2"></i>CaféYC Kitchen
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
                        <i class="fas fa-list me-2"></i>Order Queue
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="menu.php">
                        <i class="fas fa-book me-2"></i>Menu Items
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="reports.php">
                        <i class="fas fa-chart-bar me-2"></i>Kitchen Reports
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
                    <h2 class="mb-0"><i class="fas fa-list me-2"></i>All Orders</h2>
                    <!-- Search Bar and Status Filter -->
                    <form id="orderSearchForm" class="d-flex align-items-center gap-2" style="max-width: 500px;" onsubmit="searchOrders(); return false;">
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="orderSearchInput" placeholder="Search anything..." aria-label="Search">
                            <button class="btn" style="background:#000;color:#fff;" type="submit" title="Search">
                                <i class="fas fa-search"></i>
                            </button>
                            <button class="btn btn-outline-secondary" type="button" onclick="resetSearch()" title="Reset">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <select class="form-select form-select-sm ms-2" id="statusFilter" style="width:auto; min-width:120px;">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </form>
                </div>
                <div id="ordersTableWrapper">
                <?php if (empty($orders)): ?>
                    <div class="alert alert-info">No orders found.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle" id="ordersTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Order Time</th>
                                    <th>Status</th>
                                    <th>Items <span class="text-muted small">(Unit Price)</span></th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td>
                                            <?php
                                                $order_time = new DateTime($order['created_at']);
                                                echo $order_time->format('Y-m-d g:i A');
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php
                                                switch ($order['status']) {
                                                    case 'pending': echo 'warning text-dark'; break;
                                                    case 'processing': echo 'info'; break;
                                                    case 'completed': echo 'success'; break;
                                                    case 'cancelled': echo 'danger'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($order_items[$order['id']])): ?>
                                                <ul class="mb-0 ps-3">
                                                    <?php foreach ($order_items[$order['id']] as $item): ?>
                                                        <li>
                                                            <?php echo htmlspecialchars($item['quantity'] . 'x ' . $item['product_name']); ?>
                                                            <span class="text-muted small">(LKR <?php echo number_format($item['unit_price'], 2); ?>)</span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <span class="text-muted">No items</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $order['delivery_notes'] ? htmlspecialchars($order['delivery_notes']) : '<span class="text-muted">-</span>'; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script>
    // Enhanced search: search on enter, submit, or filter change
    document.getElementById('orderSearchForm').addEventListener('submit', function(e) {
        e.preventDefault();
        searchOrders();
    });
    document.getElementById('orderSearchInput').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchOrders();
        }
    });
    document.getElementById('statusFilter').addEventListener('change', function() {
        searchOrders();
    });

    function searchOrders() {
        const q = document.getElementById('orderSearchInput').value.trim();
        const status = document.getElementById('statusFilter').value;
        let url = '../api/orders.php?action=search&q=' + encodeURIComponent(q);
        if (status) url += '&status=' + encodeURIComponent(status);
        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    renderOrdersTable(data.orders);
                } else {
                    document.getElementById('ordersTableWrapper').innerHTML =
                        '<div class="alert alert-danger">No matching orders found.</div>';
                }
            })
            .catch(() => {
                document.getElementById('ordersTableWrapper').innerHTML =
                    '<div class="alert alert-danger">Error loading orders.</div>';
            });
    }
    function resetSearch() {
        document.getElementById('orderSearchInput').value = '';
        document.getElementById('statusFilter').value = '';
        location.reload();
    }
    function renderOrdersTable(orders) {
        if (!orders.length) {
            document.getElementById('ordersTableWrapper').innerHTML =
                '<div class="alert alert-info">No orders found.</div>';
            return;
        }
        let html = `<div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Order Time</th>
                        <th>Status</th>
                        <th>Items <span class="text-muted small">(Unit Price)</span></th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>`;
        orders.forEach(order => {
            html += `<tr>
                <td>${String(order.id).padStart(6, '0')}</td>
                <td>${escapeHtml(order.customer_name)}</td>
                <td>${new Date(order.created_at).toLocaleString()}</td>
                <td><span class="badge bg-${
                    order.status === 'pending' ? 'warning text-dark'
                    : order.status === 'processing' ? 'info'
                    : order.status === 'completed' ? 'success'
                    : order.status === 'cancelled' ? 'danger'
                    : 'secondary'
                }">${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span></td>
                <td>`;
            if (order.items && order.items.length) {
                html += '<ul class="mb-0 ps-3">';
                order.items.forEach(item => {
                    html += `<li>${item.quantity}x ${escapeHtml(item.product_name)} <span class="text-muted small">(LKR ${parseFloat(item.unit_price).toFixed(2)})</span></li>`;
                });
                html += '</ul>';
            } else {
                html += '<span class="text-muted">No items</span>';
            }
            html += `</td>
                <td>${order.delivery_notes ? escapeHtml(order.delivery_notes) : '<span class="text-muted">-</span>'}</td>
            </tr>`;
        });
        html += '</tbody></table></div>';
        document.getElementById('ordersTableWrapper').innerHTML = html;
    }
    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    </script>
</body>
</html>
