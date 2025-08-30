<?php
$category = readData("category");
$product = readData("product");

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    $cartData = json_decode($_POST['cart'], true);

    if (!$cartData) {
        echo json_encode(['success' => false, 'msg' => 'Keranjang kosong']);
        exit;
    }

    // trx_id acak 8 digit
    $trx_id = mt_rand(10000000, 99999999);

    // buat product_id string sesuai qty
    $product_ids = [];
    foreach ($cartData as $item) {
        for ($i=0;$i<$item['qty'];$i++){
            $product_ids[] = $item['id'];
        }
    }
    $product_id_str = implode(',', $product_ids);

    // total harga
    $total_price = array_sum(array_map(fn($i)=>$i['price']*$i['qty'], $cartData));

    // simpan transaksi
    $result = createData("transactions", [
        "trx_id" => $trx_id,
        "product_id" => $product_id_str,
        "price" => $total_price
    ]);

    if ($result) {
        echo json_encode(['success'=>true,'trx_id'=>$trx_id]);
    } else {
        echo json_encode(['success'=>false,'msg'=>'Gagal simpan transaksi']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Etalase Produk</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-50 text-gray-800">
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

<div class="container mx-auto p-4">

  <!-- Category -->
  <div class="flex gap-3 overflow-x-auto pb-3 mb-5">
    <button onclick="filterCategory('all')" class="category-btn px-4 py-2 rounded-full text-sm whitespace-nowrap bg-blue-600 text-white">Semua</button>
    <?php foreach($category as $cat): ?>
      <button onclick="filterCategory(<?= $cat['id'] ?>)" class="category-btn px-4 py-2 rounded-full text-sm whitespace-nowrap bg-gray-200 text-gray-700 hover:bg-blue-600 hover:text-white transition">
        <?= htmlspecialchars($cat['category_name']) ?>
      </button>
    <?php endforeach; ?>
  </div>

  <!-- Product Grid -->
  <div id="product-list" class="grid gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
    <?php foreach($product as $p): ?>
      <div class="bg-white rounded-2xl shadow hover:shadow-lg transition p-4 flex flex-col product-card" data-category="<?= $p['category_id'] ?>">
        <img src="<?= htmlspecialchars($p['img_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="rounded-lg mb-4 object-cover w-full h-40">
        <h2 class="font-semibold text-lg mb-1"><?= htmlspecialchars($p['name']) ?></h2>
        <p class="text-gray-500 text-sm">Stok: <span class="font-medium"><?= $p['stock'] ?></span></p>
        <p class="text-gray-500 text-sm">Harga Beli: <span class="font-medium">Rp <?= number_format($p['buy']) ?></span></p>
        <p class="text-gray-700 text-base font-bold mb-4">Harga Jual: Rp <?= number_format($p['sell']) ?></p>
        <button onclick="addToCart(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name']) ?>', <?= $p['sell'] ?>, '<?= htmlspecialchars($p['img_url']) ?>')" 
          class="mt-auto bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Masukkan Keranjang</button>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Panel Keranjang -->
<div id="cart-panel" class="fixed bottom-0 left-0 right-0 bg-white rounded-t-2xl shadow-2xl transform translate-y-full transition-transform duration-300 max-h-[70vh] flex flex-col">
  <!-- Header Panel -->
  <div class="p-4 border-b flex justify-between items-center cursor-pointer" onclick="toggleCartPanel()">
    <h2 class="font-semibold text-lg">Keranjang</h2>
    <div class="flex items-center gap-2">
      <span id="cart-count" class="bg-blue-600 text-white text-xs px-2 py-1 rounded-full">0</span>
      <button id="toggle-btn" class="text-xl">⬆️</button>
    </div>
  </div>

  <!-- List Produk -->
  <div id="cart-items" class="overflow-y-auto p-4 flex-1 space-y-3"></div>

  <!-- Footer Panel -->
  <div class="p-4 border-t">
    <div class="flex justify-between mb-3">
      <span class="font-medium">Total:</span>
      <span id="cart-total" class="font-bold">Rp 0</span>
    </div>
    <button onclick="checkout()" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700">Lanjutkan</button>
  </div>
</div>

<script>
lucide.createIcons();

let cart = {};
let cartOpen = true;

// Filter kategori
function filterCategory(catId) {
  document.querySelectorAll(".product-card").forEach(card => {
    if (catId === 'all' || card.dataset.category == catId) {
      card.style.display = "flex";
    } else {
      card.style.display = "none";
    }
  });
  // Update highlight category
  document.querySelectorAll(".category-btn").forEach(btn => btn.classList.remove("bg-blue-600","text-white"));
  event.target.classList.add("bg-blue-600","text-white");
  event.target.classList.remove("bg-gray-200","text-gray-700");
}

// Keranjang
function addToCart(id, name, price, img) {
  if (!cart[id]) cart[id] = { id, name, price, img, qty: 1 };
  else cart[id].qty++;
  renderCart();
}

function removeFromCart(id) {
  delete cart[id];
  renderCart();
}

function updateQty(id, qty) {
  if (qty <= 0) removeFromCart(id);
  else cart[id].qty = qty;
  renderCart();
}

function renderCart() {
  const cartItemsEl = document.getElementById("cart-items");
  const cartCountEl = document.getElementById("cart-count");
  const cartTotalEl = document.getElementById("cart-total");

  let totalItems = 0;
  let totalPrice = 0;
  cartItemsEl.innerHTML = "";

  for (let id in cart) {
    const item = cart[id];
    totalItems += item.qty;
    totalPrice += item.qty * item.price;

    cartItemsEl.innerHTML += `
      <div class="flex items-center gap-3">
        <img src="${item.img}" class="w-14 h-14 rounded-lg object-cover">
        <div class="flex-1">
          <h3 class="font-medium">${item.name}</h3>
          <p class="text-sm text-gray-500">Rp ${item.price.toLocaleString()}</p>
          <div class="flex items-center gap-2 mt-1">
            <button onclick="removeFromCart(${item.id})" class="text-red-500 hover:text-red-700"><i data-lucide="trash-2"></i></button>
            <button onclick="updateQty(${item.id}, ${item.qty-1})" class="px-2 py-1 bg-gray-200 rounded">-</button>
            <input type="number" value="${item.qty}" min="1" onchange="updateQty(${item.id}, this.value)" class="w-12 text-center border rounded">
            <button onclick="updateQty(${item.id}, ${item.qty+1})" class="px-2 py-1 bg-gray-200 rounded">+</button>
          </div>
        </div>
      </div>
    `;
  }

  cartCountEl.textContent = totalItems;
  cartTotalEl.textContent = "Rp " + totalPrice.toLocaleString();

  const panel = document.getElementById("cart-panel");
  if (totalItems > 0) panel.classList.remove("translate-y-full");
  else panel.classList.add("translate-y-full");

  lucide.createIcons();
}

// Toggle panel
function toggleCartPanel() {
  const panel = document.getElementById("cart-panel");
  const toggleBtn = document.getElementById("toggle-btn");
  if (cartOpen) {
    panel.style.transform = "translateY(calc(100% - 60px))"; // minimize
    toggleBtn.textContent = "⬆️";
    cartOpen = false;
  } else {
    panel.style.transform = "translateY(0)"; // expand
    toggleBtn.textContent = "⬇️";
    cartOpen = true;
  }
}

// Checkout
function checkout() {
  if (Object.keys(cart).length === 0) {
    alert("Keranjang kosong!");
    return;
  }

  const cartArray = Object.values(cart);
  const formData = new FormData();
  formData.append('action','checkout');
  formData.append('cart', JSON.stringify(cartArray));

  fetch('', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      window.location.href = "/invoice?cart=" + data.trx_id;
    } else {
      alert(data.msg || "Gagal checkout");
    }
  })
  .catch(err => console.error(err));
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