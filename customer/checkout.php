<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
require_once '../config/database.php';
$db     = getDB();
$userId = (int)$_SESSION['user_id'];

if (empty($_SESSION['cart'])) { header('Location: cart.php'); exit; }

$stmt = $db->prepare("SELECT * FROM users WHERE user_id=?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$cart  = $_SESSION['cart'];
$total = 0;
foreach ($cart as $item) $total += $item['price'] * $item['qty'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['customer_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $contact = trim($_POST['contact_number'] ?? '');
    $method    = $_POST['order_method'] ?? 'pickup';
    $payment   = $_POST['payment_method'] ?? 'cash_on_pickup';
    $accId     = !empty($_POST['payment_account_id']) ? (int)$_POST['payment_account_id'] : null;
    $gcashRef  = trim($_POST['gcash_reference'] ?? '');

    if (empty($name))    $errors[] = 'Customer name is required.';
    if (empty($address)) $errors[] = 'Address is required.';
    if (empty($contact)) $errors[] = 'Contact number is required.';
    if (!in_array($method, ['pickup','shipping','dropoff'])) $errors[] = 'Invalid order method.';
    if (!in_array($payment, ['cash_on_pickup','cash_on_delivery','gcash'])) $errors[] = 'Invalid payment method.';
    if ($method === 'pickup' && in_array($payment, ['cash_on_delivery','gcash'])) $errors[] = 'Only Cash on Pickup is available for Meet up orders.';
    if ($method === 'shipping' && $payment === 'cash_on_pickup') $errors[] = 'Cash on Pickup is not available for Shipping Delivery orders.';
    if ($method === 'dropoff' && $payment !== 'gcash') $errors[] = 'Only GCash is available for Drop off orders.';
    if ($payment === 'gcash' && empty($gcashRef)) $errors[] = 'GCash reference number is required to confirm your payment.';

    if ($payment === 'gcash' && $accId !== null) {
        $s = $db->prepare("SELECT account_id FROM user_payment_accounts WHERE account_id=? AND user_id=? AND account_type=?");
        $s->bind_param('iis', $accId, $userId, $payment);
        $s->execute();
        if ($s->get_result()->num_rows === 0) $accId = null;
        $s->close();
    } else { $accId = null; }

    if (empty($errors)) {
        $db->begin_transaction();
        try {
            $res = $db->query("SELECT IFNULL(MAX(queue_number),100)+1 AS next_q FROM orders");
            $queueNum = (int)$res->fetch_assoc()['next_q'];
            $s = $db->prepare("INSERT INTO orders (user_id,customer_name,address,contact_number,order_method,payment_method,payment_account_id,payment_status,gcash_reference,queue_number,total_amount,order_status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $payStatus   = ($payment === 'gcash') ? 'pending_verification' : 'pending';
            $orderStatus = 'pending';
            $gcashRefVal = ($payment === 'gcash') ? $gcashRef : null;
            $s->bind_param('isssssissids', $userId, $name, $address, $contact, $method, $payment, $accId, $payStatus, $gcashRefVal, $queueNum, $total, $orderStatus);
            $s->execute();
            $orderId = $db->insert_id;
            $s->close();
            $s = $db->prepare("INSERT INTO order_items (order_id,product_id,quantity,price) VALUES (?,?,?,?)");
            foreach ($cart as $pid => $item) {
                $s->bind_param('iiid', $orderId, $pid, $item['qty'], $item['price']);
                $s->execute();
                $db->query("UPDATE products SET stock=stock-".(int)$item['qty']." WHERE product_id=".(int)$pid." AND stock>=".(int)$item['qty']);
            }
            $s->close();
            $db->commit();
            $_SESSION['cart'] = [];
            header("Location: order_confirmation.php?order_id=$orderId");
            exit;
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = 'Order failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Checkout — Marguax Collections</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
<style>
/* ── Base ── */
html, body {
  background: linear-gradient(to bottom right,#0e0507 0%,#1a0a0e 30%,#2a0d14 60%,#3d1020 100%) !important;
  color: #f0e6da !important;
  font-family: 'Jost', sans-serif !important;
  min-height: 100vh !important;
}

/* ── Hero ── */
.page-hero {
  background: transparent !important;
  border-bottom: 1px solid rgba(196,80,100,.15) !important;
  padding: 64px 40px 52px !important;
  text-align: center !important;
  position: relative !important;
}
.page-hero::before {
  content: '';
  position: absolute; inset: 0;
  background: radial-gradient(ellipse 80% 70% at 50% -10%, rgba(196,80,100,.13) 0%, transparent 70%);
  pointer-events: none;
}
.hero-eyebrow {
  display: inline-flex; align-items: center; gap: 8px;
  font-size: .68rem; font-weight: 600; letter-spacing: .28em;
  text-transform: uppercase; color: #c45064;
  padding: 6px 20px;
  border: 1px solid rgba(196,80,100,.3); border-radius: 40px;
  margin-bottom: 20px;
  background: rgba(196,80,100,.06);
  animation: heroIn .7s cubic-bezier(.16,1,.3,1) both;
}
.page-hero h1 {
  font-family: 'Playfair Display', serif !important;
  font-size: clamp(2.4rem,5vw,4rem) !important;
  font-weight: 700 !important; color: #f0e6da !important;
  line-height: 1.05 !important; margin: 0 0 12px !important;
  animation: heroIn .8s cubic-bezier(.16,1,.3,1) both;
}
.page-hero h1 em { font-style: italic !important; color: #c45064 !important; }
.page-hero p { color: #7a6058 !important; font-size: .88rem !important; font-weight: 300 !important; margin: 0 !important; }
.hero-divider {
  width: 56px; height: 1px;
  background: linear-gradient(90deg,transparent,#c45064,transparent);
  margin: 20px auto 0;
}

/* ── Container ── */
.co-container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 32px 24px 80px;
}

/* ── Grid ── */
.checkout-grid { display: flex; gap: 20px; flex-wrap: wrap; }
.col-left  { flex: 1 1 340px; display: flex; flex-direction: column; gap: 16px; }
.col-right { flex: 0 0 320px; }

/* ── Card ── */
.co-card {
  background: rgba(42,13,20,.7);
  border: 1px solid rgba(196,80,100,.14);
  border-radius: 16px; overflow: hidden;
  backdrop-filter: blur(4px);
  transition: border-color .3s;
}
.co-card:hover { border-color: rgba(196,80,100,.28); }
.co-card-header {
  padding: 14px 20px;
  border-bottom: 1px solid rgba(196,80,100,.12);
  display: flex; align-items: center; gap: 10px;
}
.co-card-header h3 {
  margin: 0; font-family: 'Jost', sans-serif;
  font-size: .72rem; font-weight: 600;
  letter-spacing: .18em; text-transform: uppercase;
  color: rgba(196,80,100,.8);
}
.co-card-body  { padding: 20px; }
.co-card-footer { padding: 16px 20px; border-top: 1px solid rgba(196,80,100,.12); display: flex; flex-direction: column; gap: 10px; }

/* ── Form Fields ── */
.form-stack { display: flex; flex-direction: column; gap: 16px; }
.form-field { display: flex; flex-direction: column; gap: 6px; }
.form-field label {
  font-size: .68rem; font-weight: 600; letter-spacing: .14em;
  text-transform: uppercase; color: #7a6058;
}
.form-field input,
.form-field textarea {
  width: 100%; padding: 11px 14px;
  background: rgba(255,255,255,.04);
  border: 1px solid rgba(196,80,100,.18);
  border-radius: 10px;
  font-size: .88rem; font-family: 'Jost', sans-serif;
  color: #f0e6da;
  transition: border-color .2s, background .2s, box-shadow .2s;
  box-sizing: border-box;
}
.form-field input::placeholder,
.form-field textarea::placeholder { color: #5a4a42; }
.form-field input:focus,
.form-field textarea:focus {
  outline: none;
  border-color: #c45064;
  background: rgba(196,80,100,.06);
  box-shadow: 0 0 0 3px rgba(196,80,100,.12);
}
.form-field textarea { resize: vertical; min-height: 80px; }

/* ── Radio Options ── */
.radio-opt { display: none; }
.radio-label {
  display: flex; align-items: center; gap: 14px;
  padding: 12px 16px;
  border: 1px solid rgba(196,80,100,.14);
  border-radius: 12px; cursor: pointer; margin-bottom: 8px;
  font-family: 'Jost', sans-serif; font-size: .82rem; font-weight: 500;
  color: #7a6058; background: rgba(255,255,255,.02);
  transition: all .25s; user-select: none;
}
.radio-label .opt-icon { font-size: 1.3rem; flex-shrink: 0; }
.radio-label:hover { border-color: rgba(196,80,100,.4); background: rgba(196,80,100,.06); color: #e8a0a8; }
.radio-opt:checked + .radio-label {
  border-color: #c45064;
  background: rgba(196,80,100,.12);
  color: #f0e6da;
  box-shadow: 0 0 0 1px rgba(196,80,100,.2);
}

/* ── GCash Payment Modal ── */
.gcash-modal-overlay {
  display: none;
  position: fixed; inset: 0;
  background: rgba(5,2,3,.75);
  backdrop-filter: blur(3px);
  z-index: 100000;
  align-items: center; justify-content: center;
  padding: 20px;
}
.gcash-modal-overlay.active { display: flex; }
.gcash-modal {
  position: relative;
  width: 100%; max-width: 380px;
  background: linear-gradient(160deg,#1a0a0e,#2a0d14);
  border: 1px solid rgba(196,80,100,.25);
  border-radius: 18px;
  padding: 32px 26px 26px;
  text-align: center;
  box-shadow: 0 30px 70px rgba(0,0,0,.6);
  animation: heroIn .3s cubic-bezier(.16,1,.3,1) both;
}
.gcash-modal-close {
  position: absolute; top: 14px; right: 14px;
  width: 30px; height: 30px;
  background: rgba(255,255,255,.05);
  border: 1px solid rgba(196,80,100,.2);
  border-radius: 50%;
  color: #7a6058;
  font-size: .9rem;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all .2s;
}
.gcash-modal-close:hover { background: rgba(196,80,100,.15); color: #e8a0a8; }
.qr-title {
  font-family: 'Playfair Display', serif;
  font-size: 1.15rem; font-style: italic;
  color: #f0e6da; margin-bottom: 4px;
}
.qr-sub { font-size: .8rem; color: #7a6058; margin-bottom: 18px; }
.qr-img-wrap {
  display: inline-flex; align-items: center; justify-content: center;
  width: 200px; height: 200px;
  background: white; border-radius: 12px; padding: 12px;
  box-shadow: 0 8px 26px rgba(0,0,0,.35);
  box-sizing: content-box;
}
.qr-img-wrap img { display: block; width: 100%; height: 100%; object-fit: contain; }
.qr-fallback {
  font-family: 'Jost', sans-serif; font-size: .8rem; color: #2a0d14;
  line-height: 1.5;
}
.qr-fallback strong { display: block; font-size: 1rem; margin-top: 6px; letter-spacing: .04em; }
.qr-note {
  margin: 16px 0 18px; font-size: .76rem; color: #e8a0a8;
  background: rgba(196,80,100,.1); border: 1px solid rgba(196,80,100,.2);
  border-radius: 8px; padding: 9px 14px;
}
.gcash-modal .btn-place { margin-bottom: 10px; }

/* ── Order Summary Items ── */
.order-item {
  display: flex; align-items: center; gap: 14px;
  padding: 12px 0; border-bottom: 1px solid rgba(196,80,100,.1);
}
.order-item:last-of-type { border-bottom: none; }
.order-item img {
  width: 58px; height: 58px; border-radius: 10px;
  object-fit: cover; flex-shrink: 0;
  border: 1px solid rgba(196,80,100,.2);
  filter: brightness(.92) saturate(.9);
}
.order-item-name { font-family: 'Playfair Display', serif; font-size: .9rem; color: #f0e6da; }
.order-item-meta { font-size: .75rem; color: #7a6058; margin-top: 3px; }
.order-item-price { font-family: 'Playfair Display', serif; font-size: .95rem; color: #c45064; margin-top: 4px; }
.total-row {
  display: flex; justify-content: space-between; align-items: center;
  padding-top: 14px; margin-top: 8px;
  border-top: 1px solid rgba(196,80,100,.18);
}
.total-label { font-size: .72rem; font-weight: 600; letter-spacing: .14em; text-transform: uppercase; color: #7a6058; }
.total-amount { font-family: 'Playfair Display', serif; font-size: 1.6rem; color: #c45064; }

/* ── Buttons ── */
.btn-place {
  display: flex; align-items: center; justify-content: center;
  width: 100%; padding: 14px 18px;
  background: #c45064; color: #fff; border: none;
  border-radius: 10px; font-family: 'Jost', sans-serif;
  font-size: .74rem; font-weight: 600;
  letter-spacing: .14em; text-transform: uppercase;
  cursor: pointer; transition: background .25s, transform .2s, box-shadow .25s;
  box-shadow: 0 6px 20px rgba(196,80,100,.35);
}
.btn-place:hover { background: #a83d53; transform: translateY(-2px); box-shadow: 0 10px 28px rgba(196,80,100,.45); }
.btn-place:active { transform: scale(.98); }

.btn-back {
  display: flex; align-items: center; justify-content: center;
  width: 100%; padding: 13px 18px;
  background: transparent;
  color: #5a4a42; border: 1px solid rgba(196,80,100,.15);
  border-radius: 10px; font-family: 'Jost', sans-serif;
  font-size: .74rem; font-weight: 500;
  letter-spacing: .12em; text-transform: uppercase;
  cursor: pointer; text-decoration: none;
  transition: all .25s; box-sizing: border-box;
}
.btn-back:hover { border-color: rgba(196,80,100,.4); color: #e8a0a8; background: rgba(196,80,100,.06); }

/* ── Alert ── */
.alert-danger {
  background: rgba(196,80,100,.15); border: 1px solid rgba(196,80,100,.4);
  color: #e8a0a8; padding: 14px 18px; border-radius: 12px;
  margin-bottom: 20px; font-size: .85rem; font-family: 'Jost', sans-serif;
}
.alert-danger ul { margin: 6px 0 0; padding-left: 18px; }

/* ── Disabled ── */
.disabled-opt { opacity: .3; pointer-events: none; }

/* ── Keyframes ── */
@keyframes heroIn { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }

@media(max-width:768px) {
  .checkout-grid { flex-direction: column; }
  .col-right { flex: 1 1 auto; }
  .co-container { padding: 20px 16px 60px; }
}
</style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<!-- Hero -->
<div class="page-hero">
  <div class="hero-eyebrow">Marguax Collections</div>
  <h1>Complete <em>Checkout</em></h1>
  <p>Review and confirm your order details below</p>
  <div class="hero-divider"></div>
</div>

<div class="co-container">

  <?php if (!empty($errors)): ?>
    <div class="alert-danger">
      ⚠️ <ul><?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="POST" id="checkoutForm">
  <div class="checkout-grid">

    <!-- ── Left ── -->
    <div class="col-left">

      <!-- Customer Information -->
      <div class="co-card">
        <div class="co-card-header">
          <span>👤</span>
          <h3>Customer Information</h3>
        </div>
        <div class="co-card-body">
          <div class="form-stack">
            <div class="form-field">
              <label for="customer_name">Full Name *</label>
              <input type="text" id="customer_name" name="customer_name"
                     value="<?= htmlspecialchars($_POST['customer_name'] ?? $user['name']) ?>"
                     placeholder="Enter your full name" required>
            </div>
            <div class="form-field">
              <label for="contact_number">Contact Number *</label>
              <input type="text" id="contact_number" name="contact_number"
                     value="<?= htmlspecialchars($_POST['contact_number'] ?? $user['contact_number']) ?>"
                     placeholder="09XXXXXXXXX" required>
            </div>
            <div class="form-field">
              <label for="address">Delivery Address *</label>
              <textarea id="address" name="address" rows="3"
                        placeholder="House No., Street, Barangay, City, Province" required><?= htmlspecialchars($_POST['address'] ?? $user['address'] ?? '') ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- Order Method -->
      <div class="co-card">
        <div class="co-card-header">
          <span>🚚</span>
          <h3>Order Method</h3>
        </div>
        <div class="co-card-body">
          <input type="radio" class="radio-opt" name="order_method" id="methodPickup" value="pickup"
                 <?= (($_POST['order_method'] ?? 'pickup') === 'pickup') ? 'checked' : '' ?>>
          <label for="methodPickup" class="radio-label">
            <span class="opt-icon">👤</span> Meet up
          </label>

          <input type="radio" class="radio-opt" name="order_method" id="methodDropoff" value="dropoff"
                 <?= (($_POST['order_method'] ?? '') === 'dropoff') ? 'checked' : '' ?>>
          <label for="methodDropoff" class="radio-label">
            <span class="opt-icon">📦</span> Drop off at MJM BUILDING
          </label>

          <input type="radio" class="radio-opt" name="order_method" id="methodShipping" value="shipping"
                 <?= (($_POST['order_method'] ?? '') === 'shipping') ? 'checked' : '' ?>>
          <label for="methodShipping" class="radio-label">
            <span class="opt-icon">🚚</span> Shipping Delivery
          </label>
        </div>
      </div>

      <!-- Payment Method -->
      <div class="co-card">
        <div class="co-card-header">
          <span>💳</span>
          <h3>Payment Method</h3>
        </div>
        <div class="co-card-body">

          <input type="radio" class="radio-opt" name="payment_method" id="payCOP" value="cash_on_pickup"
                 <?= (($_POST['payment_method'] ?? 'cash_on_pickup') === 'cash_on_pickup') ? 'checked' : '' ?>>
          <label for="payCOP" id="labelCOP" class="radio-label">
            <span class="opt-icon">💵</span> Cash on Pickup
          </label>

          <input type="radio" class="radio-opt" name="payment_method" id="payCOD" value="cash_on_delivery"
                 <?= (($_POST['payment_method'] ?? '') === 'cash_on_delivery') ? 'checked' : '' ?>>
          <label for="payCOD" id="labelCOD" class="radio-label">
            <span class="opt-icon">🏠</span> Cash on Delivery
          </label>

          <input type="radio" class="radio-opt" name="payment_method" id="payGcash" value="gcash"
                 <?= (($_POST['payment_method'] ?? '') === 'gcash') ? 'checked' : '' ?>>
          <label for="payGcash" id="labelGcash" class="radio-label">
            <span class="opt-icon">📱</span> GCash
          </label>

        </div>
      </div>

    </div><!-- /col-left -->

    <!-- ── Right: Order Summary ── -->
    <div class="col-right">
      <div class="co-card" style="position:sticky;top:80px;">
        <div class="co-card-header">
          <span>🛒</span>
          <h3>Order Summary</h3>
        </div>
        <div class="co-card-body">
          <?php foreach ($cart as $pid => $item): ?>
          <div class="order-item">
            <img src="../<?= htmlspecialchars($item['image']) ?>"
                 onerror="this.src='../images/product-placeholder.jpg'"
                 alt="<?= htmlspecialchars($item['product_name']) ?>">
            <div style="flex:1;">
              <div class="order-item-name"><?= htmlspecialchars($item['product_name']) ?></div>
              <div class="order-item-meta">Qty: <?= $item['qty'] ?></div>
              <div class="order-item-price">₱<?= number_format($item['price'] * $item['qty'], 2) ?></div>
            </div>
          </div>
          <?php endforeach; ?>

          <div class="total-row">
            <span class="total-label">Total</span>
            <span class="total-amount">₱<?= number_format($total, 2) ?></span>
          </div>
        </div>
        <div class="co-card-footer">
          <button type="submit" class="btn-place">Place Order →</button>
          <a href="cart.php" class="btn-back">← Back to Cart</a>
        </div>
      </div>
    </div>

  </div>
  </form>

  <!-- GCash Payment Confirmation Modal -->
  <div class="gcash-modal-overlay" id="gcashModalOverlay">
    <div class="gcash-modal">
      <button type="button" class="gcash-modal-close" onclick="closeGcashModal()">✕</button>
      <div class="qr-title">Scan to Pay via GCash</div>
      <div class="qr-sub">Open your GCash app and scan the QR code below</div>
      <div class="qr-img-wrap" id="qrImgWrap">
        <img id="qrCodeImg" alt="GCash QR Code" style="display:none;">
        <div class="qr-fallback" id="qrFallback" style="display:none;">
          Can't load QR code.<br>Send payment manually to:
          <strong id="qrFallbackNumber"></strong>
        </div>
      </div>
      <div class="qr-note">📌 Please complete your payment first before confirming</div>

      <div class="form-field" style="text-align:left; margin-bottom:14px;">
        <label for="gcashRefInput">GCash Reference No. *</label>
        <input type="text" id="gcashRefInput" placeholder="e.g. 1234567890123" maxlength="30" autocomplete="off">
        <div id="gcashRefError" style="display:none; color:#e8a0a8; font-size:.72rem; margin-top:2px;">
          Please enter the reference number from your GCash receipt.
        </div>
      </div>

      <button type="button" class="btn-place" id="confirmGcashBtn" onclick="confirmGcashPaid()">PROCEED→</button>
      <button type="button" class="btn-back" onclick="closeGcashModal()">Cancel</button>
    </div>
  </div>

</div>

<!-- Page Transition (same as products.php) -->
<div class="page-transition" id="pageTransition">
  <div class="pt-panel"></div>
  <div class="pt-logo">
    <div class="pt-logo-text">Marguax Collections</div>
    <div class="pt-logo-bar"></div>
  </div>
</div>

<style>
.page-transition { position:fixed;inset:0;z-index:99998;pointer-events:none;display:flex;align-items:center;justify-content:center; }
.pt-panel { position:absolute;inset:0;background:linear-gradient(135deg,#0e0507,#2a0d14);transform:scaleY(0);transform-origin:bottom;transition:transform .5s cubic-bezier(.77,0,.18,1); }
.pt-logo { position:relative;z-index:2;opacity:0;transform:scale(.5);transition:all .4s ease .2s;text-align:center; }
.pt-logo-text { font-family:'Playfair Display',serif;font-size:1.6rem;color:#e8a0a8;letter-spacing:.15em;font-weight:400; }
.pt-logo-bar { width:0;height:1px;background:linear-gradient(90deg,transparent,#c45064,transparent);margin:12px auto 0;transition:width .5s ease .3s; }
.page-transition.active .pt-panel { transform:scaleY(1); }
.page-transition.active .pt-logo  { opacity:1;transform:scale(1); }
.page-transition.active .pt-logo-bar { width:120px; }
</style>

<script>
// ── UI Logic ─────────────────────────────────────
const methodPickup   = document.getElementById('methodPickup');
const methodDropoff  = document.getElementById('methodDropoff');
const methodShipping = document.getElementById('methodShipping');
const payCOP         = document.getElementById('payCOP');
const payCOD         = document.getElementById('payCOD');
const payGcash       = document.getElementById('payGcash');
const labelCOP       = document.getElementById('labelCOP');
const labelCOD       = document.getElementById('labelCOD');
const labelGcash     = document.getElementById('labelGcash');
let gcashConfirmed = false;

function updateUI() {
  const isPickup   = methodPickup.checked;   // Meet up
  const isShipping = methodShipping.checked; // Shipping Delivery
  const isDropoff  = methodDropoff.checked;  // Drop off

  // Meet up: only Cash on Pickup allowed — disable GCash & Cash on Delivery
  labelCOD.classList.toggle('disabled-opt', isPickup);
  labelGcash.classList.toggle('disabled-opt', isPickup);
  if (isPickup && (payCOD.checked || payGcash.checked)) payCOP.checked = true;

  // Shipping Delivery: Cash on Pickup not allowed
  labelCOP.classList.toggle('disabled-opt', isShipping);
  if (isShipping && payCOP.checked) payCOD.checked = true;

  // Drop off: only GCash allowed — disable Cash on Pickup & Cash on Delivery
  labelCOP.classList.toggle('disabled-opt', isShipping || isDropoff);
  labelCOD.classList.toggle('disabled-opt', isPickup || isDropoff);
  if (isDropoff && (payCOP.checked || payCOD.checked)) payGcash.checked = true;

  // Any change to payment selection means the payment isn't confirmed yet
  gcashConfirmed = false;
}

[methodPickup, methodDropoff, methodShipping, payCOP, payCOD, payGcash].forEach(el => {
  if (el) el.addEventListener('change', updateUI);
});
updateUI();

// ── GCash Payment Popup ──────────────────────────
const GCASH_NUMBER      = '09482841494';
const checkoutForm      = document.getElementById('checkoutForm');
const gcashModalOverlay = document.getElementById('gcashModalOverlay');
let qrGenerated    = false;

// FIX: Uses your actual GCash Personal QR (static image saved in ../images/)
// instead of a hosted "generate QR from text" API. The old version encoded
// the plain mobile number as a generic QR — that is NOT a valid EMV/InstaPay
// payload, so GCash's scanner correctly rejected it as "not supported."
// A real GCash QR (with the InstaPay logo, exported from your app) already
// contains the correct structured payload, so we just display it as-is.
function generateQR() {
  if (qrGenerated) return;
  qrGenerated = true;

  const img      = document.getElementById('qrCodeImg');
  const fallback = document.getElementById('qrFallback');
  document.getElementById('qrFallbackNumber').textContent = GCASH_NUMBER;

  img.onload  = () => { img.style.display = 'block'; fallback.style.display = 'none'; };
  img.onerror = () => { img.style.display = 'none';  fallback.style.display = 'block';
    console.error('GCash QR image failed to load — check that ../images/gcash_qr.png exists.'); };

  // Static QR image path — update this if you save the file elsewhere
  img.src = '../images/gcash_qr.png';
}

function openGcashModal() {
  // Show the modal FIRST, regardless of whether QR generation succeeds —
  // that way a QR-loading hiccup never blocks the popup itself.
  gcashModalOverlay.classList.add('active');
  try {
    generateQR();
  } catch (err) {
    console.error('QR generation error:', err);
    document.getElementById('qrCodeImg').style.display = 'none';
    document.getElementById('qrFallback').style.display = 'block';
    document.getElementById('qrFallbackNumber').textContent = GCASH_NUMBER;
  }
}

function closeGcashModal() {
  gcashModalOverlay.classList.remove('active');
}

document.getElementById('gcashRefInput').addEventListener('input', function () {
  if (this.value.trim()) document.getElementById('gcashRefError').style.display = 'none';
});

function confirmGcashPaid() {
  const refInput  = document.getElementById('gcashRefInput');
  const refError  = document.getElementById('gcashRefError');
  const refValue  = refInput.value.trim();

  if (!refValue) {
    refError.style.display = 'block';
    refInput.focus();
    return;
  }
  refError.style.display = 'none';

  // Inject the reference number into the main form as a hidden field
  let hidden = document.getElementById('gcashReferenceHidden');
  if (!hidden) {
    hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'gcash_reference';
    hidden.id   = 'gcashReferenceHidden';
    checkoutForm.appendChild(hidden);
  }
  hidden.value = refValue;

  gcashConfirmed = true;
  closeGcashModal();
  checkoutForm.submit();
}

checkoutForm.addEventListener('submit', function (e) {
  const payMethod = document.querySelector('input[name="payment_method"]:checked')?.value;
  if (payMethod === 'gcash' && !gcashConfirmed) {
    e.preventDefault();
    openGcashModal();
  }
});

// Page transition
window.addEventListener('pageshow', () =>
  document.getElementById('pageTransition').classList.remove('active')
);
</script>
</body>
</html>