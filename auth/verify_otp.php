<?php
/**
 * verify_otp.php — Step 2 of login: verify the emailed OTP
 * Place in: /Marguax_Collection/auth/verify_otp.php
 */
require_once '../includes/security.php';
require_once '../includes/mailer.php';
require_once '../config/database.php';

// Must have a pending OTP login in session
if (empty($_SESSION['otp_pending'])) {
    header('Location: login.php');
    exit;
}

// Already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'admin'
        ? '/Marguax_Collection/admin/dashboard.php'
        : '/Marguax_Collection/customer/products.php'));
    exit;
}

$db      = getDB();
$error   = '';
$pending = $_SESSION['otp_pending'];
$email   = $pending['email'];

// Guard: if pending data is older than 10 minutes, kill it
if (time() - $pending['issued_at'] > 600) {
    unset($_SESSION['otp_pending']);
    header('Location: login.php?reason=otp_expired');
    exit;
}

// ── Resend OTP ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'resend') {
    csrf_verify();

    if (rate_limit_check('otp_resend', 3, 300)) {
        $error = 'Too many resend requests. Please wait a few minutes.';
    } else {
        rate_limit_increment('otp_resend');
        $otp = generate_otp();
        store_otp($db, $email, 'login', $otp, 300);

        $sent = send_mail(
            $email,
            $pending['name'],
            'Your Marguax Collection sign-in code',
            otp_email_html($otp, 'login', 5)
        );

        if ($sent) {
            $_SESSION['otp_pending']['issued_at'] = time();
            $resent = true;
        } else {
            $error = 'Failed to resend OTP. Please try again.';
        }
    }
}

// ── Verify OTP ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
    csrf_verify();

    if (rate_limit_check('otp_verify', 5, 300)) {
        $error = 'Too many incorrect attempts. Please request a new code.';
    } else {
        $otp = preg_replace('/\D/', '', $_POST['otp'] ?? '');

        if (strlen($otp) !== 6) {
            $error = 'Please enter the 6-digit code sent to your email.';
        } elseif (verify_otp_db($db, $email, 'login', $otp)) {
            rate_limit_clear('otp_verify');
            rate_limit_clear('otp_resend');

            session_regenerate_id(true);
            $_SESSION['user_id']       = $pending['user_id'];
            $_SESSION['name']          = $pending['name'];
            $_SESSION['role']          = $pending['role'];
            $_SESSION['last_activity'] = time();
            unset($_SESSION['otp_pending']);

            header('Location: ' . ($pending['role'] === 'admin'
                ? '/Marguax_Collection/admin/dashboard.php'
                : '/Marguax_Collection/customer/products.php'));
            exit;
        } else {
            rate_limit_increment('otp_verify');
            $error = 'Invalid or expired code. Please try again or request a new one.';
        }
    }
}

// Mask email for display: j***@gmail.com
$emailParts  = explode('@', $email);
$masked      = substr($emailParts[0], 0, 1) . str_repeat('*', max(1, strlen($emailParts[0]) - 1)) . '@' . $emailParts[1];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Verify Code — Marguax Collections</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Jost', sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: linear-gradient(to right, #0e0507 0%, #1a0a0e 25%, #2a0d14 55%, #3d1020 78%, #4a1020 100%);
    color: #f0e6da;
}

/* Radial glow */
body::after {
    content: '';
    position: fixed;
    top: 0; right: 0;
    width: 60%; height: 100%;
    background: radial-gradient(ellipse at 75% 45%, rgba(196,80,100,0.14) 0%, transparent 68%);
    pointer-events: none;
    z-index: 0;
}

/* Decorative circle top-left */
body::before {
    content: '';
    position: fixed;
    top: -120px; left: -120px;
    width: 460px; height: 460px;
    border-radius: 50%;
    background: rgba(196,80,100,0.05);
    pointer-events: none;
    z-index: 0;
}

.card {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 460px;
    background: rgba(255,255,255,0.03);
    border: .5px solid rgba(196,80,100,0.2);
    border-radius: 20px;
    box-shadow: 0 32px 80px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.04);
    padding: 52px 48px;
    text-align: center;
}

