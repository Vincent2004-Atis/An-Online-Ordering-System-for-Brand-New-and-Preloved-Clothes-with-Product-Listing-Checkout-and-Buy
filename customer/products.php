<?php
require_once '../includes/security.php';
if (!isset($_SESSION['user_id'])) { header('Location: /Marguax_Collection/auth/login.php'); exit; }
require_once '../config/database.php';
$db     = getDB();
$userId = (int)$_SESSION['user_id'];

$stmt = $db->prepare("SELECT name, member_status FROM users WHERE user_id=?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$user) { session_destroy(); header('Location: /Marguax_Collection/auth/login.php'); exit; }
$isMember = ($user['member_status'] === 'member');

$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$typeFilter     = $_GET['filter'] ?? 'all';
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search         = trim($_GET['search'] ?? '');

if (!$isMember && $typeFilter === 'member') {
    header('Location: products.php?filter=loose'); exit;
}

$where  = ['1=1'];
$params = [];
$types  = '';

if ($typeFilter === 'member' && $isMember) { $where[] = "p.product_type = 'member'"; }
elseif ($typeFilter === 'package') { $where[] = "p.product_type = 'package'"; }
elseif ($typeFilter === 'loose')   { $where[] = "p.product_type = 'loose'"; }

if ($categoryFilter > 0) {
    $where[]  = "p.category_id = ?";
    $types   .= 'i';
    $params[] = $categoryFilter;
}
if (!empty($search)) {
    $where[]  = "(p.product_name LIKE ? OR p.description LIKE ?)";
    $types   .= 'ss';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql  = "SELECT p.*, c.name AS category_name
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.category_id
         WHERE " . implode(' AND ', $where) . " ORDER BY p.product_name";
$stmt = $db->prepare($sql);
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Product Catalog — Marguax Collections</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
<style>
/* ============================================================
   NUCLEAR OVERRIDE — kills every pink !important from style.css
   Matches login.php dark burgundy theme exactly
   ============================================================ */

/* 1. Root canvas — same gradient as login.php */
html, body {
  background: linear-gradient(to bottom right, #0e0507 0%, #1a0a0e 30%, #2a0d14 60%, #3d1020 100%) !important;
  color: #f0e6da !important;
  font-family: 'Jost', sans-serif !important;
  min-height: 100vh !important;
}

/* 2. Kill the pink page-hero */
.page-hero {
  background: transparent !important;
  border-bottom: 1px solid rgba(196,80,100,.15) !important;
  color: #f0e6da !important;
  padding: 72px 40px 56px !important;
  text-align: center !important;
  position: relative !important;
}
.page-hero::before {
  content: '' !important;
  position: absolute !important;
  inset: 0 !important;
  background: radial-gradient(ellipse 80% 70% at 50% -10%, rgba(196,80,100,.13) 0%, transparent 70%) !important;
  pointer-events: none !important;
}
.page-hero h1 {
  font-family: 'Playfair Display', serif !important;
  font-size: clamp(2.6rem, 6vw, 4.8rem) !important;
  font-weight: 700 !important;
  color: #f0e6da !important;
  line-height: 1.05 !important;
  letter-spacing: -.5px !important;
  margin: 0 0 14px !important;
  animation: heroIn .8s cubic-bezier(.16,1,.3,1) both !important;
}
.page-hero h1 em {
  font-style: italic !important;
  color: #c45064 !important;
}
.page-hero p {
  color: #7a6058 !important;
  font-size: .92rem !important;
  font-weight: 300 !important;
  margin: 0 !important;
  animation: heroIn .8s .12s cubic-bezier(.16,1,.3,1) both !important;
}
.page-hero p strong { color: #e8a0a8 !important; font-weight: 500 !important; }

/* Hero eyebrow pill */
.hero-eyebrow {
  display: inline-flex !important;
  align-items: center !important;
  gap: 8px !important;
  font-size: .68rem !important;
  font-weight: 600 !important;
  letter-spacing: .28em !important;
  text-transform: uppercase !important;
  color: #c45064 !important;
  padding: 6px 20px !important;
  border: 1px solid rgba(196,80,100,.3) !important;
  border-radius: 40px !important;
  margin-bottom: 22px !important;
  background: rgba(196,80,100,.06) !important;
  animation: heroIn .7s cubic-bezier(.16,1,.3,1) both !important;
}
.hero-divider {
  width: 56px !important; height: 1px !important;
  background: linear-gradient(90deg, transparent, #c45064, transparent) !important;
  margin: 22px auto 0 !important;
  animation: heroIn .7s .2s cubic-bezier(.16,1,.3,1) both !important;
}

/* 3. Container */
.container {
  max-width: 1280px !important;
  margin: 0 auto !important;
  padding: 0 36px !important;
  background: transparent !important;
}

/* 4. Filter section */
.filter-section {
  padding: 32px 0 18px !important;
  display: flex !important;
  flex-direction: column !important;
  align-items: center !important;
  gap: 10px !important;
  margin-bottom: 8px !important;
  animation: fadeUp .6s .3s cubic-bezier(.16,1,.3,1) both !important;
}
.filter-bar {
  display: flex !important;
  gap: 8px !important;
  flex-wrap: wrap !important;
  justify-content: center !important;
}
.filter-divider {
  width: 100% !important;
  height: 1px !important;
  background: rgba(196,80,100,.12) !important;
  margin: 4px 0 !important;
}
.filter-tab {
  padding: 9px 22px !important;
  border-radius: 40px !important;
  font-size: .7rem !important;
  font-weight: 500 !important;
  letter-spacing: .14em !important;
  text-transform: uppercase !important;
  color: #7a6058 !important;
  background: rgba(255,255,255,.03) !important;
  border: 1px solid rgba(196,80,100,.12) !important;
  text-decoration: none !important;
  display: inline-block !important;
  transition: color .25s, border-color .25s, background .25s, transform .2s !important;
  font-family: 'Jost', sans-serif !important;
  box-shadow: none !important;
}
.filter-tab:hover {
  color: #e8a0a8 !important;
  border-color: rgba(196,80,100,.4) !important;
  background: rgba(196,80,100,.06) !important;
  transform: translateY(-2px) !important;
  box-shadow: none !important;
}
.filter-tab.active {
  background: #c45064 !important;
  color: #fff !important;
  border-color: #c45064 !important;
  font-weight: 600 !important;
  box-shadow: 0 6px 18px rgba(196,80,100,.35) !important;
  transform: translateY(-1px) !important;
}
.filter-tab.locked-tab {
  opacity: .35 !important;
  cursor: not-allowed !important;
  color: #5a4a42 !important;
  background: transparent !important;
  border-color: rgba(196,80,100,.08) !important;
}
.filter-tab.locked-tab:hover {
  transform: none !important;
  box-shadow: none !important;
}
.filter-bar.type-bar .filter-tab {
  padding: 10px 26px !important;
  font-size: .72rem !important;
}

/* 5. Product grid */
.product-grid {
  display: grid !important;
  grid-template-columns: repeat(auto-fill, minmax(285px, 1fr)) !important;
  gap: 20px !important;
  padding: 28px 0 80px !important;
}

/* 6. Product card — full dark override */
.product-card {
  background: rgba(42,13,20,.7) !important;
  border: 1px solid rgba(196,80,100,.12) !important;
  border-radius: 16px !important;
  overflow: hidden !important;
  display: flex !important;
  flex-direction: column !important;
  position: relative !important;
  transition: transform .4s cubic-bezier(.16,1,.3,1), border-color .3s, box-shadow .4s !important;
  animation: cardIn .55s cubic-bezier(.16,1,.3,1) both !important;
  backdrop-filter: blur(4px) !important;
}
.product-card:hover {
  transform: translateY(-7px) !important;
  border-color: rgba(196,80,100,.4) !important;
  box-shadow: 0 28px 56px rgba(0,0,0,.55), 0 0 0 1px rgba(196,80,100,.15) !important;
}
.product-card.locked { opacity: .62 !important; }

/* stagger */
.product-card:nth-child(1)  { animation-delay:.05s !important }
.product-card:nth-child(2)  { animation-delay:.10s !important }
.product-card:nth-child(3)  { animation-delay:.15s !important }
.product-card:nth-child(4)  { animation-delay:.20s !important }
.product-card:nth-child(5)  { animation-delay:.25s !important }
.product-card:nth-child(6)  { animation-delay:.30s !important }
.product-card:nth-child(7)  { animation-delay:.35s !important }
.product-card:nth-child(8)  { animation-delay:.40s !important }
.product-card:nth-child(9)  { animation-delay:.45s !important }
.product-card:nth-child(10) { animation-delay:.50s !important }

/* 7. Product image */
.product-img {
  position: relative !important;
  height: 272px !important;
  overflow: hidden !important;
  background: #1a0a0e !important;
}
.product-img img {
  width: 100% !important; height: 100% !important;
  object-fit: cover !important;
  display: block !important;
  border-bottom: none !important;
  filter: brightness(.92) saturate(.88) !important;
  transition: transform .65s cubic-bezier(.16,1,.3,1), filter .4s !important;
}
.product-card:hover .product-img img {
  transform: scale(1.07) !important;
  filter: brightness(1.04) saturate(1.04) !important;
}
.product-card.locked .product-img img {
  filter: blur(5px) brightness(.55) !important;
}

/* 8. Badges */
.product-type-badge {
  position: absolute !important;
  top: 12px !important; left: 12px !important;
  font-size: .62rem !important;
  font-weight: 600 !important;
  letter-spacing: .13em !important;
  text-transform: uppercase !important;
  padding: 4px 12px !important;
  border-radius: 20px !important;
  backdrop-filter: blur(10px) !important;
  z-index: 3 !important;
  box-shadow: none !important;
  animation: none !important;
}
.badge-loose, .product-type-badge:not(.badge-member):not(.badge-package) {
  background: rgba(14,5,7,.75) !important;
  color: #7a6058 !important;
  border: 1px solid rgba(196,80,100,.18) !important;
}
.badge-member {
  background: rgba(196,80,100,.18) !important;
  color: #e8a0a8 !important;
  border: 1px solid rgba(196,80,100,.45) !important;
}
.badge-package {
  background: rgba(196,80,100,.12) !important;
  color: #c8788a !important;
  border: 1px solid rgba(196,80,100,.3) !important;
}

/* 9. Lock overlay */
.lock-overlay {
  position: absolute !important; inset: 0 !important;
  background: rgba(14,5,7,.82) !important;
  display: flex !important; flex-direction: column !important;
  align-items: center !important; justify-content: center !important;
  gap: 10px !important;
  backdrop-filter: blur(6px) !important;
  z-index: 4 !important;
}
.lock-icon {
  font-size: 1.8rem !important;
  width: 52px !important; height: 52px !important;
  border: 1px solid rgba(196,80,100,.4) !important;
  border-radius: 50% !important;
  display: flex !important; align-items: center !important; justify-content: center !important;
  background: rgba(196,80,100,.08) !important;
  filter: none !important;
  animation: lockGlow 3s ease-in-out infinite !important;
}
.lock-label {
  font-size: .62rem !important;
  letter-spacing: .18em !important;
  text-transform: uppercase !important;
  color: rgba(196,80,100,.7) !important;
  font-weight: 500 !important;
  font-family: 'Jost', sans-serif !important;
  background: transparent !important;
  padding: 0 !important;
  border-radius: 0 !important;
}

/* 10. Product info */
.product-info {
  padding: 20px 20px 14px !important;
  flex: 1 !important;
  display: flex !important;
  flex-direction: column !important;
  gap: 5px !important;
  background: transparent !important;
}
.product-name {
  font-family: 'Playfair Display', serif !important;
  font-size: 1.15rem !important;
  font-weight: 400 !important;
  color: #f0e6da !important;
  line-height: 1.3 !important;
  transition: color .25s !important;
}
.product-card:hover .product-name { color: #e8a0a8 !important; }
.category-tag {
  font-size: .67rem !important;
  color: rgba(196,80,100,.6) !important;
  letter-spacing: .1em !important;
  text-transform: uppercase !important;
  font-weight: 500 !important;
  margin-bottom: 0 !important;
}
.product-desc {
  font-size: .8rem !important;
  color: #5a4a42 !important;
  line-height: 1.65 !important;
  flex: 1 !important;
  height: auto !important;
  overflow: hidden !important;
  display: -webkit-box !important;
  -webkit-line-clamp: 3 !important;
  -webkit-box-orient: vertical !important;
  margin-top: 4px !important;
}
.product-price {
  font-family: 'Playfair Display', serif !important;
  font-size: 1.45rem !important;
  font-weight: 400 !important;
  color: #c45064 !important;
  margin-top: 10px !important;
  filter: none !important;
  letter-spacing: .02em !important;
}
.product-card.locked .product-price {
  filter: blur(5px) !important;
  user-select: none !important;
}

/* 11. Actions */
.product-actions {
  padding: 0 20px 18px !important;
  display: flex !important;
  flex-direction: column !important;
  gap: 8px !important;
  background: transparent !important;
}
.qty-control {
  display: flex !important;
  align-items: center !important;
  gap: 8px !important;
}
.qty-btn {
  width: 34px !important; height: 34px !important;
  background: rgba(196,80,100,.08) !important;
  border: 1px solid rgba(196,80,100,.22) !important;
  color: #c45064 !important;
  font-size: 1rem !important;
  border-radius: 8px !important;
  cursor: pointer !important;
  display: flex !important; align-items: center !important; justify-content: center !important;
  transition: background .2s, transform .15s !important;
  padding: 0 !important;
  font-family: 'Jost', sans-serif !important;
}
.qty-btn:hover {
  background: #c45064 !important;
  color: #fff !important;
  border-color: #c45064 !important;
  transform: scale(1.1) !important;
}
.qty-input {
  width: 46px !important;
  text-align: center !important;
  background: rgba(255,255,255,.04) !important;
  border: 1px solid rgba(196,80,100,.18) !important;
  color: #f0e6da !important;
  border-radius: 8px !important;
  padding: 6px 4px !important;
  font-size: .88rem !important;
  font-family: 'Jost', sans-serif !important;
}
.qty-input:focus {
  outline: none !important;
  border-color: #c45064 !important;
  box-shadow: 0 0 0 3px rgba(196,80,100,.12) !important;
}

/* 12. Buttons — full override */
.btn-primary {
  display: flex !important; align-items: center !important; justify-content: center !important;
  width: 100% !important;
  background:rgba(255,255,255,.04) !important;
  color: #fff !important;
  border: none !important;
  border-radius: 9px !important;
  padding: 13px 18px !important;
  font-family: 'Jost', sans-serif !important;
  font-size: .74rem !important;
  font-weight: 600 !important;
  letter-spacing: .14em !important;
  text-transform: uppercase !important;
  cursor: pointer !important;
  text-decoration: none !important;
  transition: background .25s, transform .2s, box-shadow .25s !important;
  gap: 6px !important;
}
.btn-primary:hover {
  background: #ffffff !important;
  transform: translateY(-2px) !important;
  box-shadow: 0 10px 24px rgba(196,80,100,.35) !important;
}
.btn-primary:active { transform: scale(.98) !important; }

.btn-outline {
  display: flex !important; align-items: center !important; justify-content: center !important;
  width: 100% !important;
  background: transparent !important;
  color: #5a4a42 !important;
  border: 1px solid rgba(196,80,100,.15) !important;
  border-radius: 9px !important;
  padding: 13px 18px !important;
  font-family: 'Jost', sans-serif !important;
  font-size: .74rem !important;
  font-weight: 500 !important;
  letter-spacing: .12em !important;
  text-transform: uppercase !important;
  cursor: not-allowed !important;
}

.btn-locked, .btn-locked:hover {
  display: flex !important; align-items: center !important; justify-content: center !important;
  width: 100% !important;
  background: transparent !important;
  color: rgba(196,80,100,.7) !important;
  border: 1px solid rgba(196,80,100,.25) !important;
  border-radius: 9px !important;
  padding: 13px 18px !important;
  font-family: 'Jost', sans-serif !important;
  font-size: .72rem !important;
  font-weight: 500 !important;
  letter-spacing: .12em !important;
  text-transform: uppercase !important;
  cursor: pointer !important;
  text-decoration: none !important;
  transition: all .25s !important;
  gap: 6px !important;
  box-shadow: none !important;
}
.btn-locked:hover {
  background: rgba(196,80,100,.1) !important;
  color: #e8a0a8 !important;
  border-color: rgba(196,80,100,.45) !important;
  transform: translateY(-1px) !important;
}

/* 13. Empty state */
.card {
  background: rgba(42,13,20,.6) !important;
  border: 1px solid rgba(196,80,100,.18) !important;
  color: #f0e6da !important;
  border-radius: 16px !important;
  box-shadow: none !important;
}
.card h3 { color: #f0e6da !important; }
.card p  { color: #7a6058 !important; }

/* 14. Footer */
footer {
  background: #09040a !important;
  color: rgba(240,230,218,.5) !important;
  padding: 72px 36px 40px !important;
  border-top: 1px solid rgba(196,80,100,.1) !important;
  position: relative !important;
}
footer::before {
  content: '' !important;
  position: absolute !important;
  top: 0 !important; left: 0 !important; right: 0 !important; height: 1px !important;
  background: linear-gradient(90deg, transparent, rgba(196,80,100,.4), transparent) !important;
}
footer h4 {
  color: #c45064 !important;
  font-family: 'Jost', sans-serif !important;
  font-size: .72rem !important;
  letter-spacing: .2em !important;
  text-transform: uppercase !important;
  font-weight: 500 !important;
  margin-bottom: 16px !important;
}
footer a {
  color: rgba(240,230,218,.35) !important;
  text-decoration: none !important;
  font-family: 'Jost', sans-serif !important;
  font-size: .82rem !important;
  font-weight: 300 !important;
  transition: color .2s, padding-left .2s !important;
}
footer a:hover { color: #e8a0a8 !important; padding-left: 4px !important; }

/* 15. Toast */
#toast-container {
  position: fixed !important;
  bottom: 28px !important; right: 28px !important;
  z-index: 9999 !important;
  display: flex !important; flex-direction: column !important; gap: 10px !important;
}
.toast {
  background: #2a0d14 !important;
  border: 1px solid rgba(196,80,100,.3) !important;
  color: #f0e6da !important;
  border-radius: 12px !important;
  padding: 14px 18px !important;
  font-family: 'Jost', sans-serif !important;
  font-size: .84rem !important;
  min-width: 240px !important;
  animation: toastIn .35s cubic-bezier(.34,1.56,.64,1) both !important;
  box-shadow: 0 16px 40px rgba(0,0,0,.6) !important;
}
.toast.error { border-color: rgba(196,80,100,.6) !important; }

/* 16. Ripple */
.ripple-effect {
  position: fixed !important;
  border-radius: 50% !important;
  background: rgba(196,80,100,.12) !important;
  transform: scale(0) !important;
  animation: rippleOut .6s ease-out forwards !important;
  pointer-events: none !important;
  z-index: 99999 !important;
}

/* 17. Page transition */
.page-transition {
  position: fixed !important; inset: 0 !important;
  z-index: 99998 !important;
  pointer-events: none !important;
  display: flex !important;
  align-items: center !important; justify-content: center !important;
}
.pt-panel {
  position: absolute !important; inset: 0 !important;
  background: linear-gradient(135deg, #0e0507, #2a0d14) !important;
  transform: scaleY(0) !important;
  transform-origin: bottom !important;
  transition: transform .5s cubic-bezier(.77,0,.18,1) !important;
}
.pt-logo {
  position: relative !important; z-index: 2 !important;
  opacity: 0 !important; transform: scale(.5) !important;
  transition: all .4s ease .2s !important;
  text-align: center !important;
}
.pt-logo-text {
  font-family: 'Playfair Display', serif !important;
  font-size: 1.6rem !important;
  color: #e8a0a8 !important;
  letter-spacing: .15em !important;
  font-weight: 400 !important;
}
.pt-logo-bar {
  width: 0 !important; height: 1px !important;
  background: linear-gradient(90deg, transparent, #c45064, transparent) !important;
  margin: 12px auto 0 !important;
  transition: width .5s ease .3s !important;
}
.page-transition.active .pt-panel  { transform: scaleY(1) !important; }
.page-transition.active .pt-logo   { opacity: 1 !important; transform: scale(1) !important; }
.page-transition.active .pt-logo-bar { width: 120px !important; }

/* 18. Keyframes */
@keyframes heroIn  { from { opacity:0; transform:translateY(18px); } to { opacity:1; transform:translateY(0); } }
@keyframes fadeUp  { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
@keyframes cardIn  { from { opacity:0; transform:translateY(32px) scale(.96); } to { opacity:1; transform:translateY(0) scale(1); } }
@keyframes toastIn { from { opacity:0; transform:translateX(20px); } to { opacity:1; transform:translateX(0); } }
@keyframes rippleOut { to { transform:scale(8); opacity:0; } }
@keyframes lockGlow {
  0%,100% { box-shadow:0 0 0 0 rgba(196,80,100,.3); }
  50%      { box-shadow:0 0 0 8px rgba(196,80,100,0); }
}

/* 19. Mobile */
@media(max-width:768px){
  .product-grid { grid-template-columns:1fr 1fr !important; gap:12px !important; }
  .page-hero { padding:48px 20px 40px !important; }
  .container { padding:0 16px !important; }
  .filter-tab { font-size:.62rem !important; padding:8px 14px !important; }
}
@media(max-width:480px){
  .product-grid { grid-template-columns:1fr !important; }
}
</style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<?php
  $heroTitle  = 'Our';
  $heroItalic = 'Collection';
  $heroSub    = 'Discover every product in the Marguax Collections range';
  if ($typeFilter==='member')  { $heroTitle='Member';  $heroItalic='Exclusives'; $heroSub='Curated products available only to our valued members'; }
  if ($typeFilter==='package') { $heroTitle='Curated'; $heroItalic='Packages';   $heroSub='Choose the package that fits your lifestyle'; }
  if ($typeFilter==='loose')   { $heroTitle='Loose';   $heroItalic='Products';   $heroSub='Individual pieces available for every shopper'; }
?>

<div class="page-hero">
  <div class="hero-eyebrow">Marguax Collections</div>
  <h1><?= $heroTitle ?> <em><?= $heroItalic ?></em></h1>
 
  <div class="hero-divider"></div>
</div>

<div class="container">


    <div class="filter-divider"></div>

    <div class="filter-bar">
      <?php foreach ($categories as $cat): ?>
      <a href="products.php?filter=<?= urlencode($typeFilter) ?>&category=<?= $cat['category_id'] ?>"
         class="filter-tab <?= $categoryFilter===$cat['category_id'] ? 'active' : '' ?>">
        <?= htmlspecialchars($cat['name']) ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if (empty($products)): ?>
  <div class="card" style="text-align:center;padding:70px 24px;">
    <div style="font-size:2.4rem;margin-bottom:18px;opacity:.25;">◆</div>
    <h3 style="font-family:'Playfair Display',serif;font-size:1.9rem;font-weight:400;margin-bottom:10px;">Nothing found</h3>
    <p style="margin-bottom:26px;">Try adjusting your filters or search terms.</p>
    <a href="products.php" class="btn-primary" style="display:inline-flex;width:auto;padding:13px 40px;">Clear Filters</a>
  </div>
  <?php else: ?>
  <div class="product-grid">
    <?php foreach ($products as $p):
      $typeLabel = match($p['product_type']) { 'member'=>'Member Exclusive','package'=>'Package',default=>'Loose' };
      $typeClass = match($p['product_type']) { 'member'=>'badge-member','package'=>'badge-package',default=>'badge-loose' };
      $isLocked  = !$isMember && $p['product_type'] !== 'loose';
    ?>
    <div class="product-card <?= $isLocked ? 'locked' : '' ?>">
      <div class="product-img">
        <img src="../<?= htmlspecialchars($p['image']) ?>"
             alt="<?= htmlspecialchars($p['product_name']) ?>"
             onerror="this.src='../images/product-placeholder.jpg'">
        <span class="product-type-badge <?= $typeClass ?>"><?= $typeLabel ?></span>
        <?php if (!$isLocked && $p['stock'] <= 10 && $p['stock'] > 0): ?>
          <span class="product-type-badge" style="top:12px;right:12px;left:auto;background:rgba(196,80,100,.18)!important;color:#e8a0a8!important;border:1px solid rgba(196,80,100,.45)!important;animation:none!important;">Low Stock</span>
        <?php elseif (!$isLocked && $p['stock'] == 0): ?>
          <span class="product-type-badge" style="top:12px;right:12px;left:auto;background:rgba(42,13,20,.7)!important;color:#5a4a42!important;border:1px solid rgba(196,80,100,.1)!important;animation:none!important;">Sold Out</span>
        <?php endif; ?>
        <?php if ($isLocked): ?>
        <div class="lock-overlay">
          <div class="lock-icon">🔒</div>
          <div class="lock-label">Members Only</div>
        </div>
        <?php endif; ?>
      </div>

      <div class="product-info">
        <div class="product-name"><?= htmlspecialchars($p['product_name']) ?></div>
        <?php if (!empty($p['category_name'])): ?>
        <div class="category-tag"><?= htmlspecialchars($p['category_name']) ?></div>
        <?php endif; ?>
        <div class="product-desc"><?= htmlspecialchars($p['description']) ?></div>
        <div class="product-price"><?= $isLocked ? '₱ · · · · ·' : '₱ '.number_format($p['price'],2) ?></div>
      </div>

      <?php if ($isLocked): ?>
      <div class="product-actions">
        <a href="#how-to-join" class="btn-locked">✦ Unlock — Become a Member</a>
      </div>
      <?php elseif ($p['stock'] > 0): ?>
      <div class="product-actions">
        <div class="qty-control">
          <button class="qty-btn" onclick="changeQty(<?= $p['product_id'] ?>, -1)">−</button>
          <input type="number" class="qty-input" id="qty-<?= $p['product_id'] ?>" value="1" min="1" max="<?= $p['stock'] ?>">
          <button class="qty-btn" onclick="changeQty(<?= $p['product_id'] ?>, 1)">+</button>
          <span style="font-size:.7rem;color:#5a4a42;">/ <?= $p['stock'] ?> left</span>
        </div>
        <button class="btn-primary"
                onclick="addToCart(<?= $p['product_id'] ?>, '<?= htmlspecialchars(addslashes($p['product_name'])) ?>', <?= $p['price'] ?>)">
          Add to Cart
        </button>
      </div>
      <?php else: ?>
      <div class="product-actions">
        <button class="btn-outline" disabled>Out of Stock</button>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>

<!-- FOOTER -->
<footer>
  <div style="max-width:1200px;margin:auto;">
    <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:48px;margin-bottom:52px;">
      <div>
        <img src="../images/logo.png" alt="Marguax" onerror="this.style.display='none'"
             style="width:54px;height:54px;border-radius:50%;border:1px solid rgba(196,80,100,.3);object-fit:cover;margin-bottom:18px;display:block;">
        <p style="font-size:.82rem;line-height:1.85;max-width:270px;color:rgba(240,230,218,.35);font-family:'Jost',sans-serif;font-weight:300;">
          Marguax Collections — bringing premium Ardeur de France products and wellness solutions to Filipino families.
        </p>
        <div style="display:flex;gap:10px;margin-top:20px;">
          <?php foreach(['https://facebook.com/Marguaxworldmktg'=>'📘','#'=>'📸','#'=>'🐦'] as $href=>$icon): ?>
          <a href="<?= $href ?>"
             style="width:36px;height:36px;border-radius:8px;background:rgba(196,80,100,.07);border:1px solid rgba(196,80,100,.18);display:flex;align-items:center;justify-content:center;font-size:.88rem;text-decoration:none;transition:background .2s,transform .2s;padding:0!important;"
             onmouseover="this.style.background='rgba(196,80,100,.18)';this.style.transform='translateY(-2px)'"
             onmouseout="this.style.background='rgba(196,80,100,.07)';this.style.transform=''"><?= $icon ?></a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div style="border-top:1px solid rgba(196,80,100,.1);padding-top:26px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;font-size:.75rem;color:rgba(240,230,218,.2);font-family:'Jost',sans-serif;">
      <span>© 2026 Marguax Collections. All rights reserved.</span>
      <span>🌐 www.MarguaxCollection.com · 📘 MarguaxCollection</span>
    </div>
  </div>
</footer>

<!-- PAGE TRANSITION -->
<div class="page-transition" id="pageTransition">
  <div class="pt-panel"></div>
  <div class="pt-logo">
    <div class="pt-logo-text">Marguax Collections</div>
    <div class="pt-logo-bar"></div>
  </div>
</div>
<div id="toast-container"></div>

<script>
function changeQty(id, delta) {
  const input = document.getElementById('qty-' + id);
  let val = parseInt(input.value) + delta;
  input.value = Math.max(1, Math.min(val, parseInt(input.max)));
}
function addToCart(productId, name, price) {
  const qty = parseInt(document.getElementById('qty-' + productId).value) || 1;
  fetch('/Marguax_Collection/customer/cart_action.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `action=add&product_id=${productId}&qty=${qty}`
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      showToast('✦ ' + name + ' added to cart', 'success');
      const badge = document.querySelector('.cart-badge');
      if (badge) badge.textContent = data.cart_count;
    } else {
      showToast('✕ ' + (data.message || 'Failed to add'), 'error');
    }
  })
  .catch(() => showToast('✕ Network error', 'error'));
}
function showToast(msg, type = '') {
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  t.textContent = msg;
  document.getElementById('toast-container').appendChild(t);
  setTimeout(() => {
    t.style.transition = 'opacity .3s, transform .3s';
    t.style.opacity = '0'; t.style.transform = 'translateX(14px)';
    setTimeout(() => t.remove(), 320);
  }, 3200);
}
const transition = document.getElementById('pageTransition');
document.querySelectorAll('a.filter-tab.locked-tab').forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    transition.classList.add('active');
    setTimeout(() => {
      transition.classList.remove('active');
      document.getElementById('how-to-join')?.scrollIntoView({ behavior: 'instant', block: 'start' });
    }, 1100);
  });
});
document.addEventListener('click', e => {
  const r = document.createElement('div');
  const s = 60;
  r.className = 'ripple-effect';
  r.style.cssText = `width:${s}px;height:${s}px;left:${e.clientX-s/2}px;top:${e.clientY-s/2}px`;
  document.body.appendChild(r);
  setTimeout(() => r.remove(), 620);
});
window.addEventListener('pageshow', () => transition.classList.remove('active'));
</script>
</body>
</html>
