<?php
require_once '../includes/security.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php'); exit;
}
require_once '../config/database.php';
$db = getDB();

$uid = (int)($_GET['id'] ?? 0);
if (!$uid) { header('Location: manage_users.php'); exit; }

/* ── Fetch user ── */
$stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param('i', $uid);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$u) { header('Location: manage_users.php'); exit; }

/* ── Handle membership update ── */
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_membership'])) {
    $status = $_POST['member_status'];
    if (in_array($status, ['member', 'non-member'])) {
        $s = $db->prepare("UPDATE users SET member_status=? WHERE user_id=?");
        $s->bind_param('si', $status, $uid);
        $s->execute();
        $s->close();
        header("Location: view_user.php?id=$uid&msg=updated"); exit;
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'updated') {
    $msg = '✅ Membership updated successfully.';
    // Re-fetch after update
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

/* ── Orders ── */
$orderRows = [];
$ordersStmt = $db->prepare("
    SELECT o.*,
           (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) AS item_count
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.order_date DESC
");
if ($ordersStmt) {
    $ordersStmt->bind_param('i', $uid);
    $ordersStmt->execute();
    $orderRows = $ordersStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $ordersStmt->close();
}

/* ── Order stats ── */
$totalSpent      = array_sum(array_column($orderRows, 'total_amount'));
$totalOrders     = count($orderRows);
$pendingOrders   = count(array_filter($orderRows, fn($o) => $o['order_status'] === 'pending'));
$completedOrders = count(array_filter($orderRows, fn($o) => $o['order_status'] === 'completed'));

/* ── Recent items (last order) ── */
$recentItems = [];
if (!empty($orderRows)) {
    $lastOrderId = $orderRows[0]['order_id'];
    $itemStmt = $db->prepare("
        SELECT oi.*, p.product_name, p.product_type
        FROM order_items oi
        JOIN products p ON p.product_id = oi.product_id
        WHERE oi.order_id = ?
    ");
    if ($itemStmt) {
        $itemStmt->bind_param('i', $lastOrderId);
        $itemStmt->execute();
        $recentItems = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $itemStmt->close();
    }
}

$statusBadge = [
    'pending'    => 'badge-amber',
    'processing' => 'badge-blue',
    'completed'  => 'badge-green',
    'cancelled'  => 'badge-gray',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>View User — <?= htmlspecialchars($u['name']) ?></title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/admin.css">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
.profile-header {
  background: linear-gradient(135deg, #1a2a4a 0%, #243660 100%);
  border-radius: 16px;
  padding: 28px 28px 24px;
  display: flex;
  align-items: center;
  gap: 22px;
  margin-bottom: 24px;
  color: #fff;
}
.profile-avatar {
  width: 72px; height: 72px;
  border-radius: 50%;
  background: rgba(255,255,255,.15);
  display: flex; align-items: center; justify-content: center;
  font-size: 2rem; flex-shrink: 0;
  border: 3px solid rgba(255,255,255,.25);
}
.profile-name   { font-family:'Sora',sans-serif; font-size:1.3rem; font-weight:700; margin-bottom:4px; }
.profile-email  { font-size:.85rem; opacity:.75; margin-bottom:8px; }
.profile-badges { display:flex; gap:8px; flex-wrap:wrap; }
.profile-badge  {
  padding: 3px 10px; border-radius: 20px; font-size:.73rem; font-weight:600;
  background: rgba(255,255,255,.15); color:#fff;
}
.profile-badge.member   { background: rgba(240,165,0,.3); color:#fde68a; }
.profile-badge.admin    { background: rgba(59,130,246,.3); color:#bfdbfe; }
.profile-badge.customer { background: rgba(255,255,255,.12); color:#e2e8f0; }

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}
.info-card {
  background: #fff;
  border-radius: 12px;
  padding: 18px 20px;
  box-shadow: 0 2px 8px rgba(0,0,0,.06);
}
.info-card-label { font-size:.72rem; font-weight:600; color:#94a3b8; text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px; }
.info-card-value { font-size:.95rem; font-weight:600; color:#0b1f3a; }

.section-card {
  background: #fff;
  border-radius: 14px;
  padding: 22px;
  box-shadow: 0 2px 10px rgba(0,0,0,.07);
  margin-bottom: 20px;
}
.section-card h4 {
  font-family:'Sora',sans-serif; font-size:.9rem; font-weight:700;
  color:#0b1f3a; margin-bottom:16px;
  padding-bottom:10px; border-bottom:1px solid #f1f5f9;
}

.order-row { cursor:pointer; }
.order-row:hover td { background:#f8faff; }

.stat-mini-grid {
  display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:24px;
}
.stat-mini {
  background:#fff; border-radius:12px; padding:16px 18px;
  box-shadow:0 2px 8px rgba(0,0,0,.06); text-align:center;
}
.stat-mini-val { font-family:'Sora',sans-serif; font-size:1.4rem; font-weight:700; color:#0b1f3a; }
.stat-mini-lbl { font-size:.73rem; color:#94a3b8; margin-top:2px; }

.back-link {
  display:inline-flex; align-items:center; gap:6px;
  color:#64748b; font-size:.82rem; text-decoration:none;
  margin-bottom:16px; font-weight:500;
}
.back-link:hover { color:#0b1f3a; }
</style>
</head>
<body>
<div class="admin-layout">
  <?php include 'includes/sidebar.php'; ?>

  <div class="admin-content">
    <div class="admin-topbar">
      <span class="admin-topbar-title">👤 User Profile</span>
      <div class="admin-topbar-actions">
        <a href="manage_users.php" class="btn btn-outline btn-sm">← Back to Users</a>
      </div>
    </div>

    <div class="admin-page">

      <?php if ($msg): ?>
        <div class="alert alert-success"><?= $msg ?></div>
      <?php endif; ?>

      <a href="manage_users.php" class="back-link">← Back to Manage Users</a>

      <!-- Profile Header -->
      <div class="profile-header">
        <?php $hasPhoto = !empty($u['profile_photo']) && file_exists('../' . $u['profile_photo']); ?>
        <div class="profile-avatar" style="<?= $hasPhoto ? 'padding:0;overflow:hidden;' : '' ?>">
          <?php if ($hasPhoto): ?>
            <img src="/Marguax_Collection/<?= htmlspecialchars($u['profile_photo']) ?>"
                 alt="<?= htmlspecialchars($u['name']) ?>"
                 style="width:100%;height:100%;object-fit:cover;display:block;border-radius:50%;">
          <?php else: ?>
            <?= mb_strtoupper(mb_substr($u['name'], 0, 1)) ?>
          <?php endif; ?>
        </div>
        <div style="flex:1;">
          <div class="profile-name"><?= htmlspecialchars($u['name']) ?></div>
          <div class="profile-email"><?= htmlspecialchars($u['email']) ?></div>
          <div class="profile-badges">
            <span class="profile-badge <?= $u['role'] ?>"><?= $u['role'] === 'admin' ? '⚙️ Admin' : '👤 Customer' ?></span>
            <span class="profile-badge <?= $u['member_status'] === 'member' ? 'member' : '' ?>">
              <?= $u['member_status'] === 'member' ? '⭐ Member' : 'Non-Member' ?>
            </span>
            <span class="profile-badge">📅 Joined <?= date('M d, Y', strtotime($u['created_at'])) ?></span>
          </div>
        </div>
        <div>
          <button class="btn btn-outline btn-sm" onclick="document.getElementById('editModal').classList.add('open')"
            style="border-color:rgba(255,255,255,.4);color:#fff;background:rgba(255,255,255,.1);">
            ✏️ Edit Membership
          </button>
        </div>
      </div>

      <!-- Order Stats -->
      <div class="stat-mini-grid">
        <div class="stat-mini">
          <div class="stat-mini-val">₱<?= number_format($totalSpent, 2) ?></div>
          <div class="stat-mini-lbl">Total Spent</div>
        </div>
        <div class="stat-mini">
          <div class="stat-mini-val"><?= $totalOrders ?></div>
          <div class="stat-mini-lbl">Total Orders</div>
        </div>
        <div class="stat-mini">
          <div class="stat-mini-val"><?= $pendingOrders ?></div>
          <div class="stat-mini-lbl">Pending</div>
        </div>
        <div class="stat-mini">
          <div class="stat-mini-val"><?= $completedOrders ?></div>
          <div class="stat-mini-lbl">Completed</div>
        </div>
      </div>

      <!-- Account Info -->
      <div class="info-grid">
        <div class="info-card">
          <div class="info-card-label">Full Name</div>
          <div class="info-card-value"><?= htmlspecialchars($u['name']) ?></div>
        </div>
        <div class="info-card">
          <div class="info-card-label">Email Address</div>
          <div class="info-card-value" style="font-size:.85rem;"><?= htmlspecialchars($u['email']) ?></div>
        </div>
        <div class="info-card">
          <div class="info-card-label">Contact Number</div>
          <div class="info-card-value"><?= htmlspecialchars($u['contact_number'] ?? '—') ?></div>
        </div>
        <div class="info-card">
          <div class="info-card-label">Address</div>
          <div class="info-card-value" style="font-size:.85rem;"><?= htmlspecialchars($u['address'] ?? '—') ?></div>
        </div>
        <div class="info-card">
          <div class="info-card-label">User ID</div>
          <div class="info-card-value">#<?= $u['user_id'] ?></div>
        </div>
        <div class="info-card">
          <div class="info-card-label">Member Since</div>
          <div class="info-card-value"><?= date('F d, Y', strtotime($u['created_at'])) ?></div>
        </div>
      </div>

      <!-- Orders Table -->
      <div class="section-card">
        <h4>📋 Order History (<?= $totalOrders ?>)</h4>
        <?php if (empty($orderRows)): ?>
          <div style="text-align:center;padding:40px;color:#94a3b8;font-size:.88rem;">This user has no orders yet.</div>
        <?php else: ?>
        <div style="overflow-x:auto;">
          <table>
            <thead>
              <tr>
                <th>Order</th>
                <th>Items</th>
                <th>Total</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Method</th>
                <th>Date</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orderRows as $o): ?>
              <tr class="order-row">
                <td>
                  <strong>#<?= $o['order_id'] ?></strong><br>
                  <span style="color:#f59e0b;font-size:.75rem;font-weight:700;">
                    Q<?= str_pad($o['queue_number'], 3, '0', STR_PAD_LEFT) ?>
                  </span>
                </td>
                <td style="text-align:center;"><?= $o['item_count'] ?></td>
                <td><strong>₱<?= number_format($o['total_amount'], 2) ?></strong></td>
                <td>
                  <span class="badge <?= $statusBadge[$o['order_status']] ?? 'badge-gray' ?>">
                    <?= ucfirst($o['order_status']) ?>
                  </span>
                </td>
                <td>
                  <span class="badge <?= $o['payment_status'] === 'paid' ? 'badge-green' : 'badge-amber' ?>">
                    <?= ucfirst($o['payment_status'] ?? 'unpaid') ?>
                  </span>
                </td>
                <td style="font-size:.8rem;color:#64748b;"><?= ucfirst($o['payment_method'] ?? '—') ?></td>
                <td style="font-size:.75rem;color:#94a3b8;">
                  <?= date('M d, Y', strtotime($o['order_date'])) ?><br>
                  <?= date('h:i A', strtotime($o['order_date'])) ?>
                </td>
                <td>
                  <a href="manage_orders.php?order_id=<?= $o['order_id'] ?>"
                     class="btn btn-outline btn-sm" style="font-size:.72rem;">View</a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <?php if (!empty($recentItems)): ?>
      <!-- Last Order Items -->
      <div class="section-card">
        <h4>🛍️ Last Order Items <span style="color:#94a3b8;font-weight:400;">(Order #<?= $orderRows[0]['order_id'] ?>)</span></h4>
        <div style="overflow-x:auto;">
          <table>
            <thead>
              <tr><th>Product</th><th>Type</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr>
            </thead>
            <tbody>
              <?php foreach ($recentItems as $item): ?>
              <tr>
                <td style="font-weight:600;"><?= htmlspecialchars($item['product_name']) ?></td>
                <td style="font-size:.8rem;color:#64748b;"><?= htmlspecialchars($item['product_type'] ?? '—') ?></td>
                <td style="text-align:center;"><?= $item['quantity'] ?></td>
                <td>₱<?= number_format($item['price'], 2) ?></td>
                <td><strong>₱<?= number_format($item['quantity'] * $item['price'], 2) ?></strong></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

    </div><!-- /admin-page -->
  </div><!-- /admin-content -->
</div>

<!-- Edit Membership Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal" style="max-width:420px;">
    <div class="modal-header">
      <h3>✏️ Edit Membership</h3>
      <button class="modal-close" onclick="document.getElementById('editModal').classList.remove('open')">✕</button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="update_membership" value="1">
        <div class="form-group">
          <label class="form-label">Name</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($u['name']) ?>" disabled>
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($u['email']) ?>" disabled>
        </div>
        <div class="form-group">
          <label class="form-label">Membership Status</label>
          <select name="member_status" class="form-control">
            <option value="non-member" <?= $u['member_status'] !== 'member' ? 'selected' : '' ?>>Non-Member</option>
            <option value="member"     <?= $u['member_status'] === 'member'  ? 'selected' : '' ?>>⭐ Member</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline"
          onclick="document.getElementById('editModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">💾 Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById('editModal').addEventListener('click', function(e) {
  if (e.target === this) this.classList.remove('open');
});
</script>
</body>
</html>
