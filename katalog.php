<?php
require __DIR__ . '/config/functions.php';
$u = auth(['pengguna']);
$title = 'Katalog Buku';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $bukuId = (int) post('buku_id');

    $st = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE user_id=? AND status IN ('menunggu','dipinjam')");
    $st->execute([$u['id']]);
    if ((int) $st->fetchColumn() >= 3) {
        back('katalog.php', 'Maksimal 3 pinjaman aktif. Kembalikan buku dulu.', 'err');
    }

    $st = $pdo->prepare("SELECT stok_tersedia FROM buku WHERE id=?");
    $st->execute([$bukuId]);
    $stok = $st->fetchColumn();
    if ($stok === false) {
        back('katalog.php', 'Buku tidak ditemukan.', 'err');
    }
    if ((int) $stok < 1) {
        back('katalog.php', 'Stok buku sedang habis.', 'err');
    }

    $st = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE user_id=? AND buku_id=? AND status IN ('menunggu','dipinjam')");
    $st->execute([$u['id'], $bukuId]);
    if ((int) $st->fetchColumn() > 0) {
        back('katalog.php', 'Buku ini sudah ada di pinjaman aktif kamu.', 'err');
    }

    $st = $pdo->prepare("INSERT INTO peminjaman (user_id, buku_id, status) VALUES (?,?,'menunggu')");
    $st->execute([$u['id'], $bukuId]);
    back('katalog.php', 'Ajuan pinjam terkirim. Tunggu persetujuan petugas.');
}

$q = trim($_GET['q'] ?? '');
$sql = 'SELECT b.*, k.nama AS kategori FROM buku b LEFT JOIN kategori k ON k.id=b.kategori_id';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE b.judul LIKE ? OR b.pengarang LIKE ? OR b.isbn LIKE ?';
    $like = '%' . $q . '%';
    $params = [$like, $like, $like];
}
$sql .= ' ORDER BY b.judul';
$st = $pdo->prepare($sql);
$st->execute($params);
$buku = $st->fetchAll();

$st = $pdo->prepare("SELECT buku_id FROM peminjaman WHERE user_id=? AND status IN ('menunggu','dipinjam')");
$st->execute([$u['id']]);
$aktif = array_column($st->fetchAll(), 'buku_id');

require __DIR__ . '/includes/header.php';
?>

<div class="card">
    <form class="inline-form" method="get">
        <input name="q" value="<?= e($q) ?>" placeholder="Cari judul, pengarang, atau ISBN">
        <button class="btn" type="submit">Cari</button>
        <?php if ($q !== ''): ?><a class="btn" href="katalog.php">Reset</a><?php endif; ?>
    </form>
</div>

<div class="card">
    <div class="card-head"><h2>Daftar Buku</h2><span class="muted"><?= count($buku) ?> judul</span></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Judul</th><th>Pengarang</th><th>Kategori</th><th>Tahun</th><th>Stok</th><th></th></tr></thead>
            <tbody>
            <?php if (!$buku): ?>
                <tr><td colspan="6" class="empty">Buku tidak ditemukan.</td></tr>
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
                        <?php if (in_array($b['id'], $aktif, true)): ?>
                            <span class="badge blue">Sudah diajukan</span>
                        <?php elseif ((int) $b['stok_tersedia'] < 1): ?>
                            <span class="badge grey">Habis</span>
                        <?php else: ?>
                            <form method="post" style="display:inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="buku_id" value="<?= (int) $b['id'] ?>">
                                <button class="btn btn-primary btn-sm" type="submit">Ajukan Pinjam</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
