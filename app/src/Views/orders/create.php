<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yeni Sipariş Oluştur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <h1 class="mb-4">Yeni Sipariş Oluştur (Çok Ürünlü)</h1>

    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success">
            <?= $successMessage ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars((string)$errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="card p-4 shadow-sm bg-white" action="index.php?c=orders&a=store">
        <div class="mb-3">
            <label for="customer_id" class="form-label">Müşteri</label>
            <select name="customer_id" id="customer_id" class="form-select" required>
                <option value="">-- Seç --</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?= (int)$c['id'] ?>">
                        <?= htmlspecialchars((string)$c['name']) ?> (<?= htmlspecialchars((string)$c['city']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="warehouse_id" class="form-label">Depo</label>
            <select name="warehouse_id" id="warehouse_id" class="form-select" required>
                <option value="">-- Seç --</option>
                <?php foreach ($warehouses as $w): ?>
                    <option value="<?= (int)$w['id'] ?>">
                        <?= htmlspecialchars((string)$w['name']) ?> (<?= htmlspecialchars((string)$w['city']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <h4 class="mt-4">Ürünler</h4>

        <table class="table align-middle" id="items-table">
            <thead>
                <tr>
                    <th style="width:60%;">Ürün</th>
                    <th style="width:20%;">Adet</th>
                    <th style="width:15%;">Birim Fiyat</th>
                    <th style="width:5%;"></th>
                </tr>
            </thead>
            <tbody id="items-body"></tbody>
        </table>

        <button type="button" class="btn btn-secondary btn-sm mb-3" id="add-row">+ Satır Ekle</button>

        <div class="text-end mb-3">
            <strong>Tahmini Ürün Toplamı: </strong>
            <span id="items-total">0.00</span> ₺
        </div>

        <button type="submit" class="btn btn-primary">Siparişi Oluştur</button>
        <a href="index.php?c=orders&a=index" class="btn btn-secondary ms-2">Geri Dön</a>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const products = <?= json_encode($products, JSON_UNESCAPED_UNICODE) ?>;

let rowIndex = 0;

function createRow(index) {
    const tbody = document.getElementById('items-body');
    const tr = document.createElement('tr');

    const tdProduct = document.createElement('td');
    const select = document.createElement('select');
    select.name = `items[${index}][product_id]`;
    select.className = 'form-select';
    select.required = true;

    const optEmpty = document.createElement('option');
    optEmpty.value = '';
    optEmpty.textContent = '-- Ürün seç --';
    select.appendChild(optEmpty);

    products.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = `${p.name} - ${p.sku}`;
        opt.dataset.price = p.price;
        select.appendChild(opt);
    });

    tdProduct.appendChild(select);

    const tdQty = document.createElement('td');
    const inputQty = document.createElement('input');
    inputQty.type = 'number';
    inputQty.name = `items[${index}][quantity]`;
    inputQty.min = '1';
    inputQty.value = '1';
    inputQty.required = true;
    inputQty.className = 'form-control';
    tdQty.appendChild(inputQty);

    const tdUnit = document.createElement('td');
    tdUnit.className = 'text-end unit-price-cell';
    tdUnit.textContent = '0.00 ₺';

    const tdActions = document.createElement('td');
    const btnDel = document.createElement('button');
    btnDel.type = 'button';
    btnDel.className = 'btn btn-danger btn-sm';
    btnDel.textContent = 'Sil';
    btnDel.onclick = function() {
        tr.remove();
        recalcTotals();
    };
    tdActions.appendChild(btnDel);

    tr.appendChild(tdProduct);
    tr.appendChild(tdQty);
    tr.appendChild(tdUnit);
    tr.appendChild(tdActions);

    select.addEventListener('change', recalcTotals);
    inputQty.addEventListener('input', recalcTotals);

    tbody.appendChild(tr);
    recalcTotals();
}

function recalcTotals() {
    const rows = document.querySelectorAll('#items-body tr');
    let total = 0;

    rows.forEach(row => {
        const select = row.querySelector('select');
        const qtyInput = row.querySelector('input[type="number"]');
        const unitCell = row.querySelector('.unit-price-cell');

        const qty = parseInt(qtyInput.value || '0', 10);
        let unitPrice = 0;

        if (select.value) {
            const opt = select.selectedOptions[0];
            unitPrice = parseFloat(opt.dataset.price || '0');
        }

        total += unitPrice * (isNaN(qty) ? 0 : qty);
        unitCell.textContent = unitPrice.toFixed(2) + ' ₺';
    });

    document.getElementById('items-total').textContent = total.toFixed(2);
}

document.getElementById('add-row').addEventListener('click', function () {
    createRow(rowIndex++);
});

createRow(rowIndex++);
</script>
</body>
</html>
