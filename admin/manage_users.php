<?php
require_once '../includes/security.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php'); exit;
}
require_once '../config/database.php';
$db = getDB();

$msg    = '';
$errors = [];

/* ── Delete user ── */
if (isset($_GET['delete'])) {
    $uid = (int)$_GET['delete'];
    if ($uid === (int)$_SESSION['user_id']) {
        $msg = '⚠️ You cannot delete your own account.';
    } else {
        $s = $db->prepare("DELETE FROM users WHERE user_id=? AND role='customer'");
        $s->bind_param('i', $uid);
        $s->execute();
        $s->close();
        header('Location: manage_users.php?msg=deleted'); exit;
    }
}

if (isset($_GET['msg'])) $msg = match($_GET['msg']) {
    'deleted' => '✅ User deleted successfully.',
    'updated' => '✅ User updated successfully.',
    default   => ''
};


/* ── Filters ── */
$search    = trim($_GET['search'] ?? '');
$roleFilter = $_GET['role'] ?? '';
$memberFilter = $_GET['member'] ?? '';

$where  = ['1=1'];
$params = [];
$types  = '';

if (!empty($search)) {
    $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.contact_number LIKE ?)';
    $types  .= 'sss';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($roleFilter) {
    $where[] = 'u.role = ?';
    $types  .= 's';
    $params[] = $roleFilter;
}
if ($memberFilter) {
    $where[] = 'u.member_status = ?';
    $types  .= 's';
    $params[] = $memberFilter;
}

$sql = "SELECT u.*,
        (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.user_id) AS order_count
        FROM users u
        WHERE " . implode(' AND ', $where) . "
        ORDER BY u.created_at DESC";

$stmt = $db->prepare($sql);
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* ── Stats ── */
$totalUsers   = (int)$db->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetch_row()[0];
$totalAdmins  = (int)$db->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manage Users — OrderSync Admin</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<div class="admin-layout">
  <?php
require_once '../includes/security.php'; include 'includes/sidebar.php'; ?>

  <div class="admin-content">
    <div class="admin-topbar">
      <span class="admin-topbar-title">👥 Manage Users</span>
      <div class="admin-topbar-actions">
        <span class="badge badge-blue"><?= count($users) ?> result<?= count($users)!=1?'s':'' ?></span>
      </div>
    </div>

    <div class="admin-page">

      <?php
require_once '../includes/security.php'; if ($msg): ?>
        <div class="alert <?= str_starts_with($msg,'✅')?'alert-success':'alert-warning' ?>"><?= $msg ?></div>
      <?php
require_once '../includes/security.php'; endif; ?>

      <!-- Stats -->
      <div class="stats-grid mb-24" style="grid-template-columns:repeat(3,1fr);">
        <div class="stat-card">
          <div class="stat-icon stat-icon-blue">👥</div>
          <div>
            <div class="stat-val"><?= $totalUsers ?></div>
            <div class="stat-lbl">Total Customers</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon stat-icon-amber">⭐</div>
          <div>
            <div class="stat-val"><?= $totalMembers ?></div>
            <div class="stat-lbl">Members</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon stat-icon-purple">⚙️</div>
          <div>
            <div class="stat-val"><?= $totalAdmins ?></div>
            <div class="stat-lbl">Admins</div>
          </div>
        </div>
      </div>

      <!-- Filter bar -->
      <form method="GET" class="card mb-24">
        <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
          <div class="form-group" style="margin:0;flex:1;min-width:180px;">
            <label class="form-label">Search</label>
            <input type="text" name="search" class="form-control"
                   placeholder="Name, email, contact..." value="<?= htmlspecialchars($search) ?>">
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label">Role</label>
            <select name="role" class="form-control">
              <option value="">All Roles</option>
              <option value="customer" <?= $roleFilter==='customer'?'selected':'' ?>>Customer</option>
              <option value="admin"    <?= $roleFilter==='admin'   ?'selected':'' ?>>Admin</option>
            </select>
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label">Membership</label>
            <select name="member" class="form-control">
              <option value="">All</option>
              <option value="member"     <?= $memberFilter==='member'    ?'selected':'' ?>>Member</option>
              <option value="non-member" <?= $memberFilter==='non-member'?'selected':'' ?>>Non-Member</option>
            </select>
          </div>
          <div style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary">🔍 Filter</button>
            <a href="manage_users.php" class="btn btn-outline">✕ Clear</a>
          </div>
        </div>
      </form>

      <!-- Users Table -->
      <div class="card">
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Contact</th>
                <th>Role</th>
                <th>Orders</th>
                <th>Joined</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
