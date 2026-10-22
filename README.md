# Perpustakaan Sekolah (PHP + MySQL, tanpa JS)

Aplikasi multiuser: pengguna (siswa), petugas, operator, admin. Masing-masing punya dashboard dan tugas sendiri.

## Instalasi (XAMPP)
1. Start Apache + MySQL di XAMPP.
2. Import `database.sql` lewat phpMyAdmin (`http://localhost/phpmyadmin` -> Import), atau:
   `C:\xampp\mysql\bin\mysql.exe -u root < database.sql`
3. Buka `http://localhost/perpus/`
4. Kredensial MySQL ada di `config/db.php` (default XAMPP: user `root`, password kosong).

## Akun demo
| Role | Username | Password |
|---|---|---|
| Admin | admin | admin123 |
| Petugas | petugas | petugas123 |
| Operator | operator | operator123 |
| Pengguna | siswa | siswa123 |

## Tugas per role
- **Pengguna**: cari buku di katalog, ajukan pinjam, batalkan ajuan, lihat riwayat + denda, edit profil, ganti password.
- **Petugas**: setujui/tolak ajuan, catat pengembalian (denda otomatis), daftar buku yang sedang dipinjam.
- **Operator**: CRUD buku + kategori, atur stok.
- **Admin**: semua hak petugas/operator, CRUD semua user, laporan peminjaman + export CSV.

## Aturan sirkulasi
- Peminjaman 7 hari, maksimal 3 pinjaman aktif per pengguna.
- Denda Rp1.000 per hari telat, dihitung saat pengembalian dicatat.
- Alur: pengguna ajukan (`menunggu`) -> petugas setujui (stok turun) -> petugas catat pengembalian (stok naik, status `dikembalikan`).

## Catatan
- Password disimpan **plaintext** sesuai permintaan. Untuk aman, ganti `password_hash()`/`password_verify()` di `config/functions.php`, `login.php`, `profil.php`, `users.php`.
- Tidak ada JavaScript sama sekali; konfirmasi hapus dan notifikasi pakai halaman biasa.

## Cek logika denda
```
C:\xampp\php\php.exe check.php
```
