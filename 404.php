<?php
require_once 'includes/security.php';
http_response_code(404);
$home = isset($_SESSION['user_id']) ? ($_SESSION['role']==='admin' ? '/Marguax_Collection/admin/dashboard.php' : '/Marguax_Collection/customer/products.php') : '/Marguax_Collection/auth/login.php';
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>404 — Marguax_Collection</title>
<style>*{box-sizing:border-box;margin:0;padding:0}body{font-family:'Plus Jakarta Sans',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#2a0d14,#1a4070);color:#fff;padding:24px}.wrap{text-align:center;max-width:500px}.logo{width:90px;height:90px;border-radius:50%;border:3px solid rgba(255,255,255,.2);object-fit:cover;margin:0 auto 28px;display:block}.code{font-size:7rem;font-weight:800;line-height:1;background:linear-gradient(135deg,#c8a96a,#f97316);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}h1{font-size:1.6rem;font-weight:700;margin:12px 0 8px}p{color:rgba(255,255,255,.65);font-size:.95rem;line-height:1.7;margin-bottom:32px}.btn{display:inline-block;padding:13px 32px;background:#c45064;color:#fff;border-radius:12px;font-weight:700;text-decoration:none;margin:0 6px;transition:all .2s}.btn:hover{background:#1d4ed8;transform:translateY(-2px)}.btn.outline{background:transparent;border:2px solid rgba(255,255,255,.3)}.btn.outline:hover{background:rgba(255,255,255,.1)}</style></head>
<body><div class="wrap">
  <img src="/Marguax_Collection/images/logo.jpg" class="logo" alt="Logo" onerror="this.style.display='none'">
  <div class="code">404</div>
  <h1>Page Not Found</h1>
  <p>The page you're looking for doesn't exist or has been moved.</p>
  <a href="<?= $home ?>" class="btn">Go Home</a>
  <a href="javascript:history.back()" class="btn outline">Go Back</a>
</div></body></html>
