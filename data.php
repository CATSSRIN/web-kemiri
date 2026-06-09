<?php

declare(strict_types=1);

session_start();

function baseCustomers(): array
{
    return [
        [
            'id' => 'CUST-001',
            'name' => 'Toko Sehat Makmur',
            'email' => 'sehat.makmur@example.com',
            'phone' => '081200000001',
            'address' => 'Jl. Melati No. 11, Bandung',
        ],
        [
            'id' => 'CUST-002',
            'name' => 'Klinik Herbal Nusantara',
            'email' => 'herbal.nusantara@example.com',
            'phone' => '081200000002',
            'address' => 'Jl. Kenanga No. 8, Jakarta',
        ],
        [
            'id' => 'CUST-003',
            'name' => 'Pomade Corner Surabaya',
            'email' => 'pomade.corner@example.com',
            'phone' => '081200000003',
            'address' => 'Jl. Pahlawan No. 77, Surabaya',
        ],
    ];
}

function baseProducts(): array
{
    return [
        ['id' => 'PRD-001', 'name' => 'Minyak Kemiri Original 100ml', 'price' => 55000.0],
        ['id' => 'PRD-002', 'name' => 'Pomade Strong Hold 80gr', 'price' => 65000.0],
        ['id' => 'PRD-003', 'name' => 'Minyak Kemiri Premium 60ml', 'price' => 45000.0],
    ];
}

function bootstrapSessionData(): void
{
    $_SESSION['customers'] ??= baseCustomers();
    $_SESSION['products'] ??= baseProducts();
    $_SESSION['personalized_prices'] ??= [
        'CUST-001' => ['PRD-001' => 53000.0],
        'CUST-002' => ['PRD-002' => 62000.0, 'PRD-003' => 43000.0],
    ];
    $_SESSION['delivery_orders'] ??= [];
    $_SESSION['invoices'] ??= [];
}

function allCustomers(): array
{
    return $_SESSION['customers'];
}

function allProducts(): array
{
    return $_SESSION['products'];
}

function personalizedPrices(): array
{
    return $_SESSION['personalized_prices'];
}

function deliveryOrders(): array
{
    return $_SESSION['delivery_orders'];
}

function invoices(): array
{
    return $_SESSION['invoices'];
}

function findById(array $rows, string $id): ?array
{
    foreach ($rows as $row) {
        if ($row['id'] === $id) {
            return $row;
        }
    }

    return null;
}

function buildProductCode(string $customerName, string $productId): string
{
    preg_match_all('/[A-Za-z]/', strtoupper($customerName), $matches);
    $letters = implode('', $matches[0]);
    $prefix = substr($letters, 0, 3);
    $prefix = str_pad($prefix, 3, 'X');

    return $productId . '-' . $prefix;
}

function nextDocumentId(string $prefix, int $count): string
{
    return sprintf('%s-%s-%03d', $prefix, date('Ymd'), $count + 1);
}

function formatRupiah(float $value): string
{
    return 'Rp ' . number_format($value, 0, ',', '.');
}
