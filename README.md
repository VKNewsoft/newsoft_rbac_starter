# Newsoft RBAC Starter

**Bahasa / Language:** **Indonesia** | [English](README_EN.md)

---

Proyek starter **Role-Based Access Control (RBAC)** yang dapat digunakan ulang, menyediakan manajemen akses yang lengkap dan dapat dikonfigurasi secara dinamis — meliputi manajemen menu dinamis, manajemen role dan permission, serta kontrol akses tingkat modul.

Proyek ini dibangun sebagai **starter kit / fondasi** untuk aplikasi baru: sudah dilengkapi admin panel yang berfungsi penuh, di mana user, role, permission, menu, dan modul semuanya dikelola dari database, sehingga kontrol akses dapat diubah saat aplikasi berjalan tanpa menyentuh kode. Dapat dijadikan basis aplikasi yang lebih besar, atau sebagai sistem administrasi dan kontrol akses mandiri.

> **Stack**
> - **Framework:** CodeIgniter 4.x (arsitektur HMVC penuh)
> - **Bahasa:** PHP 8.2+
> - **Database:** MySQL 8.x (kompatibel MariaDB)
> - **Platform:** dikembangkan dan diuji di XAMPP (Windows), berjalan di stack AMP standar mana pun

---

## Gambaran Proyek

Aplikasi ini adalah starter Admin Panel yang dirancang sebagai fondasi *Web Security*: mengontrol secara terpusat user, role, permission, menu, dan modul untuk setiap aplikasi yang dibangun di atasnya.

- **Semuanya dinamis.** Menu, role, permission, dan registrasi modul disimpan di tabel database `core_*` dan dirender oleh aplikasi saat runtime.
- **HMVC penuh.** Setiap fitur berada di modulnya sendiri (`app/Modules/<Nama>`), masing-masing berisi controller, model, view, assets, dan routes sendiri. Kode bersama berada di modul `Common`.
- **Role multi level dan multi perusahaan.** Role dan user dapat diorganisasikan berdasarkan level hierarki dan perusahaan, memungkinkan manajemen akses yang fleksibel untuk organisasi kompleks.
- **Siap dikembangkan.** Fitur baru ditambahkan sebagai modul dan otomatis terintegrasi dengan sistem RBAC.

---

## Fitur Utama

Fitur-fitur berikut sudah diimplementasikan dan terverifikasi di codebase:

- **Manajemen user** — membuat, mengubah, menonaktifkan, dan menghapus akun user (`builtin/user`), dengan penugasan role ke user (`builtin/user-role`).
- **Manajemen role** — mendefinisikan dan mengelola role multi level (`builtin/role`), menugaskan role ke user, dan mengatur role mana yang dapat melihat menu tertentu (`builtin/menu-role`).
- **Manajemen permission** — mendefinisikan permission per modul (`builtin/permission`) dan mengaitkan permission ke role (`builtin/role-permission`). Permission ditegakkan di controller melalui pengecekan `hasPermission()`.
- **Manajemen menu dinamis** — membangun dan mengurutkan menu aplikasi dari admin panel (`builtin/menu`); menu mendukung kategori, induk (parent), ikon, urutan, dan visibilitas per role.
- **Manajemen modul** — mendaftarkan modul ke database (`builtin/module`), mengaktifkan/menonaktifkan, dan mengatur apakah modul muncul saat login.
- **Kontrol akses tingkat modul** — entri menu terikat pada modul, dan akses modul diberikan per role; navigasi dan route difilter berdasarkan permission user.
- **Autentikasi dan otorisasi** — modul login, registrasi, dan pemulihan password, auth berbasis session, penanganan token CSRF pada form, serta filter proteksi route.
- **Security Monitor** — pencatatan jenis serangan (SQL injection / XSS / brute force), rate limiting, dan manajemen blokir IP (`securitymonitor`).
- **Pemuat asset modul** — CSS/JS modul disajikan melalui route `module-assets` dengan MIME type yang benar, caching, dan dukungan path bersarang.
- **Dukungan multi perusahaan** — data perusahaan dan identitas (`company`, `identitas`) dapat dikelola dan dikaitkan dengan user.
- **Arsitektur yang dapat digunakan ulang** — modul `Common` bersama (base controller, base model, design system, JS/CSS bersama) yang menjadi fondasi semua modul.

> Dashboard bawaan, tool sinkronisasi skema DB, data wilayah, dan modul pendukung lainnya adalah contoh dari arsitektur ini dan dapat dipertahankan, disesuaikan, atau dihapus sesuai kebutuhan proyek.

---

## Arsitektur RBAC

### Bagaimana semuanya terhubung

