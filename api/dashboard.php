<?php
/**
 * API — Dashboard Stats (Admin only)
 * Margaux Collectionss Ordering System
 *
 * GET /api/dashboard.php                   → all live stats
 * GET /api/dashboard.php?type=stats        → summary stats only
 * GET /api/dashboard.php?type=revenue&days=7 → revenue chart data
 * GET /api/dashboard.php?type=recent       → recent orders
 * GET /api/dashboard.php?type=top_products → top selling products
 */

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';
require_once '../middleware/auth.php';

if (!isLoggedIn()) { apiUnauthorized(); }
if (!isAdmin())    { apiUnauthorized('Admins only.'); }

$db   = getDB();
$type = $_GET['type'] ?? 'all';
$days = max(7, min(90, (int)($_GET['days'] ?? 7)));

$response = [];

// ── Summary stats ──────────────────────────────────────────────────────────
if ($type === 'all' || $type === 'stats') {
    $response['stats'] = [
        'total_revenue'  => (float)$db->query("SELECT IFNULL(SUM(total_amount),0) FROM orders WHERE payment_status='paid'")->fetch_row()[0],
        'total_orders'   => (int)  $db->query("SELECT COUNT(*) FROM orders")->fetch_row()[0],
        'pending_orders' => (int)  $db->query("SELECT COUNT(*) FROM orders WHERE order_status='pending'")->fetch_row()[0],
        'processing_orders' => (int) $db->query("SELECT COUNT(*) FROM orders WHERE order_status='processing'")->fetch_row()[0],
        'completed_orders'  => (int) $db->query("SELECT COUNT(*) FROM orders WHERE order_status='completed'")->fetch_row()[0],
        'total_customers'=> (int)  $db->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetch_row()[0],
        'total_products' => (int)  $db->query("SELECT COUNT(*) FROM products")->fetch_row()[0],
        'low_stock'      => (int)  $db->query("SELECT COUNT(*) FROM products WHERE stock <= 10 AND stock > 0")->fetch_row()[0],
        'out_of_stock'   => (int)  $db->query("SELECT COUNT(*) FROM products WHERE stock = 0")->fetch_row()[0],
    ];
}

// ── Revenue chart data ─────────────────────────────────────────────────────
if ($type === 'all' || $type === 'revenue') {
    $rows = $db->query("
        SELECT DATE(order_date) AS day,
               IFNULL(SUM(total_amount), 0) AS revenue,
               COUNT(*) AS order_count
        FROM orders
        WHERE order_date >= CURDATE() - INTERVAL {$days} DAY
        GROUP BY DATE(order_date)
        ORDER BY day
    ")->fetch_all(MYSQLI_ASSOC);

    $response['revenue'] = $rows;
}

// ── Recent orders ──────────────────────────────────────────────────────────
if ($type === 'all' || $type === 'recent') {
    $rows = $db->query("
        SELECT o.order_id, o.queue_number, o.total_amount, o.order_status,
               o.payment_status, o.payment_method, o.order_method, o.order_date,
               u.name AS customer_name
        FROM orders o
        JOIN users u ON u.user_id = o.user_id
        ORDER BY o.order_date DESC
        LIMIT 8
    ")->fetch_all(MYSQLI_ASSOC);

    $response['recent_orders'] = $rows;
}

// ── Top products ───────────────────────────────────────────────────────────
if ($type === 'all' || $type === 'top_products') {
    $rows = $db->query("
        SELECT p.product_name,
               SUM(oi.quantity) AS units_sold,
               SUM(oi.quantity * oi.price) AS revenue
        FROM order_items oi
        JOIN products p ON p.product_id = oi.product_id
        GROUP BY oi.product_id
        ORDER BY units_sold DESC
        LIMIT 5
    ")->fetch_all(MYSQLI_ASSOC);

    $response['top_products'] = $rows;
}

// ── Payment & Order method distribution ────────────────────────────────────
if ($type === 'all') {
    $response['payment_dist'] = $db->query("
        SELECT payment_method, COUNT(*) AS count
        FROM orders GROUP BY payment_method
    ")->fetch_all(MYSQLI_ASSOC);

    $response['order_method_dist'] = $db->query("
        SELECT order_method, COUNT(*) AS count
        FROM orders GROUP BY order_method
    ")->fetch_all(MYSQLI_ASSOC);

    $response['status_dist'] = $db->query("
        SELECT order_status, COUNT(*) AS count
        FROM orders GROUP BY order_status
    ")->fetch_all(MYSQLI_ASSOC);
}

$response['success']      = true;
$response['generated_at'] = date('Y-m-d H:i:s');

echo json_encode($response);