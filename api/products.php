<?php
/**
 * API — Products
 * Margaux Collections Ordering System
 *
 * GET /api/products.php                    → all products
 * GET /api/products.php?type=member        → member products
 * GET /api/products.php?type=loose         → loose products
 * GET /api/products.php?type=package       → packages
 * GET /api/products.php?search=rice        → search by name/description
 * GET /api/products.php?id=3               → single product by ID
 */

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';
require_once '../middleware/auth.php';

// Must be logged in
if (!isLoggedIn()) {
    apiUnauthorized('Please log in to access products.');
}

$db       = getDB();
$member   = isMember();
$where    = ['1=1'];
$params   = [];
$types    = '';

// Single product by ID
if (!empty($_GET['id'])) {
    $id   = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM products WHERE product_id = ? AND stock > 0");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$product) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found.']);
        exit;
    }

    // Block member products for non-members
    if ($product['product_type'] === 'member' && !$member) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'This product is for  .']);
        exit;
    }

    echo json_encode(['success' => true, 'product' => $product]);
    exit;
}

// Filter by type
$type = $_GET['type'] ?? 'all';
if ($type === 'member' && $member) {
    $where[] = "product_type = 'member'";
} elseif ($type === 'package') {
    $where[] = "product_type = 'package'";
} elseif ($type === 'loose') {
    $where[] = "product_type = 'loose'";
}

// Non-members cannot see member products
if (!$member) {
    $where[] = "product_type != 'member'";
}

// Search
if (!empty($_GET['search'])) {
    $search  = '%' . trim($_GET['search']) . '%';
    $where[] = "(product_name LIKE ? OR description LIKE ?)";
    $types  .= 'ss';
    $params[] = $search;
    $params[] = $search;
}

// Only show in-stock products (optional — remove condition to show out of stock too)
// $where[] = "stock > 0";

$sql  = "SELECT * FROM products WHERE " . implode(' AND ', $where) . " ORDER BY product_name";
$stmt = $db->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode([
    'success'  => true,
    'count'    => count($products),
    'products' => $products
]);
