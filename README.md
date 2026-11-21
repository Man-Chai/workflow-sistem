# KorlasSync

KorlasSync adalah aplikasi web pengelolaan kelas untuk **admin sekolah**, **koordinator kelas**, dan **orang tua**. Satu ruang kerja untuk kas kelas, iuran, agenda, dan pemberitahuan — dengan tampilan yang dirancang khusus, bukan template starter kit.

Aplikasi dibangun dengan **Laravel 12**, **SQLite** untuk pengembangan lokal, **PostgreSQL (Neon)** untuk demo hosted, dan **Blade + CSS/JS manual** di `public/assets/`. Tidak memakai Breeze, Jetstream, Laravel UI, atau template admin pihak ketiga.

---

## 1. Fitur utama

### Admin
- Dashboard seluruh sekolah: jumlah kelas, siswa, orang tua, saldo gabungan, pembayaran menunggu, grafik arus kas 6 bulan.
- CRUD pengguna dengan peran admin / koordinator / orang tua, status aktif, dan reset kata sandi oleh admin.
- CRUD kelas, penugasan koordinator, dan CRUD siswa (termasuk menghubungkan siswa ke akun orang tua).
- Akses penuh ke kas semua kelas, iuran, verifikasi/penolakan pembayaran, serta ekspor CSV.
- Agenda dan pemberitahuan global (seluruh sekolah) maupun per kelas.
- Halaman profil dan ganti kata sandi.

### Koordinator kelas
- Dashboard kelas yang dikoordinasikan: saldo, capaian iuran, agenda, dan kabar terbaru.
- Buku kas kelas: pemasukan, pengeluaran, filter bulan, dan ekspor CSV.
- Membuat/mengubah iuran dan mencatat transaksi manual.
- Mencatat pembayaran iuran yang sudah diterima (tunai, transfer, atau QRIS). Status langsung `verified` dan transaksi kas `masuk` dibuat otomatis.
- Memproses sisa pembayaran berstatus `pending` dari alur lama (verifikasi/tolak).
- CRUD agenda dan pemberitahuan untuk kelasnya sendiri.
- Melihat dan mengelola data siswa di kelasnya.
- Semua akses tulis dibatasi ke kelas yang ditugaskan; tidak bisa mengakses kelas lain.

### Orang tua
- Dashboard multi-anak: ringkasan tagihan, total dibayar, agenda, dan pemberitahuan.
- Melihat kas dan riwayat iuran anak di setiap kelas (hanya baca).
- Melihat status pembayaran yang telah dicatat koordinator, termasuk catatan penolakan bila ada.
- Agenda dan pemberitahuan hanya dari kelas anaknya + informasi global sekolah.
- Halaman profil dan ganti kata sandi.
- **Kas dan iuran dikelola sepenuhnya oleh koordinator kelas.** Orang tua tidak memiliki aksi pembayaran, verifikasi, atau pencatatan kas di aplikasi.
- Pendaftaran akun mandiri melalui halaman **Daftar**; akun baru selalu berperan sebagai orang tua.

### Alur pembayaran
1. Orang tua membayar langsung kepada koordinator kelas (tunai/transfer/QRIS).
2. Koordinator mencatat pembayaran di menu **Kas kelas → Catat pembayaran**.
3. Sistem menyimpan pembayaran sebagai `verified` dan langsung membuat transaksi kas `masuk`.
4. Pembayaran sebagian diperbolehkan; sisa tagihan dihitung dari total pembayaran terverifikasi.
5. Pembayaran tidak boleh melebihi sisa tagihan. Penghapusan pembayaran terverifikasi juga menghapus transaksi kas terkait.

Saldo kas **selalu dihitung dari transaksi kas** (`masuk - keluar`), bukan angka yang disimpan terpisah.

---

## 2. Akun demo

Jalankan seeder terlebih dahulu, lalu masuk dengan akun berikut. Semua kata sandi: **`password`**.

| Peran | Email | Catatan |
| --- | --- | --- |
| Admin | `admin@korlassync.test` | Akses seluruh sekolah |
| Koordinator | `koordinator@korlassync.test` | Koordinator Kelas 1 |
| Koordinator | `sinta@korlassync.test` | Koordinator Kelas 2 |
| Koordinator | `andi@korlassync.test` | Koordinator Kelas 3 |
| Koordinator | `dewi@korlassync.test` | Koordinator Kelas 4 |
| Koordinator | `rudi@korlassync.test` | Koordinator Kelas 5 |
| Koordinator | `lina@korlassync.test` | Koordinator Kelas 6 |
| Orang tua | `orangtua@korlassync.test` | Memiliki anak di beberapa kelas |
| Orang tua | `dian@korlassync.test`, `maya@korlassync.test`, `fajar@korlassync.test`, `ratna@korlassync.test` | Akun orang tua lain untuk uji coba |

