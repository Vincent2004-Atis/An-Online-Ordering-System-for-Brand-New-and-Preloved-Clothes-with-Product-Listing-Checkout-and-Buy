<?php
require_once '../includes/security.php';
require_once '../middleware/auth.php';
requireAdmin();
require_once '../config/database.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    csrf_verify();
    require_once '../includes/notify_helper.php';   // ← ADD THIS

    $oid    = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    $status = clean($_POST['order_status'] ?? '', 20);
    $pay    = clean($_POST['payment_status'] ?? '', 20);

    if ($oid && in_array($status, ['pending','processing','completed']) && in_array($pay, ['pending','paid'])) {
        $s = $db->prepare("UPDATE orders SET order_status=?, payment_status=? WHERE order_id=?");
        $s->bind_param('ssi', $status, $pay, $oid);
        $s->execute();
        $s->close();

        // ── Notify the customer ──────────────────────────────────────────
        $r = $db->prepare("SELECT user_id FROM orders WHERE order_id=?");
        $r->bind_param('i', $oid);
        $r->execute();
        $row = $r->get_result()->fetch_assoc();
        $r->close();

        if ($row) {
            createNotification($db, (int)$row['user_id'], $oid, $status);
        }
        // ────────────────────────────────────────────────────────────────
    }
    header('Location: manage_orders.php?updated=1');
    exit;
}

$filterStatus  = clean($_GET['status'] ?? '', 20);
$filterPayment = clean($_GET['payment'] ?? '', 20);
$search        = clean($_GET['search'] ?? '', 100);

$where  = ['1=1'];
$params = [];
$types  = '';

if ($filterStatus)  { $where[] = 'o.order_status=?'; $types.='s'; $params[] = $filterStatus; }
if ($filterPayment) { $where[] = 'o.payment_status=?'; $types.='s'; $params[] = $filterPayment; }
if ($search) {
    $where[] = '(u.name LIKE ? OR o.order_id LIKE ? OR o.contact_number LIKE ?)';
    $types  .= 'sss';
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}

$sql = "SELECT o.*, u.name AS uname, u.email AS uemail FROM orders o
        JOIN users u ON u.user_id=o.user_id
        WHERE ".implode(' AND ',$where)."
        ORDER BY o.order_date DESC";
