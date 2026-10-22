<?php
require __DIR__ . '/config/functions.php';
$u = auth();
$title = 'Dashboard';
$hariIni = date('Y-m-d');

$one = function (string $sql, array $p = []) use ($pdo) {
    $st = $pdo->prepare($sql);
    $st->execute($p);
    return $st->fetchColumn();
};

if ($u['role'] === 'pengguna') {
    $aktif   = (int) $one("SELECT COUNT(*) FROM peminjaman WHERE user_id=? AND status IN ('menunggu','dipinjam')", [$u['id']]);
    $telat   = (int) $one("SELECT COUNT(*) FROM peminjaman WHERE user_id=? AND status='dipinjam' AND tgl_jatuh_tempo < ?", [$u['id'], $hariIni]);
    $selesai = (int) $one("SELECT COUNT(*) FROM peminjaman WHERE user_id=? AND status='dikembalikan'", [$u['id']]);
    $denda   = (int) $one("SELECT COALESCE(SUM(denda),0) FROM peminjaman WHERE user_id=?", [$u['id']]);

    $st = $pdo->prepare("SELECT p.*, b.judul FROM peminjaman p JOIN buku b ON b.id=p.buku_id
                         WHERE p.user_id=? ORDER BY p.id DESC LIMIT 5");
    $st->execute([$u['id']]);
    $riwayat = $st->fetchAll();
} else {
    $buku     = (int) $one("SELECT COUNT(*) FROM buku");
    $stok     = (int) $one("SELECT COALESCE(SUM(stok_tersedia),0) FROM buku");
    $menunggu = (int) $one("SELECT COUNT(*) FROM peminjaman WHERE status='menunggu'");
    $dipinjam = (int) $one("SELECT COUNT(*) FROM peminjaman WHERE status='dipinjam'");
    $telat    = (int) $one("SELECT COUNT(*) FROM peminjaman WHERE status='dipinjam' AND tgl_jatuh_tempo < ?", [$hariIni]);
    $anggota  = (int) $one("SELECT COUNT(*) FROM users WHERE role='pengguna'");
    $denda    = (int) $one("SELECT COALESCE(SUM(denda),0) FROM peminjaman");

    $st = $pdo->query("SELECT p.*, b.judul, u.nama FROM peminjaman p
                       JOIN buku b ON b.id=p.buku_id JOIN users u ON u.id=p.user_id
                       ORDER BY p.id DESC LIMIT 8");
    $riwayat = $st->fetchAll();
}

require __DIR__ . '/includes/header.php';
?>

<?php if ($u['role'] === 'pengguna'): ?>
    <div class="grid grid-4">
        <div class="card stat"><div class="label">Pinjaman Aktif</div><div class="value"><?= $aktif ?></div></div>
        <div class="card stat"><div class="label">Terlambat</div><div class="value"><?= $telat ?></div></div>
        <div class="card stat"><div class="label">Selesai</div><div class="value"><?= $selesai ?></div></div>
        <div class="card stat"><div class="label">Total Denda</div><div class="value small">Rp <?= number_format($denda, 0, ',', '.') ?></div></div>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>Pinjaman Terakhir</h2>
            <a class="btn btn-sm" href="pinjaman_saya.php">Lihat semua</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Buku</th><th>Jatuh Tempo</th><th>Status</th><th>Denda</th></tr></thead>
                <tbody>
                <?php if (!$riwayat): ?>
                    <tr><td colspan="4" class="empty">Belum ada pinjaman. <a href="katalog.php">Cari buku</a>.</td></tr>
                <?php endif; ?>
                <?php foreach ($riwayat as $r): ?>
                    <tr>
                        <td><?= e($r['judul']) ?></td>
                        <td class="nowrap"><?= e($r['tgl_jatuh_tempo'] ?? '-') ?></td>
                        <td><?= status_badge($r['status']) ?></td>
                        <td class="nowrap"><?= $r['denda'] ? 'Rp ' . number_format($r['denda'], 0, ',', '.') : '-' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="grid grid-4">
        <div class="card stat"><div class="label">Menunggu Persetujuan</div><div class="value"><?= $menunggu ?></div></div>
        <div class="card stat"><div class="label">Sedang Dipinjam</div><div class="value"><?= $dipinjam ?></div></div>
        <div class="card stat"><div class="label">Terlambat</div><div class="value"><?= $telat ?></div></div>
        <div class="card stat"><div class="label">Total Denda</div><div class="value small">Rp <?= number_format($denda, 0, ',', '.') ?></div></div>
    </div>
    <div class="grid grid-4" style="margin-top:14px">
        <div class="card stat"><div class="label">Judul Buku</div><div class="value"><?= $buku ?></div></div>
        <div class="card stat"><div class="label">Stok Tersedia</div><div class="value"><?= $stok ?></div></div>
        <div class="card stat"><div class="label">Anggota</div><div class="value"><?= $anggota ?></div></div>
        <div class="card stat"><div class="label">Akses</div><div class="value small" style="text-transform:capitalize"><?= e($u['role']) ?></div></div>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>Aktivitas Terbaru</h2>
            <?php if ($u['role'] === 'operator'): ?>
                <a class="btn btn-sm" href="buku.php">Kelola buku</a>
            <?php elseif ($u['role'] !== 'admin'): ?>
                <a class="btn btn-sm" href="sirkulasi.php">Buka sirkulasi</a>
            <?php else: ?>
                <a class="btn btn-sm" href="laporan.php">Lihat laporan</a>
            <?php endif; ?>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Anggota</th><th>Buku</th><th>Pinjam</th><th>Jatuh Tempo</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (!$riwayat): ?>
                    <tr><td colspan="5" class="empty">Belum ada aktivitas.</td></tr>
                <?php endif; ?>
                <?php foreach ($riwayat as $r): ?>
                    <tr>
                        <td><?= e($r['nama']) ?></td>
                        <td><?= e($r['judul']) ?></td>
                        <td class="nowrap"><?= e($r['tgl_pinjam'] ?? '-') ?></td>
                        <td class="nowrap"><?= e($r['tgl_jatuh_tempo'] ?? '-') ?></td>
                        <td><?= status_badge($r['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
