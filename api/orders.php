<?php
/**
 * API — Orders
 * Margaux Collections Ordering System
 *
 * GET  /api/orders.php                     → my orders (customer) or all orders (admin)
 * GET  /api/orders.php?id=5                → single order with items
 * GET  /api/orders.php?status=pending      → filter by status (admin only)
 * POST /api/orders.php  { action: 'update_status', order_id, status, payment_status }
 *                                          → update order (admin only)
 */

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';
require_once '../middleware/auth.php';

if (!isLoggedIn()) {
    apiUnauthorized();
}

$db     = getDB();
$userId = (int)$_SESSION['user_id'];
$admin  = isAdmin();

// ── POST: update order status (admin only) ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$admin) { apiUnauthorized('Admins only.'); }

    $input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $input['action'] ?? '';

    if ($action === 'update_status') {
    $orderId       = (int)($input['order_id'] ?? 0);
    $orderStatus   = $input['order_status'] ?? '';
    $paymentStatus = $input['payment_status'] ?? '';

    $validOrder   = ['pending', 'processing', 'completed'];
    $validPayment = ['pending', 'paid'];

    if (!in_array($orderStatus, $validOrder) || !in_array($paymentStatus, $validPayment)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status values.']);
        exit;
    }

    $stmt = $db->prepare("UPDATE orders SET order_status=?, payment_status=? WHERE order_id=?");
    $stmt->bind_param('ssi', $orderStatus, $paymentStatus, $orderId);
    $success = $stmt->execute();
    $stmt->close();

    // ── Notify the customer ──────────────────────────────────────────────
    if ($success) {
        require_once '../includes/notify_helper.php';
        $r = $db->prepare("SELECT user_id FROM orders WHERE order_id=?");
        $r->bind_param('i', $orderId);
        $r->execute();
        $row = $r->get_result()->fetch_assoc();
        $r->close();
        if ($row) {
            createNotification($db, (int)$row['user_id'], $orderId, $orderStatus);
        }
    }
    // ────────────────────────────────────────────────────────────────────

    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Order updated successfully.' : 'Update failed.'
    ]);
    exit;
}

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit;
}

// ── GET: single order with items ───────────────────────────────────────────
if (!empty($_GET['id'])) {
    $orderId = (int)$_GET['id'];

    // Customers can only see their own orders
    $sql = $admin
        ? "SELECT o.*, u.name AS customer_name, u.email FROM orders o JOIN users u ON u.user_id=o.user_id WHERE o.order_id=?"
        : "SELECT o.*, u.name AS customer_name FROM orders o JOIN users u ON u.user_id=o.user_id WHERE o.order_id=? AND o.user_id=?";

    $stmt = $db->prepare($sql);
    if ($admin) {
        $stmt->bind_param('i', $orderId);
    } else {
        $stmt->bind_param('ii', $orderId, $userId);
    }
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found.']);
        exit;
    }

    // Fetch order items
    $stmt = $db->prepare("
        SELECT oi.*, p.product_name, p.image
        FROM order_items oi
        JOIN products p ON p.product_id = oi.product_id
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['success' => true, 'order' => $order, 'items' => $items]);
    exit;
}

// ── GET: list orders ───────────────────────────────────────────────────────
$where  = ['1=1'];
$params = [];
$types  = '';

if (!$admin) {
    // Customers only see their own orders
    $where[]  = 'o.user_id = ?';
    $types   .= 'i';
    $params[] = $userId;
}

if (!empty($_GET['status'])) {
    $where[]  = 'o.order_status = ?';
    $types   .= 's';
    $params[] = $_GET['status'];
}

if (!empty($_GET['payment'])) {
    $where[]  = 'o.payment_status = ?';
    $types   .= 's';
    $params[] = $_GET['payment'];
}

$sql  = "SELECT o.*, u.name AS customer_name
         FROM orders o JOIN users u ON u.user_id=o.user_id
         WHERE " . implode(' AND ', $where) . "
         ORDER BY o.order_date DESC";
$stmt = $db->prepare($sql);
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode([
    'success' => true,
    'count'   => count($orders),
    'orders'  => $orders
]);
