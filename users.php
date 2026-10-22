<?php
require __DIR__ . '/config/functions.php';
$u = auth(['admin']);
$title = 'Kelola User';
$role = ['admin', 'petugas', 'operator', 'pengguna'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = post('aksi');

    if ($aksi === 'simpan') {
        $id = (int) post('id');
        $data = [
            post('nama'),
            post('username'),
            post('password'),
            in_array(post('role'), $role, true) ? post('role') : 'pengguna',
            post('identitas'),
            post('kelas'),
            post('email'),
            post('no_telp'),
            post('alamat'),
        ];
        if ($data[0] === '' || $data[1] === '') {
            back('users.php', 'Nama dan username wajib diisi.', 'err');
        }
        if ($data[2] !== '' && strlen($data[2]) < 6) {
            back('users.php', 'Password minimal 6 karakter.', 'err');
        }
        $st = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? AND id <> ?');
        $st->execute([$data[1], $id]);
        if ((int) $st->fetchColumn() > 0) {
            back('users.php', 'Username sudah dipakai user lain.', 'err');
        }
        if ($id > 0) {
            if ($data[2] === '') {
                $pdo->prepare('UPDATE users SET nama=?, username=?, role=?, identitas=?, kelas=?, email=?, no_telp=?, alamat=? WHERE id=?')
                    ->execute([$data[0], $data[1], $data[3], $data[4], $data[5], $data[6], $data[7], $data[8], $id]);
                back('users.php', 'User diperbarui.');
            }
            $data[] = $id;
            $pdo->prepare('UPDATE users SET nama=?, username=?, password=?, role=?, identitas=?, kelas=?, email=?, no_telp=?, alamat=? WHERE id=?')
                ->execute($data);
            back('users.php', 'User dan password diperbarui.');
        }
        $pdo->prepare('INSERT INTO users (nama, username, password, role, identitas, kelas, email, no_telp, alamat) VALUES (?,?,?,?,?,?,?,?,?)')
            ->execute($data);
        back('users.php', 'User ditambahkan.');
    }

    if ($aksi === 'hapus') {
        $id = (int) post('id');
        if ($id === (int) $u['id']) {
            back('users.php', 'Tidak bisa menghapus akun sendiri.', 'err');
        }
        // Kembalikan stok buku yang masih dipinjam user ini sebelum dihapus.
        $pdo->beginTransaction();
        $st = $pdo->prepare("SELECT buku_id FROM peminjaman WHERE user_id=? AND status='dipinjam'");
        $st->execute([$id]);
        foreach ($st->fetchAll() as $row) {
            $pdo->prepare('UPDATE buku SET stok_tersedia = stok_tersedia + 1 WHERE id=?')->execute([$row['buku_id']]);
        }
        $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
        $pdo->commit();
        back('users.php', 'User dihapus. Riwayat pinjamannya ikut terhapus.');
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM users WHERE id=?');
    $st->execute([(int) $_GET['edit']]);
    $edit = $st->fetch() ?: null;
}

$users = $pdo->query('SELECT u.*, (SELECT COUNT(*) FROM peminjaman p WHERE p.user_id=u.id AND p.status IN (\'menunggu\',\'dipinjam\')) AS aktif
                      FROM users u ORDER BY FIELD(u.role,"admin","petugas","operator","pengguna"), u.nama')->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="grid grid-2">
    <div class="card">
        <h2><?= $edit ? 'Edit User' : 'Tambah User' ?></h2>
        <form method="post" autocomplete="off">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="simpan">
            <input type="hidden" name="id" value="<?= $edit ? (int) $edit['id'] : 0 ?>">
            <div class="form-grid">
                <div>
                    <label for="nama">Nama Lengkap</label>
                    <input id="nama" name="nama" value="<?= e($edit['nama'] ?? '') ?>" required>
                </div>
                <div>
                    <label for="username">Username</label>
                    <input id="username" name="username" value="<?= e($edit['username'] ?? '') ?>" required>
                </div>
                <div>
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" placeholder="<?= $edit ? 'kosongkan bila tidak diganti' : 'min. 6 karakter' ?>" <?= $edit ? '' : 'required' ?>>
                </div>
                <div>
                    <label for="role">Role</label>
                    <select id="role" name="role">
                        <?php foreach ($role as $r): ?>
                            <option value="<?= $r ?>" <?= ($edit['role'] ?? 'pengguna') === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="identitas">NIP / NIS</label>
                    <input id="identitas" name="identitas" value="<?= e($edit['identitas'] ?? '') ?>">
                </div>
                <div>
                    <label for="kelas">Kelas</label>
                    <input id="kelas" name="kelas" value="<?= e($edit['kelas'] ?? '') ?>">
                </div>
                <div>
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="<?= e($edit['email'] ?? '') ?>">
                </div>
                <div>
                    <label for="no_telp">No. Telepon</label>
                    <input id="no_telp" name="no_telp" value="<?= e($edit['no_telp'] ?? '') ?>">
                </div>
                <div class="full">
                    <label for="alamat">Alamat</label>
                    <textarea id="alamat" name="alamat"><?= e($edit['alamat'] ?? '') ?></textarea>
                </div>
            </div>
            <p style="margin-top:14px">
                <button class="btn btn-primary" type="submit"><?= $edit ? 'Simpan Perubahan' : 'Tambah User' ?></button>
                <?php if ($edit): ?><a class="btn" href="users.php">Batal</a><?php endif; ?>
            </p>
        </form>
    </div>

    <div class="card">
        <h2>Daftar User</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Kelas</th>
                        <th>Aktif</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $r): ?>
                        <tr>
                            <td><strong><?= e($r['nama']) ?></strong>
                                <div class="muted" style="font-size:12px"><?= e($r['identitas'] ?: $r['email']) ?></div>
                            </td>
                            <td><?= e($r['username']) ?></td>
                            <td><span class="badge <?= $r['role'] === 'admin' ? 'red' : ($r['role'] === 'pengguna' ? 'grey' : 'blue') ?>"><?= e(ucfirst($r['role'])) ?></span></td>
                            <td><?= e($r['kelas'] ?? '-') ?></td>
                            <td><?= (int) $r['aktif'] ?></td>
                            <td class="nowrap">
                                <div class="actions">
                                    <a class="btn btn-sm" href="users.php?edit=<?= (int) $r['id'] ?>">Edit</a>
                                    <?php if ((int) $r['id'] !== (int) $u['id']): ?>
                                        <form method="post" style="display:inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="aksi" value="hapus">
                                            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                            <button class="btn btn-sm btn-danger" type="submit">Hapus</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>