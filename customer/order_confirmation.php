<?php
require_once '../includes/security.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

$db      = getDB();
$userId  = (int)$_SESSION['user_id'];
$orderId = (int)($_GET['order_id'] ?? 0);
if (!$orderId) { header('Location: products.php'); exit; }

// Fetch order
$stmt = $db->prepare("SELECT o.*, u.name as customer_name FROM orders o JOIN users u ON u.user_id=o.user_id WHERE o.order_id=? AND o.user_id=?");
$stmt->bind_param('ii', $orderId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$order) { header('Location: products.php'); exit; }

// Fetch items
$stmt = $db->prepare("SELECT oi.*, p.product_name, p.image FROM order_items oi JOIN products p ON p.product_id=oi.product_id WHERE oi.order_id=?");
$stmt->bind_param('i', $orderId);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// GCash-specific verification state — the only payment method that requires
// manual admin confirmation before we can say the order is truly "confirmed".
$isGcashUnpaid = ($order['payment_method'] === 'gcash' && $order['payment_status'] !== 'paid');

// Estimated arrival — no DB column for this yet, so we compute a simple
// offset from order_date based on order_method. Adjust the day counts here
// if the actual turnaround changes.
$estimateDays = [
    'pickup'   => 1,
    'dropoff'  => 1,
    'shipping' => 3,
];
$estimateOffset = $estimateDays[$order['order_method']] ?? 3;
$expectedDate   = date('F j, Y', strtotime($order['order_date'] . " +{$estimateOffset} days"));
$expectedLabel  = ($order['order_method'] === 'shipping') ? 'Estimated Delivery' : 'Available for Pickup';

$orderMethodIcons = [
    'pickup'   => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#c8a96a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-9 9 9v10a2 2 0 0 1-2 2h-4v-6h-6v6H5a2 2 0 0 1-2-2z"/></svg> Store Pickup',
    'shipping' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#c8a96a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18v12H3z"/><path d="M3 6l9-4 9 4"/></svg> Shipping'
];

