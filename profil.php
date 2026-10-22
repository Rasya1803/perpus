<?php
require __DIR__ . '/config/functions.php';
$u = auth();
$title = 'Profil Saya';
$kicker = 'Akun';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (post('aksi') === 'profil') {
        $nama    = post('nama');
        $username = post('username');
        $email   = post('email');
        $telp    = post('no_telp');
        $alamat  = post('alamat');
        $identitas = post('identitas');
        $kelas   = post('kelas');

        if ($nama === '' || $username === '') {
            back('profil.php', 'Nama dan username wajib diisi.', 'err');
        }
        $st = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? AND id <> ?');
        $st->execute([$username, $u['id']]);
        if ($st->fetchColumn() > 0) {
            back('profil.php', 'Username sudah dipakai user lain.', 'err');
        }
        $st = $pdo->prepare('UPDATE users SET nama=?, username=?, email=?, no_telp=?, alamat=?, identitas=?, kelas=? WHERE id=?');
        $st->execute([$nama, $username, $email, $telp, $alamat, $identitas, $kelas, $u['id']]);
        back('profil.php', 'Profil berhasil diperbarui.');
    }

    if (post('aksi') === 'password') {
        if (post('sekarang') !== $u['password']) {
            back('profil.php', 'Password lama salah.', 'err');
        }
        $baru = post('baru');
        if (strlen($baru) < 6) {
            back('profil.php', 'Password baru minimal 6 karakter.', 'err');
        }
        if ($baru !== post('ulangi')) {
            back('profil.php', 'Konfirmasi password baru tidak sama.', 'err');
        }
        // Plaintext sesuai permintaan; ganti ke password_hash() bila nanti mau aman.
        $st = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $st->execute([$baru, $u['id']]);
        back('profil.php', 'Password berhasil diganti.');
    }
}

// Muat ulang supaya tampilan ikut ter-update.
$st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$st->execute([$u['id']]);
$u = $st->fetch();

require __DIR__ . '/includes/header.php';
?>

<div class="grid grid-2">
    <div class="card">
        <h2>Data Diri</h2>
        <p class="muted">Dipakai untuk identitas peminjaman di perpustakaan.</p>
        <form method="post" autocomplete="off">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="profil">
            <div class="form-grid">
                <div>
                    <label for="nama">Nama Lengkap</label>
                    <input id="nama" name="nama" value="<?= e($u['nama']) ?>" required>
                </div>
                <div>
                    <label for="username">Username</label>
                    <input id="username" name="username" value="<?= e($u['username']) ?>" required>
                </div>
                <div>
                    <label for="identitas"><?= $u['role'] === 'pengguna' ? 'NIS' : 'NIP' ?></label>
                    <input id="identitas" name="identitas" value="<?= e($u['identitas']) ?>">
                </div>
                <?php if ($u['role'] === 'pengguna'): ?>
                <div>
                    <label for="kelas">Kelas</label>
                    <input id="kelas" name="kelas" value="<?= e($u['kelas']) ?>">
                </div>
                <?php endif; ?>
                <div>
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="<?= e($u['email']) ?>">
                </div>
                <div>
                    <label for="no_telp">No. Telepon</label>
                    <input id="no_telp" name="no_telp" value="<?= e($u['no_telp']) ?>">
                </div>
                <div class="full">
                    <label for="alamat">Alamat</label>
                    <textarea id="alamat" name="alamat"><?= e($u['alamat']) ?></textarea>
                </div>
            </div>
            <p style="margin-top:14px"><button class="btn btn-primary" type="submit">Simpan Perubahan</button></p>
        </form>
    </div>

    <div class="card">
        <h2>Ganti Password</h2>
        <p class="muted">Minimal 6 karakter. Password disimpan tanpa hash sesuai permintaan.</p>
        <form method="post" autocomplete="off">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="password">
            <div>
                <label for="sekarang">Password Lama</label>
                <input id="sekarang" name="sekarang" type="password" required>
            </div>
            <div style="margin-top:12px">
                <label for="baru">Password Baru</label>
                <input id="baru" name="baru" type="password" required>
            </div>
            <div style="margin-top:12px">
                <label for="ulangi">Ulangi Password Baru</label>
                <input id="ulangi" name="ulangi" type="password" required>
            </div>
            <p style="margin-top:14px"><button class="btn btn-primary" type="submit">Ganti Password</button></p>
        </form>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
