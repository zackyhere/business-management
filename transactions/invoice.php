<?php
$trx_id = $_GET['cart'] ?? null;
if (!$trx_id) {
    die("Transaksi tidak ditemukan");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    header('Content-Type: application/json; charset=utf-8');

    if ($action === 'cancel') {
        $trx = $_POST['trx'] ?? null;
        if (!$trx) {
            echo json_encode(['success' => false, 'msg' => 'trx kosong']);
            exit;
        }
        $ok = deleteData("transactions", ["trx_id" => $trx]);
        if ($ok) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'Gagal menghapus transaksi']);
        }
        exit;
    }

    if ($action === 'process') {
        $trx = $_POST['trx'] ?? null;
        $bayar = isset($_POST['bayar']) ? (float)$_POST['bayar'] : null;
        $kembalian = isset($_POST['kembalian']) ? (float)$_POST['kembalian'] : null;
        if (!$trx || $bayar === null || $kembalian === null) {
            echo json_encode(['success' => false, 'msg' => 'Data tidak lengkap']);
            exit;
        }

        $ok = updateData("transactions", [
            "trx_id" => $trx,
            "bayar" => $bayar,
            "kembalian" => $kembalian
        ]);

        if ($ok) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'Gagal update transaksi']);
        }
        exit;
    }
}

$trx = readData("transactions", ["trx_id" => $trx_id]);
if (!$trx || count($trx) === 0) {
    die("Transaksi tidak ditemukan");
}
$trx = $trx[0];

$product_ids = array_filter(explode(",", $trx['product_id']));
$products_all = readData("product");

$items = [];
foreach ($product_ids as $pid) {
    foreach ($products_all as $p) {
        if ((string)$p['id'] === (string)$pid) {
            if (!isset($items[$pid])) {
                $items[$pid] = [
                    "id" => $p['id'],
                    "name" => $p['name'],
                    "price" => $p['sell'],
                    "qty" => 0,
                    "subtotal" => 0
                ];
            }
            $items[$pid]['qty']++;
            $items[$pid]['subtotal'] = $items[$pid]['qty'] * $items[$pid]['price'];
            break;
        }
    }
}

$total_price = (float)$trx['price'];
$bayar_saved = isset($trx['bayar']) && $trx['bayar'] !== '' ? (float)$trx['bayar'] : null;
$kembalian_saved = isset($trx['kembalian']) && $trx['kembalian'] !== '' ? (float)$trx['kembalian'] : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Invoice #<?= htmlspecialchars($trx['trx_id']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">
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

  <div class="max-w-4xl mx-auto p-4">
    <div class="bg-white rounded-xl shadow-md overflow-hidden mt-8">

      <div class="px-6 py-5 border-b">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
          <div>
            <h1 class="text-2xl font-semibold">Invoice</h1>
            <p class="text-sm text-gray-500">No. Transaksi: <span class="font-medium"><?= htmlspecialchars($trx['trx_id']) ?></span></p>
            <p class="text-sm text-gray-500">Tanggal: <span class="font-medium"><?= htmlspecialchars($trx['date']) ?></span></p>
          </div>
          <div class="flex items-center gap-3">

            <button id="btn-cancel" class="bg-red-100 text-red-600 px-3 py-2 rounded-md hover:bg-red-200 transition">
              Batalkan
            </button>
            
            <button id="btn-back" onclick="history.back()" class="bg-gray-50 text-gray-700 px-3 py-2 rounded-md hover:bg-gray-100 transition">
              Kembali
            </button>
          </div>
        </div>
      </div>


      <div class="p-6">

        <div class="overflow-x-auto">
          <table class="w-full text-sm table-auto">
            <thead>
              <tr class="bg-gray-50">
                <th class="px-4 py-3 text-left">Produk</th>
                <th class="px-4 py-3 text-center">Qty</th>
                <th class="px-4 py-3 text-right">Harga</th>
                <th class="px-4 py-3 text-right">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $it): ?>
              <tr class="border-b">
                <td class="px-4 py-3"><?= htmlspecialchars($it['name']) ?></td>
                <td class="px-4 py-3 text-center"><?= $it['qty'] ?></td>
                <td class="px-4 py-3 text-right">Rp <?= number_format($it['price'], 0, ',', '.') ?></td>
                <td class="px-4 py-3 text-right">Rp <?= number_format($it['subtotal'], 0, ',', '.') ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="mt-4 flex justify-end">
          <div class="text-right">
            <div class="text-gray-500">Total</div>
            <div class="text-2xl font-bold">Rp <?= number_format($total_price, 0, ',', '.') ?></div>
          </div>
        </div>

        <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-3 items-center">
          <input id="input-bayar" type="number" min="0" placeholder="Masukkan jumlah bayar (Rp)"
                 value="<?= $bayar_saved !== null ? htmlspecialchars($bayar_saved) : '' ?>"
                 class="col-span-1 sm:col-span-2 border rounded-lg px-3 py-2 focus:ring focus:ring-blue-200 outline-none" <?= $bayar_saved !== null ? 'disabled' : '' ?>>

          <button id="btn-lanjut" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition <?= $bayar_saved !== null ? 'opacity-60 cursor-not-allowed' : '' ?>">
            Lanjutkan
          </button>
        </div>

        <div id="summary-box" class="mt-6 bg-gray-50 border rounded-lg p-4 hidden">
          <h3 class="font-semibold mb-3">Ringkasan Pembayaran</h3>
          <div class="grid grid-cols-2 gap-2 text-sm">
            <div class="text-gray-500">Total:</div>
            <div class="text-right font-medium" id="summary-total">Rp <?= number_format($total_price,0,',','.') ?></div>

            <div class="text-gray-500">Dibayar:</div>
            <div class="text-right font-medium" id="summary-bayar">Rp 0</div>

            <div class="text-gray-500">Kembalian:</div>
            <div class="text-right font-medium text-green-600" id="summary-kembalian">Rp 0</div>
          </div>

          <div class="mt-4 flex justify-end gap-2">
            <button id="btn-process" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition">
              Process
            </button>
            <button id="btn-edit" class="bg-gray-50 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-100 transition">
              Edit
            </button>
          </div>
        </div>

        <?php if ($bayar_saved !== null): ?>
          <div class="mt-6 bg-white border rounded-lg p-4">
            <h3 class="font-semibold mb-2">Pembayaran Tersimpan</h3>
            <div class="grid grid-cols-2 gap-2 text-sm">
              <div class="text-gray-500">Total:</div>
              <div class="text-right font-medium">Rp <?= number_format($total_price,0,',','.') ?></div>

              <div class="text-gray-500">Dibayar:</div>
              <div class="text-right font-medium">Rp <?= number_format($bayar_saved,0,',','.') ?></div>

              <div class="text-gray-500">Kembalian:</div>
              <div class="text-right font-medium">Rp <?= number_format($kembalian_saved ?? 0,0,',','.') ?></div>
            </div>
          </div>
        <?php endif; ?>

      </div>

      <div class="px-6 py-4 border-t text-center text-sm text-gray-500">
        Toprion • Digital Store
      </div>
    </div>
  </div>