$payIcons = [
    'cash_on_pickup'   => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#c8a96a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><line x1="2" y1="7" x2="22" y2="7"/></svg> Cash on Pickup',
    'cash_on_delivery' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#c8a96a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-9 9 9v10a2 2 0 0 1-2 2h-4v-6h-6v6H5a2 2 0 0 1-2-2z"/></svg> Cash on Delivery',
    'gcash'            => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#c8a96a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="12" y1="8" x2="12" y2="16"/></svg> GCash',
    'bank_transfer'    => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#c8a96a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="12"/><line x1="3" y1="12" x2="21" y2="12"/><polyline points="3 6 12 2 21 6"/></svg> Bank Transfer'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Order Confirmed — Marguax Collections</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
<style>
/* ============================================================
   NUCLEAR OVERRIDE — same pattern as products.php, immune to
   whatever is currently sitting in style.css on the server.
   ============================================================ */
html, body {
  background: linear-gradient(to bottom right, #0e0507 0%, #1a0a0e 30%, #2a0d14 60%, #3d1020 100%) !important;
  color: #f0e6da !important;
  font-family: 'Jost', sans-serif !important;
  min-height: 100vh !important;
  margin: 0 !important;
}

/* Push content clear of the fixed/sticky navbar */
.container {
  max-width: 720px !important;
  margin: 0 auto !important;
  padding: 120px 24px 60px !important;
  background: transparent !important;
}

.queue-card {
  background: rgba(42,13,20,.7) !important;
  border: 1px solid rgba(196,80,100,.25) !important;
  border-radius: 16px !important;
  padding: 40px 28px !important;
  text-align: center !important;
  margin-bottom: 32px !important;
  box-shadow: 0 28px 56px rgba(0,0,0,.4) !important;
  backdrop-filter: blur(4px) !important;
  color: #f0e6da !important;
}
.queue-card h2 {
  font-family: 'Playfair Display', serif !important;
  font-weight: 700 !important;
  font-size: 1.9rem !important;
  color: #f0e6da !important;
  margin: 8px 0 !important;
}
.queue-card p { color: #7a6058 !important; margin: 0 !important; }

.card {
  background: rgba(42,13,20,.85) !important;
  border: 1px solid rgba(196,80,100,.18) !important;
  border-radius: 16px !important;
  box-shadow: 0 4px 12px rgba(0,0,0,.3) !important;
  margin-bottom: 24px !important;
  color: #f0e6da !important;
  backdrop-filter: blur(4px) !important;
}
.card-header {
  padding: 18px 24px !important;
  border-bottom: 1px solid rgba(196,80,100,.18) !important;
  font-family: 'Playfair Display', serif !important;
  font-weight: 700 !important;
  font-size: 1.15rem !important;
  display: flex !important; align-items: center !important; gap: 8px !important;
  color: #f0e6da !important;
  background: transparent !important;
}
.card-body { padding: 24px !important; background: transparent !important; }

.order-item-row {
  display: flex !important; align-items: center !important; justify-content: space-between !important;
  padding: 14px 0 !important; border-bottom: 1px solid rgba(196,80,100,.12) !important;
}
.order-item-img { width: 60px !important; height: 60px !important; object-fit: cover !important; border-radius: 8px !important; margin-right: 12px !important; filter: brightness(.92) saturate(.88) !important; }
.order-item-info { flex: 1 !important; display: flex !important; flex-direction: column !important; }
.order-item-name { font-family: 'Playfair Display', serif !important; font-weight: 400 !important; color: #f0e6da !important; margin-bottom: 4px !important; font-size: 1.02rem !important; }
.order-item-qty { font-size: .8rem !important; color: #7a6058 !important; }
.order-item-price { font-family: 'Playfair Display', serif !important; font-weight: 400 !important; min-width: 80px !important; text-align: right !important; color: #c45064 !important; font-size: 1.05rem !important; }

.total-row {
  display: flex !important; justify-content: space-between !important; align-items: center !important;
  padding: 16px 0 4px !important;
  color: #f0e6da !important;
  border-top: 1px solid rgba(196,80,100,.18) !important;
  margin-top: 6px !important;
}
.total-row span:first-child {
  text-transform: uppercase !important; letter-spacing: .08em !important; font-size: .78rem !important; font-weight: 600 !important; color: #f0e6da !important;
}
.total-row span:last-child {
  font-family: 'Playfair Display', serif !important; font-size: 1.35rem !important; color: #c45064 !important; font-weight: 400 !important;
}

.btn {
  padding: 13px 22px !important; border-radius: 9px !important; font-weight: 600 !important;
  font-size: .74rem !important; letter-spacing: .14em !important; text-transform: uppercase !important;
  text-decoration: none !important; text-align: center !important; display: inline-block !important;
  transition: all .25s ease !important;
}
.btn-primary { background: rgba(255,255,255,.06) !important; color: #fff !important; border: 1px solid rgba(255,255,255,.15) !important; }
.btn-primary:hover { background: #ffffff !important; color: #2a0d14 !important; transform: translateY(-2px) !important; box-shadow: 0 10px 24px rgba(196,80,100,.35) !important; }
.btn-outline { border: 1px solid rgba(196,80,100,.3) !important; color: #7a6058 !important; background: transparent !important; }
.btn-outline:hover { border-color: #c45064 !important; color: #e8a0a8 !important; }

.queue-num {
  font-family: 'Playfair Display', serif !important; font-size: 4.5rem !important; font-weight: 700 !important;
  color: #c45064 !important; line-height: 1 !important;
}
.queue-label { font-size: .8rem !important; color: #7a6058 !important; text-transform: uppercase !important; letter-spacing: .12em !important; margin-top: 6px !important; }

.detail-label {
  font-size: .67rem !important; color: #d99aa5 !important; font-weight: 600 !important;
  text-transform: uppercase !important; letter-spacing: .1em !important;
}
.detail-value { font-weight: 400 !important; color: #f0e6da !important; margin-top: 3px !important; font-size: .92rem !important; }

.pay-status-pill {
  display: inline-block !important;
  margin-top: 6px !important;
  padding: 3px 10px !important;
  border-radius: 20px !important;
  font-size: .68rem !important;
  font-weight: 600 !important;
  text-transform: uppercase !important;
  letter-spacing: .06em !important;
}
.pay-status-pending {
  background: rgba(217,154,70,.15) !important;
  color: #e0b46a !important;
  border: 1px solid rgba(217,154,70,.35) !important;
}
.pay-status-verified {
  background: rgba(111,191,115,.15) !important;
  color: #8fd192 !important;
  border: 1px solid rgba(111,191,115,.35) !important;
}

@media(max-width:600px) {
  .container { padding: 100px 16px 40px !important; }
  .order-item-row { flex-direction: column !important; align-items: flex-start !important; }
  .order-item-price { text-align: left !important; margin-top: 4px !important; }
}
</style>
</head>
<body>
<?php
require_once '../includes/security.php'; include '../includes/navbar.php'; ?>

<div class="container">

  <div class="queue-card">
    <div style="font-size:2.6rem;margin-bottom:12px;color:#c45064;"><?= $isGcashUnpaid ? '⏳' : '✔' ?></div>
    <h2><?= $isGcashUnpaid ? 'Pending Payment Verification' : 'Order Confirmed' ?></h2>
    <p>
      <?php if ($isGcashUnpaid): ?>
        We're verifying your GCash payment. You'll get notified once it's confirmed.
      <?php else: ?>
        Your order has been placed successfully.
      <?php endif; ?>
    </p>
    <div style="margin:28px 0;">
      <div class="queue-label">Queue Number</div>
      <div class="queue-num"><?= str_pad($order['queue_number'],3,'0',STR_PAD_LEFT) ?></div>
      <div class="queue-label" style="margin-top:10px;">Order #<?= $orderId ?></div>
    </div>
    <div style="margin:-12px 0 20px;">
      <div class="queue-label"><?= $expectedLabel ?></div>
      <div style="font-family:'Playfair Display',serif;font-size:1.15rem;color:#e8a0a8;margin-top:4px;">
        <?= $expectedDate ?>
      </div>
      <?php if ($isGcashUnpaid): ?>
        <div style="font-size:.72rem;color:#7a6058;margin-top:4px;">Estimate assumes payment gets verified promptly.</div>
      <?php endif; ?>
    </div>
    <?php
require_once '../includes/security.php'; if ($order['order_method'] === 'pickup'): ?>
      <p style="font-size:.85rem;">Please present this queue number at the store.</p>
    <?php
require_once '../includes/security.php'; else: ?>
      <p style="font-size:.85rem;">Your order will be delivered to your address.</p>
    <?php
require_once '../includes/security.php'; endif; ?>
  </div>

  <div class="card">
    <div class="card-header">📋 Order Details</div>
    <div class="card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:22px;">
        <div><div class="detail-label">Customer</div><div class="detail-value"><?= htmlspecialchars($order['customer_name']) ?></div></div>
        <div><div class="detail-label">Contact</div><div class="detail-value"><?= htmlspecialchars($order['contact_number']) ?></div></div>
        <div><div class="detail-label">Order Method</div><div class="detail-value"><?= $orderMethodIcons[$order['order_method']] ?? $order['order_method'] ?></div></div>
        <div>
          <div class="detail-label">Order Status</div>
          <div class="detail-value"><?= ucfirst(htmlspecialchars($order['order_status'])) ?></div>
        </div>
        <div>
          <div class="detail-label">Payment</div>
          <div class="detail-value"><?= $payIcons[$order['payment_method']] ?? $order['payment_method'] ?></div>
          <?php if ($order['payment_method'] === 'gcash'): ?>
            <?php if ($order['payment_status'] === 'paid'): ?>
              <span class="pay-status-pill pay-status-verified">✓ Payment Verified</span>
            <?php else: ?>
              <span class="pay-status-pill pay-status-pending">Awaiting Verification</span>
            <?php endif; ?>
          <?php endif; ?>
        </div>
        <div style="grid-column:1/-1;"><div class="detail-label">Delivery Address</div><div class="detail-value"><?= htmlspecialchars($order['address']) ?></div></div>
      </div>

      <?php
require_once '../includes/security.php'; foreach ($items as $item): ?>
      <div class="order-item-row">
        <img src="../<?= htmlspecialchars($item['image'] ?? 'images/product-placeholder.jpg') ?>"
             class="order-item-img"
             onerror="this.src='../images/product-placeholder.jpg'"
             alt="<?= htmlspecialchars($item['product_name']) ?>">
        <div class="order-item-info">
          <div class="order-item-name"><?= htmlspecialchars($item['product_name']) ?></div>
          <div class="order-item-qty">× <?= $item['quantity'] ?></div>
        </div>
        <div class="order-item-price">₱<?= number_format($item['price']*$item['quantity'],2) ?></div>
      </div>
      <?php
require_once '../includes/security.php'; endforeach; ?>

      <div class="total-row">
        <span>Total Amount</span>
        <span>₱<?= number_format($order['total_amount'],2) ?></span>
      </div>
    </div>
  </div>

  <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:16px;">
    <a href="my_orders.php" class="btn btn-primary">View My Orders</a>
    <a href="products.php" class="btn btn-outline">Continue Shopping</a>
  </div>

</div>
</body>
</html>