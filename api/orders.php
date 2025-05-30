<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

// Only allow kitchen staff
checkAuth('kitchen');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // --- Search orders by any field and status ---
    if (isset($_GET['action']) && $_GET['action'] === 'search') {
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        $status = isset($_GET['status']) ? trim($_GET['status']) : '';

        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = '(o.id = ? OR u.name LIKE ? OR o.status LIKE ? OR o.delivery_notes LIKE ?)';
            $params[] = intval($q);
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }
        if ($status !== '') {
            $where[] = 'o.status = ?';
            $params[] = $status;
        }

        $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "
            SELECT o.*, u.name AS customer_name
            FROM orders o
            JOIN users u ON o.user_id = u.id
            $where_sql
            ORDER BY o.created_at DESC
            LIMIT 50
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch items for each order
        $order_items = [];
        if ($orders) {
            $order_ids = array_column($orders, 'id');
            $in_query = implode(',', array_fill(0, count($order_ids), '?'));
            $stmt2 = $pdo->prepare("
                SELECT oi.order_id, oi.quantity, p.name AS product_name, oi.unit_price
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id IN ($in_query)
                ORDER BY oi.order_id, oi.id
            ");
            $stmt2->execute($order_ids);
            foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $item) {
                $order_items[$item['order_id']][] = $item;
            }
        }

        // Build response
        $result = [];
        foreach ($orders as $order) {
            $items = isset($order_items[$order['id']]) ? $order_items[$order['id']] : [];
            $total = 0;
            foreach ($items as $item) {
                $total += $item['quantity'] * $item['unit_price'];
            }
            $result[] = [
                'id' => $order['id'],
                'customer_name' => $order['customer_name'],
                'created_at' => $order['created_at'],
                'status' => $order['status'],
                'delivery_notes' => $order['delivery_notes'],
                'items' => $items,
                'total_amount' => $total,
                'currency' => 'LKR'
            ];
        }

        echo json_encode(['success' => true, 'orders' => $result]);
        exit;
    }

    // ...existing code for get_order...
    if (isset($_GET['action']) && $_GET['action'] === 'get_order' && isset($_GET['id'])) {
        $order_id = intval($_GET['id']);

        // Fetch order info and customer name
        $stmt = $pdo->prepare("
            SELECT o.*, u.name AS customer_name
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
            LIMIT 1
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit;
        }

        // Fetch order items with product name and price
        $stmt = $pdo->prepare("
            SELECT oi.quantity, p.name AS product_name, oi.unit_price
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate total
        $total = 0;
        foreach ($items as $item) {
            $total += $item['quantity'] * $item['unit_price'];
        }

        // Prepare response
        $response = [
            'success' => true,
            'order' => [
                'id' => $order['id'],
                'customer_name' => $order['customer_name'],
                'created_at' => $order['created_at'],
                'status' => $order['status'],
                'delivery_notes' => $order['delivery_notes'],
                'items' => $items,
                'total_amount' => $total,
                'currency' => 'LKR'
            ]
        ];

        echo json_encode($response);
        exit;
    }
}

// Invalid request
echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit;