Data demo mencakup **Kelas 1–6**, 18 siswa, iuran aktif, pembayaran verified/pending/rejected, transaksi kas 6 bulan, agenda, dan pemberitahuan.

---

## 3. Menjalankan aplikasi

### Prasyarat
- PHP **8.2+** dengan ekstensi `pdo_sqlite`
- Composer
- (Opsional) Node.js hanya jika ingin build aset Vite bawaan; aset UI KorlasSync tidak memerlukannya.

### Instalasi
```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
New-Item database/database.sqlite -ItemType File -Force
php artisan migrate:fresh --seed
php artisan serve
```

Buka `http://localhost:8000`.

Di Linux/macOS, ganti `Copy-Item` dengan `cp` dan `New-Item` dengan `touch`.

### Konfigurasi penting
- `APP_LOCALE=id`, `APP_TIMEZONE=Asia/Jakarta`
- `DB_CONNECTION=sqlite` dengan database di `database/database.sqlite`
- Bukti pembayaran disimpan di kolom `payments.proof_data` (base64, maks. 2 MB), sehingga tidak bergantung pada disk lokal

Untuk produksi, gunakan MySQL/PostgreSQL, `APP_DEBUG=false`, HTTPS, dan jalankan `php artisan optimize`.

---

## 4. Pengujian

```powershell
php artisan test
```

Saat ini tersedia **42 pengujian / 222 assertion** yang mencakup:
- Login valid, login gagal, akun nonaktif, logout, sesi nonaktif, dan pendaftaran akun orang tua.
- Pembatasan akses per peran dan scoping kelas.
- Alur pembayaran: pencatatan oleh koordinator, pembayaran sebagian, verifikasi alur lama, pencegahan nominal berlebih, dan penghapusan transaksi kas terkait.
- Penyimpanan bukti pembayaran di database dan pembatasan akses unduhannya.
- Visibilitas pemberitahuan global/kelas/draf.
- CRUD inti: agenda, pemberitahuan, iuran, kelas, siswa, pengguna.
- Smoke test seluruh halaman utama untuk ketiga peran.

Pengujian memakai SQLite in-memory (`phpunit.xml`), sehingga tidak mengubah database demo.

---

## 5. Deploy ke Vercel

Demo produksi: **https://korlassync.vercel.app**

### Arsitektur
- **Vercel Container** (`Dockerfile` + `Caddyfile`, FrankenPHP PHP 8.4) yang dideklarasikan di `vercel.json` sebagai service `app`.
- **PostgreSQL Neon** dari Vercel Marketplace untuk seluruh data aplikasi.
- **Sesi** memakai cookie terenkripsi (`SESSION_DRIVER=cookie`) agar tidak bergantung pada instance container.
- **Cache/rate limit** memakai tabel `cache` di PostgreSQL (`CACHE_STORE=database`).
- **Bukti pembayaran** disimpan sebagai base64 di `payments.proof_data`, sehingga tetap tersedia lintas instance dan saat container didaur ulang.

### Environment variable produksi
| Variabel | Nilai |
| --- | --- |
| `APP_ENV` / `APP_DEBUG` | `production` / `false` |
| `APP_KEY` | hasil `php artisan key:generate --show` |
| `APP_URL` | `https://korlassync.vercel.app` |
| `DB_CONNECTION` | `pgsql` |
| `DB_URL` | connection string Neon **direct/unpooled** |
| `SESSION_DRIVER` | `cookie` |
| `SESSION_SECURE_COOKIE` | `true` |
| `CACHE_STORE` | `database` |
| `LOG_CHANNEL` | `stderr` |
| `SETUP_TOKEN` | token acak untuk hook inisialisasi database |

> Gunakan host Neon **direct** (tanpa `-pooler`). PgBouncer transaction pooling memutus prepared statement PDO di tengah transaksi migrasi dan memunculkan `SQLSTATE[25P02] current transaction is aborted`.

### Inisialisasi dan pembaruan
Setelah deploy pertama, buka sekali:

```
https://korlassync.vercel.app/deploy/setup?token=<SETUP_TOKEN>
```

Hook tersebut menjalankan `migrate --force` dan seeder hanya jika tabel `users` masih kosong. Setelah data siap, hapus atau ganti `SETUP_TOKEN` untuk menutup akses.

Deploy ulang setelah perubahan kode:

```powershell
vercel deploy --prod
```

Reset data demo (dari mesin lokal dengan ekstensi `pdo_pgsql` dan `DB_*` mengarah ke Neon **direct**):

```powershell
php artisan migrate:fresh --seed --force
```

---

## 6. Struktur penting

