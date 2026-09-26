# Changelog

Semua perubahan penting pada proyek ini didokumentasikan di file ini.

Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/),
dan versi mengikuti [Semantic Versioning](https://semver.org/lang/id/).

## [1.0.0] - 2026-09-26

Rilis perdana **Newsoft RBAC Starter** — fondasi aplikasi siap pakai dengan kontrol akses berbasis role yang sepenuhnya dinamis.

### Ditambahkan

- **Inti RBAC dinamis**: user, role, permission, modul, dan menu semuanya dikelola dari database (`core_*`) dan dapat diubah saat aplikasi berjalan tanpa menyentuh kode.
- **Manajemen user & role** — CRUD user (`builtin/user`), penugasan role (`builtin/user-role`), role multi level (`builtin/role`).
- **Manajemen permission** — permission per modul (`builtin/permission`), pemberian permission ke role (`builtin/role-permission`), ditegakkan lewat `hasPermission()` di controller.
- **Menu dinamis** — kategori, parent, ikon, urutan, dan visibilitas per role (`builtin/menu`, `builtin/menu-role`).
- **Manajemen modul HMVC** — registrasi, aktivasi, dan kontrol tampilan saat login (`builtin/module`); route modul terdeteksi otomatis.
- **Autentikasi & otorisasi** — login, registrasi, pemulihan password, auth berbasis session, CSRF, filter proteksi route, filter `InstallerCheck` untuk instalasi awal.
- **Security Monitor** — log serangan (SQL injection / XSS / brute force), rate limiting, dan blokir IP (`securitymonitor`).
- **Multi perusahaan** — pengelolaan `company` dan `identitas` yang terkait user.
- **DB Synchronisation** — perbandingan skema live dengan dump installer dan pembuatan SQL sinkronisasi (`db-synchronisation`).
- **Installer**: web installer (tulis `app/Config/Database.php` otomatis) dan installer CLI di `manual_installer/` dengan skrip verifikasi.
- **Data seed**: 34 tabel, 82.503 kelurahan (wilayah administratif Indonesia lengkap), 141 bank Indonesia, akun bootstrap `admin`.
- **Tooling**: generator modul HMVC (`tools/create_hmvc_module.php`).
- **Dokumentasi**: README dwibahasa (Indonesia default, `README_EN.md`), panduan instalasi, panduan modul HMVC, `CONTRIBUTING.md`.
- **Infrastruktur kontribusi**: `.gitattributes`, kebijakan keamanan (`SECURITY.md`), template pull request & issue.

### Keamanan

- Branch `main` dilindungi: push langsung ditolak, semua perubahan lewat pull request, force-push dan penghapusan branch dimatikan, aturan berlaku juga untuk administrator.

[1.0.0]: https://github.com/VKNewsoft/newsoft_rbac_starter/releases/tag/v1.0.0