$stmt = $db->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$statusBadge = ['pending'=>'badge-amber','processing'=>'badge-blue','completed'=>'badge-green'];
$payBadge    = ['pending'=>'badge-amber','paid'=>'badge-green'];
$payLabels   = ['cash_on_pickup'=>'💵 Cash Pickup','cash_on_delivery'=>'🏠 Cash Delivery','gcash'=>'📱 GCash','bank_transfer'=>'🏦 Cash On Delivery'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manage Orders — OrderSync Admin</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<div class="admin-layout">
  <?php include 'includes/sidebar.php'; ?>
  <div class="admin-content">
    <div class="admin-topbar">
      <span class="admin-topbar-title">📋 Manage Orders</span>
      <div class="admin-topbar-actions">
        <span class="badge badge-blue"><?= count($orders) ?> result<?= count($orders)!=1?'s':'' ?></span>
      </div>
    </div>
    <div class="admin-page">
      <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">✅ Order updated successfully.</div>
      <?php endif; ?>
      <form method="GET" class="card mb-24">
        <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
          <div class="form-group" style="margin:0;flex:1;min-width:180px;">
            <label class="form-label">Search</label>
            <input type="text" name="search" class="form-control" placeholder="Name, order #, phone..." value="<?= e($search) ?>" maxlength="100">
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label">Order Status</label>
            <select name="status" class="form-control">
              <option value="">All Statuses</option>
              <option value="pending"    <?= $filterStatus==='pending'   ?'selected':'' ?>>Pending</option>
              <option value="processing" <?= $filterStatus==='processing'?'selected':'' ?>>Processing</option>
              <option value="completed"  <?= $filterStatus==='completed' ?'selected':'' ?>>Completed</option>
            </select>
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label">Payment Status</label>
            <select name="payment" class="form-control">
              <option value="">All</option>
              <option value="pending" <?= $filterPayment==='pending'?'selected':'' ?>>Pending</option>
              <option value="paid"    <?= $filterPayment==='paid'   ?'selected':'' ?>>Paid</option>
            </select>
          </div>
          <div style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary">🔍 Filter</button>
            <a href="manage_orders.php" class="btn btn-outline">✕ Clear</a>
          </div>
        </div>
      </form>
      <div class="card">
        <div class="table-wrap">
          <table>
            <thead><tr><th>Order</th><th>Customer</th><th>Method</th><th>Payment</th><th>Total</th><th>Order Status</th><th>Pay Status</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
              <?php if (empty($orders)): ?>
              <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-3);">No orders found.</td></tr>
              <?php endif; ?>
              <?php foreach ($orders as $o): ?>
              <tr>
                <td><strong>#<?= (int)$o['order_id'] ?></strong><br><span style="color:var(--amber);font-weight:800;">Q<?= str_pad((int)$o['queue_number'],3,'0',STR_PAD_LEFT) ?></span></td>
                <td><div style="font-weight:600;"><?= e($o['uname']) ?></div><div style="font-size:.75rem;color:var(--text-3);"><?= e($o['contact_number']) ?></div></td>
                <td><?= $o['order_method']==='pickup'?' PICKUP':' SHIPPING' ?></td>
                <td style="font-size:.82rem;"><?= $payLabels[$o['payment_method']]??e($o['payment_method']) ?></td>
                <td><strong>₱<?= number_format((float)$o['total_amount'],2) ?></strong></td>
                <td><span class="badge <?= $statusBadge[$o['order_status']]??'badge-gray' ?>"><?= ucfirst(e($o['order_status'])) ?></span></td>
                <td><span class="badge <?= $payBadge[$o['payment_status']]??'badge-gray' ?>"><?= ucfirst(e($o['payment_status'])) ?></span></td>
                <td style="font-size:.78rem;"><?= date('M d, Y H:i', strtotime($o['order_date'])) ?></td>
                <td><button class="btn btn-outline btn-sm" onclick="openEdit(<?= htmlspecialchars(json_encode($o), ENT_QUOTES) ?>)">✏️ Edit</button></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-header">
      <h3>✏️ Update Order <span id="modalOrderId"></span></h3>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="update_status" value="1">
        <input type="hidden" name="order_id" id="editOrderId">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="form-group">
          <label class="form-label">Customer</label>
          <input type="text" id="editCustomer" class="form-control" disabled>
        </div>
        <div class="form-group">
          <label class="form-label">Address</label>
          <textarea id="editAddress" class="form-control" rows="2" disabled></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Order Status</label>
            <select name="order_status" id="editOrderStatus" class="form-control">
              <option value="pending">Pending</option>
              <option value="processing">Processing</option>
              <option value="completed">Completed</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Payment Status</label>
            <select name="payment_status" id="editPayStatus" class="form-control">
              <option value="pending">Pending</option>
              <option value="paid">Paid</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">💾 Save Changes</button>
      </div>
    </form>
  </div>
</div>
<script>
function openEdit(order) {
  document.getElementById('modalOrderId').textContent  = '#'+order.order_id;
  document.getElementById('editOrderId').value         = order.order_id;
  document.getElementById('editCustomer').value        = order.customer_name;
  document.getElementById('editAddress').value         = order.address;
  document.getElementById('editOrderStatus').value     = order.order_status;
  document.getElementById('editPayStatus').value       = order.payment_status;
  document.getElementById('editModal').classList.add('open');
}
function closeModal() { document.getElementById('editModal').classList.remove('open'); }
document.getElementById('editModal').addEventListener('click',function(e){ if(e.target===this) closeModal(); });
</script>
</body>
</html>
