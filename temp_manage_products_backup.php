<?php
require_once '../includes/security.php';
require_once '../middleware/auth.php';
requireAdmin();
require_once '../config/database.php';
$db = getDB();

$msg    = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    csrf_verify();
    $pid = filter_input(INPUT_POST, 'delete', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    if ($pid) {
        $s = $db->prepare("DELETE FROM products WHERE product_id=?");
        $s->bind_param('i',$pid); $s->execute(); $s->close();
    }
    header('Location: manage_products.php?msg=deleted'); exit;
}

if (isset($_GET['msg'])) $msg = match($_GET['msg']) {
    'added'   => '✅ Product added.',
    'updated' => '✅ Product updated.',
    'deleted' => '✅ Product deleted.',
    default   => ''
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $pid   = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT) ?: 0;
    $name  = clean($_POST['product_name'] ?? '', 200);
    $desc  = clean($_POST['description']  ?? '', 1000);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $type  = clean($_POST['product_type'] ?? '', 20);
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT, ['options'=>['min_range'=>0]]) ?? 0;

    if (empty($name))             $errors[] = 'Product name required.';
    if (!$price || $price <= 0)   $errors[] = 'Price must be greater than 0.';
    if (!in_array($type,['loose','member','package'])) $errors[] = 'Invalid product type.';

    // Secure file upload using validate_image() from security.php
    $image = clean($_POST['current_image'] ?? 'images/product-placeholder.jpg', 255);
    if (!empty($_FILES['image']['name'])) {
        $check = validate_image($_FILES['image'], 3);
        if (!$check['ok']) {
            $errors[] = $check['error'];
        } else {
            $uploadDir = '../images/products/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename = safe_filename($check['ext']); // random secure filename
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                $image = 'images/products/' . $filename;
            } else {
                $errors[] = 'Image upload failed.';
            }
        }
    }

    if (empty($errors)) {
        if ($pid > 0) {
            $s = $db->prepare("UPDATE products SET product_name=?,description=?,price=?,product_type=?,image=?,stock=? WHERE product_id=?");
            $s->bind_param('ssdssii',$name,$desc,$price,$type,$image,$stock,$pid);
            $s->execute(); $s->close();
            header('Location: manage_products.php?msg=updated'); exit;
        } else {
            $s = $db->prepare("INSERT INTO products (product_name,description,price,product_type,image,stock) VALUES (?,?,?,?,?,?)");
            $s->bind_param('ssdssi',$name,$desc,$price,$type,$image,$stock);
            $s->execute(); $s->close();
            header('Location: manage_products.php?msg=added'); exit;
        }
    }
}

