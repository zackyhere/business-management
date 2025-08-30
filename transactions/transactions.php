<?php
// transactions.php
$transactions = readData("transactions");
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

$filtered = $transactions;
if ($q !== '') {
    $qLower = mb_strtolower($q, 'UTF-8');
    $filtered = array_filter($transactions, function($t) use ($qLower) {
        foreach (['trx_id', 'price', 'bayar', 'kembalian'] as $f) {
            $val = isset($t[$f]) ? (string)$t[$f] : '';
            if (mb_stripos($val, $qLower, 0, 'UTF-8') !== false) return true;
        }
        return false;
    });
}
$count = count($filtered);
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Daftar Transaksi</title>
<style>
body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; margin:0; background:#f7f8fa; color:#111; }
.card { background:#fff; border-radius:8px; padding:16px; box-shadow:0 1px 6px rgba(0,0,0,0.08); max-width:1200px; margin:0 auto; }
h1 { font-size:20px; margin-bottom:16px; }
form.search { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:14px; align-items:center; }
input[type="text"] { flex:1 1 200px; padding:10px 12px; border:1px solid #d6dbe0; border-radius:6px; min-width:150px; max-height: 30px; font-size:14px; line-height:1.3; }
button { padding:10px 16px; border-radius:6px; border:0; cursor:pointer; background:#2563eb; color:#fff; font-size:14px; line-height:1.3; }
a.reset { padding:10px 16px; border-radius:6px; background:#6b7280; color:#fff; text-decoration:none; display:inline-block; line-height:1.3; text-align:center; }
.table-wrapper { overflow-x:auto; }
table { width:100%; border-collapse:collapse; margin-top:6px; min-width:720px; }
th, td { padding:10px 8px; border-bottom:1px solid #eef2f6; text-align:left; font-size:14px; white-space:nowrap; }
th { background:#fafafa; position:sticky; top:0; z-index:1; }
.muted { color:#6b7280; font-size:13px; }
.btn-view { padding:6px 10px; border-radius:6px; background:#0ea5a4; color:white; text-decoration:none; display:inline-block; }
.no-data { padding:18px; text-align:center; color:#374151; }
@media (max-width:720px){
    th, td { font-size:13px; padding:8px 6px; }
    form.search { flex-direction:column; gap:6px; }
    input[type="text"], button, a.reset { width:100%; }
    button, a.reset { text-align:center; }
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
      <a href="/product-edit" style="display:block;padding:6px 12px;text-decoration:none;color:#111827;font-weight:500;font-size:14px;">Edit</a>
      <a href="/category" style="display:block;padding:6px 12px;text-decoration:none;color:#111827;font-weight:500;font-size:14px;">Category</a>
    </div>
    <a href="/transactions-list" style="display:block;width:100%;padding:10px 14px;text-decoration:none;color:#111827;font-weight:600;border-radius:6px;text-align:left;background:none;border:none;cursor:pointer;">Transactions</a>
  </nav>
</aside>

<div class="card">
<h1>Daftar Transaksi</h1>

<form class="search" method="get" action="">
    <input type="text" name="q" placeholder="Cari trx_id, price, bayar, kembalian" value="<?php echo e($q); ?>" autocomplete="off" />
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <button type="submit">Cari</button>
        <a href="<?php echo e(preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI'])); ?>" class="reset">Reset</a>
    </div>
</form>

<div class="muted">Menampilkan <strong><?php echo $count; ?></strong> transaksi<?php echo $q !== '' ? ' (filter: "'.e($q).'")' : ''; ?></div>

<?php if ($count === 0): ?>
    <div class="no-data">Tidak ada transaksi ditemukan.</div>
<?php else: ?>
<div class="table-wrapper">
<table>
<thead>
<tr>
<th>Trx ID</th>
<th>Tanggal</th>
<th>Produk</th>
<th>Harga</th>
<th>Bayar</th>
<th>Kembalian</th>
<th>Aksi</th>
</tr>
</thead>
<tbody>
<?php foreach ($filtered as $t): 
    $trx_id = isset($t['trx_id']) ? $t['trx_id'] : '';
    $date = isset($t['date']) ? $t['date'] : '';
    $prod = isset($t['product_id']) ? $t['product_id'] : '';
    $price = isset($t['price']) ? $t['price'] : '';
    $bayar = isset($t['bayar']) && $t['bayar'] !== '' ? $t['bayar'] : '';
    $kembalian = isset($t['kembalian']) && $t['kembalian'] !== '' ? $t['kembalian'] : '';

    // Hitung jumlah produk
    $prodList = array_filter(array_map('trim', explode(',', (string)$prod)));
    $prodCount = count($prodList);
?>
<tr>
<td><strong><?php echo e($trx_id); ?></strong></td>
<td class="muted"><?php echo e($date); ?></td>
<td><?php echo $prodCount; ?> item<?php echo $prodCount > 1 ? 's' : ''; ?></td>
<td><?php echo $price !== '' ? 'Rp '.number_format((int)$price,0,',','.') : '-'; ?></td>
<td><?php echo $bayar !== '' ? 'Rp '.number_format((int)$bayar,0,',','.') : '-'; ?></td>
<td><?php echo $kembalian !== '' ? 'Rp '.number_format((int)$kembalian,0,',','.') : '-'; ?></td>
<td><a class="btn-view" href="/invoice?cart=<?php echo urlencode($trx_id); ?>">View</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>

</div>
</body>
<script>
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
</html>