<script>
function formatIDR(n) {
  return new Intl.NumberFormat('id-ID').format(n);
}

const totalPrice = <?= json_encode($total_price) ?>;
const trxId = <?= json_encode($trx_id) ?>;
const inputBayar = document.getElementById('input-bayar');
const btnLanjut = document.getElementById('btn-lanjut');
const summaryBox = document.getElementById('summary-box');
const summaryTotalEl = document.getElementById('summary-total');
const summaryBayarEl = document.getElementById('summary-bayar');
const summaryKembalianEl = document.getElementById('summary-kembalian');
const btnProcess = document.getElementById('btn-process');
const btnEdit = document.getElementById('btn-edit');
const btnCancel = document.getElementById('btn-cancel');

summaryTotalEl.textContent = 'Rp ' + formatIDR(totalPrice);

btnLanjut && btnLanjut.addEventListener('click', (e) => {
  e.preventDefault();
  if (inputBayar.disabled) return;

  const bayarVal = Number(inputBayar.value || 0);
  if (isNaN(bayarVal) || bayarVal <= 0) {
    alert('Masukkan jumlah bayar yang valid.');
    inputBayar.focus();
    return;
  }

  const kembalian = bayarVal - totalPrice;

  summaryBayarEl.textContent = 'Rp ' + formatIDR(bayarVal);
  summaryKembalianEl.textContent = 'Rp ' + formatIDR(kembalian < 0 ? 0 : kembalian);
  summaryBox.classList.remove('hidden');

  summaryBox.dataset.bayar = bayarVal;
  summaryBox.dataset.kembalian = kembalian < 0 ? 0 : kembalian;

  summaryBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
});

btnEdit && btnEdit.addEventListener('click', () => {
  summaryBox.classList.add('hidden');
  inputBayar.focus();
});

btnProcess && btnProcess.addEventListener('click', () => {
  const bayar = Number(summaryBox.dataset.bayar || 0);
  const kembalian = Number(summaryBox.dataset.kembalian || 0);

  if (isNaN(bayar)) {
    alert('Nilai bayar tidak valid');
    return;
  }

  const fd = new FormData();
  fd.append('action', 'process');
  fd.append('trx', trxId);
  fd.append('bayar', bayar);
  fd.append('kembalian', kembalian);

  btnProcess.disabled = true;
  btnProcess.textContent = 'Processing...';

  fetch('', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        window.location.href = window.location.pathname + '?cart=' + encodeURIComponent(trxId);
      } else {
        alert(data.msg || 'Gagal memproses pembayaran');
        btnProcess.disabled = false;
        btnProcess.textContent = 'Process';
      }
    })
    .catch(err => {
      console.error(err);
      alert('Terjadi kesalahan jaringan');
      btnProcess.disabled = false;
      btnProcess.textContent = 'Process';
    });
});

btnCancel && btnCancel.addEventListener('click', () => {
  if (!confirm('Yakin batalkan transaksi ini? Data transaksi akan dihapus.')) return;

  const fd = new FormData();
  fd.append('action', 'cancel');
  fd.append('trx', trxId);

  btnCancel.disabled = true;
  btnCancel.textContent = 'Menghapus...';

  fetch('', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        window.location.href = '/invoice';
      } else {
        alert(data.msg || 'Gagal menghapus transaksi');
        btnCancel.disabled = false;
        btnCancel.textContent = 'Batalkan';
      }
    })
    .catch(err => {
      console.error(err);
      alert('Terjadi kesalahan jaringan');
      btnCancel.disabled = false;
      btnCancel.textContent = 'Batalkan';
    });
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