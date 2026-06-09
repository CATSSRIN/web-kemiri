<?php

declare(strict_types=1);

require __DIR__ . '/data.php';

bootstrapSessionData();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'set_personalized_price') {
        $customerId = trim((string) ($_POST['customer_id'] ?? ''));
        $productId = trim((string) ($_POST['product_id'] ?? ''));
        $priceInput = trim((string) ($_POST['price'] ?? ''));

        if ($customerId === '' || $productId === '' || $priceInput === '' || !is_numeric($priceInput) || (float) $priceInput <= 0) {
            $error = 'Data personalized pricing tidak valid.';
        } else {
            $_SESSION['personalized_prices'][$customerId][$productId] = (float) $priceInput;
            $message = 'Personalized pricing berhasil disimpan.';
        }
    }

    if ($action === 'create_do') {
        $customerId = trim((string) ($_POST['customer_id'] ?? ''));
        $productId = trim((string) ($_POST['product_id'] ?? ''));
        $qtyInput = trim((string) ($_POST['qty'] ?? ''));
        $qty = ctype_digit($qtyInput) ? (int) $qtyInput : 0;

        $customer = findById(allCustomers(), $customerId);
        $product = findById(allProducts(), $productId);

        if ($customer === null || $product === null || $qty <= 0) {
            $error = 'Data untuk membuat DO tidak valid.';
        } else {
            $prices = personalizedPrices();
            $unitPrice = $prices[$customerId][$productId] ?? $product['price'];
            $doId = nextDocumentId('DO', count(deliveryOrders()));

            $_SESSION['delivery_orders'][] = [
                'id' => $doId,
                'customer_id' => $customerId,
                'customer_name' => $customer['name'],
                'product_id' => $productId,
                'product_name' => $product['name'],
                'product_code' => buildProductCode($customer['name'], $productId),
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'total' => $unitPrice * $qty,
                'invoice_id' => null,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            $message = 'DO berhasil dibuat.';
        }
    }

    if ($action === 'create_invoice') {
        $doId = trim((string) ($_POST['do_id'] ?? ''));
        $doIndex = null;

        foreach (deliveryOrders() as $index => $order) {
            if ($order['id'] === $doId) {
                $doIndex = $index;
                break;
            }
        }

        if ($doIndex === null) {
            $error = 'DO tidak ditemukan.';
        } elseif ($_SESSION['delivery_orders'][$doIndex]['invoice_id'] !== null) {
            $error = 'Invoice untuk DO ini sudah dibuat.';
        } else {
            $invoiceId = nextDocumentId('INV', count(invoices()));
            $do = $_SESSION['delivery_orders'][$doIndex];

            $_SESSION['invoices'][] = [
                'id' => $invoiceId,
                'do_id' => $do['id'],
                'customer_name' => $do['customer_name'],
                'product_name' => $do['product_name'],
                'product_code' => $do['product_code'],
                'qty' => $do['qty'],
                'unit_price' => $do['unit_price'],
                'total' => $do['total'],
                'created_at' => date('Y-m-d H:i:s'),
            ];

            $_SESSION['delivery_orders'][$doIndex]['invoice_id'] = $invoiceId;
            $message = 'Invoice berhasil dibuat dari DO.';
        }
    }
}