```
app/
  Http/Controllers/          # Auth, dashboard, kas, pembayaran, agenda, pemberitahuan, admin
  Http/Middleware/           # EnsureRole, EnsureAccountIsActive
  Http/Controllers/Concerns/ # ManagesClassData (scoping tulis per kelas)
  Models/                    # User, ClassRoom, Student, Due, Payment, CashTransaction, Agenda, Announcement
  Support/                   # Cashflow, helper rupiah()/initials()
database/
  migrations/                # Skema database
  seeders/DatabaseSeeder.php # Data demo
public/assets/               # CSS & JS UI KorlasSync (ditulis manual)
resources/views/             # Blade: layout, dashboard per peran, kas, agenda, pemberitahuan, admin
routes/web.php               # Semua route + middleware peran
tests/Feature/               # Pengujian fitur
```

### Model peran
- `users.role`: `admin`, `koordinator`, `orang_tua`
- `classes.coordinator_id`: kelas yang dikelola koordinator
- `students.parent_id`: relasi anak ke akun orang tua
- `User::accessibleClassIds()` dan `User::canManageClass()` menjadi sumber scoping data
- Middleware `role` melindungi route, sedangkan controller tetap memvalidasi kepemilikan kelas/siswa

---

## 7. Rekomendasi pengembangan per peran

### Admin / sekolah
1. **Audit trail** untuk perubahan data penting (siapa mengubah nominal iuran, menghapus transaksi, atau memverifikasi pembayaran).
2. **Import/export master data** (siswa, orang tua, kelas) via Excel/CSV, plus proses kenaikan kelas dan rollover tahun ajaran.
3. **Izin granular & multi-sekolah**: pisahkan super-admin, bendahara, dan wali kelas; dukung beberapa unit/sekolah dalam satu instalasi.
4. **Persetujuan pengeluaran**: koordinator mengajukan, admin/bendahara menyetujui, baru masuk buku kas.
5. **Dashboard analitik**: tren tunggakan per kelas, rasio pembayaran, kelas dengan kas terendah, dan rekap bulanan otomatis.
6. **Keamanan produksi**: 2FA untuk admin, reset kata sandi via email, pembatasan IP panel admin, backup database terjadwal, dan monitoring error.

### Koordinator kelas
1. **Pembuatan iuran massal** (template bulanan/semester) dan penyalinan iuran antar-periode.
2. **Pengingat otomatis** via email/WhatsApp untuk siswa yang belum membayar, dengan tautan langsung ke halaman pembayaran.
3. **Bulk verification** dan rekonsiliasi bank/QRIS, termasuk pencocokan nominal dan tanggal transfer.
4. **Kwitansi digital/PDF** per pembayaran dan rekap kas per periode yang bisa diunduh orang tua.
5. **Pengeluaran dengan bukti**: unggah nota, kategori, dan alur persetujuan sebelum transaksi keluar.
6. **Dukungan multi-kelas** dalam satu akun koordinator dengan pemilih kelas di dashboard.
7. **Agenda dengan konfirmasi kehadiran** dan pengingat H-1 agar rapat orang tua lebih terukur.

### Orang tua
1. **Pembayaran online** melalui payment gateway (Virtual Account/QRIS) agar verifikasi bisa otomatis.
2. **Pengingat jatuh tempo** via push notification/WhatsApp dan ringkasan tagihan mingguan.
3. **Pemilih anak** yang lebih menonjol di dashboard serta tampilan gabungan tagihan semua anak.
4. **Kwitansi dan rekap pembayaran** yang dapat diunduh, termasuk status verifikasi dan catatan koordinator.
5. **Ajukan pertanyaan/keberatan** pada tagihan tertentu tanpa harus keluar dari aplikasi.
6. **Mode hemat data/PWA** untuk akses cepat di ponsel dengan koneksi terbatas.

### Lintas peran
- Antrean notifikasi (queue) dan push notification agar pengiriman tidak memblokir request.
- Log aktivitas dan soft delete untuk data keuangan.
- Kebijakan privasi & persetujuan orang tua sesuai UU PDP.
- Uji performa dan indeks database saat data sudah besar.
- Tambahkan observability (Sentry/Pail), backup otomatis, dan staging environment sebelum produksi.

---

## 8. Catatan batasan versi demo
- Autentikasi menggunakan email + kata sandi; belum ada verifikasi email, lupa kata sandi, atau 2FA.
- Pendaftaran mandiri hanya membuat akun orang tua; akun admin/koordinator dibuat oleh sekolah.
- Pembayaran belum terhubung ke payment gateway; pencatatan dan verifikasi dilakukan manual oleh koordinator/admin.
- Notifikasi masih berupa badge di dalam aplikasi, belum push/email/WhatsApp.
- Satu koordinator demo memegang satu kelas; model data sudah siap untuk multi-kelas, tetapi dashboard masih menampilkan kelas pertama.
- SQLite hanya untuk pengembangan lokal; demo hosted memakai PostgreSQL Neon.
- Bukti pembayaran disimpan di database agar portabel di lingkungan serverless; untuk skala besar, pindahkan ke object storage (S3/Vercel Blob).
