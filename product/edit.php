<?php
// edit.php
$errors = [];
$success = null;

// Ambil data kategori dan produk
$categoryList = function_exists('readData') ? readData("category") : [];
$productList = function_exists('readData') ? readData("product") : [];

// Helper: map category id -> name
$categoryMap = [];
foreach ($categoryList as $c) {
    $cid = $c['id'] ?? null;
    $cname = $c['category_name'] ?? ($c['name'] ?? '');
    if ($cid !== null) $categoryMap[$cid] = $cname;
}

// Proses edit produk
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ==========================
    // EDIT PRODUCT
    // ==========================
    if ($action === 'edit_product') {
        $id = $_POST['id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $category_id = $_POST['category_id'] ?? '';
        $buy = $_POST['buy'] ?? '';
        $sell = $_POST['sell'] ?? '';
        $stock = $_POST['stock'] ?? '';
        $old_img_url = $_POST['old_img_url'] ?? '';

        if ($id === '') $errors[] = "ID produk tidak ditemukan.";
        if ($name === '') $errors[] = "Nama produk wajib diisi.";
        if ($category_id === '') $errors[] = "Kategori wajib dipilih.";
        if ($buy === '' || !is_numeric($buy)) $errors[] = "Harga beli wajib angka.";
        if ($sell === '' || !is_numeric($sell)) $errors[] = "Harga jual wajib angka.";
        if ($stock === '' || !is_numeric($stock)) $errors[] = "Stock wajib angka.";

        // handle upload gambar opsional
        $new_img_filename = null;
        if (isset($_FILES['img']) && $_FILES['img']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload = $_FILES['img'];
            if ($upload['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "Terjadi kesalahan saat mengunggah gambar.";
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $upload['tmp_name']);
                finfo_close($finfo);
                $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
                if (!in_array($mime, $allowed)) {
                    $errors[] = "Tipe file tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.";
                } else {
                    $timestamp = time();
                    $baseName = 'product_' . $id . '-' . $timestamp;
                    $destDir = dirname(__DIR__) . '/image/product';
                    if (!is_dir($destDir)) mkdir($destDir, 0755, true);

                    $finalFilename = $baseName . '.webp';
                    $finalPath = $destDir . '/' . $finalFilename;
                    $converted = false;

                    if (function_exists('imagewebp')) {
                        switch ($mime) {
                            case 'image/jpeg': $srcImg = @imagecreatefromjpeg($upload['tmp_name']); break;
                            case 'image/png': $srcImg = @imagecreatefrompng($upload['tmp_name']); 
                                if ($srcImg !== false){ imagepalettetotruecolor($srcImg); imagealphablending($srcImg,true); imagesavealpha($srcImg,true);} break;
                            case 'image/gif': $srcImg = @imagecreatefromgif($upload['tmp_name']); break;
                            case 'image/webp': $srcImg = @imagecreatefromwebp($upload['tmp_name']); break;
                            default: $srcImg=false;
                        }
                        if ($srcImg!==false){ if(@imagewebp($srcImg,$finalPath,80)){ imagedestroy($srcImg); $converted=true;}else{imagedestroy($srcImg); $converted=false;} }
                    }

                    if (!$converted){
                        $ext = pathinfo($upload['name'], PATHINFO_EXTENSION);
                        $finalFilename = $baseName . '.' . $ext;
                        $finalPath = $destDir . '/' . $finalFilename;
                        if (!move_uploaded_file($upload['tmp_name'],$finalPath)) $errors[] = "Gagal menyimpan file gambar ke server.";
                    }

                    if(empty($errors)) $new_img_filename = $finalFilename;
                }
            }
        }

        if(empty($errors)){
            $other = [
                "name"=>$name,
                "category_id"=>$category_id,
                "buy"=>(int)$buy,
                "sell"=>(int)$sell,
                "stock"=>(int)$stock,
            ];
            if($new_img_filename!==null) $other['img_url']="../image/product/".$new_img_filename;
            else $other['img_url']=$old_img_url;

            $payload = array_merge(['id'=>$id], $other);
            if(function_exists('updateData')){
                try {
                    $kirim = updateData('product',$payload);
                    $success="Produk berhasil diperbarui.";
                    $productList = readData("product");
                } catch(Exception $e){$errors[]="Gagal memperbarui data: ".$e->getMessage();}
            } else $errors[]="Fungsi updateData tidak tersedia.";
        }
    }

    // ==========================
    // DELETE PRODUCT
    // ==========================
    if ($action === 'delete_product') {
        $id = $_POST['id'] ?? '';
        if ($id==='') $errors[]="ID produk tidak ditemukan.";
        else {
            if(function_exists('deleteData')){
                try {
                    $hapus = deleteData('product',['id'=>$id]);
                    $success="Produk berhasil dihapus.";
                    $productList = readData("product");
                } catch(Exception $e){ $errors[]="Gagal menghapus produk: ".$e->getMessage(); }
            } else $errors[]="Fungsi deleteData tidak tersedia.";
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Edit Produk</title>
<style>
:root{--bg:#f7fbfd;--card:#fff;--muted:#556070;--accent:#0ea5a4;--radius:12px;--shadow:0 6px 20px rgba(16,24,40,0.06);font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial}
body{margin:0;background:var(--bg);color:#0b2430}
.container{max-width:1100px;margin:0 auto}
.card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);padding:18px;margin-bottom:18px}
h1{margin:0 0 8px;font-size:20px}
p.lead{color:var(--muted);margin:0 0 12px}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px}
.prod{border-radius:10px;padding:12px;background:#fbfeff;border:1px solid #eef6f7;display:flex;gap:10px;align-items:flex-start}
.prod img{width:84px;height:84px;object-fit:cover;border-radius:8px;flex-shrink:0}
.prod .meta{flex:1}
.prod .meta h3{margin:0 0 6px;font-size:16px}
.prod .meta .muted{font-size:13px;color:var(--muted)}
.prod .prod .meta .row{display:flex;gap:8px;margin-top:8px;align-items:center}
.btn{cursor:pointer;padding:8px 10px;border-radius:8px;border:0;font-weight:600}
.btn.edit{background:var(--accent);color:#fff}
.btn.ghost{background:transparent;border:1px solid #e6eef6;color:#0b2430}
.modal-backdrop{position:fixed;inset:0;background:rgba(2,6,23,0.45);display:none;align-items:center;justify-content:center;padding:20px;z-index:40}
.modal{background:var(--card);border-radius:12px;max-width:760px;width:100%;padding:18px;box-shadow:var(--shadow);max-height:90vh;overflow:auto}
.modal .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media(max-width:720px){.modal .grid{grid-template-columns:1fr}.prod img{width:64px;height:64px}}
.form-control{margin-bottom:10px}
input[type="text"],input[type="number"],select{width:100%;padding:10px;border-radius:10px;border:1px solid #e6eef6;background:#fbfdff;font-size:14px}
input[type="file"]{padding:6px 0}
.img-preview{max-width:180px;max-height:180px;border-radius:8px;object-fit:cover;margin-top:8px;display:block}
.alert{padding:10px;border-radius:8px;margin-bottom:12px}
.alert.error{background:#fff0f0;color:#b91c1c;border:1px solid rgba(239,68,68,0.08)}
.alert.success{background:#f0fdf4;color:#065f46;border:1px solid rgba(6,95,70,0.08)}
</style>
</head>
<body>
  <header style="display:flex;justify-content:space-between;align-items:center;padding:12px 20px;background:#fff;box-shadow:0 1px 6px rgba(0,0,0,0.1);position:sticky;top:0;z-index:1000;">
  <div id="brandToggle" style="display:flex;align-items:center;gap:10px;cursor:pointer;user-select:none;">
    <img src="../image/logo/ToprionLogo.webp" alt="Logo" style="width:40px;height:40px;border-radius:8px;">
    <span style="font-weight:bold;font-size:16px;">Toprion Market</span>
  </div>
</header>

<div id="overlay" style="position:fixed;inset:0;background:rgba(0,0,0,0.5);opacity:0;visibility:hidden;transition:.3s;z-index:900;"></div>

<aside id="sidebar" style="position:fixed;top:0;left:0;width:260px;height:100%;background:#fff;box-shadow:2px 0 6px rgba(0,0,0,.15);transform:translateX(-100%);transition:.3s;z-index:1000;overflow-y:auto;">
  <div style="display:flex;justify-content:space-between;align-items:center;padding:20px;border-bottom:1px solid #e5e7eb;">
    <h3 style="margin:0;font-size:16px;color:#1e3a8a;">Menu</h3>
    <button id="closeSidebar" style="background:none;border:none;font-size:22px;cursor:pointer;">&times;</button>
  </div>
  <nav style="padding:20px;display:flex;flex-direction:column;gap:4px;">
    <a href="/" style="display:block;width:100%;padding:10px 14px;text-decoration:none;color:#111827;font-weight:600;border-radius:6px;text-align:left;background:none;border:none;cursor:pointer;">Dashboard</a>
    <button id="produkToggle" style="display:block;width:100%;padding:10px 14px;text-decoration:none;color:#111827;font-weight:600;border-radius:6px;text-align:left;background:none;border:none;cursor:pointer;">Produk ▾</button>
    <div id="produkSubmenu" style="display:none;flex-direction:column;margin-left:12px;gap:2px;">
      <a href="/product-list" style="display:block;padding:6px 12px;text-decoration:none;color:#111827;font-weight:500;font-size:14px;">List</a>
      <a href="/product-add" style="display:block;padding:6px 12px;text-decoration:none;color:#111827;font-weight:500;font-size:14px;">Add</a>
      <a href="/product-edit" style="display:block;padding:6px 12px;text-decoration:none;color:#111827;font-weight:500;font-size:14px;">Edit</a>
      <a href="/category" style="display:block;padding:6px 12px;text-decoration:none;color:#111827;font-weight:500;font-size:14px;">Category</a>
    </div>
    <a href="/transactions-list" style="display:block;width:100%;padding:10px 14px;text-decoration:none;color:#111827;font-weight:600;border-radius:6px;text-align:left;background:none;border:none;cursor:pointer;">Transactions</a>
  </nav>
</aside>

<div class="container">
<div class="card">
<h1>Edit Produk</h1>
<?php if(!empty($errors)):?>
<div class="alert error">
<strong>Terjadi kesalahan:</strong>
<ul style="margin:8px 0 0 18px;">
<?php foreach($errors as $e):?><li><?=htmlspecialchars($e)?></li><?php endforeach;?>
</ul>
</div>
<?php endif;?>
<?php if($success):?><div class="alert success"><?=htmlspecialchars($success)?></div><?php endif;?>

<div style="margin:12px 0;color:var(--muted);font-size:13px">Jumlah produk: <?=count($productList)?></div>
<div class="grid">
<?php foreach($productList as $p):
$pid=$p['id']??'';$pname=$p['name']??'';$pcat=$p['category_id']??'';$buy=$p['buy']??0;$sell=$p['sell']??0;$stock=$p['stock']??0;$img=$p['img_url']??'../image/product/default.png';
$displayImg=$img; if(strpos($displayImg,'../')===0) $displayImg='/' . substr($displayImg,3);
?>
<div class="prod">
<img src="<?=htmlspecialchars($displayImg)?>" alt="<?=htmlspecialchars($pname)?>">
<div class="meta">
<h3><?=htmlspecialchars($pname)?></h3>
<div class="muted">Kategori: <?=htmlspecialchars($categoryMap[$pcat]??'—')?></div>
<div class="muted">Harga beli: Rp <?=number_format($buy,0,',','.')?> • Harga jual: Rp <?=number_format($sell,0,',','.')?></div>
<div class="muted">Stock: <?=htmlspecialchars($stock)?> • Dibuat: <?=htmlspecialchars($p['created_at']??'-')?></div>
<div class="row" style="margin-top:10px;">
<button class="btn edit" onclick="openEditModal(<?=htmlspecialchars(json_encode($p))?>)">Edit</button>
</div>
</div>
</div>
<?php endforeach;?>
</div>
</div>
</div>

<!-- Modal -->
<div id="modalBackdrop" class="modal-backdrop" role="dialog" aria-hidden="true">
<div class="modal" role="document" aria-modal="true">
<h2 id="modalTitle">Edit Produk</h2>
<p class="muted" id="modalSubtitle">ID produk: <span id="modalId"></span></p>
<form id="editForm" method="post" enctype="multipart/form-data" style="margin-top:8px;">
<input type="hidden" name="action" id="formAction" value="edit_product">
<input type="hidden" name="id" id="fieldId" value="">
<input type="hidden" name="old_img_url" id="fieldOldImg" value="">
<div class="grid">
<div>
<div class="form-control"><label for="fieldName">Nama Produk</label><input type="text" id="fieldName" name="name" required></div>
<div class="form-control"><label for="fieldCategory">Kategori</label><select id="fieldCategory" name="category_id" required><option value="">-- Pilih Kategori --</option>
<?php foreach($categoryList as $c):$cid=$c['id']??($c['category_id']??'');$cname=$c['category_name']??($c['name']??'');?>
<option value="<?=htmlspecialchars($cid)?>"><?=htmlspecialchars($cname)?></option>
<?php endforeach;?>
</select></div>
<div class="form-control"><label for="fieldBuy">Harga Beli (Rupiah)</label><input type="number" id="fieldBuy" name="buy" min="0" required></div>
<div class="form-control"><label for="fieldSell">Harga Jual (Rupiah)</label><input type="number" id="fieldSell" name="sell" min="0" required></div>
<div class="form-control"><label for="fieldStock">Stock</label><input type="number" id="fieldStock" name="stock" min="0" required></div>
</div>
<div>
<div class="form-control"><label for="fieldImg">Ganti Gambar Produk (opsional)</label><input type="file" id="fieldImg" name="img" accept="image/*"></div>
<div><label>Preview Gambar Saat Ini</label><img id="currentPreview" class="img-preview" src="" alt="Preview"></div>
<div id="newPreviewWrap" style="margin-top:8px; display:none;"><label>Preview Gambar Baru</label><img id="newPreview" class="img-preview" src="" alt="Preview Baru"></div>
</div>
</div>

<div style="display:flex;gap:8px;margin-top:12px;align-items:center;">
<button type="submit" class="btn edit">Simpan Perubahan</button>
<button type="button" class="btn ghost" onclick="closeModal()">Batal</button>
<button type="button" class="btn" style="background:#ef4444;color:#fff" onclick="hapusProduk()">Hapus</button>
</div>
</form>
</div>
</div>

<script>
const modalBackdrop=document.getElementById('modalBackdrop');
const fieldId=document.getElementById('fieldId');
const fieldName=document.getElementById('fieldName');
const fieldCategory=document.getElementById('fieldCategory');
const fieldBuy=document.getElementById('fieldBuy');
const fieldSell=document.getElementById('fieldSell');
const fieldStock=document.getElementById('fieldStock');
const fieldOldImg=document.getElementById('fieldOldImg');
const currentPreview=document.getElementById('currentPreview');
const fieldImg=document.getElementById('fieldImg');
const newPreviewWrap=document.getElementById('newPreviewWrap');
const newPreview=document.getElementById('newPreview');
const editForm=document.getElementById('editForm');
const formAction=document.getElementById('formAction');

function openEditModal(product){
  let img=product.img_url||'../image/product/default.png';
  let displayImg=img; if(displayImg.indexOf('../')===0) displayImg='/'+displayImg.substring(3);
  modalBackdrop.style.display='flex';
  document.getElementById('modalId').textContent=product.id;
  fieldId.value=product.id;
  fieldName.value=product.name||'';
  fieldCategory.value=product.category_id||'';
  fieldBuy.value=product.buy||0;
  fieldSell.value=product.sell||0;
  fieldStock.value=product.stock||0;
  fieldOldImg.value=product.img_url||'';
  currentPreview.src=displayImg;
  currentPreview.style.display='block';
  newPreviewWrap.style.display='none';
  newPreview.src='';
  fieldImg.value='';
  formAction.value='edit_product';
  modalBackdrop.scrollTop=0;
}

function closeModal(){ modalBackdrop.style.display='none'; }

fieldImg.addEventListener('change',e=>{ const f=e.target.files[0]; if(!f){newPreviewWrap.style.display='none';newPreview.src='';return;} const url=URL.createObjectURL(f); newPreview.src=url; newPreviewWrap.style.display='block'; });

modalBackdrop.addEventListener('click',e=>{if(e.target===modalBackdrop) closeModal();});

function hapusProduk(){
  if(!confirm('Yakin ingin menghapus produk ini? Tindakan ini tidak bisa dibatalkan.')) return;
  formAction.value='delete_product';
  editForm.submit();
}

document.addEventListener("DOMContentLoaded", function(){
  const sidebar = document.getElementById("sidebar"),
        overlay = document.getElementById("overlay"),
        brandToggle = document.getElementById("brandToggle"),
        closeBtn = document.getElementById("closeSidebar"),
        produkToggle = document.getElementById("produkToggle"),
        produkSubmenu = document.getElementById("produkSubmenu");

  function openSidebar() {
    sidebar.style.transform = "translateX(0)";
    overlay.style.opacity = "1";
    overlay.style.visibility = "visible";
  }

  function closeSidebar() {
    sidebar.style.transform = "translateX(-100%)";
    overlay.style.opacity = "0";
    overlay.style.visibility = "hidden";
  }

  brandToggle.addEventListener("click", openSidebar);
  closeBtn.addEventListener("click", closeSidebar);
  overlay.addEventListener("click", closeSidebar);
  document.addEventListener("keydown", e => { if(e.key==="Escape") closeSidebar(); });

  produkToggle.addEventListener("click", ()=> {
    if(produkSubmenu.style.display === "flex") {
      produkSubmenu.style.display = "none";
      produkToggle.textContent = "Produk ▾";
    } else {
      produkSubmenu.style.display = "flex";
      produkToggle.textContent = "Produk ▴";
    }
  });
});
</script>
</body>
</html>