| Konsep | Tabel | Dikelola oleh |
|---|---|---|
| User | `core_user`, `core_user_role` | `builtin/user`, `builtin/user-role` |
| Role | `core_role` | `builtin/role` |
| Modul | `core_module` (+ `core_module_status`) | `builtin/module` |
| Permission modul | `core_module_permission` | `builtin/permission` |
| Pemberian role ↔ permission | `core_role_module_permission` | `builtin/role-permission` |
| Menu | `core_menu`, `core_menu_kategori` | `builtin/menu` |
| Visibilitas menu ↔ role | `core_menu_role` | `builtin/menu-role` |
| Perusahaan | `core_company` | `company` |

### Bagaimana akses dihitung

1. **Modul didaftarkan** di `core_module` dengan `nama_module` unik (mis. `builtin/role`, `dashboard`).
2. **Permission dilekatkan** ke modul di `core_module_permission` (mis. `create`, `read_all`, `update_all`, `delete_all`).
3. **Role diberi permission** melalui `core_role_module_permission` dari layar Role Permission.
4. **Menu diikat ke modul** di `core_menu` dan dibuat terlihat untuk role tertentu melalui `core_menu_role`.
5. **Saat request masuk**, filter `Bootstrap` memuat session user, pohon menu, dan peta permission (di-cache demi performa). Controller memanggil `$this->hasPermission('read_all')` dan seterusnya sebelum merender atau menulis, dan sidebar hanya menampilkan menu yang diizinkan oleh role user.

Karena semua relasi ini tersimpan di database, kontrol akses **dapat dikonfigurasi secara dinamis**: menambah layar ke suatu role, mencabut permission, atau menyembunyikan menu adalah perubahan data, bukan perubahan kode.

---

## Struktur Proyek

```text
app/
  Config/               Konfigurasi CodeIgniter (Routes, Database, Filters, ...)
  Database/
    newsoft_base.sql    Skema awal lengkap + data seed (34 tabel)
  Filters/              Filter request (Bootstrap, security, ...)
  Helpers/              Helper global
  Language/             File bahasa
  Libraries/            Library bersama
  Models/               Model global
  Modules/              Modul HMVC (lihat di bawah)
  Views/                View global/error
public/                 Web root (index.php, assets)
system/                 Framework CodeIgniter 4
tools/
  create_hmvc_module.php  Generator modul HMVC (scaffold modul baru)
manual_installer/       Installer CLI + skrip verifikasi
writable/               Cache, log, session, upload (tidak ikut version control)
```

### Struktur modul HMVC

Setiap modul mengikuti struktur mandiri yang sama:

```text
app/Modules/<Nama>/
  Config/Routes.php     Route modul (otomatis ditemukan oleh app/Config/Routes.php)
  Controllers/          Controller modul
  Models/               Model modul
  Views/                View modul (resolusi view dengan namespace)
  Assets/               CSS/JS modul (dilayani via /module-assets/<modul>/...)
```

Modul `Common` berisi base controller, base model, design system global, dan aset frontend yang dapat digunakan ulang oleh semua modul.

---

## Panduan Instalasi

### Prasyarat

