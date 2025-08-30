<?php

$transaksi = readData("transactions");
$produk = readData("product");

if (!is_array($transaksi)) $transaksi = [];
if (!is_array($produk)) $produk = [];

$produkMap = [];
foreach ($produk as $p) {
    $produkMap[$p['id']] = $p['name'];
}

$produkTerjual = [];
foreach ($transaksi as $t) {
    $ids = explode(",", $t['product_id'] ?? '');
    foreach ($ids as $id) {
        $id = trim($id);
        if ($id === '') continue;
        $name = $produkMap[$id] ?? "Produk $id";
        $produkTerjual[$name] = ($produkTerjual[$name] ?? 0) + 1;
    }
}

$trxLabels = [];
$trxValues = [];
foreach ($transaksi as $t) {
    $trxLabels[] = "Trx #" . $t['trx_id'];

    $trxValues[] = round($t['price'] / 5000) * 5000;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    :root{
        --bg:#f6f8fb;
        --card:#ffffff;
        --accent:#2b6cb0;
        --muted:#6b7280;
        --shadow: 0 6px 18px rgba(15,23,42,0.08);
        --radius:12px;
    }
    body{background:var(--bg); margin:0; font-family:Inter,ui-sans-serif; color:#0f172a;}
    .wrapp{padding:27px;}
    .container{max-width:1100px; margin:0 auto;}
    .grid-cards{display:grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap:16px; margin-bottom:28px;}
    .card-link{display:block; text-decoration:none; color:inherit;}
    .card{background:var(--card); border-radius:var(--radius); padding:18px; box-shadow:var(--shadow);
        display:flex; flex-direction:column; gap:10px; transition:transform .14s ease, box-shadow .14s ease; min-height:100px; cursor:pointer;}
    .card:hover{transform:translateY(-6px); box-shadow: 0 18px 40px rgba(2,6,23,0.12);}
    .title{font-size:16px; font-weight:600;}
    .meta{font-size:13px; color:var(--muted);}
    .icon{margin-left:auto; background:rgba(43,108,176,0.09); color:var(--accent); padding:8px; border-radius:10px; font-weight:700; min-width:36px; text-align:center;}
    .charts{display:grid; grid-template-columns:1fr; gap:18px;}
    @media(min-width:900px){.charts{grid-template-columns:1fr 1fr;}}
    .chart-card{background:var(--card); border-radius:var(--radius); padding:16px; box-shadow:var(--shadow);}
    .chart-card h3{margin:0 0 8px 0; font-size:15px;}
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
    <a href="/transactions" style="display:block;width:100%;padding:10px 14px;text-decoration:none;color:#111827;font-weight:600;border-radius:6px;text-align:left;background:none;border:none;cursor:pointer;">Transactions</a>
  </nav>
</aside>
<div class="wrapp">
<div class="container">

    <!-- Cards (Tombol Navigasi) -->
    <div class="grid-cards">
        <a class="card-link" href="/product-list"><div class="card"><div class="title">Etalase Produk</div><div class="meta">Daftar produk</div><div class="icon">P</div></div></a>
        <a class="card-link" href="/product-add"><div class="card"><div class="title">Tambah Produk</div><div class="meta">Tambahkan produk baru</div><div class="icon">+</div></div></a>
        <a class="card-link" href="/product-edit"><div class="card"><div class="title">Edit Produk</div><div class="meta">Ubah data produk</div><div class="icon">✎</div></div></a>
        <a class="card-link" href="/caregory"><div class="card"><div class="title">Category</div><div class="meta">Kelola kategori</div><div class="icon">C</div></div></a>
        <a class="card-link" href="/transactions-list"><div class="card"><div class="title">Transaksi</div><div class="meta">Daftar transaksi</div><div class="icon">T</div></div></a>
    </div>

    <!-- Charts -->
    <div class="charts">
        <div class="chart-card">
            <h3>Produk Terjual</h3>
            <canvas id="produkChart" height="220"></canvas>
        </div>
        <div class="chart-card">
            <h3>Total Harga per Transaksi</h3>
            <canvas id="trxChart" height="220"></canvas>
        </div>
    </div>

</div>
</div>
<script>
const produkLabels = <?= json_encode(array_keys($produkTerjual)) ?>;
const produkData = <?= json_encode(array_values($produkTerjual)) ?>;

const trxLabels = <?= json_encode($trxLabels) ?>;
const trxValues = <?= json_encode($trxValues) ?>;

new Chart(document.getElementById('produkChart'), {
    type: 'bar',
    data: {
        labels: produkLabels,
        datasets: [{
            label: 'Jumlah Terjual',
            data: produkData,
            backgroundColor: '#2b6cb0'
        }]
    },
    options: {
        scales: { y: { beginAtZero:true, ticks:{ stepSize:1 } } }
    }
});

new Chart(document.getElementById('trxChart'), {
    type: 'line',
    data: {
        labels: trxLabels,
        datasets: [{
            label: 'Total Harga',
            data: trxValues,
            borderColor: '#2b6cb0',
            backgroundColor: 'rgba(43,108,176,0.2)',
            tension:0.3,
            fill:true
        }]
    },
    options: {
        scales: { y: { beginAtZero:true, ticks:{ stepSize:5000 } } }
    }
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