require_once '../includes/security.php'; if (empty($users)): ?>
              <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-3);">No users found.</td></tr>
              <?php
require_once '../includes/security.php'; endif; ?>
              <?php
require_once '../includes/security.php'; foreach ($users as $u): ?>
              <tr>
                <td><strong>#<?= $u['user_id'] ?></strong></td>
                <td>
                  <div style="font-weight:600;"><?= htmlspecialchars($u['name']) ?></div>
                </td>
                <td style="font-size:.82rem;">
  <a href="view_user.php?id=<?= $u['user_id'] ?>"
     style="color:var(--blue);text-decoration:none;font-weight:600;"
     onmouseover="this.style.textDecoration='underline'"
     onmouseout="this.style.textDecoration='none'">
    <?= htmlspecialchars($u['email']) ?>
  </a>
</td>
                <td style="font-size:.82rem;"><?= htmlspecialchars($u['contact_number'] ?? '—') ?></td>
                <td>
                  <span class="badge <?= $u['role']==='admin'?'badge-blue':'badge-gray' ?>">
                    <?= $u['role']==='admin'?'⚙️ Admin':'👤 Customer' ?>
                  </span>
                </td>
                <td>
                  <span class="badge <?= $u['member_status']==='member'?'badge-amber':'badge-gray' ?>">
                    <?= $u['member_status']==='member'?' Member':'Non-Member' ?>
                  </span>
                </td>
                <td style="text-align:center;font-weight:600;"><?= $u['order_count'] ?></td>
                <td style="font-size:.78rem;"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                <td>
                  <div class="action-btns">
                    <button class="btn btn-outline btn-sm"
                            onclick="openEdit(<?= htmlspecialchars(json_encode($u)) ?>)">
                      ✏️ Edit
                    </button>
                    <?php
require_once '../includes/security.php'; if ($u['role'] !== 'admin'): ?>
                    <a href="manage_users.php?delete=<?= $u['user_id'] ?>"
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('Delete <?= htmlspecialchars($u['name']) ?>? This cannot be undone.')">
                      🗑️
                    </a>
                    <?php
require_once '../includes/security.php'; endif; ?>
                  </div>
                </td>
              </tr>
              <?php
require_once '../includes/security.php'; endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div><!-- /admin-page -->
  </div><!-- /admin-content -->
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal" style="max-width:480px;">
    <div class="modal-header">
      <h3>✏️ Edit User</h3>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="update_membership" value="1">
        <input type="hidden" name="user_id" id="editUserId">

        <div class="form-group">
          <label class="form-label">Name</label>
          <input type="text" id="editName" class="form-control" disabled>
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="text" id="editEmail" class="form-control" disabled>
        </div>
        <div class="form-group">
          <label class="form-label">Membership Status</label>
          <select name="member_status" id="editMembership" class="form-control">
            <option value="non-member">Non-Member</option>
            <option value="member"> Member</option>
          </select>
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
function openEdit(user) {
  document.getElementById('editUserId').value    = user.user_id;
  document.getElementById('editName').value      = user.name;
  document.getElementById('editEmail').value     = user.email;
  document.getElementById('editMembership').value = user.member_status;
  document.getElementById('editModal').classList.add('open');
}
function closeModal() {
  document.getElementById('editModal').classList.remove('open');
}
document.getElementById('editModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
</script>
</body>
</html>
