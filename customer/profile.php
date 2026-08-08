<?php
require_once '../includes/security.php';
require_once '../middleware/auth.php';
requireCustomer();
require_once '../config/database.php';
$db     = getDB();
$userId = (int)$_SESSION['user_id'];

$stmt = $db->prepare("SELECT * FROM users WHERE user_id=?");
$stmt->bind_param('i',$userId); $stmt->execute();
$user = $stmt->get_result()->fetch_assoc(); $stmt->close();

$stmt = $db->prepare("SELECT * FROM user_payment_accounts WHERE user_id=? ORDER BY is_default DESC");
$stmt->bind_param('i',$userId); $stmt->execute();
$accounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

$msg=''; $errors=[];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_verify();
    $action = clean($_POST['action']??'',20);

    if ($action==='upload_photo') {
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $file     = $_FILES['profile_photo'];
            $allowed  = ['image/jpeg','image/jpg','image/png','image/webp'];
            $maxSize  = 2 * 1024 * 1024;
            if (!in_array($file['type'], $allowed)) {
                $errors[] = 'Only JPG, PNG, or WEBP images are allowed.';
            } elseif ($file['size'] > $maxSize) {
                $errors[] = 'Image must be under 2MB.';
            } else {
                $uploadDir = '../uploads/profiles/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                if (!empty($user['profile_photo']) && file_exists('../' . $user['profile_photo'])) {
                    unlink('../' . $user['profile_photo']);
                }
                $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'user_' . $userId . '_' . time() . '.' . $ext;
                $destPath = $uploadDir . $filename;
                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    $dbPath = 'uploads/profiles/' . $filename;
                    $s = $db->prepare("UPDATE users SET profile_photo=? WHERE user_id=?");
                    $s->bind_param('si', $dbPath, $userId);
                    $s->execute(); $s->close();
                    $msg = '✅ Profile photo updated!';
                } else {
                    $errors[] = 'Failed to save image. Check folder permissions.';
                }
            }
        } else {
            $errors[] = 'No file selected.';
        }
    } elseif ($action==='remove_photo') {
        if (!empty($user['profile_photo']) && file_exists('../' . $user['profile_photo'])) {
            unlink('../' . $user['profile_photo']);
        }
        $s = $db->prepare("UPDATE users SET profile_photo=NULL WHERE user_id=?");
        $s->bind_param('i', $userId); $s->execute(); $s->close();
        $msg = '✅ Profile photo removed.';
    } elseif ($action==='update_profile') {
        $name    = clean($_POST['name']??'',150);
        $email   = clean($_POST['email']??'',150);
        $contact = clean($_POST['contact_number']??'',20);
        $address = clean($_POST['address']??'',500);
        if (empty($name))  $errors[]='Name required.';
        if (!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors[]='Valid email required.';
        if (empty($errors)) {
            $s=$db->prepare("UPDATE users SET name=?,email=?,contact_number=?,address=? WHERE user_id=?");
            $s->bind_param('ssssi',$name,$email,$contact,$address,$userId);
            if ($s->execute()) { $_SESSION['name']=$name; $msg='✅ Profile updated!'; }
            $s->close();
        }
    } elseif ($action==='change_password') {
        $cur  = $_POST['current_password']??'';
        $new  = $_POST['new_password']??'';
        $conf = $_POST['confirm_password']??'';
        if (!password_verify($cur,$user['password'])) $errors[]='Current password incorrect.';
        if (strlen($new)<6) $errors[]='New password must be at least 6 characters.';
        if ($new!==$conf) $errors[]='Passwords do not match.';
        if (empty($errors)) {
            $hash=password_hash($new,PASSWORD_DEFAULT);
            $s=$db->prepare("UPDATE users SET password=? WHERE user_id=?");
            $s->bind_param('si',$hash,$userId); $s->execute(); $s->close();
            $msg='✅ Password changed!';
        }
    } elseif ($action==='add_account') {
        $type   = clean($_POST['account_type']??'',20);
        $aname  = clean($_POST['account_name']??'',150);
        $anum   = clean($_POST['account_number']??'',50);
        $def    = isset($_POST['is_default'])?1:0;
        if ($type === 'gcash' && !empty($aname) && !empty($anum)) {
            if ($def) $db->query("UPDATE user_payment_accounts SET is_default=0 WHERE user_id=$userId");
            $s=$db->prepare("INSERT INTO user_payment_accounts (user_id,account_type,account_name,account_number,is_default) VALUES (?,?,?,?,?)");
            $s->bind_param('isssi',$userId,$type,$aname,$anum,$def);
            $s->execute(); $s->close();
            $msg='✅ Payment account added!';
        }
    } elseif ($action==='delete_account') {
        $aid=(int)($_POST['account_id']??0);
        $s=$db->prepare("DELETE FROM user_payment_accounts WHERE account_id=? AND user_id=?");
        $s->bind_param('ii',$aid,$userId); $s->execute(); $s->close();
        $msg='✅ Account removed.';
    }

    if (empty($errors)) {
        header("Location: profile.php?msg=".urlencode($msg)); exit;
    }
}
if (isset($_GET['msg'])) $msg = urldecode($_GET['msg']);

