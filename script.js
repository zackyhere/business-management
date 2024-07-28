const ngrokUrl = 'https://4020-114-5-110-64.ngrok-free.app'; // Ganti dengan URL ngrok Anda

// Fetch daftar harga
fetch(`${ngrokUrl}/price-list`)
    .then(response => response.json())
    .then(data => {
        const priceList = document.getElementById('price-list');
        data.data.forEach(item => {
            const div = document.createElement('div');
            div.textContent = `${item.product_name} - ${item.price}`;
            priceList.appendChild(div);
        });
    })
    .catch(error => console.error('Error:', error));

// Handle form submission for top-up
document.getElementById('topup-form').addEventListener('submit', function(event) {
    event.preventDefault();
    
    const buyerSkuCode = document.getElementById('buyer_sku_code').value;
    const customerNo = document.getElementById('customer_no').value;
    const refId = document.getElementById('ref_id').value;

    const topupData = {
        buyer_sku_code: buyerSkuCode,
        customer_no: customerNo,
        ref_id: refId
    };

    fetch(`${ngrokUrl}/transaction`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(topupData)
    })
    .then(response => response.json())
    .then(data => {
        const resultDiv = document.getElementById('topup-result');
        resultDiv.textContent = `Status: ${data.data.status}, Message: ${data.data.message}`;
    })
    .catch(error => console.error('Error:', error));
});
