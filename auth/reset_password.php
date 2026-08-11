<?php
/**
 * reset_password.php — Step 3: set a new password
 * Place in: /Margaux_Collections/auth/reset_password.php
 */
require_once '../includes/security.php';
require_once '../config/database.php';

// Must have completed OTP verification
if (empty($_SESSION['reset_verified'])) {
    header('Location: forgot_password.php');
    exit;
}

// Safety net: grant expires after 15 minutes
if (time() - $_SESSION['reset_verified']['granted_at'] > 900) {
    unset($_SESSION['reset_verified']);
    header('Location: forgot_password.php?reason=expired');
    exit;
}

// Already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'admin'
        ? '/Margaux_Collections/admin/dashboard.php'
        : '/Margaux_Collections/customer/products.php'));
    exit;
}

$db      = getDB();
$email   = $_SESSION['reset_verified']['email'];
$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $password = $_POST['password']         ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'Password must contain at least one number.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed, $email);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected > 0) {
            unset($_SESSION['reset_verified']);
            $success = true;
        } else {
            $error = 'Something went wrong. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reset Password — Margaux Collections</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Plus Jakarta Sans','Sora',sans-serif;min-height:100vh;display:flex;justify-content:center;align-items:center;padding:20px;background:linear-gradient(135deg,#2a0d14 0%,#112d52 50%,#1a4070 100%);color:#fff}
a{text-decoration:none;color:#c45064;font-weight:600}
.card{background:rgba(255,255,255,.10);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.2);border-radius:20px;box-shadow:0 24px 80px rgba(0,0,0,.4);padding:56px 52px;width:100%;max-width:480px;color:#fff}
.logo{display:flex;align-items:center;gap:12px;margin-bottom:36px;justify-content:center}
.logo-img{width:48px;height:48px;border-radius:50%;border:2px solid rgba(255,255,255,.3);object-fit:cover}
.logo-name{font-weight:800;font-size:1rem;color:#fff;line-height:1.1}
.logo-sub{font-size:.65rem;color:#c8a96a;font-weight:600;text-transform:uppercase;letter-spacing:.08em}
.icon{width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;font-size:28px}
.icon-lock{background:rgba(37,99,235,.2);border:2px solid rgba(37,99,235,.4)}
.icon-ok{background:rgba(74,222,128,.15);border:2px solid rgba(74,222,128,.4)}
h1{font-size:1.8rem;font-weight:800;margin-bottom:8px;text-align:center}
.sub{color:rgba(255,255,255,.65);font-size:.9rem;line-height:1.6;margin-bottom:32px;text-align:center}

.alert{border-radius:12px;padding:12px 16px;font-size:.875rem;margin-bottom:20px}
.alert-error{background:rgba(255,241,242,.1);border:1px solid rgba(252,165,165,.4);color:#fca5a5}

.form-group{margin-bottom:20px}
label{display:block;font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:rgba(255,255,255,.75);margin-bottom:8px}

.input-wrap{position:relative}
.input-wrap input{width:100%;background:rgba(255,255,255,.1);border:1.5px solid rgba(255,255,255,.2);border-radius:12px;padding:13px 48px 13px 16px;color:#fff;font-size:.95rem;outline:none;font-family:inherit;transition:border-color .2s,box-shadow .2s}
.input-wrap input:focus{border-color:#c45064;box-shadow:0 0 0 3px rgba(37,99,235,.2)}
.input-wrap input::placeholder{color:rgba(255,255,255,.3)}
.toggle-pw{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:rgba(255,255,255,.4);cursor:pointer;padding:4px;display:flex;align-items:center;justify-content:center;transition:color .2s}
.toggle-pw:hover{color:#fff}

/* Strength bar */
.strength-bar{display:flex;gap:4px;margin-top:8px}
.seg{flex:1;height:3px;border-radius:2px;background:rgba(255,255,255,.12);transition:background .3s}
.strength-label{font-size:.72rem;color:rgba(255,255,255,.4);margin-top:5px;min-height:14px}

.btn{display:block;width:100%;padding:14px;background:#c45064;color:#fff;border:none;border-radius:12px;font-size:1rem;font-weight:700;cursor:pointer;margin-top:8px;transition:background .2s,transform .1s;font-family:inherit}
.btn:hover{background:#1d4ed8}
.btn:active{transform:scale(.98)}

.divider{height:1px;background:rgba(255,255,255,.15);margin:24px 0}
.back{text-align:center;font-size:.85rem;color:rgba(255,255,255,.55)}
.back a{color:rgba(255,255,255,.7);font-weight:600}

@media (max-width: 520px) {
  .card { padding: 40px 24px; }
}
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <img class="logo-img" src="/Margaux_Collections/images/logo.jpg" alt="Logo">
    <div><div class="logo-name">Margaux Collections</div><div class="logo-sub"></div></div>
  </div>

  <?php if ($success): ?>
    <div class="icon icon-ok">✅</div>
    <h1>Password Updated!</h1>
    <p class="sub">Your password has been changed successfully.<br>You can now sign in with your new password.</p>
    <a href="login.php" class="btn" style="text-align:center;display:block">Go to Login →</a>

  <?php else: ?>
    <div class="icon icon-lock"> </div>
    <h1>Set New Password</h1>
    <p class="sub">Create a strong new password for your account.</p>

    <?php if ($error): ?>
      <div class="alert alert-error">⚠️ <?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="password">New Password</label>
        <div class="input-wrap">
          <input type="password" id="password" name="password" placeholder="At least 8 characters" required autofocus>
          <button type="button" class="toggle-pw" onclick="togglePw('password','eyeIcon1','eyeOffIcon1')" aria-label="Toggle password">
            <svg id="eyeIcon1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg id="eyeOffIcon1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
          </button>
        </div>
        <div class="strength-bar">
          <div class="seg" id="s1"></div>
          <div class="seg" id="s2"></div>
          <div class="seg" id="s3"></div>
          <div class="seg" id="s4"></div>
        </div>
        <div class="strength-label" id="slabel"></div>
      </div>

      <div class="form-group">
        <label for="confirm_password">Confirm New Password</label>
        <div class="input-wrap">
          <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password" required>
          <button type="button" class="toggle-pw" onclick="togglePw('confirm_password','eyeIcon2','eyeOffIcon2')" aria-label="Toggle password">
            <svg id="eyeIcon2" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg id="eyeOffIcon2" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
          </button>
        </div>
      </div>

      <button type="submit" class="btn">Update Password →</button>
    </form>

    <div class="divider"></div>
    <p class="back"><a href="login.php">← Back to Login</a></p>
  <?php endif; ?>
</div>

<script>
function togglePw(id, eyeId, eyeOffId) {
  var input = document.getElementById(id);
  var show = input.type === 'password';
  input.type = show ? 'text' : 'password';
  document.getElementById(eyeId).style.display    = show ? 'none' : '';
  document.getElementById(eyeOffId).style.display = show ? '' : 'none';
}

document.getElementById('password').addEventListener('input', function(){
  var pw = this.value;
  var score = 0;
  if(pw.length >= 8)           score++;
  if(/[A-Z]/.test(pw))        score++;
  if(/[0-9]/.test(pw))        score++;
  if(/[^A-Za-z0-9]/.test(pw)) score++;

  var colors = ['','#e24b4a','#ef9f27','#1d9e75','#4ade80'];
  var labels = ['','Weak','Fair','Good','Strong'];

  for(var i = 1; i <= 4; i++){
    document.getElementById('s'+i).style.background = i <= score ? colors[score] : 'rgba(255,255,255,.12)';
  }
  var lbl = document.getElementById('slabel');
  lbl.textContent = pw.length > 0 ? labels[score] + ' password' : '';
  lbl.style.color = pw.length > 0 ? colors[score] : 'rgba(255,255,255,.4)';
});
</script>
</body>
</html>