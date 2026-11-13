# Mitrasova Centralized Headless Multi-Tenant CMS Backend

Backend CMS berbasis Laravel 12 untuk mensuplai data konten ke berbagai landing page Next.js unit usaha (KSP, Konveksi, Teknologi, Percetakan, Transportasi, Bengkel Las) secara terpusat.

---

## 1. Spesifikasi Teknis
* **Framework:** Laravel 12 (PHP 8.2+)
* **Database:** PostgreSQL (Production) / SQLite (Local Dev & Testing)
* **Arsitektur:** Headless Multi-Tenant REST API
* **Tenancy Identifier:** Header `X-Site-Key` (menerima `api_key` atau `slug` unit usaha)
* **Dokumentasi API:** OpenAPI 3.0.3 & Interactive Swagger UI di `/docs`
* **File Upload Limit:** Validasi Laravel maks 10MB (10240 KB) per file

---

## 2. Unit Usaha Terdaftar (Default Seeded)

| Unit Usaha | Slug | API Key Header (`X-Site-Key`) |
| :--- | :--- | :--- |
| **KSP Mitra Sejati Persada** | `ksp` | `key-ksp-12345` |
| **Mitrasova Konveksi & Garment** | `konveksi` | `key-konveksi-12345` |
| **Mitrasova Digital Solutions** | `teknologi` | `key-teknologi-12345` |
| **Mitrasova Printing** | `percetakan` | `key-percetakan-12345` |
| **Mitrasova Trans & Logistics** | `transportasi` | `key-trans-12345` |
| **Mitrasova Las & Interior Estetika** | `bengkel-las` | `key-las-12345` |

*Catatan: Frontend dapat mengirim `api_key` atau `slug` pada header `X-Site-Key`.*

---

## 3. Menjalankan Backend Secara Lokal

```bash
# 1. Jalankan migrasi & seeder
php artisan migrate:fresh --seed

# 2. Buat symlink storage untuk akses file media publik
php artisan storage:link

# 3. Jalankan server lokal
php artisan serve
```

Server aktif di `http://localhost:8000`.

---

## 4. Dokumentasi OpenAPI & Swagger UI

* **Interactive Swagger UI:** Buka di browser: `http://localhost:8000/docs`
* **OpenAPI Raw JSON:** `http://localhost:8000/openapi.json`
* Terdapat tombol **"Authorize"** di Swagger UI untuk memasukkan header `X-Site-Key` (misal: `key-ksp-12345` atau `ksp`).

---

## 5. Ringkasan Endpoint API (`/api/v1`)

Semua endpoint wajib menyertakan header `X-Site-Key: <api_key|slug>`:

| Endpoint | Method | Deskripsi |
| :--- | :--- | :--- |
| `/api/v1/settings` | `GET` | Pengaturan situs, logo, kontak, sosmed, meta SEO |
| `/api/v1/pages/{slug}` | `GET` | Detail halaman dan section landing page (hero, visi misi, cta) |
| `/api/v1/service-types` | `GET` | Master kategori/tipe layanan |
| `/api/v1/services` | `GET` | List produk/layanan (support filter `?service_type=...`) |
| `/api/v1/services/{slug}` | `GET` | Detail satu layanan/produk |
| `/api/v1/portfolios` | `GET` | List portofolio/galeri hasil kerja |
| `/api/v1/portfolios/{slug}` | `GET` | Detail portofolio dan list foto galeri |
| `/api/v1/article-categories`| `GET` | Master kategori artikel/berita |
| `/api/v1/articles` | `GET` | List artikel terbit (support pagination & filter kategori) |
| `/api/v1/articles/{slug}` | `GET` | Detail artikel |
| `/api/v1/faqs` | `GET` | List FAQ dan kategorinya |
| `/api/v1/testimonials` | `GET` | List testimoni/ulasan nasabah/klien |
| `/api/v1/partners` | `GET` | List logo partner/klien |
| `/api/v1/inquiries` | `POST` | Kirim pesan kontak / permintaan penawaran (dukung upload lampiran maks 10MB) |

---

## 6. Konfigurasi Upload Max Filesize di Server (VPS Tencent Cloud)

Untuk memastikan upload file gambar/dokumen hingga 10MB tidak ditolak oleh web server:

### A. PHP Configuration (`php.ini` atau `/etc/php/8.2/fpm/php.ini`)
```ini
upload_max_filesize = 10M
post_max_size = 12M
memory_limit = 256M
max_execution_time = 60
```

### B. Nginx Server Block (`/etc/nginx/sites-available/...`)
```nginx
server {
    ...
    client_max_body_size 12M;
    ...
}
```

---

## 7. Menjalankan Automated Test

```bash
php artisan test
```
Seluruh 14 test suite (114 assertions) menguji isolasi tenancy, validasi header `X-Site-Key`, CRUD query scope, validasi form inquiries & upload file, serta route Swagger UI.
