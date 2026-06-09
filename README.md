# web-kemiri

Aplikasi PHP native sederhana untuk bisnis Minyak Kemiri/Pomade dengan alur:

- Master data produk dan pelanggan (dummy data)
- Personalized pricing per pelanggan
- Pembuatan Surat Jalan (DO)
- Invoicing yang ditarik dari DO
- Relasi 1 DO : 1 Invoice

## Menjalankan aplikasi

```bash
cd /tmp/workspace/CATSSRIN/web-kemiri
php -S 127.0.0.1:8000 -t .
```

Buka `http://127.0.0.1:8000/index.php`.