$stmt=$db->prepare("SELECT * FROM users WHERE user_id=?");
$stmt->bind_param('i',$userId); $stmt->execute();
$user=$stmt->get_result()->fetch_assoc(); $stmt->close();

$initials = strtoupper(substr($user['name'] ?? 'U', 0, 1));
$hasPhoto  = !empty($user['profile_photo']) && file_exists('../' . $user['profile_photo']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Profile — Margaux Collections</title>
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
  display: inline-block !important;
  font-size: .65rem !important; font-weight: 600 !important;
  letter-spacing: .28em !important; text-transform: uppercase !important;
  color: #c45064 !important; padding: 5px 20px !important;
  border: 1px solid rgba(196,80,100,.35) !important; border-radius: 40px !important;
  margin-bottom: 18px !important; background: rgba(196,80,100,.07) !important;
  position: relative !important;
  -webkit-text-fill-color: #c45064 !important;
}
.page-hero h1 {
  font-family: 'Playfair Display', serif !important;
  font-size: clamp(2rem, 4vw, 3rem) !important;
  font-weight: 400 !important; color: #f0e6da !important;
  line-height: 1.1 !important; margin: 8px 0 6px !important;
  position: relative !important;
  background: transparent !important;
  -webkit-text-fill-color: #f0e6da !important;
}
.page-hero p {
  color: #7a6058 !important; font-size: .9rem !important;
  font-weight: 300 !important; position: relative !important;
  background: transparent !important;
  -webkit-text-fill-color: #7a6058 !important;
  margin: 0 !important;
}
.hero-divider {
  width: 48px !important; height: 2px !important;
  background: #c45064 !important;
  margin: 16px auto 0 !important; opacity: .65 !important;
  border: none !important;
}

