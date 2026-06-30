<?php
require_once '../includes/security.php';
require_once '../middleware/auth.php';
requireAdmin();
require_once '../config/database.php';
$db = getDB();

$msg    = '';
$errors = [];

// Save / replace one slot's image
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $slotId = clean($_POST['slot_id'] ?? '', 30);
    $validSlots = ['featured','dresses','tops','preowned','accessories'];

    if (!in_array($slotId, $validSlots, true)) {
        $errors[] = 'Invalid slot.';
    } elseif (empty($_FILES['image']['name'])) {
        $errors[] = 'Please choose a photo to upload.';
    } else {
        $check = validate_image($_FILES['image'], 3);
        if (!$check['ok']) {
            $errors[] = $check['error'];
        } else {
            $uploadDir = '../images/homepage/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename = safe_filename($check['ext']);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                $imagePath = 'images/homepage/' . $filename;
                $s = $db->prepare("UPDATE homepage_slots SET image_path=? WHERE slot_id=?");
                $s->bind_param('ss', $imagePath, $slotId);
                $s->execute();
                $s->close();
                header('Location: manage_homepage.php?msg=updated');
                exit;
            } else {
                $errors[] = 'Image upload failed.';
            }
        }
    }
}

if (isset($_GET['msg'])) $msg = match($_GET['msg']) {
    'updated' => '✅ Photo updated on homepage.',
    default   => ''
};

$slots = $db->query("SELECT * FROM homepage_slots")->fetch_all(MYSQLI_ASSOC);
$slotsById = [];
foreach ($slots as $s) $slotsById[$s['slot_id']] = $s;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Homepage Photos — Marguax Admin</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/admin.css">
<style>
.slots-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:20px;margin-top:16px}
.slot-card{border:1px solid var(--border,rgba(0,0,0,.08));border-radius:12px;overflow:hidden}
.slot-card img{width:100%;height:200px;object-fit:cover;display:block}
.slot-body{padding:14px}
.slot-label{font-weight:700;margin-bottom:10px}
.slot-form input[type=file]{font-size:.78rem}
</style>
</head>
<body>
<div class="admin-layout">
  <?php include 'includes/sidebar.php'; ?>
  <div class="admin-content">
    <div class="admin-topbar">
      <span class="admin-topbar-title">🖼️ Homepage Photos</span>
    </div>
    <div class="admin-page">
      <?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><ul style="margin:0;padding-left:16px;"><?php foreach($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header" style="padding:16px 20px;font-weight:700;">Featured Outfit &amp; Category Photos</div>
        <div style="padding:20px;">
          <div class="slots-grid">
            <?php
            $order = ['featured'=>'🌟 Featured Outfit','dresses'=>'👗 Dresses','tops'=>'👚 Tops & Blouses','preowned'=>'♻️ Pre-Owned','accessories'=>'👜 Accessories'];
            foreach ($order as $id => $title):
              $slot = $slotsById[$id] ?? null;
              if (!$slot) continue;
            ?>
            <div class="slot-card">
              <img src="../<?= e($slot['image_path']) ?>" onerror="this.src='../images/product-placeholder.jpg'">
              <div class="slot-body">
                <div class="slot-label"><?= $title ?></div>
                <form method="POST" enctype="multipart/form-data" class="slot-form">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="slot_id" value="<?= e($slot['slot_id']) ?>">
                  <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" class="form-control" required style="margin-bottom:8px;">
                  <button type="submit" class="btn btn-primary btn-sm" style="width:100%;">⬆️ Change Photo</button>
                </form>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
</html>
