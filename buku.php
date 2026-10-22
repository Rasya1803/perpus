<?php
require __DIR__ . '/config/functions.php';
$u = auth(['operator', 'admin']);
$title = 'Kelola Buku';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = post('aksi');

    if ($aksi === 'simpan') {
        $id = (int) post('id');
        $data = [
            post('judul'), post('pengarang'), post('penerbit'),
            post('tahun') !== '' ? (int) post('tahun') : null,
            post('isbn'),
            post('kategori_id') !== '' ? (int) post('kategori_id') : null,
            max(0, (int) post('stok_total')),
        ];
        if ($data[0] === '') {
            back('buku.php', 'Judul wajib diisi.', 'err');
        }
        if ($id > 0) {
            $st = $pdo->prepare('SELECT stok_total, stok_tersedia FROM buku WHERE id=?');
            $st->execute([$id]);
            $lama = $st->fetch();
            if (!$lama) {
                back('buku.php', 'Buku tidak ditemukan.', 'err');
            }
            $dipinjam = $lama['stok_total'] - $lama['stok_tersedia'];
            if ($data[6] < $dipinjam) {
                back('buku.php', "Stok total tidak boleh kurang dari $dipinjam (sedang dipinjam).", 'err');
            }
            $data[] = $data[6] - $dipinjam;
            $data[] = $id;
            $pdo->prepare('UPDATE buku SET judul=?, pengarang=?, penerbit=?, tahun=?, isbn=?, kategori_id=?, stok_total=?, stok_tersedia=? WHERE id=?')
                ->execute($data);
            back('buku.php', 'Buku diperbarui.');
        }
        $data[] = $data[6];
        $pdo->prepare('INSERT INTO buku (judul, pengarang, penerbit, tahun, isbn, kategori_id, stok_total, stok_tersedia) VALUES (?,?,?,?,?,?,?,?)')
            ->execute($data);
        back('buku.php', 'Buku ditambahkan.');
    }

    if ($aksi === 'hapus') {
        try {
            $pdo->prepare('DELETE FROM buku WHERE id=?')->execute([(int) post('id')]);
            back('buku.php', 'Buku dihapus.');
        } catch (PDOException $ex) {
            back('buku.php', 'Buku tidak bisa dihapus karena punya riwayat peminjaman.', 'err');
        }
    }

    if ($aksi === 'kategori_tambah') {
        $nama = post('nama');
        if ($nama === '') {
            back('buku.php', 'Nama kategori wajib diisi.', 'err');
        }
        try {
            $pdo->prepare('INSERT INTO kategori (nama) VALUES (?)')->execute([$nama]);
            back('buku.php', 'Kategori ditambahkan.');
        } catch (PDOException $ex) {
            back('buku.php', 'Kategori sudah ada.', 'err');
        }
    }

    if ($aksi === 'kategori_hapus') {
        $pdo->prepare('DELETE FROM kategori WHERE id=?')->execute([(int) post('id')]);
        back('buku.php', 'Kategori dihapus. Buku terkait jadi tanpa kategori.');
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM buku WHERE id=?');
    $st->execute([(int) $_GET['edit']]);
    $edit = $st->fetch() ?: null;
}

$kategori = $pdo->query('SELECT k.*, COUNT(b.id) AS jumlah FROM kategori k LEFT JOIN buku b ON b.kategori_id=k.id GROUP BY k.id ORDER BY k.nama')->fetchAll();