/* ── AVATAR ── */
.avatar-wrap {
  position: relative !important;
  display: inline-block !important;
  margin-bottom: 18px !important;
}
.avatar-ring {
  width: 108px !important; height: 108px !important;
  border-radius: 50% !important; cursor: pointer !important;
  position: relative !important; overflow: hidden !important;
  border: 2px solid rgba(196,80,100,.4) !important;
  box-shadow: 0 6px 28px rgba(0,0,0,.5), 0 0 0 4px rgba(196,80,100,.08) !important;
  transition: transform .2s, box-shadow .2s !important;
  display: block !important;
}
.avatar-ring:hover {
  transform: scale(1.05) !important;
  box-shadow: 0 8px 32px rgba(196,80,100,.35), 0 0 0 4px rgba(196,80,100,.15) !important;
}
.avatar-overlay {
  position: absolute !important; inset: 0 !important;
  background: rgba(0,0,0,.52) !important;
  display: flex !important; align-items: center !important; justify-content: center !important;
  opacity: 0 !important; transition: opacity .2s !important; font-size: 1.5rem !important;
}
.avatar-ring:hover .avatar-overlay { opacity: 1 !important; }
.remove-dot {
  position: absolute !important; bottom: 3px !important; right: 3px !important;
  width: 26px !important; height: 26px !important; border-radius: 50% !important;
  background: #c45064 !important; border: 2px solid #1a0609 !important;
  color: #fff !important; font-size: .6rem !important; font-weight: 800 !important;
  display: flex !important; align-items: center !important; justify-content: center !important;
  cursor: pointer !important; transition: background .2s !important; z-index: 10 !important;
  line-height: 1 !important;
}
.remove-dot:hover { background: #a83d53 !important; }

/* ── PAGE WRAP ── */
.page-wrap {
  max-width: 820px !important;
  margin: 0 auto !important;
  padding: 36px 24px 80px !important;
  background: transparent !important;
}

/* ── CARD ── */
.mg-card {
  background: #2e0c18 !important;
  border: 1px solid rgba(196,80,100,.22) !important;
  border-radius: 14px !important;
  overflow: hidden !important;
  box-shadow: none !important;
  margin-bottom: 20px !important;
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
  font-size: 1.05rem !important; font-weight: 400 !important;
  color: #f0e6da !important;
  background: transparent !important;
  -webkit-text-fill-color: #f0e6da !important;
}
.mg-pill {
  background: rgba(196,80,100,.15) !important;
  color: #c45064 !important;
  border: 1px solid rgba(196,80,100,.3) !important;
  border-radius: 20px !important; padding: 4px 14px !important;
  font-size: .65rem !important; font-weight: 600 !important;
  letter-spacing: .1em !important;
  -webkit-text-fill-color: #c45064 !important;
}
.mg-card-body {
  padding: 22px !important;
  background: transparent !important;
}

/* ── ALERTS ── */
.alert {
  padding: 13px 16px !important; border-radius: 10px !important;
  margin-bottom: 18px !important; font-size: .875rem !important;
  background: transparent !important;
}
.alert-success {
  background: rgba(100,196,130,.08) !important;
  border: 1px solid rgba(100,196,130,.25) !important;
  color: #6dbf8a !important;
  -webkit-text-fill-color: #6dbf8a !important;
}
.alert-error {
  background: rgba(196,80,100,.08) !important;
  border: 1px solid rgba(196,80,100,.25) !important;
  color: #e8a0a8 !important;
  -webkit-text-fill-color: #e8a0a8 !important;
}

/* ── FORMS ── */
.form-group { margin-bottom: 18px !important; }
.form-label {
  display: block !important; font-size: .65rem !important; font-weight: 600 !important;
  color: #7a6058 !important; margin-bottom: 7px !important;
  text-transform: uppercase !important; letter-spacing: .1em !important;
  -webkit-text-fill-color: #7a6058 !important;
}
.form-control {
  width: 100% !important; padding: 11px 14px !important;
  background: rgba(255,255,255,.04) !important;
  border: 1px solid rgba(196,80,100,.18) !important;
  border-radius: 10px !important; font-size: .9rem !important;
  color: #f0e6da !important; outline: none !important;
  transition: border-color .2s, box-shadow .2s !important;
  font-family: 'Jost', sans-serif !important;
  -webkit-text-fill-color: #f0e6da !important;
}
.form-control::placeholder { color: #3a2820 !important; }
.form-control:focus {
  border-color: #c45064 !important;
  box-shadow: 0 0 0 3px rgba(196,80,100,.12) !important;
}
.form-control:disabled { opacity: .7 !important; cursor: not-allowed !important; }
select.form-control option { background: #1a0609 !important; color: #f0e6da !important; }
.form-row { display: flex !important; gap: 14px !important; flex-wrap: wrap !important; }
.form-row .form-group { flex: 1 !important; min-width: 200px !important; }

/* ── PAYMENT ACCOUNTS ── */
.acc-item {
  display: flex !important; align-items: center !important; gap: 14px !important;
  padding: 14px 16px !important;
  background: rgba(14,5,7,.4) !important;
  border: 1px solid rgba(196,80,100,.12) !important;
  border-radius: 12px !important; margin-bottom: 10px !important;
  transition: border-color .2s !important;
}
.acc-item:hover { border-color: rgba(196,80,100,.28) !important; }
.acc-icon { font-size: 1.4rem !important; width: 40px !important; text-align: center !important; flex-shrink: 0 !important; }
.acc-name {
  font-weight: 500 !important; color: #f0e6da !important;
  font-size: .9rem !important; margin-bottom: 3px !important;
  -webkit-text-fill-color: #f0e6da !important;
}
.acc-detail {
  font-size: .78rem !important; color: #5a4a42 !important;
  -webkit-text-fill-color: #5a4a42 !important;
}
.badge-default {
  background: rgba(196,80,100,.12) !important; color: #c45064 !important;
  border: 1px solid rgba(196,80,100,.3) !important;
  padding: 3px 12px !important; border-radius: 20px !important;
  font-size: .65rem !important; font-weight: 600 !important;
  letter-spacing: .08em !important;
  -webkit-text-fill-color: #c45064 !important;
}
.hr-divider {
  border: none !important;
  border-top: 1px solid rgba(196,80,100,.12) !important;
  margin: 22px 0 !important;
}

/* ── BUTTONS ── */
.btn-primary {
  display: inline-flex !important; align-items: center !important;
  justify-content: center !important; gap: 6px !important;
  background: #c45064 !important; color: #fff !important;
  border: none !important; border-radius: 10px !important;
  padding: 12px 28px !important;
  font-family: 'Jost', sans-serif !important;
  font-size: .7rem !important; font-weight: 600 !important;
  letter-spacing: .16em !important; text-transform: uppercase !important;
  cursor: pointer !important; text-decoration: none !important;
  transition: background .25s, transform .2s, box-shadow .25s !important;
  box-shadow: 0 4px 18px rgba(196,80,100,.3) !important;
  -webkit-text-fill-color: #fff !important;
}
.btn-primary:hover {
  background: #a83d53 !important;
  transform: translateY(-2px) !important;
  box-shadow: 0 10px 24px rgba(196,80,100,.35) !important;
}
.btn-danger {
  background: transparent !important; color: #7a6058 !important;
  border: 1px solid rgba(196,80,100,.15) !important;
  border-radius: 8px !important; padding: 7px 16px !important;
  font-family: 'Jost', sans-serif !important; font-size: .68rem !important;
  font-weight: 500 !important; letter-spacing: .1em !important;
  text-transform: uppercase !important; cursor: pointer !important;
  transition: all .2s !important;
  -webkit-text-fill-color: #7a6058 !important;
}
.btn-danger:hover {
  background: rgba(196,80,100,.1) !important;
  color: #e8a0a8 !important;
  border-color: rgba(196,80,100,.3) !important;
  -webkit-text-fill-color: #e8a0a8 !important;
}

/* ── SECTION LABEL ── */
.section-label {
  font-size: .72rem !important; letter-spacing: .16em !important;
  text-transform: uppercase !important; color: #5a4048 !important;
  margin-bottom: 18px !important; display: block !important;
  -webkit-text-fill-color: #5a4048 !important;
}

@media(max-width: 600px) {
  .form-row { flex-direction: column !important; gap: 0 !important; }
}
</style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<!-- HERO -->
<div class="page-hero">
  <div class="hero-eyebrow">Margaux Collections</div>

  <!-- AVATAR — centered -->
  <div style="display:flex;justify-content:center;margin-bottom:14px;position:relative;z-index:1;">
    <div class="avatar-wrap">
      <form method="POST" enctype="multipart/form-data" id="photoForm">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="upload_photo">
        <input type="file" name="profile_photo" id="photoFileInput"
               accept="image/jpeg,image/png,image/webp"
               onchange="document.getElementById('photoForm').submit()" style="display:none;">
        <div class="avatar-ring" onclick="document.getElementById('photoFileInput').click()" title="Click to change photo">
          <?php if ($hasPhoto): ?>
            <img src="/Margaux_Collections/<?= htmlspecialchars($user['profile_photo']) ?>"
                 alt="Profile" style="width:100%;height:100%;object-fit:cover;display:block;">
          <?php else: ?>
            <div style="width:100%;height:100%;
                        background:linear-gradient(135deg,#3d1020,#c45064);
                        display:flex;align-items:center;justify-content:center;
                        font-family:'Playfair Display',serif;font-size:2.6rem;font-weight:400;color:#f0e6da;">
              <?= $initials ?>
            </div>
          <?php endif; ?>
          <div class="avatar-overlay">📷</div>
        </div>
      </form>

      <?php if ($hasPhoto): ?>
      <form method="POST" style="margin:0;" onsubmit="return confirm('Remove your profile photo?')">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="remove_photo">
        <button type="submit" class="remove-dot" title="Remove photo">✕</button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <h1><?= e($user['name']) ?></h1>
  <p>Manage your account and preferences</p>
  <div class="hero-divider"></div>
</div>

<!-- CONTENT -->
<div class="page-wrap">

  <?php if ($msg): ?>
    <div class="alert alert-success"><?= e($msg) ?></div>
  <?php endif; ?>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
      <ul style="margin:0;padding-left:16px">
        <?php foreach($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <!-- PERSONAL INFO -->
  <div class="mg-card">
    <div class="mg-card-head">
      <span class="mg-card-head-title">Personal Information</span>
    </div>
    <div class="mg-card-body">
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_profile">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required maxlength="150">
          </div>
          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required maxlength="150">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Contact Number</label>
            <input type="text" name="contact_number" class="form-control" value="<?= e($user['contact_number']??'') ?>" maxlength="20" placeholder="09XXXXXXXXX">
          </div>
          <div class="form-group">
            <label class="form-label">Address</label>
            <input type="text" name="address" class="form-control" value="<?= e($user['address']??'') ?>" maxlength="500" placeholder="Your address">
          </div>
        </div>
        <button type="submit" class="btn-primary">Save Changes</button>
      </form>
    </div>
  </div>

  <!-- CHANGE PASSWORD -->
  <div class="mg-card">
    <div class="mg-card-head">
      <span class="mg-card-head-title">Change Password</span>
    </div>
    <div class="mg-card-body">
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="change_password">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Current Password</label>
            <input type="password" name="current_password" class="form-control" required maxlength="255" placeholder="••••••••">
          </div>
          <div class="form-group">
            <label class="form-label">New Password</label>
            <input type="password" name="new_password" class="form-control" required minlength="6" maxlength="255" placeholder="Min. 6 characters">
          </div>
          <div class="form-group">
            <label class="form-label">Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" required maxlength="255" placeholder="Repeat new password">
          </div>
        </div>
        <button type="submit" class="btn-primary">Update Password</button>
      </form>
    </div>
  </div>

  <!-- PAYMENT ACCOUNTS -->
  <div class="mg-card">
    <div class="mg-card-head">
      <span class="mg-card-head-title">Payment Accounts</span>
      <?php if (!empty($accounts)): ?>
        <span class="mg-pill"><?= count($accounts) ?> saved</span>
      <?php endif; ?>
    </div>
    <div class="mg-card-body">
      <?php if (empty($accounts)): ?>
        <p style="color:#5a4a42;font-size:.875rem;font-weight:300;margin-bottom:22px;-webkit-text-fill-color:#5a4a42;">No payment accounts saved yet.</p>
      <?php else: ?>
        <?php foreach ($accounts as $acc): ?>
        <div class="acc-item">
          <span class="acc-icon">📱</span>
          <div style="flex:1">
            <div class="acc-name"><?= e($acc['account_name']) ?></div>
            <div class="acc-detail"><?= e($acc['account_number']) ?></div>
          </div>
          <?php if($acc['is_default']): ?><span class="badge-default">Default</span><?php endif; ?>
          <form method="POST" style="margin:0">
            <input type="hidden" name="action" value="delete_account">
            <input type="hidden" name="account_id" value="<?= (int)$acc['account_id'] ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn-danger" onclick="return confirm('Remove this account?')">Remove</button>
          </form>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <hr class="hr-divider">
      <span class="section-label">Add Payment Account</span>
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_account">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Type</label>
            <input type="hidden" name="account_type" value="gcash">
            <input type="text" class="form-control" value="📱 GCash" disabled>
          </div>
          <div class="form-group">
            <label class="form-label">Account Name</label>
            <input type="text" name="account_name" class="form-control" placeholder="Juan dela Cruz" maxlength="150">
          </div>
          <div class="form-group">
            <label class="form-label">Account Number</label>
            <input type="text" name="account_number" class="form-control" placeholder="09XXXXXXXXX" maxlength="50">
          </div>
        </div>
        <label style="display:flex;align-items:center;gap:10px;margin-bottom:18px;font-size:.85rem;color:#7a6058;cursor:pointer;-webkit-text-fill-color:#7a6058;">
          <input type="checkbox" name="is_default" style="accent-color:#c45064"> Set as default
        </label>
        <button type="submit" class="btn-primary">Add Account</button>
      </form>
    </div>
  </div>

</div>
</body>
</html>