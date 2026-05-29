<?php
require_once '../includes/security.php';
require_once '../middleware/auth.php';
requireCustomer();
require_once '../config/database.php';
$db = getDB();

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
$cart  = $_SESSION['cart'];
$total = 0;
foreach ($cart as $item) $total += $item['price'] * $item['qty'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Cart — Marguax Collections</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
<style>
/* ── HARD RESET — beats style.css ── */
html { background: #1a0609 !important; }
body {
  background: #1a0609 !important;
  background-image: none !important;
  color: #f0e6da !important;
  font-family: 'Jost', sans-serif !important;
  min-height: 100vh !important;
  margin: 0 !important;
  padding: 0 !important;
}
body * { box-sizing: border-box; }

/* kill any pink/light section backgrounds from style.css */
section, .section, main, .main,
.hero, .page-hero-wrap, .banner, .header-banner,
[class*="hero"], [class*="banner"], [class*="header"] {
  background: transparent !important;
  background-image: none !important;
  background-color: transparent !important;
}

/* ── HERO ── */
.page-hero {
  background: #1a0609 !important;
  background-image: none !important;
  border-bottom: 1px solid rgba(196,80,100,.2) !important;
  padding: 60px 24px 48px !important;
  text-align: center !important;
  position: relative !important;
  overflow: hidden !important;
}
.page-hero::before {
  content: '';
  position: absolute; inset: 0;
  background: radial-gradient(ellipse 70% 55% at 50% 0%, rgba(196,80,100,.14) 0%, transparent 68%) !important;
  pointer-events: none;
}
.hero-eyebrow {
  display: inline-block;
  font-size: .65rem !important; font-weight: 600 !important;
  letter-spacing: .28em !important; text-transform: uppercase !important;
  color: #c45064 !important; padding: 5px 20px !important;
  border: 1px solid rgba(196,80,100,.35) !important; border-radius: 40px !important;
  margin-bottom: 18px !important; background: rgba(196,80,100,.07) !important;
  position: relative !important;
}
.page-hero h1 {
  font-family: 'Playfair Display', serif !important;
  font-size: clamp(2.4rem, 5vw, 3.6rem) !important;
  font-weight: 700 !important; color: #f0e6da !important;
  line-height: 1.08 !important; margin: 0 0 10px !important;
  position: relative !important;
  background: transparent !important;
  -webkit-text-fill-color: #f0e6da !important;
}
.page-hero h1 em {
  font-style: italic !important; color: #c45064 !important;
  -webkit-text-fill-color: #c45064 !important;
}
.page-hero p {
  color: #7a6058 !important; font-size: .9rem !important;
  font-weight: 300 !important; position: relative !important;
  background: transparent !important;
}
.hero-divider {
  width: 48px !important; height: 2px !important;
  background: #c45064 !important;
  margin: 16px auto 0 !important; opacity: .65 !important;
  border: none !important;
}

/* ── PAGE WRAP ── */
.page-wrap {
  max-width: 1020px !important;
  margin: 0 auto !important;
  padding: 36px 24px 80px !important;
  background: transparent !important;
}
.cart-grid {
  display: flex !important; gap: 24px !important;
  align-items: flex-start !important; flex-wrap: wrap !important;
}
.cart-items-col { flex: 1 !important; min-width: 300px !important; }
.cart-summary-col { width: 300px !important; flex-shrink: 0 !important; }

/* ── CARD ── */
.mg-card {
  background: #2e0c18 !important;
  border: 1px solid rgba(196,80,100,.22) !important;
  border-radius: 14px !important;
  overflow: hidden !important;
  box-shadow: none !important;
}
.mg-card-head {
  background: #1e0810 !important;
  padding: 15px 20px !important;
  border-bottom: 1px solid rgba(196,80,100,.16) !important;
  display: flex !important; align-items: center !important;
  justify-content: space-between !important;
}
.mg-card-head-title {
  font-family: 'Playfair Display', serif !important;
  font-size: 1rem !important; font-weight: 400 !important;
  color: #f0e6da !important;
  background: transparent !important;
  -webkit-text-fill-color: #f0e6da !important;
}
.mg-pill {
  background: rgba(196,80,100,.15) !important;
  color: #c45064 !important;
  border: 1px solid rgba(196,80,100,.3) !important;
  border-radius: 20px !important; padding: 3px 13px !important;
  font-size: .65rem !important; font-weight: 600 !important;
  letter-spacing: .1em !important;
  -webkit-text-fill-color: #c45064 !important;
}
.mg-clear-btn {
  background: none !important; border: none !important; cursor: pointer !important;
  color: rgba(196,80,100,.55) !important; font-size: .68rem !important;
  font-weight: 600 !important; letter-spacing: .1em !important;
  text-transform: uppercase !important; font-family: 'Jost', sans-serif !important;
  transition: color .2s !important;
  -webkit-text-fill-color: rgba(196,80,100,.55) !important;
}
.mg-clear-btn:hover {
  color: #e8a0a8 !important;
  -webkit-text-fill-color: #e8a0a8 !important;
}

/* ── CART ITEM ── */
.cart-item {
  display: flex !important; align-items: center !important; gap: 16px !important;
  padding: 16px 20px !important;
  border-bottom: 1px solid rgba(196,80,100,.09) !important;
  transition: background .2s !important;
  background: transparent !important;
}
.cart-item:last-child { border-bottom: none !important; }
.cart-item:hover { background: rgba(196,80,100,.05) !important; }
.item-img {
  width: 72px !important; height: 72px !important;
  border-radius: 10px !important; object-fit: cover !important;
  flex-shrink: 0 !important;
  border: 1px solid rgba(196,80,100,.2) !important;
  filter: brightness(.9) saturate(.85) !important;
  transition: filter .3s !important;
}
.cart-item:hover .item-img { filter: brightness(1) saturate(1) !important; }
.item-info { flex: 1 !important; background: transparent !important; }
.item-name {
  font-family: 'Playfair Display', serif !important;
  font-size: 1rem !important; font-weight: 400 !important;
  color: #f0e6da !important; margin-bottom: 5px !important;
  background: transparent !important;
  -webkit-text-fill-color: #f0e6da !important;
}
.item-unit-price {
  color: #c45064 !important; font-size: .8rem !important;
  font-weight: 500 !important;
  -webkit-text-fill-color: #c45064 !important;
}

/* ── QTY ── */
.qty-wrap { display: flex !important; align-items: center !important; gap: 8px !important; }
.qty-btn {
  width: 32px !important; height: 32px !important; border-radius: 8px !important;
  background: rgba(196,80,100,.12) !important;
  border: 1px solid rgba(196,80,100,.28) !important;
  color: #c45064 !important; font-size: 1rem !important; font-weight: 600 !important;
  display: flex !important; align-items: center !important; justify-content: center !important;
  cursor: pointer !important; font-family: 'Jost', sans-serif !important;
  transition: background .2s, transform .15s !important;
  -webkit-text-fill-color: #c45064 !important;
}
.qty-btn:hover {
  background: #c45064 !important; color: #fff !important;
  border-color: #c45064 !important; transform: scale(1.08) !important;
  -webkit-text-fill-color: #fff !important;
}
.qty-val {
  width: 44px !important; text-align: center !important;
  background: rgba(255,255,255,.05) !important;
  border: 1px solid rgba(196,80,100,.2) !important;
  color: #f0e6da !important; border-radius: 7px !important;
  padding: 6px 4px !important; font-size: .88rem !important;
  font-family: 'Jost', sans-serif !important;
  -webkit-text-fill-color: #f0e6da !important;
}
.qty-val:focus {
  outline: none !important; border-color: #c45064 !important;
  box-shadow: 0 0 0 3px rgba(196,80,100,.12) !important;
}
.remove-btn {
  background: none !important; border: none !important; cursor: pointer !important;
  color: rgba(196,80,100,.4) !important; padding: 8px !important;
  border-radius: 8px !important; font-size: 1rem !important;
  transition: color .2s, background .2s !important;
}
.remove-btn:hover {
  background: rgba(196,80,100,.1) !important; color: #e8a0a8 !important;
}

/* ── SUMMARY ── */
.summary-line {
  display: flex !important; justify-content: space-between !important;
  align-items: center !important; padding: 10px 20px !important;
  border-bottom: 1px solid rgba(196,80,100,.08) !important;
  font-size: .83rem !important; color: #7a6058 !important;
  background: transparent !important;
}
.summary-line span:last-child {
  color: #d0c0c8 !important; font-weight: 500 !important;
  -webkit-text-fill-color: #d0c0c8 !important;
}
.summary-total-row {
  display: flex !important; justify-content: space-between !important;
  align-items: center !important; padding: 18px 20px !important;
  border-top: 1px solid rgba(196,80,100,.22) !important;
  background: transparent !important;
}
.summary-total-label {
  font-size: .68rem !important; font-weight: 600 !important;
  letter-spacing: .18em !important; text-transform: uppercase !important;
  color: #7a6058 !important;
  -webkit-text-fill-color: #7a6058 !important;
}
.summary-total-amount {
  font-family: 'Playfair Display', serif !important;
  font-size: 1.65rem !important; color: #c45064 !important;
  -webkit-text-fill-color: #c45064 !important;
}
.summary-actions {
  padding: 0 18px 18px !important;
  display: flex !important; flex-direction: column !important; gap: 10px !important;
  background: transparent !important;
}

/* ── BUTTONS ── */
.btn-checkout {
  display: flex !important; align-items: center !important;
  justify-content: center !important;
  width: 100% !important; padding: 13px 18px !important;
  background: #c45064 !important; color: #fff !important;
  border: none !important; border-radius: 10px !important;
  font-family: 'Jost', sans-serif !important; font-size: .7rem !important;
  font-weight: 600 !important; letter-spacing: .16em !important;
  text-transform: uppercase !important; cursor: pointer !important;
  text-decoration: none !important;
  transition: background .25s, transform .2s, box-shadow .25s !important;
  box-shadow: 0 4px 18px rgba(196,80,100,.32) !important;
  -webkit-text-fill-color: #fff !important;
}
.btn-checkout:hover {
  background: #a83d53 !important; transform: translateY(-2px) !important;
  box-shadow: 0 8px 28px rgba(196,80,100,.44) !important;
}
.btn-continue {
  display: flex !important; align-items: center !important;
  justify-content: center !important;
  width: 100% !important; padding: 12px 18px !important;
  background: transparent !important; color: #9a8a90 !important;
  border: 1px solid rgba(255,255,255,.13) !important; border-radius: 10px !important;
  font-family: 'Jost', sans-serif !important; font-size: .7rem !important;
  font-weight: 600 !important; letter-spacing: .16em !important;
  text-transform: uppercase !important; cursor: pointer !important;
  text-decoration: none !important;
  transition: background .25s, color .25s, border-color .25s !important;
  -webkit-text-fill-color: #9a8a90 !important;
}
.btn-continue:hover {
  background: rgba(255,255,255,.06) !important; color: #c8b8c0 !important;
  border-color: rgba(255,255,255,.25) !important;
  -webkit-text-fill-color: #c8b8c0 !important;
}

/* ── EMPTY ── */
.empty-cart { text-align: center !important; padding: 72px 24px !important; background: transparent !important; }
.empty-cart h3 {
  font-family: 'Playfair Display', serif !important; font-size: 1.8rem !important;
  font-weight: 400 !important; color: #f0e6da !important; margin-bottom: 10px !important;
  -webkit-text-fill-color: #f0e6da !important;
}
.empty-cart p { color: #6a5058 !important; font-weight: 300 !important; margin-bottom: 26px !important; }

/* ── TOAST ── */
#toast-container {
  position: fixed !important; bottom: 28px !important; right: 28px !important;
  z-index: 9999 !important; display: flex !important;
  flex-direction: column !important; gap: 10px !important;
}
.toast {
  background: #2e0c18 !important; border: 1px solid rgba(196,80,100,.3) !important;
  color: #f0e6da !important; border-radius: 12px !important;
  padding: 14px 18px !important; min-width: 240px !important;
  font-family: 'Jost', sans-serif !important; font-size: .84rem !important;
  box-shadow: 0 16px 40px rgba(0,0,0,.6) !important;
  animation: toastIn .35s cubic-bezier(.34,1.56,.64,1) both !important;
}
@keyframes toastIn { from { opacity:0; transform:translateX(20px); } to { opacity:1; transform:translateX(0); } }

@media(max-width: 680px) {
  .cart-summary-col { width: 100% !important; }
  .item-img { width: 58px !important; height: 58px !important; }
}
</style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="page-hero">
  <div class="hero-eyebrow">Marguax Collections</div>
  <h1>My <em>Cart</em></h1>
  <p>Review your items before checkout</p>
  <div class="hero-divider"></div>
</div>

<div class="page-wrap">
<?php if (empty($cart)): ?>
  <div class="mg-card">
    <div class="empty-cart">
      <div style="font-size:2.6rem;opacity:.2;margin-bottom:18px;">◆</div>
      <h3>Your cart is empty</h3>
      <p>Add some products to get started</p>
      <a href="products.php" class="btn-checkout" style="display:inline-flex;width:auto;padding:13px 40px;">Browse Products</a>
    </div>
  </div>
<?php else: ?>
  <div class="cart-grid">

    <!-- Items -->
    <div class="cart-items-col">
      <div class="mg-card">
        <div class="mg-card-head">
          <span class="mg-card-head-title">Cart Items</span>
          <span style="display:flex;align-items:center;gap:14px">
            <span class="mg-pill"><?= count($cart) ?> item<?= count($cart)!=1?'s':'' ?></span>
            <button onclick="clearCart()" class="mg-clear-btn">Clear All</button>
          </span>
        </div>
        <?php foreach ($cart as $pid => $item): ?>
        <div class="cart-item" id="row-<?= (int)$pid ?>">
          <img src="../<?= e($item['image'] ?? 'images/product-placeholder.jpg') ?>"
               class="item-img"
               onerror="this.src='../images/product-placeholder.jpg'"
               alt="<?= e($item['product_name']) ?>">
          <div class="item-info">
            <div class="item-name"><?= e($item['product_name']) ?></div>
            <div class="item-unit-price">₱<?= number_format($item['price'],2) ?> each</div>
          </div>
          <div class="qty-wrap">
            <button class="qty-btn" onclick="updateQty(<?= (int)$pid ?>, -1)">−</button>
            <input class="qty-val" id="qty-<?= (int)$pid ?>" type="number"
                   value="<?= (int)$item['qty'] ?>" min="1" max="999"
                   onchange="setQty(<?= (int)$pid ?>, this.value)">
            <button class="qty-btn" onclick="updateQty(<?= (int)$pid ?>, 1)">+</button>
          </div>
          <button class="remove-btn" onclick="removeItem(<?= (int)$pid ?>)" title="Remove">🗑</button>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Summary -->
    <div class="cart-summary-col">
      <div class="mg-card">
        <div class="mg-card-head">
          <span class="mg-card-head-title">Order Summary</span>
        </div>
        <?php foreach ($cart as $item): ?>
        <div class="summary-line">
          <span><?= e($item['product_name']) ?> ×<?= (int)$item['qty'] ?></span>
          <span>₱<?= number_format($item['price']*$item['qty'],2) ?></span>
        </div>
        <?php endforeach; ?>
        <div class="summary-total-row">
          <span class="summary-total-label">Total</span>
          <span class="summary-total-amount" id="cartTotal">₱<?= number_format($total,2) ?></span>
        </div>
        <div class="summary-actions">
          <a href="checkout.php" class="btn-checkout">Proceed to Checkout →</a>
          <a href="products.php" class="btn-continue">Continue Shopping</a>
        </div>
      </div>
    </div>

  </div>
<?php endif; ?>
</div>

<div id="toast-container"></div>
<script>
function updateQty(pid, delta) {
  const input = document.getElementById('qty-'+pid);
  let val = parseInt(input.value) + delta;
  if (val < 1) { removeItem(pid); return; }
  input.value = val;
  setQty(pid, val);
}
function setQty(pid, qty) {
  fetch('/Marguax_Collection/customer/cart_action.php', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`action=update&product_id=${pid}&qty=${qty}`
  }).then(r=>r.json()).then(()=>location.reload());
}
function removeItem(pid) {
  fetch('/Marguax_Collection/customer/cart_action.php', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`action=remove&product_id=${pid}`
  }).then(r=>r.json()).then(()=>location.reload());
}
function clearCart() {
  if (!confirm('Clear all items?')) return;
  fetch('/Marguax_Collection/customer/cart_action.php', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'action=clear'
  }).then(r=>r.json()).then(()=>location.reload());
}
function showToast(msg) {
  const c = document.getElementById('toast-container');
  const t = document.createElement('div');
  t.className = 'toast'; t.textContent = msg; c.appendChild(t);
  setTimeout(()=>t.remove(), 3000);
}
</script>
</body>
</html>