$q = trim($_GET['q'] ?? '');
$sql = 'SELECT b.*, k.nama AS kategori FROM buku b LEFT JOIN kategori k ON k.id=b.kategori_id';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE b.judul LIKE ? OR b.pengarang LIKE ? OR b.isbn LIKE ?';
    $like = '%' . $q . '%';
    $params = [$like, $like, $like];
}
$st = $pdo->prepare($sql . ' ORDER BY b.judul');
$st->execute($params);
$buku = $st->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="grid grid-2">
    <div class="card">
        <h2><?= $edit ? 'Edit Buku' : 'Tambah Buku' ?></h2>
        <form method="post" autocomplete="off">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="simpan">
            <input type="hidden" name="id" value="<?= $edit ? (int) $edit['id'] : 0 ?>">
            <div class="form-grid">
                <div class="full">
                    <label for="judul">Judul</label>
                    <input id="judul" name="judul" value="<?= e($edit['judul'] ?? '') ?>" required>
                </div>
                <div>
                    <label for="pengarang">Pengarang</label>
                    <input id="pengarang" name="pengarang" value="<?= e($edit['pengarang'] ?? '') ?>">
                </div>
                <div>
                    <label for="penerbit">Penerbit</label>
                    <input id="penerbit" name="penerbit" value="<?= e($edit['penerbit'] ?? '') ?>">
                </div>
                <div>
                    <label for="tahun">Tahun</label>
                    <input id="tahun" name="tahun" type="number" min="1900" max="2100" value="<?= e($edit['tahun'] ?? '') ?>">
                </div>
                <div>
                    <label for="isbn">ISBN</label>
                    <input id="isbn" name="isbn" value="<?= e($edit['isbn'] ?? '') ?>">
                </div>
                <div>
                    <label for="kategori_id">Kategori</label>
                    <select id="kategori_id" name="kategori_id">
                        <option value="">- tanpa kategori -</option>
                        <?php foreach ($kategori as $k): ?>
                            <option value="<?= (int) $k['id'] ?>" <?= (int) ($edit['kategori_id'] ?? 0) === (int) $k['id'] ? 'selected' : '' ?>><?= e($k['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="stok_total">Stok Total</label>
                    <input id="stok_total" name="stok_total" type="number" min="0" value="<?= e($edit['stok_total'] ?? 1) ?>" required>
                </div>
            </div>
            <p style="margin-top:14px">
                <button class="btn btn-primary" type="submit"><?= $edit ? 'Simpan Perubahan' : 'Tambah Buku' ?></button>
                <?php if ($edit): ?><a class="btn" href="buku.php">Batal</a><?php endif; ?>
            </p>
        </form>
    </div>

    <div class="card">
        <h2>Kategori</h2>
        <form class="inline-form" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="kategori_tambah">
            <input name="nama" placeholder="Nama kategori baru" required>
            <button class="btn" type="submit">Tambah</button>
        </form>
        <div class="table-wrap" style="margin-top:12px">
            <table>
                <thead><tr><th>Kategori</th><th>Buku</th><th></th></tr></thead>
                <tbody>
                <?php if (!$kategori): ?>
                    <tr><td colspan="3" class="empty">Belum ada kategori.</td></tr>
                <?php endif; ?>
                <?php foreach ($kategori as $k): ?>
                    <tr>
                        <td><?= e($k['nama']) ?></td>
                        <td><?= (int) $k['jumlah'] ?></td>
                        <td>
                            <form method="post" style="display:inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="aksi" value="kategori_hapus">
                                <input type="hidden" name="id" value="<?= (int) $k['id'] ?>">
                                <button class="btn btn-sm btn-danger" type="submit">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <h2>Daftar Buku</h2>
        <form class="inline-form" method="get">
            <input name="q" value="<?= e($q) ?>" placeholder="Cari buku">
            <button class="btn" type="submit">Cari</button>
        </form>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Judul</th><th>Pengarang</th><th>Kategori</th><th>Tahun</th><th>Stok</th><th></th></tr></thead>
            <tbody>
            <?php if (!$buku): ?>
                <tr><td colspan="6" class="empty">Tidak ada buku.</td></tr>
            <?php endif; ?>
            <?php foreach ($buku as $b): ?>
                <tr>
                    <td>
                        <strong><?= e($b['judul']) ?></strong>
                        <div class="muted" style="font-size:12px"><?= e($b['penerbit']) ?><?= $b['isbn'] ? ' &middot; ' . e($b['isbn']) : '' ?></div>
                    </td>
                    <td><?= e($b['pengarang']) ?></td>
                    <td><?= e($b['kategori'] ?? '-') ?></td>
                    <td><?= e($b['tahun']) ?></td>
                    <td><?= (int) $b['stok_tersedia'] ?> / <?= (int) $b['stok_total'] ?></td>
                    <td class="nowrap">
                        <div class="actions">
                            <a class="btn btn-sm" href="buku.php?edit=<?= (int) $b['id'] ?>">Edit</a>
                            <form method="post" style="display:inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="aksi" value="hapus">
                                <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                                <button class="btn btn-sm btn-danger" type="submit">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