- PHP **8.2+** dengan ekstensi `mysqli`/`pdo_mysql` aktif
- **MySQL 8.x** (atau MariaDB)
- Apache dengan `mod_rewrite` (atau web server lain yang mampu menjalankan CodeIgniter 4)
- [XAMPP](https://www.apachefriends.org/) adalah pilihan termudah di Windows — jalankan **Apache** dan **MySQL**

### Langkah 1 — Dapatkan proyeknya

```bash
git clone https://github.com/VKNewsoft/newsoft_rbac_starter.git
```

Atau unduh dan ekstrak ZIP ke web root Anda (mis. `C:\xampp\htdocs\newsoft_rbac_starter`). Pastikan direktori `writable/` dapat ditulis oleh web server (cache, log, session).

### Langkah 2 — Jalankan installer

Database dibuat otomatis oleh installer — Anda tidak perlu membuatnya manual.

#### Opsi A — Installer web (disarankan) ⭐

1. Jalankan **Apache** dan **MySQL** (XAMPP Control Panel).
2. Buka URL aplikasi di browser, mis. `http://localhost/newsoft_rbac_starter/`.
3. Jika database belum diinisialisasi, filter `InstallerCheck` otomatis mengalihkan Anda ke halaman `/installer`.
4. Isi form konfigurasi database:

   | Field | Nilai khas XAMPP |
   |---|---|
   | Database Host | `localhost` |
   | Database Port | `3306` |
   | Database Username | `root` |
   | Database Password | *(biarkan kosong)* |
   | Database Name | `newsoft_app` |

5. Klik **Install Database**. Installer mengimpor `app/Database/newsoft_base.sql` — **34 tabel**, 82.000+ baris data wilayah, dan seluruh data seed — lalu menulis `app/Config/Database.php` secara otomatis.
6. Tunggu halaman sukses. Proses impor memakan waktu sekitar 1–2 menit; jangan tutup tab browser selama proses berjalan.
7. Setelah berhasil, Anda diarahkan ke halaman login.

**Keunggulan:** UI ramah pengguna, validasi input otomatis, konfigurasi database tersimpan otomatis, pesan error yang jelas, tanpa perlu terminal.

#### Opsi B — Installer CLI

Dari root repositori:

```bash
cd manual_installer
install.bat            # Menu interaktif Windows
# atau langsung:
php import_sql.php
php verify_import.php
php check_tables.php
```

Proses impor menampilkan progres realtime dan memakan waktu 1–2 menit karena dataset wilayah yang besar. Setelah impor manual, konfigurasikan `app/Config/Database.php` dengan pengaturan server Anda.

> ⚠️ **Peringatan:** kedua installer adalah alur fresh-install yang dapat **menghapus dan membuat ulang database target**. Jangan pernah menjalankannya pada database produksi atau database apa pun yang datanya harus dipertahankan. Buat backup terlebih dahulu, mis. `mysqldump -u root newsoft_app > backup.sql`.

### Langkah 3 — Verifikasi instalasi

```bash
cd manual_installer
php verify_import.php
```

Hasil yang diharapkan:

- `Expected: 34 tables` / `Found: 34 tables` ✅
- Jumlah baris ditampilkan untuk tabel-tabel penting (`core_user`, `core_menu`, `core_role`, `core_wilayah_kelurahan`, `core_bank`, ...)
- `php check_tables.php` menampilkan checklist per tabel (✅ ada / ❌ hilang)
- Tabel `core_user` ada — `InstallerCheck` menggunakannya untuk mendeteksi aplikasi yang sudah diinisialisasi
- `writable/logs/` tidak berisi error baru

### Langkah 4 — Login pertama

| Username | Password |
|---|---|
| `admin` | `123456` |

Instalasi bawaan mencakup 1 perusahaan default, role Administrator, konfigurasi menu/role default, 141 bank Indonesia, dan 82.503 baris kelurahan (data wilayah administratif Indonesia lengkap).

> ⚠️ Kredensial bawaan hanya untuk setup awal. **Segera ganti password admin** sebelum aplikasi diakses di luar komputer lokal Anda.

### Troubleshooting (ringkas)

| Masalah | Solusi |
|---|---|
| `Connection refused` / tidak bisa konek MySQL | Jalankan service MySQL, lalu periksa host dan port. |
| `Access denied for user` | Periksa username/password; akun harus bisa membuat database dan tabel. |
| `Unknown database` | Normal saat setup pertama — jalankan installer web atau `php manual_installer/import_sql.php`. |
| `Table 'core_module' doesn't exist` | Impor belum selesai — jalankan ulang impor, lalu `php check_tables.php`. |
| Halaman installer tidak muncul | Periksa `app/Config/Database.php`, buka `/installer` manual, inspeksi `writable/logs/`. |
| Impor terasa berhenti | Normal untuk 82.000+ baris (1–2 menit); jangan tutup browser/terminal. |

📖 Detail lebih lanjut: [INSTALLATION.md](INSTALLATION.md) (panduan langkah demi langkah) dan [DATABASE_INSTALLATION_GUIDE.md](DATABASE_INSTALLATION_GUIDE.md) (troubleshooting teknis & FAQ).

---

## Konfigurasi

- **Database** — dikonfigurasi di `app/Config/Database.php` (ditulis oleh installer web; tidak ikut version control). Salin `.env.example` / `project.config.example.json` sebagai template environment.
- **Base URL** — diatur di `app/Config/App.php` atau biarkan terdeteksi otomatis.
- **Routes** — route global ada di `app/Config/Routes.php`; setiap modul menyumbang `app/Modules/*/Config/Routes.php` sendiri yang ditemukan secara otomatis.
- **Filters** — filter request (bootstrap, security, rate limiting) didaftarkan di `app/Config/Filters.php`.
- **Direktori writable** — `writable/` harus dapat ditulis oleh web server (cache, log, session). Isinya tidak di-commit.

Jangan pernah commit kredensial asli. Jauhkan rahasia produksi dari repositori.

---

## Panduan Pengembangan Modul

### Membuat modul

Gunakan generator yang tersedia:

```bash
php tools/create_hmvc_module.php Produk produk
```

Ini membuat kerangka `app/Modules/Produk/` berisi `Config/Routes.php`, `Controllers/Produk.php`, `Models/ProdukModel.php`, dan views.

### Mendaftarkannya ke sistem RBAC

1. **Routes** — tambahkan route di `app/Modules/Produk/Config/Routes.php`; route otomatis terbaca (tanpa registrasi terpusat).
2. **Daftarkan modul** — di admin panel buka **Module** (`builtin/module`) dan tambahkan modul dengan `nama_module` = `produk`. Di sini Anda juga dapat mengaktifkan/menonaktifkannya dan mengatur perilaku login.
3. **Definisikan permission** — buka **Module Permission** (`builtin/permission`) dan tambahkan permission yang dipahami modul (mis. `create`, `read_all`, `update_all`, `delete_all`).
4. **Beri permission ke role** — buka **Role Permission** (`builtin/role-permission`) dan centang permission baru per role.
5. **Tambahkan menu** — buka **Menu** (`builtin/menu`) dan buat entri menu yang terikat ke modul `produk`; atur role mana yang melihatnya melalui **Menu Role** (`builtin/menu-role`).
6. **Struktur database** — buat tabel milik modul (mis. `base_produk`) dengan migration atau SQL, dan simpan query model modul di dalam modul itu sendiri.
7. **Amankan controller** — panggil `$this->hasPermission('read_all')` (dan seterusnya) di action controller Anda agar framework menegakkan aturan yang sama dengan yang ditampilkan UI.

Sejak titik ini modul berpartisipasi dalam sistem kontrol akses seperti modul bawaan lainnya: visibilitas menu, pengecekan permission, dan penugasan role semuanya bekerja tanpa kode tambahan.

---

## Panduan Penggunaan

Login dan kelola semuanya dari sidebar:

- **Dashboard** (`dashboard`) — halaman landing setelah login.
- **Manajemen Aplikasi / Module** (`builtin/module`) — daftarkan modul, aktif/nonaktifkan, lihat apakah controller tiap modul ada.
- **Module Permission** (`builtin/permission`) — definisikan verb permission yang tersedia per modul.
- **Role** (`builtin/role`) — buat role (mis. Administrator, User Biasa) dan atur levelnya.
- **Role Permission** (`builtin/role-permission`) — berikan set permission modul ke tiap role.
- **User / Semua User** (`builtin/user`) — kelola akun; **User Role** (`builtin/user-role`) menugaskan role ke user.
- **Menu** (`builtin/menu`) — bangun sidebar: kategori, induk, ikon, urutan; **Menu Role** (`builtin/menu-role`) mengatur visibilitas per role.
- **Security Monitor** (`securitymonitor`) — tinjau log serangan, penghitung rate-limit, dan IP yang diblokir.
- **DB Synchronisation** (`db-synchronisation`) — bandingkan skema live dengan dump installer dan hasilkan SQL sinkronisasi aman/penuh.
- **Setting** (`builtin/setting-app`, `builtin/setting-layout`, `builtin/setting-registrasi`) — nama aplikasi, layout, dan perilaku registrasi.

Alur setup yang umum: buat role → berikan permission → buat user dan tugaskan role → tambahkan menu untuk layar baru dan kaitkan role-nya.

---

## Pengembangan dan Kontribusi

- Simpan kode controller/model/view/asset modul bersama di dalam modul HMVC; letakkan kode frontend yang dapat digunakan ulang di modul `Common`, jangan diduplikasi.
- Pertahankan class design system bersama (`page-shell`, `page-hero`, `page-toolbar`, `page-card`, `form-card`, `card-table-wrap`).
- Untuk layar daftar, pertahankan pagination sisi server, kolom query eksplisit, dan lookup terbatas per halaman (hindari query N+1).
- Gunakan **Migrations** CodeIgniter untuk perubahan skema di masa depan agar tetap terlacak dan dapat direproduksi; backup database sebelum DDL destruktif atau sinkronisasi.
- Periksa `writable/logs/` dan console browser setelah mengubah UI; uji perilaku responsif hingga lebar mobile.
- Panduan tambahan di repositori ini: [`HMVC_MODULE_GUIDE.md`](HMVC_MODULE_GUIDE.md), [`INSTALLATION.md`](INSTALLATION.md), [`DATABASE_INSTALLATION_GUIDE.md`](DATABASE_INSTALLATION_GUIDE.md).

Kontribusi: fork repositori, buat branch fitur, jaga perubahan tetap kecil dan terverifikasi, lalu buka pull request yang menjelaskan motivasi dan pengujian yang dilakukan.

---

## Lisensi

Proyek ini dilisensikan berdasarkan ketentuan di [`LICENSE`](LICENSE) (© 2025 Newsoft Developer). Lihat file lisensi untuk teks lengkap.