/* Brand */
.brand-logo {
    display: flex;
    align-items: center;
    gap: 14px;
    justify-content: center;
    margin-bottom: 40px;
}
.logo-circle {
    width: 48px; height: 48px;
    border-radius: 50%;
    border: 1.5px solid #c45064;
    display: flex; align-items: center; justify-content: center;
    overflow: hidden;
    background: rgba(196,80,100,0.1);
    flex-shrink: 0;
}
.logo-circle img { width: 100%; height: 100%; object-fit: cover; }
.logo-fallback {
    font-family: 'Playfair Display', serif;
    font-size: 20px; color: #c45064;
}
.brand-name {
    font-family: 'Playfair Display', serif;
    font-size: 16px; color: #e8d5c4;
    letter-spacing: .3px; text-align: left; line-height: 1.3;
}
.brand-name span {
    display: block;
    font-family: 'Jost', sans-serif;
    font-size: 8px; letter-spacing: 4px;
    color: #c45064; font-weight: 500;
    margin-top: 4px; text-transform: uppercase;
}

/* Icon badge */
.icon-badge {
    width: 64px; height: 64px;
    border-radius: 50%;
    background: rgba(196,80,100,0.12);
    border: 1px solid rgba(196,80,100,0.3);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 24px;
    font-size: 26px;
}

h1 {
    font-family: 'Playfair Display', serif;
    font-size: 2rem; font-weight: 700;
    color: #f0e6da;
    margin-bottom: 10px;
    line-height: 1.15;
}

