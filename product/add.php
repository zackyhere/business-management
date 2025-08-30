<?php
// add.php
// Pastikan file ini berada di folder yang benar dan fungsi readData/createData sudah dapat diakses.
// Contoh: require_once 'db_helpers.php';

// --- Proses server ---
$errors = [];
$success = null;

// Ambil kategori dari fungsi readData("category")
$categoryList = [];
if (function_exists('readData')) {
    $categoryList = readData("category"); // sesuai permintaan: Array of arrays [id, category_name]
} else {
    // fallback: kosong (atau bisa diset manual)
    $categoryList = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ambil dan sanitize input
    $name = trim($_POST['name'] ?? '');
    $category_id = trim($_POST['category_id'] ?? '');
    $buy = trim($_POST['buy'] ?? '');
    $sell = trim($_POST['sell'] ?? '');
    $stock = trim($_POST['stock'] ?? '');

    // Validasi sederhana
    if ($name === '') $errors[] = "Nama produk wajib diisi.";
    if ($category_id === '') $errors[] = "Kategori wajib dipilih.";
    if ($buy === '' || !is_numeric($buy)) $errors[] = "Harga beli wajib angka.";
    if ($sell === '' || !is_numeric($sell)) $errors[] = "Harga jual wajib angka.";
    if ($stock === '' || !is_numeric($stock)) $errors[] = "Stock wajib angka.";

    // Upload & konversi gambar
    $img_url = '';
    if (isset($_FILES['img']) && $_FILES['img']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = $_FILES['img'];
        if ($upload['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Terjadi kesalahan saat mengunggah gambar.";
        } else {
            // validasi tipe file dasar
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $upload['tmp_name']);
            finfo_close($finfo);
            $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            if (!in_array($mime, $allowed)) {
                $errors[] = "Tipe file tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.";
            } else {
                // buat nama file aman berdasarkan nama produk + timestamp
                $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
                $slug = trim($slug, '-');
                if ($slug === '') $slug = 'product';
                $timestamp = time();
                $baseName = $slug . '-' . $timestamp;
                $destDir = dirname(__DIR__) . '/image/product';
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }

                $finalFilename = $baseName . '.webp';
                $finalPath = $destDir . '/' . $finalFilename;

                // coba konversi ke webp (preferensi user)
                $converted = false;
                if (function_exists('imagewebp')) {
                    try {
                        switch ($mime) {
                            case 'image/jpeg':
                                $srcImg = @imagecreatefromjpeg($upload['tmp_name']);
                                break;
                            case 'image/png':
                                $srcImg = @imagecreatefrompng($upload['tmp_name']);
                                // maintain alpha
                                imagepalettetotruecolor($srcImg);
                                imagealphablending($srcImg, true);
                                imagesavealpha($srcImg, true);
                                break;
                            case 'image/gif':
                                $srcImg = @imagecreatefromgif($upload['tmp_name']);
                                break;
                            case 'image/webp':
                                $srcImg = @imagecreatefromwebp($upload['tmp_name']);
                                break;
                            default:
                                $srcImg = false;
                        }
                        if ($srcImg !== false) {
                            // kualitas 80
                            $q = 80;
                            if (imagewebp($srcImg, $finalPath, $q)) {
                                imagedestroy($srcImg);
                                $converted = true;
                            } else {
                                imagedestroy($srcImg);
                                $converted = false;
                            }
                        } else {
                            $converted = false;
                        }
                    } catch (\Throwable $t) {
                        $converted = false;
                    }
                }

                if (!$converted) {
                    // fallback: pindahkan file asli (tetap pakai nama baseName dengan ekstensi asli)
                    $ext = pathinfo($upload['name'], PATHINFO_EXTENSION);
                    $finalFilename = $baseName . '.' . $ext;
                    $finalPath = $destDir . '/' . $finalFilename;
                    if (!move_uploaded_file($upload['tmp_name'], $finalPath)) {
                        $errors[] = "Gagal menyimpan file gambar ke server.";
                    }
                }

                // set $img_url sesuai permintaan: relatif ke lokasi lain: "../image/product/filename"
                $img_url = "../image/product/" . $finalFilename;
            }
        }
    } else {
        $errors[] = "Gambar produk wajib diunggah.";
    }

    // Jika tidak ada error, jalankan createData
    if (empty($errors)) {
        if (!function_exists('createData')) {
            $errors[] = "Fungsi createData tidak tersedia. Pastikan file helper telah di-include.";
        } else {
            // persiapkan data final (cast angka)
            $payload = [
                "name" => $name,
                "category_id" => $category_id,
                "buy" => (int)$buy,
                "sell" => (int)$sell,
                "stock" => (int)$stock,
                "img_url" => $img_url,
            ];

            try {
                $res = createData("product", $payload);
                // asumsi createData mengembalikan truthy atau array; sesuaikan jika berbeda
                $success = "Produk berhasil dibuat.";
                // reset form nilai jika perlu
                $name = $category_id = $buy = $sell = $stock = '';
            } catch (Exception $e) {
                $errors[] = "Gagal menyimpan produk: " . $e->getMessage();
            }
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Tambah Produk</title>
  <style>
    /* Tema terang dan responsif sederhana */
    :root{
      --bg: #f7f9fc;
      --card: #ffffff;
      --muted: #6b7280;
      --accent: #0ea5a4;
      --accent-600: #089e9c;
      --danger: #ef4444;
      --radius: 12px;
      --shadow: 0 6px 20px rgba(16,24,40,0.08);
      font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
    }
    body{
      margin:0;
      background: linear-gradient(180deg, #f8fbfc 0%, var(--bg) 100%);
      color:#102a43;
      
    }
    .container{
      max-width:920px;
      margin:20px auto;
      padding:20px;
    }
    .card{
      background:var(--card);
      border-radius:var(--radius);
      box-shadow:var(--shadow);
      padding:20px;
    }
    h1{font-size:20px;margin:0 0 6px}
    p.lead{color:var(--muted);margin:0 0 18px}
    form .grid{
      display:grid;
      grid-template-columns: 1fr 1fr;
      gap:12px;
    }
    @media (max-width:720px){ form .grid{ grid-template-columns: 1fr; } }
    label{display:block;font-size:13px;margin-bottom:6px;color:#0f1724}
    input[type="text"], input[type="number"], select {
      width:100%;
      padding:10px 12px;
      border-radius:10px;
      border:1px solid #e6eef6;
      background:#fbfdff;
      box-sizing:border-box;
      outline:none;
      font-size:14px;
    }
    input[type="file"]{
      padding:6px 0;
    }
    .actions{
      margin-top:16px;
      display:flex;
      gap:10px;
      align-items:center;
    }
    button.primary{
      background:var(--accent);
      color:white;
      border:none;
      padding:10px 16px;
      border-radius:10px;
      cursor:pointer;
      font-weight:600;
      box-shadow: 0 6px 18px rgba(14,165,164,0.12);
    }
    button.secondary{
      background:transparent;
      border:1px solid #e6eef6;
      padding:10px 14px;
      border-radius:10px;
      cursor:pointer;
    }
    .help{font-size:13px;color:var(--muted)}
    .alert{padding:10px;border-radius:8px;margin-bottom:12px}
    .alert.error{background:#fff0f0;color:var(--danger);border:1px solid rgba(239,68,68,0.12)}
    .alert.success{background:#f0fdf4;color: #065f46;border:1px solid rgba(6,95,70,0.08)}
    .img-preview { max-width:180px; max-height:180px; border-radius:8px; object-fit:cover; display:block; margin-top:8px; box-shadow: 0 6px 20px rgba(2,6,23,0.06); }
    .row { display:flex; gap:12px; flex-wrap:wrap; }
    .muted { color:var(--muted); font-size:13px; }
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
      <h1>Tambah Produk</h1>

      <?php if (!empty($errors)): ?>
        <div class="alert error">
          <strong>Terjadi kesalahan:</strong>
          <ul style="margin:8px 0 0 18px;">
            <?php foreach($errors as $e): ?>
              <li><?=htmlspecialchars($e)?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert success"><?=htmlspecialchars($success)?></div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data" novalidate>
        <div style="display:flex;gap:16px;flex-wrap:wrap;">
          <div style="flex:1;min-width:220px;">
            <label for="name">Nama Produk</label>
            <input id="name" name="name" type="text" required value="<?=isset($name) ? htmlspecialchars($name) : ''?>">
          </div>

          <div style="width:220px;min-width:160px;">
            <label for="category_id">Kategori</label>
            <select id="category_id" name="category_id" required>
              <option value="">-- Pilih Kategori --</option>
              <?php foreach ($categoryList as $c): 
                $cid = $c['id'] ?? ($c['category_id'] ?? '');
                $cname = $c['category_name'] ?? ($c['name'] ?? '');
              ?>
                <option value="<?=htmlspecialchars($cid)?>" <?= (isset($category_id) && $category_id == $cid) ? 'selected' : '' ?>>
                  <?=htmlspecialchars($cname)?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="grid" style="margin-top:12px;">
          <div>
            <label for="buy">Harga Beli (Rupiah)</label>
            <input id="buy" name="buy" type="number" min="0" required value="<?=isset($buy) ? htmlspecialchars($buy) : ''?>">
          </div>
          <div>
            <label for="sell">Harga Jual (Rupiah)</label>
            <input id="sell" name="sell" type="number" min="0" required value="<?=isset($sell) ? htmlspecialchars($sell) : ''?>">
          </div>

          <div>
            <label for="stock">Stock</label>
            <input id="stock" name="stock" type="number" min="0" required value="<?=isset($stock) ? htmlspecialchars($stock) : '0'?>">
          </div>

          <div>
            <label for="img">Gambar Produk</label>
            <input id="img" name="img" type="file" accept="image/*" required>
            
            <img id="preview" class="img-preview" style="display:none;" alt="Preview Gambar">
          </div>
        </div>

        <div class="actions">
          <button class="primary" type="submit">Buat Produk</button>
          <button class="secondary" type="reset" id="btnReset">Reset</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Preview gambar saat dipilih
    const inp = document.getElementById('img');
    const preview = document.getElementById('preview');
    inp.addEventListener('change', (e) => {
      const f = e.target.files[0];
      if (!f) { preview.style.display = 'none'; preview.src = ''; return; }
      const url = URL.createObjectURL(f);
      preview.src = url;
      preview.style.display = 'block';
    });

    // Reset preview saat reset form
    document.getElementById('btnReset').addEventListener('click', () => {
      preview.style.display = 'none';
      preview.src = '';
    });
    
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