$products = $db->query("SELECT * FROM products ORDER BY product_type, product_name")->fetch_all(MYSQLI_ASSOC);
$typeBadge = ['loose'=>'badge-green','member'=>'badge-amber','package'=>'badge-purple'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manage Products — OrderSync Admin</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<div class="admin-layout">
  <?php include 'includes/sidebar.php'; ?>
  <div class="admin-content">
    <div class="admin-topbar">
      <span class="admin-topbar-title">🛍️ Manage Products</span>
      <button class="btn btn-primary btn-sm" onclick="openAdd()">➕ Add Product</button>
    </div>
    <div class="admin-page">
      <?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><ul style="margin:0;padding-left:16px;"><?php foreach($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
      <?php endif; ?>
      <div class="card">
        <div class="table-wrap">
          <table>
            <thead><tr><th>Image</th><th>Name</th><th>Type</th><th>Price</th><th>Stock</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach ($products as $p): ?>
              <tr>
                <td><img src="../<?= e($p['image']) ?>" style="width:52px;height:52px;object-fit:cover;border-radius:8px;" onerror="this.src='../images/product-placeholder.jpg'"></td>
                <td><div style="font-weight:600;"><?= e($p['product_name']) ?></div><div style="font-size:.75rem;color:var(--text-3);max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($p['description']) ?></div></td>
                <td><span class="badge <?= $typeBadge[$p['product_type']]??'badge-gray' ?>"><?= ucfirst(e($p['product_type'])) ?></span></td>
                <td><strong>₱<?= number_format((float)$p['price'],2) ?></strong></td>
                <td><?= ($p['stock'] <= 10) ? '<span style="color:var(--red);font-weight:700;">'.(int)$p['stock'].'</span>' : (int)$p['stock'] ?></td>
                <td>
                  <div class="action-btns">
                    <button class="btn btn-outline btn-sm" onclick='openEdit(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)'>✏️ Edit</button>
                    <form method="POST" action="manage_products.php" style="display:inline"
                          onsubmit="return confirm('Delete this product?')">
                      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                      <input type="hidden" name="delete" value="<?= (int)$p['product_id'] ?>">
                      <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal-overlay" id="productModal">
  <div class="modal" style="max-width:560px;">
    <div class="modal-header">
      <h3 id="modalTitle">➕ Add Product</h3>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="product_id" id="fPid">
        <input type="hidden" name="current_image" id="fCurrentImage">
        <div class="form-group"><label class="form-label">Product Name *</label><input type="text" name="product_name" id="fName" class="form-control" required maxlength="200"></div>
        <div class="form-group"><label class="form-label">Description</label><textarea name="description" id="fDesc" class="form-control" rows="3" maxlength="1000"></textarea></div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Price (₱) *</label><input type="number" name="price" id="fPrice" class="form-control" step="0.01" min="0.01" max="999999" required></div>
          <div class="form-group"><label class="form-label">Stock *</label><input type="number" name="stock" id="fStock" class="form-control" min="0" max="99999" required></div>
        </div>
        <div class="form-group">
          <label class="form-label">Product Type *</label>
          <select name="product_type" id="fType" class="form-control">
            <option value="loose">Loose</option>
            <option value="member">Member Exclusive</option>
            <option value="package">Package</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Product Image</label>
          <input type="file" name="image" id="fImage" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
          <p class="form-hint">JPG, PNG, GIF, WEBP — max 3MB. Leave blank to keep current.</p>
          <img id="fImagePreview" style="margin-top:10px;width:80px;height:80px;object-fit:cover;border-radius:8px;display:none;">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-primary" id="submitBtn">➕ Add Product</button>
      </div>
    </form>
  </div>
</div>
<script>
function openAdd() {
  document.getElementById('modalTitle').textContent='➕ Add Product';
  document.getElementById('submitBtn').textContent='➕ Add Product';
  ['fPid','fName','fDesc','fPrice','fCurrentImage'].forEach(id=>document.getElementById(id).value='');
  document.getElementById('fStock').value='100';
  document.getElementById('fType').value='loose';
  document.getElementById('fImagePreview').style.display='none';
  document.getElementById('productModal').classList.add('open');
}
function openEdit(p) {
  document.getElementById('modalTitle').textContent='✏️ Edit Product';
  document.getElementById('submitBtn').textContent='💾 Save Changes';
  document.getElementById('fPid').value=p.product_id;
  document.getElementById('fName').value=p.product_name;
  document.getElementById('fDesc').value=p.description;
  document.getElementById('fPrice').value=p.price;
  document.getElementById('fStock').value=p.stock;
  document.getElementById('fType').value=p.product_type;
  document.getElementById('fCurrentImage').value=p.image;
  const prev=document.getElementById('fImagePreview');
  prev.src='../'+p.image; prev.style.display='block';
  document.getElementById('productModal').classList.add('open');
}
function closeModal(){document.getElementById('productModal').classList.remove('open');}
document.getElementById('productModal').addEventListener('click',e=>{if(e.target===document.getElementById('productModal'))closeModal();});
document.getElementById('fImage').addEventListener('change',function(){
  const file=this.files[0]; if(!file)return;
  const reader=new FileReader();
  reader.onload=e=>{const p=document.getElementById('fImagePreview');p.src=e.target.result;p.style.display='block';};
  reader.readAsDataURL(file);
});
</script>
</body>
</html>