.subtitle {
    color: #5a4a42;
    font-size: 14px; font-weight: 300;
    line-height: 1.8;
    margin-bottom: 32px;
}
.subtitle strong { color: #c45064; font-weight: 500; }

/* Alerts */
.alert {
    border-radius: 9px; padding: 12px 16px;
    font-size: .84rem; margin-bottom: 20px;
    text-align: left; line-height: 1.6;
    display: flex; align-items: flex-start; gap: 8px;
}
.alert-error   { background: rgba(196,80,100,0.10); border: .5px solid rgba(196,80,100,0.35); color: #e8a0a8; }
.alert-success { background: rgba(80,160,100,0.08); border: .5px solid rgba(80,160,100,0.25); color: #86c49a; }

/* OTP boxes */
.otp-group {
    display: flex; gap: 10px;
    justify-content: center;
    margin-bottom: 8px;
}
.otp-group input {
    width: 52px; height: 60px;
    text-align: center;
    font-size: 1.5rem; font-weight: 700;
    font-family: 'Playfair Display', serif;
    border: .5px solid rgba(196,80,100,0.25);
    border-radius: 10px;
    background: rgba(255,255,255,0.03);
    color: #f0e6da; outline: none;
    transition: border-color .2s, box-shadow .2s, background .2s;
    caret-color: #c45064;
}
.otp-group input:focus {
    border-color: #c45064;
    background: rgba(196,80,100,0.07);
    box-shadow: 0 0 0 3px rgba(196,80,100,0.12);
}
.otp-group input.filled {
    border-color: rgba(196,80,100,0.5);
    background: rgba(196,80,100,0.08);
}

.timer {
    font-size: 12px; color: #3a2a28;
    margin-bottom: 24px; font-weight: 300;
}
.timer span { color: #c45064; font-weight: 600; }

/* Buttons */
.btn {
    display: flex; align-items: center;
    justify-content: center; gap: 10px;
    width: 100%; padding: 15px;
    background: #c45064; color: #fff;
    border: none; border-radius: 9px;
    font-size: 12px; font-weight: 500;
    font-family: 'Jost', sans-serif;
    letter-spacing: 3px; text-transform: uppercase;
    cursor: pointer; margin-bottom: 12px;
    transition: background .2s, transform .1s;
}
.btn:hover { background: #a83d53; }
.btn:active { transform: scale(.98); }
.btn:disabled {
    background: rgba(196,80,100,0.25);
    color: rgba(255,255,255,0.4);
    cursor: not-allowed;
}

.btn-ghost {
    display: flex; align-items: center;
    justify-content: center;
    width: 100%; padding: 13px;
    background: transparent;
    border: .5px solid rgba(196,80,100,0.3);
    border-radius: 9px;
    font-size: 12px; font-weight: 500;
    font-family: 'Jost', sans-serif;
    letter-spacing: 3px; text-transform: uppercase;
    color: #7a5060; cursor: pointer;
    transition: background .2s, border-color .2s, color .2s;
}
.btn-ghost:hover {
    background: rgba(196,80,100,0.07);
    border-color: rgba(196,80,100,0.5);
    color: #c45064;
}

.divider {
    height: .5px;
    background: rgba(196,80,100,0.12);
    margin: 24px 0;
}

.back {
    font-size: 13px; color: #3a2a28;
    font-weight: 300;
}
.back a {
    color: #c45064; font-weight: 500;
    text-decoration: none;
    transition: color .2s;
}
.back a:hover { color: #e07080; }

@media (max-width: 520px) {
    .card { padding: 40px 24px; }
    .otp-group input { width: 44px; height: 54px; font-size: 1.3rem; }
}
</style>
</head>
<body>
<div class="card">

  <div class="brand-logo">
    <div class="logo-circle">
      <img src="/Marguax_Collection/images/logo.jpg" alt="Logo"
           onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
      <span class="logo-fallback" style="display:none">M</span>
    </div>
    <div class="brand-name">
      Marguax Collections
      <span>+ Fashion Boutique</span>
    </div>
  </div>

  <h1>Check your email</h1>
  <p class="subtitle">
    We sent a 6-digit code to<br>
    <strong><?= e($masked) ?></strong><br>
    Enter it below to sign in.
  </p>

  <?php if (!empty($error)): ?>
    <div class="alert alert-error">⚠️ <?= e($error) ?></div>
  <?php endif; ?>

  <?php if (!empty($resent)): ?>
    <div class="alert alert-success">✅ A new code has been sent to your email.</div>
  <?php endif; ?>

  <!-- OTP Form -->
  <form method="POST" id="otpForm" autocomplete="off">
    <?= csrf_field() ?>
    <input type="hidden" name="otp" id="otpHidden">

    <div class="otp-group" id="otpBoxes">
      <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="otp-digit" data-index="0" autofocus>
      <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="otp-digit" data-index="1">
      <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="otp-digit" data-index="2">
      <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="otp-digit" data-index="3">
      <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="otp-digit" data-index="4">
      <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="otp-digit" data-index="5">
    </div>

    <div class="timer">Code expires in <span id="countdown">5:00</span></div>

    <button type="submit" class="btn" id="verifyBtn" disabled>
      Verify Code
    </button>
  </form>

  <!-- Resend Form -->
  <form method="POST" id="resendForm">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="resend">
    <button type="submit" class="btn-ghost">Resend Code</button>
  </form>

  <div class="divider"></div>
  <p class="back">Wrong account? <a href="login.php">Go back</a></p>

</div>

<script>
(function(){
  var digits    = document.querySelectorAll('.otp-digit');
  var hidden    = document.getElementById('otpHidden');
  var verifyBtn = document.getElementById('verifyBtn');

  function getOtp(){ return Array.from(digits).map(function(d){ return d.value; }).join(''); }

  function updateHidden(){
    var otp = getOtp();
    hidden.value = otp;
    verifyBtn.disabled = otp.length < 6;
    digits.forEach(function(d){
      d.classList.toggle('filled', d.value !== '');
    });
  }

  digits.forEach(function(input, i){
    input.addEventListener('input', function(){
      this.value = this.value.replace(/\D/,'');
      if(this.value && i < digits.length - 1) digits[i+1].focus();
      updateHidden();
    });

    input.addEventListener('keydown', function(e){
      if(e.key === 'Backspace' && !this.value && i > 0){
        digits[i-1].focus();
        digits[i-1].value = '';
        updateHidden();
      }
    });

    input.addEventListener('paste', function(e){
      e.preventDefault();
      var pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'');
      pasted.split('').slice(0,6).forEach(function(ch, j){
        if(digits[j]) digits[j].value = ch;
      });
      var next = Math.min(pasted.length, 5);
      digits[next].focus();
      updateHidden();
    });
  });

  // Countdown timer (5 min)
  var ttl  = <?= ($pending['issued_at'] + 300) - time() ?>;
  var el   = document.getElementById('countdown');

  function renderTime(s){
    var m = Math.floor(s/60), sec = s%60;
    return m + ':' + (sec < 10 ? '0' : '') + sec;
  }

  el.textContent = ttl > 0 ? renderTime(ttl) : '0:00';

  var tick = setInterval(function(){
    ttl--;
    if(ttl <= 0){
      clearInterval(tick);
      el.textContent = '0:00';
      el.style.color = '#e8a0a8';
      verifyBtn.disabled = true;
      return;
    }
    el.textContent = renderTime(ttl);
  }, 1000);
})();
</script>
</body>
</html>
