# Alur Kerja Kontribusi — Push via Pull Request

> Branch `main` dilindungi **branch protection**: tidak ada push langsung — semua perubahan masuk lewat **pull request**. Aturan berlaku untuk semua orang, termasuk administrator.

## Aturan yang aktif

| Aturan | Nilai |
|---|---|
| Push langsung ke `main` | ❌ Ditolak server |
| Jalur masuk ke `main` | Pull request |
| Approval reviewer | Tidak diwajibkan (PR dapat di-merge sendiri) |
| Force push ke `main` | ❌ Dimatikan |
| Hapus `main` | ❌ Dimatikan |
| Bypass aturan oleh admin | ❌ Dimatikan |

---

## Workflow harian (5 langkah)

### 1. Mulai dari `main` yang segar

```bash
git checkout main
git pull
```

### 2. Buat branch fitur/perbaikan

Beri nama singkat dan jelas, awali dengan jenis perubahannya:

```bash
git checkout -b fitur/tambah-modul-produk    # fitur baru
git checkout -b perbaikan/validasi-login     # bug fix
git checkout -b docs/perbaiki-readme         # dokumentasi
git checkout -b chore/naikkan-versi          # perawatan
```

### 3. Commit perubahan di branch tersebut

```bash
git add <file-yang-relevan>          # hindari git add -A
git commit -m "deskripsi singkat perubahan"
```

Pesan commit yang baik: satu commit = satu tujuan, jelaskan *mengapa* bukan sekadar *apa*.

### 4. Push branch ke GitHub

```bash
git push -u origin fitur/tambah-modul-produk
```

Push pertama sekaligus membuat branch di remote; berikutnya cukup `git push`.

### 5. Buat PR lalu merge

- Buka tautan yang muncul setelah push (atau tab **Pull requests** → **New pull request**).
- Pastikan arah benar: `base: main` ← `compare: <branch-anda>`.
- Isi template PR (Motivasi / Perubahan / Cara menguji) — template muncul otomatis.
- Klik **Create pull request** → **Merge pull request** → **Confirm merge**.
- Hapus branch di GitHub (tombol **Delete branch**) supaya daftar branch tetap bersih.

---

## Sinkronisasi setelah merge (jangan sampai menumpuk)

```bash
git checkout main
git pull
git branch -d fitur/tambah-modul-produk      # hapus branch lokal yang sudah masuk
git push origin --delete fitur/tambah-modul-produk   # bila belum dihapus di GitHub
```

---

## Kondisi khusus

**Commit saya masih di branch, PR belum di-merge, ada revisi** — cukup tambah commit di branch yang sama lalu push; PR otomatis terupdate.

```bash
git add file-yang-direvisi
git commit -m "perbaiki sesuai catatan review"
git push
```

**PR konflik dengan `main` terbaru** — tarik perubahan `main` ke branch Anda, selesaikan konflik, push lagi:

```bash
git checkout fitur/tambah-modul-produk
git fetch origin
git merge origin/main
# selesaikan konflik di file yang ditandai, lalu:
git add file-yang-diselesaikan
git commit
git push
```

**Saya tadi push langsung ke `main` dan ditolak (`protected branch hook declined`)** — itu aturannya bekerja dengan benar. Kembali ke langkah 2: buat branch, commit di sana, lanjut lewat PR.

---

## Ringkasan satu baris

> `git pull` → `git checkout -b <branch>` → commit → `git push -u origin <branch>` → PR → merge → `git pull` lagi.
