<?php
/**
 * register.php — Two-step registration with email OTP verification
 * Step 1: Fill form -> validate -> send OTP
 * Step 2: Enter OTP -> create account
 * Place in: /Marguax_Collection/auth/register.php
 */
require_once '../includes/security.php';
require_once '../includes/mailer.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../customer/products.php');
    exit;
}

require_once '../config/database.php';
$db     = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['step'] ?? '') === '1') {
    csrf_verify();

    if (rate_limit_check('register', 5, 600)) {
        $errors[] = 'Too many registration attempts. Please wait 10 minutes.';
    } else {
        $name    = clean($_POST['name'] ?? '', 150);
        $email   = clean($_POST['email'] ?? '', 150);
        $contact = clean($_POST['contact'] ?? '', 20);
        $pass    = $_POST['password'] ?? '';
        $conf    = $_POST['confirm_password'] ?? '';

        if (empty($name))                               $errors[] = 'Full name is required.';
        if (strlen($name) < 2)                          $errors[] = 'Name must be at least 2 characters.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if (empty($contact))                            $errors[] = 'Contact number is required.';
        if (!valid_phone($contact))                     $errors[] = 'Contact number must be in format 09XXXXXXXXX.';
        if (strlen($pass) < 6)                          $errors[] = 'Password must be at least 6 characters.';
        if (strlen($pass) > 255)                        $errors[] = 'Password is too long.';
        if ($pass !== $conf)                            $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            $stmt = $db->prepare("SELECT user_id FROM users WHERE email=?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) $errors[] = 'Email already registered.';
            $stmt->close();
        }

        if (empty($errors)) {
            rate_limit_increment('register');
            $otp  = generate_otp();
            store_otp($db, $email, 'register', $otp, 600);
            $sent = send_mail(
                $email, $name,
                'Verify your email — Marguax Collections',
                otp_email_html($otp, 'register', 10)
            );
            if ($sent) {
                $_SESSION['reg_pending'] = [
                    'name'      => $name,
                    'email'     => $email,
                    'contact'   => $contact,
                    'pass_hash' => password_hash($pass, PASSWORD_DEFAULT),
                    'issued_at' => time(),
                ];
                header('Location: verify_email.php');
                exit;
            } else {
                $errors[] = 'Failed to send verification email. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Register — Marguax Collections</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{
    font-family:'Jost',sans-serif;
    min-height:100vh;
    display:flex;
    background:linear-gradient(to right,#0e0507 0%,#1a0a0e 25%,#2a0d14 55%,#3d1020 78%,#4a1020 100%);
    color:#f0e6da;
    overflow-x:hidden;
}
a{text-decoration:none}

.auth-wrapper{
    display:flex;
    width:100%;
    min-height:100vh;
    position:relative;
}
.auth-wrapper::after{
    content:'';
    position:fixed;
    top:0;right:0;
    width:60%;height:100%;
    background:radial-gradient(ellipse at 75% 45%,rgba(196,80,100,0.16) 0%,transparent 68%);
    pointer-events:none;
    z-index:0;
}

/* LEFT */
.left{
    width:45%;
    display:flex;
    flex-direction:column;
    justify-content:center;
    padding:60px 56px;
    position:relative;
    z-index:1;
    flex-shrink:0;
}
/* Decorative circle */
.left::before{
    content:'';
    position:absolute;
    top:-110px;left:-110px;
    width:420px;height:420px;
    border-radius:50%;
    background:rgba(196,80,100,0.07);
    pointer-events:none;
}
.left::after{
    content:'';
    position:absolute;
    bottom:-80px;left:60px;
    width:260px;height:260px;
    border-radius:50%;
    background:rgba(196,80,100,0.04);
    pointer-events:none;
}

/* Brand logo */
.brand-logo{display:flex;align-items:center;gap:16px;margin-bottom:64px;}
.logo-img-circle{
    width:52px;height:52px;border-radius:50%;
    border:1.5px solid #c45064;
    display:flex;align-items:center;justify-content:center;
    flex-shrink:0;overflow:hidden;
    background:rgba(196,80,100,0.1);
}
.logo-img-circle img{width:100%;height:100%;object-fit:cover}
.logo-fallback{font-family:'Playfair Display',serif;font-size:20px;color:#c45064}
.brand-name{font-family:'Playfair Display',serif;font-size:16px;color:#e8d5c4;letter-spacing:.5px;line-height:1.3}
.brand-name span{display:block;font-family:'Jost',sans-serif;font-size:9px;letter-spacing:4px;color:#c45064;font-weight:500;margin-top:4px;text-transform:uppercase}

/* Hero text — enlarged */
.hero-text{margin-bottom:0;}
.eyebrow{
    font-family:'Jost',sans-serif;
    font-size:10px;
    letter-spacing:4px;
    color:#c45064;
    text-transform:uppercase;
    font-weight:500;
    margin-bottom:20px;
    display:flex;
    align-items:center;
    gap:10px;
}
.eyebrow::before{
    content:'';
    display:inline-block;
    width:28px;height:1px;
    background:#c45064;
    opacity:.6;
}
.hero-text h2{
    font-family:'Playfair Display',serif;
    font-size:58px;
    color:#f0e6da;
    line-height:1.05;
    font-weight:700;
    margin-bottom:24px;
    letter-spacing:-0.5px;
}
.hero-text h2 em{color:#c45064;font-style:italic}
.hero-text p{
    color:#7a6058;
    font-size:14px;
    font-weight:300;
    line-height:1.9;
    max-width:320px;
    border-left:2px solid rgba(196,80,100,0.3);
    padding-left:16px;
}

/* RIGHT */
.right{
    flex:1;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:50px 60px;
    position:relative;
    z-index:1;
}
.form-card{width:100%;max-width:395px}

.form-header{margin-bottom:28px}
.form-header h1{font-family:'Playfair Display',serif;font-size:27px;color:#f0e6da;font-weight:700;line-height:1.15;margin-bottom:8px}
.form-header p{color:#5a4a42;font-size:13px;font-weight:300;line-height:1.6}

.alert{border-radius:9px;padding:11px 14px;font-size:.83rem;margin-bottom:16px;display:flex;align-items:flex-start;gap:9px;line-height:1.6}
.alert-danger{background:rgba(196,80,100,0.1);border:.5px solid rgba(196,80,100,0.35);color:#e8a0a8}
.alert-danger ul{margin:0;padding-left:15px}

.form-group{margin-bottom:13px}
.form-row{display:flex;gap:11px}
.form-row .form-group{flex:1;min-width:0}

label{display:block;font-size:10px;letter-spacing:1.8px;color:#7a6058;font-weight:500;text-transform:uppercase;margin-bottom:7px}

input[type=text],
input[type=email],
input[type=password]{
    width:100%;padding:12px 15px;
    background:rgba(255,255,255,0.04);
    border:.5px solid rgba(196,80,100,0.22);
    border-radius:8px;font-size:13.5px;
    font-family:'Jost',sans-serif;font-weight:300;
    color:#f0e6da;outline:none;
    transition:border-color .2s,background .2s,box-shadow .2s;
}
input::placeholder{color:#3a2a28}
input:focus{border-color:#c45064;background:rgba(196,80,100,0.06);box-shadow:0 0 0 3px rgba(196,80,100,0.1)}

.btn{
    display:flex;align-items:center;justify-content:center;gap:10px;
    width:100%;padding:14px;
    background:#c45064;color:#fff;border:none;border-radius:8px;
    font-size:11.5px;font-weight:500;font-family:'Jost',sans-serif;
    letter-spacing:2.5px;text-transform:uppercase;cursor:pointer;
    margin-top:6px;transition:background .2s,transform .1s;
}
.btn:hover{background:#a83d53}
.btn:active{transform:scale(.98)}
.btn svg{width:14px;height:14px;stroke:white;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;flex-shrink:0}

.card-divider{height:.5px;background:rgba(196,80,100,0.12);margin:20px 0}
.bottom{text-align:center;font-size:12px;color:#5a4a42;font-weight:300}
.bottom a{color:#c45064;font-weight:500}
.bottom a:hover{color:#e07080}

@media(max-width:900px){
    body{background:linear-gradient(to bottom,#0e0507 0%,#2a0d14 100%)}
    .auth-wrapper{flex-direction:column}
    .auth-wrapper::after{width:100%;height:50%;top:50%}
    .left{width:100%;padding:36px 28px 32px}
    .hero-text h2{font-size:38px}
    .right{padding:28px 24px 52px}
}
@media(max-width:480px){
    .left{padding:28px 20px 24px}
    .right{padding:20px 18px 44px}
    .form-row{flex-direction:column;gap:0}
    .form-header h1{font-size:22px}
    .hero-text h2{font-size:30px}
}
</style>
</head>
<body>
<div class="auth-wrapper">

  <!-- LEFT -->
  <div class="left">
    <div class="brand-logo">
      <div class="logo-img-circle">
        <img src="/Marguax_Collection/images/logo.jpg" alt="Logo"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
        <span class="logo-fallback" style="display:none">M</span>
      </div>
      <div class="brand-name">
        Marguax Collections
        <span>+ Fashion Boutique</span>
      </div>
    </div>

    <div class="hero-text">
      <div class="eyebrow">Fashion Boutique</div>
      <h2>Style D. Dress<br><em>With Me</em></h2>
      <p>Curated brand-new outfits and pre-loved designer pieces.
         Every dress tells a story — find yours.
         Affordable fashion with a luxury feel.</p>
    </div>
  </div>

  <!-- RIGHT -->
  <div class="right">
    <div class="form-card">
      <div class="form-header">
        <h1>Create Account</h1>
        <p>Fill in your details. We&#39;ll verify your email next.</p>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          &#9888;&#65039;
          <ul>
            <?php foreach ($errors as $err): ?>
              <li><?= e($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="step" value="1">

        <div class="form-group">
          <label>Full Name *</label>
          <input type="text" name="name" value="<?= e($_POST['name'] ?? '') ?>"
                 required placeholder="Juan dela Cruz" maxlength="150" autofocus>
        </div>
        <div class="form-group">
          <label>Email Address *</label>
          <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>"
                 required placeholder="your@email.com" maxlength="150">
        </div>
        <div class="form-group">
          <label>Contact Number *</label>
          <input type="text" name="contact" value="<?= e($_POST['contact'] ?? '') ?>"
                 required placeholder="09XXXXXXXXX" maxlength="20">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Password *</label>
            <input type="password" name="password" id="passField"
                   required minlength="6" maxlength="255" placeholder="Min. 6 characters">
          </div>
          <div class="form-group">
            <label>Confirm Password *</label>
            <input type="password" name="confirm_password" id="confField"
                   required maxlength="255" placeholder="Re-enter password">
          </div>
        </div>
        <button type="submit" class="btn">
          Send Verification Code
          <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </button>
      </form>

      <div class="card-divider"></div>
      <p class="bottom">Already have an account? <a href="login.php">Sign in</a></p>
    </div>
  </div>

</div>
</body>
</html>
