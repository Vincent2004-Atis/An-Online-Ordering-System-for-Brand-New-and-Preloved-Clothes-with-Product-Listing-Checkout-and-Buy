<?php
/**
 * forgot_password.php — Step 1: enter email to receive reset OTP
 * Place in: /Marguax_Collection/auth/forgot_password.php
 */
require_once '../includes/security.php';
require_once '../includes/mailer.php';
require_once '../config/database.php';

// Already logged in — redirect away
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'admin'
        ? '/Marguax_Collection/admin/dashboard.php'
        : '/Marguax_Collection/customer/products.php'));
    exit;
}

$db    = getDB();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // rate_limit_check disabled temporarily for testing
        // rate_limit_increment('forgot_pw');

        $stmt = $db->prepare("SELECT user_id, name FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user) {
            $otp = generate_otp();
            store_otp($db, $email, 'reset', $otp, 600);

            $sent = send_mail(
                $email,
                $user['name'],
                'Reset your Marguax Collections password',
                otp_email_html($otp, 'reset', 10)
            );

            if (!$sent) {
                $error = 'Failed to send email. Please try again. (Check mail_error.txt for details)';
            } else {
                $_SESSION['reset_pending'] = [
                    'email'     => $email,
                    'name'      => $user['name'],
                    'issued_at' => time(),
                ];
                header('Location: verify_reset_otp.php');
                exit;
            }
        } else {
            // Silently redirect even if email not found (security)
            $_SESSION['reset_pending'] = [
                'email'     => $email,
                'name'      => '',
                'issued_at' => time(),
            ];
            header('Location: verify_reset_otp.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Forgot Password — Marguax Collections</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Jost:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:'Jost',sans-serif;min-height:100vh;display:flex;justify-content:center;align-items:center;padding:20px;
  background:linear-gradient(to bottom right,#0e0507 0%,#1a0a0e 30%,#2a0d14 60%,#3d1020 100%);
  color:#f0e6da;
}
a{text-decoration:none}
.page{display:flex;gap:48px;align-items:center;justify-content:center;width:100%;max-width:900px}
.left{flex:1;text-align:center;display:flex;flex-direction:column;align-items:center;gap:20px}
.left img{width:130px;height:130px;object-fit:contain}
.left h1{font-family:'Playfair Display',serif;font-size:28px;font-weight:700;line-height:1.3}
.left .tagline{color:rgba(240,230,218,.55);font-size:14px}
.feature{background:rgba(196,80,100,.06);border:1px solid rgba(196,80,100,.18);border-radius:12px;padding:14px 18px;width:100%;text-align:left}
.feature strong{display:block;font-size:14px;color:#f0e6da;margin-bottom:3px;font-weight:600}
.feature span{font-size:12px;color:rgba(240,230,218,.5)}
.card{background:rgba(42,13,20,.7);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(196,80,100,.2);border-radius:20px;box-shadow:0 24px 80px rgba(0,0,0,.5);padding:48px 44px;width:100%;max-width:440px;color:#f0e6da}
.logo{display:flex;align-items:center;gap:12px;margin-bottom:32px}
.logo img{width:44px;height:44px;border-radius:50%;border:2px solid rgba(196,80,100,.35);object-fit:cover}
.logo-name{font-family:'Playfair Display',serif;font-weight:700;font-size:.98rem;line-height:1.1}
.logo-sub{font-size:.62rem;color:#c8a96a;font-weight:600;text-transform:uppercase;letter-spacing:.14em}
.icon{width:60px;height:60px;background:rgba(196,80,100,.12);border:2px solid rgba(196,80,100,.35);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:26px}
h2{font-family:'Playfair Display',serif;font-size:1.7rem;font-weight:700;margin-bottom:8px;text-align:center}
.sub{color:rgba(240,230,218,.6);font-size:.9rem;line-height:1.6;margin-bottom:28px;text-align:center}
.alert{border-radius:12px;padding:12px 16px;font-size:.875rem;margin-bottom:20px}
.alert-error{background:rgba(196,80,100,.1);border:1px solid rgba(196,80,100,.4);color:#e8a0a8}
label{display:block;font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:rgba(240,230,218,.7);margin-bottom:8px}
input[type="email"]{width:100%;background:rgba(255,255,255,.04);border:1.5px solid rgba(196,80,100,.25);border-radius:12px;padding:13px 16px;color:#f0e6da;font-size:.95rem;outline:none;font-family:inherit;transition:border-color .2s,box-shadow .2s}
input[type="email"]:focus{border-color:#c45064;box-shadow:0 0 0 3px rgba(196,80,100,.18)}
input[type="email"]::placeholder{color:rgba(240,230,218,.3)}
.btn{display:block;width:100%;padding:14px;background:#c45064;color:#fff;border:none;border-radius:12px;font-size:.85rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;cursor:pointer;margin-top:20px;transition:background .2s,transform .1s;font-family:inherit}
.btn:hover{background:#e8a0a8;color:#2a0d14}
.btn:active{transform:scale(.98)}
.divider{height:1px;background:rgba(196,80,100,.15);margin:24px 0}
.back{text-align:center;font-size:.85rem;color:rgba(240,230,218,.55)}
.back a{color:#c8a96a;font-weight:600}
@media(max-width:700px){.left{display:none}}
</style>
</head>
<body>
<div class="page">

  <div class="left">
    <h1>Marguax Collections</h1>
    <p class="tagline">Curated fashion, delivered with care.</p>
    <div class="feature"><strong>Multiple Payment Options</strong><span>GCash, Bank Transfer, Cash on Delivery</span></div>
    <div class="feature"><strong>Smart Queue System</strong><span>Real-time queue number tracking</span></div>
  </div>

  <div class="card">
    <div class="logo">
      <img src="/Marguax_Collection/images/logo.jpg" alt="Logo">
      <div><div class="logo-name">Marguax Collections</div><div class="logo-sub">Fashion Boutique</div></div>
    </div>

    <div class="icon">🔑</div>
    <h2>Forgot Password</h2>
    <p class="sub">Enter your registered email and we'll send you a 6-digit reset code.</p>

    <?php if ($error): ?>
      <div class="alert alert-error">⚠️ <?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <?= csrf_field() ?>
      <label for="email">Email Address</label>
      <input
        type="email"
        id="email"
        name="email"
        placeholder="you@example.com"
        value="<?= e($_POST['email'] ?? '') ?>"
        required
        autofocus
      >
      <button type="submit" class="btn">Send Reset Code →</button>
    </form>

    <div class="divider"></div>
    <p class="back">Remembered it? <a href="login.php">Back to Login</a></p>
  </div>

</div>
</body>
</html>