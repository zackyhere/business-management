<?php

$errors = [];
$success = null;

$categoryList = function_exists('readData') ? readData("category") : [];
$productList = function_exists('readData') ? readData("product") : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_category') {
        $catName = trim($_POST['category_name'] ?? '');
        if ($catName === '') {
            $errors[] = "Nama kategori wajib diisi.";
        } else {
            $exists = false;
            foreach ($categoryList as $c) {
                if (strcasecmp($c['category_name'] ?? '', $catName) === 0) {
                    $exists = true;
                    break;
                }
            }
            if ($exists) {
                $errors[] = "Kategori dengan nama sama sudah ada.";
            } else {
                if (!function_exists('createData')) {
                    $errors[] = "Fungsi createData tidak tersedia. Pastikan helper sudah di-include.";
                } else {
                    try {
                        $addCat = createData('category', ["category_name" => $catName]);
                        $success = "Kategori berhasil ditambahkan.";
                        $categoryList = readData("category");
                    } catch (Exception $e) {
                        $errors[] = "Gagal menambahkan kategori: " . $e->getMessage();
                    }
                }
            }
        }
    }

    if ($action === 'delete_category') {
        $id = $_POST['id'] ?? '';
        if ($id === '') {
            $errors[] = "ID kategori tidak ditemukan.";
        } else {

            $used = false;
            foreach ($productList as $p) {
                if (isset($p['category_id']) && (string)$p['category_id'] === (string)$id) {
                    $used = true;
                    break;
                }
            }
            if ($used) {
                $errors[] = "Kategori tidak dapat dihapus karena masih digunakan oleh satu atau lebih produk.";
            } else {
                if (!function_exists('deleteData')) {
                    $errors[] = "Fungsi deleteData tidak tersedia. Pastikan helper sudah di-include.";
                } else {
                    try {
                        $hapus = deleteData('category', ['id' => $id]);
                        $success = "Kategori berhasil dihapus.";
                  
                        $categoryList = readData("category");
                    } catch (Exception $e) {
                        $errors[] = "Gagal menghapus kategori: " . $e->getMessage();
                    }
                }
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
  <title>Manajemen Kategori — Admin</title>
  <style>
    :root{
      --bg:#f7f9fb;
      --card:#ffffff;
      --muted:#6b7280;
      --accent:#0ea5a4;
      --danger:#ef4444;
      --radius:12px;
      --shadow:0 6px 20px rgba(16,24,40,0.06);
      font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
    }
    body{margin:0;background:linear-gradient(180deg,#fbfeff 0%,var(--bg)100%);color:#0b2430}
    .wrap{max-width:980px;margin:0 auto}
    .card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);padding:18px;margin-bottom:18px}
    h1{margin:0 0 6px;font-size:20px}
    p.lead{margin:0 0 14px;color:var(--muted)}
    .row{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
    .form-inline{display:flex;gap:8px;align-items:center}
    input[type="text"]{padding:10px 12px;border-radius:10px;border:1px solid #e6eef6;background:#fbfdff;font-size:14px}
    button{padding:10px 12px;border-radius:10px;border:0;cursor:pointer;font-weight:600}
    button.primary{background:var(--accent);color:#fff}
    button.ghost{background:transparent;border:1px solid #e6eef6;color:#0b2430}
    .muted{color:var(--muted);font-size:13px}
    .alert{padding:10px;border-radius:8px;margin-bottom:12px}
    .alert.error{background:#fff7f7;color:var(--danger);border:1px solid rgba(239,68,68,0.06)}
    .alert.success{background:#f0fdf4;color:#065f46;border:1px solid rgba(6,95,70,0.06)}
    /* list table */
    table{width:100%;border-collapse:collapse;margin-top:12px}
    th,td{padding:10px 12px;text-align:left;border-bottom:1px solid #f1f5f9}
    th{background:#fbfdff;font-weight:700}
    .btn-delete{background:transparent;color:var(--danger);border:1px solid rgba(239,68,68,0.08);padding:8px 10px;border-radius:8px;cursor:pointer}
    @media(max-width:720px){
      .form-inline{flex-direction:column;align-items:stretch}
      .form-inline button{width:100%}
    }
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
      <a href="/produk-edit" style="display:block;padding:6px 12px;text-decoration:none;color:#111827;font-weight:500;font-size:14px;">Edit</a>
      <a href="/category" style="display:block;padding:6px 12px;text-decoration:none;color:#111827;font-weight:500;font-size:14px;">Category</a>
    </div>
    <a href="/transactions-list" style="display:block;width:100%;padding:10px 14px;text-decoration:none;color:#111827;font-weight:600;border-radius:6px;text-align:left;background:none;border:none;cursor:pointer;">Transactions</a>
  </nav>
</aside>

  <div class="wrap">
    <div class="card">
      <h1>Manajemen Kategori</h1>
      <p class="lead">Tambahkan atau hapus kategori. Tidak bisa menghapus kategori yang sedang dipakai oleh produk.</p>

      <?php if (!empty($errors)): ?>
        <div class="alert error">
          <strong>Terjadi kesalahan:</strong>
          <ul style="margin:8px 0 0 18px;">
            <?php foreach ($errors as $e): ?><li><?=htmlspecialchars($e)?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert success"><?=htmlspecialchars($success)?></div>
      <?php endif; ?>

      <!-- Form tambah kategori -->
      <form method="post" class="form-inline" style="margin-top:8px;">
        <input type="hidden" name="action" value="add_category">
        <input type="text" name="category_name" placeholder="Nama kategori baru..." required>
        <button type="submit" class="primary">Tambah Kategori</button>
        <div style="margin-left:auto" class="muted">Jumlah kategori: <?=count($categoryList)?></div>
      </form>

      <!-- Tabel kategori -->
      <table aria-label="Daftar kategori" role="table">
        <thead>
          <tr>
            <th style="width:80px">ID</th>
            <th>Nama Kategori</th>
            <th style="width:200px">Dipakai oleh (produk)</th>
            <th style="width:140px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $usedCount = [];
          foreach ($productList as $p) {
              $cid = $p['category_id'] ?? null;
              if ($cid !== null) {
                  if (!isset($usedCount[$cid])) $usedCount[$cid] = 0;
                  $usedCount[$cid]++;
              }
          }
          foreach ($categoryList as $c):
            $cid = $c['id'] ?? '';
            $cname = $c['category_name'] ?? ($c['name'] ?? '');
            $count = isset($usedCount[$cid]) ? (int)$usedCount[$cid] : 0;
          ?>
            <tr>
              <td><?=htmlspecialchars($cid)?></td>
              <td><?=htmlspecialchars($cname)?></td>
              <td>
                <?php if ($count > 0): ?>
                  <span class="muted"><?= $count ?> produk</span>
                <?php else: ?>
                  <span class="muted">—</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($count > 0): ?>
                  <button class="btn-delete" disabled title="Kategori sedang dipakai">Hapus</button>
                <?php else: ?>
                  <!-- tombol hapus memicu form delete -->
                  <form method="post" style="display:inline" class="form-delete">
                    <input type="hidden" name="action" value="delete_category">
                    <input type="hidden" name="id" value="<?=htmlspecialchars($cid)?>">
                    <button type="button" class="btn-delete" onclick="confirmDelete(this.form, '<?=addslashes(htmlspecialchars($cname))?>')">Hapus</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($categoryList)): ?>
            <tr><td colspan="4" class="muted">Belum ada kategori.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script>
    function confirmDelete(form, categoryName) {
      const ok = confirm('Yakin ingin menghapus kategori \"' + categoryName + '\"? Tindakan ini tidak bisa dibatalkan.');
      if (!ok) return;
      form.submit();
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