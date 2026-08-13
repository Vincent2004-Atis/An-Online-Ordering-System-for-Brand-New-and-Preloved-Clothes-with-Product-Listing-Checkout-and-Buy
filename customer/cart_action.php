<?php
require_once '../includes/security.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

require_once '../config/database.php';
$db     = getDB();
$action = clean($_POST['action'] ?? '', 20);

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

function cartCount(): int {
    $c = 0;
    foreach ($_SESSION['cart'] as $item) $c += (int)$item['qty'];
    return $c;
}

if ($action === 'add') {
    // Validate product_id is a positive integer
    $pid = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $qty = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 999]]);

    if (!$pid || !$qty) {
        echo json_encode(['success' => false, 'message' => 'Invalid product or quantity.']);
        exit;
    }

    $stmt = $db->prepare("SELECT product_id, product_name, price, stock, product_type, image FROM products WHERE product_id=? LIMIT 1");
    $stmt->bind_param('i', $pid);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found.']);
        exit;
    }

    // Current qty in cart
    $currentQty = $_SESSION['cart'][$pid]['qty'] ?? 0;
    if (($currentQty + $qty) > $product['stock']) {
        echo json_encode(['success' => false, 'message' => 'Not enough stock available.']);
        exit;
    }

    if (isset($_SESSION['cart'][$pid])) {
        $_SESSION['cart'][$pid]['qty'] += $qty;
    } else {
        $_SESSION['cart'][$pid] = [
            'product_id'   => $pid,
            'product_name' => $product['product_name'],
            'price'        => (float)$product['price'],
            'qty'          => $qty,
            'image'        => $product['image'] ?? 'images/product-placeholder.jpg'
        ];
    }
    echo json_encode(['success' => true, 'cart_count' => cartCount()]);

} elseif ($action === 'update') {
    $pid = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $qty = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 999]]);

    if (!$pid) { echo json_encode(['success' => false, 'message' => 'Invalid product.']); exit; }

    if ($qty === 0 || $qty === false) {
        unset($_SESSION['cart'][$pid]);
    } elseif (isset($_SESSION['cart'][$pid])) {
        $_SESSION['cart'][$pid]['qty'] = $qty;
    }
    echo json_encode(['success' => true, 'cart_count' => cartCount()]);

} elseif ($action === 'remove') {
    $pid = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($pid) unset($_SESSION['cart'][$pid]);
    echo json_encode(['success' => true, 'cart_count' => cartCount()]);

} elseif ($action === 'clear') {
    $_SESSION['cart'] = [];
    echo json_encode(['success' => true, 'cart_count' => 0]);

} else {
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}