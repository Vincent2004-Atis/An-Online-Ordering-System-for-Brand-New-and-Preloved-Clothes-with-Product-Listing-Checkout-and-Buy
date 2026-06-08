<?php
$currentPage  = basename($_SERVER['PHP_SELF']);
$unreadCount  = 0;
$pendingCount = 0;
if (isset($db)) {
    // Use conversations table — count unread messages from customers
    $r = $db->query("
        SELECT COUNT(*) FROM messages m
        JOIN conversations c ON c.conversation_id = m.conversation_id
        WHERE m.sender_type = 'customer' AND m.is_read = 0
    ");
    if ($r) $unreadCount = (int)$r->fetch_row()[0];

    $r2 = $db->query("SELECT COUNT(*) FROM orders WHERE order_status='pending'");
    if ($r2) $pendingCount = (int)$r2->fetch_row()[0];
}
?>
<div class="sidebar">

  <div class="sidebar-brand">
    <div class="sidebar-brand-icon">M</div>
    <div>
      <div class="brand-name">Marguax <em>Collections</em></div>
      <span class="brand-sub">✦ Admin Panel</span>
    </div>
  </div>

  <div class="sidebar-nav">

    <div class="sidebar-section-label">Main</div>
    <a href="dashboard.php" class="sidebar-link <?= $currentPage==='dashboard.php'?'active':'' ?>">
      <span>📊</span> Dashboard
    </a>
    <a href="analytics.php" class="sidebar-link <?= $currentPage==='analytics.php'?'active':'' ?>">
      <span>📈</span> Analytics
    </a>

    <div class="sidebar-section-label">Management</div>
    <a href="manage_orders.php" class="sidebar-link <?= $currentPage==='manage_orders.php'?'active':'' ?>">
      <span>📋</span> Orders
      <?php if($pendingCount>0): ?><span class="count"><?= $pendingCount ?></span><?php endif; ?>
    </a>
    <a href="manage_products.php" class="sidebar-link <?= $currentPage==='manage_products.php'?'active':'' ?>">
      <span>🛍️</span> Products
    </a>
    <a href="manage_users.php" class="sidebar-link <?= $currentPage==='manage_users.php'?'active':'' ?>">
      <span>👥</span> Users
    </a>

    <div class="sidebar-section-label">Support</div>
    <a href="messages.php" class="sidebar-link <?= $currentPage==='messages.php'?'active':'' ?>">
      <span>💬</span> Messages
      <?php if($unreadCount>0): ?><span class="count"><?= $unreadCount ?></span><?php endif; ?>
    </a>

    <div class="sidebar-section-label">Account</div>
    <a href="../auth/logout.php" class="sidebar-link">
      <span>🚪</span> Logout
    </a>

  </div>

  <div class="sidebar-footer">
    <strong><?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></strong>
    <span>✦ Admin</span>
  </div>

</div>
