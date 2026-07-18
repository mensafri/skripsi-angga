# Dashboard Skripsi — Klasterisasi Tingkat Gangguan Jaringan (K-Means)

Dashboard web untuk menyajikan **apa yang dikerjakan pada penelitian ini sampai
hasilnya**: memonitor trafik jaringan, mengolah datanya, lalu mengelompokkan
kondisi jaringan menjadi tiga tingkat gangguan memakai algoritma **K-Means**.

Dibangun dengan **Laravel 13 + MySQL** dan dijalankan lewat **Docker (Laravel Sail)**.
Frontend memakai **Blade + Tailwind CSS v4 + Chart.js** (di-bundle Vite, tanpa CDN).

---

## Isi penelitian (ringkasan)

- **Sumber data:** trafik pada interface `bridge-10.5.0.1` dimonitor via SNMP
  selama **4 hari kerja** (Senin–Kamis), merekam metrik setiap ~3 menit.
- **Fitur yang dianalisis:** `latency_ms`, `packet_loss_percent`, dan
  `total_traffic_mbps` (throughput agregat = bits sent + bits received).
- **Pra-pemrosesan:** data dibersihkan menjadi **590 sampel**, lalu distandarisasi
  dengan **StandardScaler (Z-score)**.
- **Model:** **K-Means** (`k=3`, `random_state=42`, `n_init=10`), WCSS ≈ `752.38`.
- **Hasil — 3 tingkat gangguan:**

  | Tingkat | Jumlah | Persentase | Ciri (centroid) |
  |---------|-------:|-----------:|-----------------|
  | Rendah  | 523    | 88,64%     | latency ~1,2 ms · packet loss 0% |
  | Sedang  | 58     | 9,83%      | latency ~2,4 ms · throughput tinggi |
  | Tinggi  | 9      | 1,53%      | packet loss ~11% · latency tertinggi |

Sumber data mentah ada di folder [`Data Penelitian/`](Data%20Penelitian/)
(CSV per hari, grafik, output Google Colab, dokumen Tugas Akhir).

## Apa yang ditampilkan dashboard

- Ringkasan metodologi & parameter K-Means.
- Kartu hasil kunci (total sampel, rata-rata latency, packet loss, jumlah gangguan tinggi).
- Distribusi klaster (donut) & tabel centroid.
- Time series latency & throughput (titik diwarnai per tingkat gangguan).
- Scatter latency vs throughput per klaster.
- Komposisi gangguan per hari.
- Tabel sampel gangguan tinggi + tabel data lengkap (paginasi).

---

## Prasyarat

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (berjalan).
- Tidak perlu PHP/Composer/Node di host — semuanya jalan di dalam container.

## Cara menjalankan (Docker / Laravel Sail)

### 1. Siapkan environment

```bash
cp .env.example .env
```

### 2. Install dependency PHP (sekali di awal)

`vendor/` tidak ikut di-commit, jadi bootstrap dulu memakai container composer:

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php85-composer:latest \
    composer install --ignore-platform-reqs
```

### 3. Nyalakan container

```bash
./vendor/bin/sail up -d
```

> Build image pertama kali butuh beberapa menit. Aplikasi jalan di port `8080`
> (MySQL di `3306`) — lihat `APP_PORT` / `FORWARD_DB_PORT` di `.env`.

### 4. Generate app key, migrasi + import data, build aset

```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

`migrate --seed` membuat tabel `network_measurements` dan mengimpor 590 sampel
hasil klasterisasi dari `database/data/hasil_klasterisasi.csv`.

### 5. Buka dashboard

```
http://localhost:8080
```

---

## Perintah berguna

```bash
./vendor/bin/sail up -d          # start
./vendor/bin/sail down           # stop
./vendor/bin/sail artisan migrate:fresh --seed   # reset + re-import data
./vendor/bin/sail npm run dev     # hot reload aset saat mengembangkan UI
./vendor/bin/sail artisan tinker  # REPL
```

> Tip: buat alias `alias sail='./vendor/bin/sail'` agar lebih singkat.

## Struktur kode utama

| Path | Fungsi |
|------|--------|
| `app/Http/Controllers/DashboardController.php` | Query agregat untuk dashboard |
| `app/Models/NetworkMeasurement.php` | Model 1 sampel jaringan berlabel klaster |
| `config/research.php` | Parameter & hasil resmi penelitian (dari ringkasan Colab) |
| `database/migrations/*_create_network_measurements_table.php` | Skema tabel |
| `database/seeders/NetworkMeasurementSeeder.php` | Import CSV → MySQL |
| `database/data/hasil_klasterisasi.csv` | Dataset 590 sampel berlabel |
| `resources/views/dashboard.blade.php` | Halaman dashboard |
| `resources/js/dashboard.js` | Konfigurasi grafik Chart.js |

## Stack

Laravel 13 · PHP 8.5 · MySQL 8.4 · Laravel Sail (Docker) · Tailwind CSS v4 · Vite · Chart.js