$customers = allCustomers();
$products = allProducts();
$prices = personalizedPrices();
$orders = deliveryOrders();
$allInvoices = invoices();

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Web Kemiri - DO & Invoicing</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; background: #f8f9fc; color: #1f2d3d; }
        h1, h2 { margin-bottom: 10px; }
        .box { background: #fff; border-radius: 8px; padding: 16px; margin-bottom: 16px; box-shadow: 0 2px 6px rgba(0,0,0,.08); }
        table { border-collapse: collapse; width: 100%; margin-top: 8px; }
        th, td { border: 1px solid #d6d9df; padding: 8px; font-size: 14px; text-align: left; vertical-align: top; }
        th { background: #eff2f7; }
        form.inline { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        select, input, button { padding: 6px 8px; }
        button { cursor: pointer; background: #2f6fed; color: #fff; border: 0; border-radius: 4px; }
        .msg { color: #0a7b34; margin: 8px 0; }
        .err { color: #b42318; margin: 8px 0; }
        .muted { color: #667085; }
    </style>
</head>
<body>
    <h1>Web Kemiri (PHP Native)</h1>
    <p class="muted">Alur: buat Surat Jalan (DO) terlebih dulu, lalu tarik ke Invoicing. Relasi dijaga 1 DO : 1 Invoice.</p>

    <?php if ($message !== ''): ?><p class="msg"><?= e($message) ?></p><?php endif; ?>
    <?php if ($error !== ''): ?><p class="err"><?= e($error) ?></p><?php endif; ?>

    <div class="box">
        <h2>Master Data Produk</h2>
        <table>
            <tr><th>ID Produk</th><th>Nama Produk</th><th>Harga Dasar</th></tr>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= e($product['id']) ?></td>
                    <td><?= e($product['name']) ?></td>
                    <td><?= e(formatRupiah((float) $product['price'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="box">
        <h2>Master Data Pelanggan (Dummy)</h2>
        <table>
            <tr><th>ID</th><th>Nama</th><th>Email</th><th>No Telp</th><th>Alamat</th><th>Personalized Pricing</th></tr>
            <?php foreach ($customers as $customer): ?>
                <tr>
                    <td><?= e($customer['id']) ?></td>
                    <td><?= e($customer['name']) ?></td>
                    <td><?= e($customer['email']) ?></td>
                    <td><?= e($customer['phone']) ?></td>
                    <td><?= e($customer['address']) ?></td>
                    <td>
                        <?php if (isset($prices[$customer['id']])): ?>
                            <?php foreach ($prices[$customer['id']] as $productId => $price): ?>
                                <div><?= e($productId) ?>: <?= e(formatRupiah((float) $price)) ?></div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="muted">Belum ada harga khusus</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <h3>Set Personalized Pricing</h3>
        <form method="post" class="inline">
            <input type="hidden" name="action" value="set_personalized_price">
            <select name="customer_id" required>
                <option value="">Pilih Pelanggan</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?= e($customer['id']) ?>"><?= e($customer['id'] . ' - ' . $customer['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="product_id" required>
                <option value="">Pilih Produk</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= e($product['id']) ?>"><?= e($product['id'] . ' - ' . $product['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="price" min="1" step="1" placeholder="Harga khusus" required>
            <button type="submit">Simpan Harga</button>
        </form>
    </div>

    <div class="box">
        <h2>Buat Surat Jalan (DO)</h2>
        <form method="post" class="inline">
            <input type="hidden" name="action" value="create_do">
            <select name="customer_id" required>
                <option value="">Pilih Pelanggan</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?= e($customer['id']) ?>"><?= e($customer['id'] . ' - ' . $customer['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="product_id" required>
                <option value="">Pilih Produk</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= e($product['id']) ?>"><?= e($product['id'] . ' - ' . $product['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="qty" min="1" step="1" placeholder="Qty" required>
            <button type="submit">Buat DO</button>
        </form>
    </div>

    <div class="box">
        <h2>Daftar DO</h2>
        <table>
            <tr><th>ID DO</th><th>Pelanggan</th><th>Produk</th><th>Kode Produk (Pelanggan)</th><th>Qty</th><th>Harga</th><th>Total</th><th>Invoice</th><th>Aksi</th></tr>
            <?php if (count($orders) === 0): ?>
                <tr><td colspan="9" class="muted">Belum ada DO.</td></tr>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= e($order['id']) ?></td>
                        <td><?= e($order['customer_name']) ?></td>
                        <td><?= e($order['product_name']) ?></td>
                        <td><?= e($order['product_code']) ?></td>
                        <td><?= e((string) $order['qty']) ?></td>
                        <td><?= e(formatRupiah((float) $order['unit_price'])) ?></td>
                        <td><?= e(formatRupiah((float) $order['total'])) ?></td>
                        <td><?= e((string) ($order['invoice_id'] ?? '-')) ?></td>
                        <td>
                            <?php if ($order['invoice_id'] === null): ?>
                                <form method="post">
                                    <input type="hidden" name="action" value="create_invoice">
                                    <input type="hidden" name="do_id" value="<?= e($order['id']) ?>">
                                    <button type="submit">Tarik ke Invoice</button>
                                </form>
                            <?php else: ?>
                                <span class="muted">Sudah ditarik</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>

    <div class="box">
        <h2>Daftar Invoice (dari DO)</h2>
        <table>
            <tr><th>ID Invoice</th><th>Ref DO</th><th>Pelanggan</th><th>Produk</th><th>Kode Produk</th><th>Qty</th><th>Harga</th><th>Total</th></tr>
            <?php if (count($allInvoices) === 0): ?>
                <tr><td colspan="8" class="muted">Belum ada Invoice.</td></tr>
            <?php else: ?>
                <?php foreach ($allInvoices as $invoice): ?>
                    <tr>
                        <td><?= e($invoice['id']) ?></td>
                        <td><?= e($invoice['do_id']) ?></td>
                        <td><?= e($invoice['customer_name']) ?></td>
                        <td><?= e($invoice['product_name']) ?></td>
                        <td><?= e($invoice['product_code']) ?></td>
                        <td><?= e((string) $invoice['qty']) ?></td>
                        <td><?= e(formatRupiah((float) $invoice['unit_price'])) ?></td>
                        <td><?= e(formatRupiah((float) $invoice['total'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>
