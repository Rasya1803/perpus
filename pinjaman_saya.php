<?php
require __DIR__ . '/config/functions.php';
$u = auth(['pengguna']);
$title = 'Pinjaman Saya';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('aksi') === 'batal') {
        $st = $pdo->prepare("UPDATE peminjaman SET status='dibatalkan'
                             WHERE id=? AND user_id=? AND status='menunggu'");
        $st->execute([(int) post('id'), $u['id']]);
        back('pinjaman_saya.php', $st->rowCount() ? 'Ajuan pinjam dibatalkan.' : 'Ajuan tidak bisa dibatalkan.', $st->rowCount() ? 'ok' : 'err');
    }
}

$st = $pdo->prepare("SELECT p.*, b.judul, b.pengarang FROM peminjaman p
                     JOIN buku b ON b.id=p.buku_id
                     WHERE p.user_id=? ORDER BY p.id DESC");
$st->execute([$u['id']]);
$data = $st->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="card">
    <div class="card-head"><h2>Riwayat Pinjaman</h2><a class="btn btn-sm btn-primary" href="katalog.php">Pinjam Buku</a></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Buku</th><th>Tanggal Pinjam</th><th>Jatuh Tempo</th><th>Dikembalikan</th><th>Status</th><th>Denda</th><th></th></tr></thead>
            <tbody>
            <?php if (!$data): ?>
                <tr><td colspan="7" class="empty">Belum ada pinjaman.</td></tr>
            <?php endif; ?>
            <?php foreach ($data as $r): ?>
                <tr>
                    <td><strong><?= e($r['judul']) ?></strong><div class="muted" style="font-size:12px"><?= e($r['pengarang']) ?></div></td>
                    <td class="nowrap"><?= e($r['tgl_pinjam'] ?? '-') ?></td>
                    <td class="nowrap">
                        <?= e($r['tgl_jatuh_tempo'] ?? '-') ?>
                        <?php if ($r['status'] === 'dipinjam' && $r['tgl_jatuh_tempo'] < date('Y-m-d')): ?>
                            <span class="badge red">Telat</span>
                        <?php endif; ?>
                    </td>
                    <td class="nowrap"><?= e($r['tgl_kembali'] ?? '-') ?></td>
                    <td><?= status_badge($r['status']) ?></td>
                    <td class="nowrap"><?= $r['denda'] ? 'Rp ' . number_format($r['denda'], 0, ',', '.') : '-' ?></td>
                    <td>
                        <?php if ($r['status'] === 'menunggu'): ?>
                            <form method="post" style="display:inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="aksi" value="batal">
                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                <button class="btn btn-sm" type="submit">Batalkan</button>
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
