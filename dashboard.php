<?php
require __DIR__ . '/config/functions.php';
$u = auth();
$title = 'Dashboard';
$kicker = $u['role'] === 'pengguna' ? 'Anggota' : 'Panel ' . ucfirst($u['role']);
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

    $st = $pdo->prepare("SELECT MIN(tgl_jatuh_tempo) FROM peminjaman
                         WHERE user_id=? AND status='dipinjam'");
    $st->execute([$u['id']]);
    $dekat = $st->fetchColumn() ?: 'Tidak ada';
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
    <section class="hero">
        <div class="hero-text">
            <div class="kicker">Selamat datang</div>
            <h2>Halo, <em><?= e(explode(' ', $u['nama'])[0]) ?></em>.<br>Mau baca apa hari ini?</h2>
            <p class="hero-sub">Katalog, pinjaman, dan riwayat kamu — semua di satu tempat.</p>
            <a class="btn btn-primary" href="katalog.php">Cari Buku</a>
        </div>
        <ul class="hero-tickets">
            <li><span>Pinjaman aktif</span><b><?= $aktif ?></b></li>
            <li><span>Terlambat</span><b><?= $telat ?></b></li>
            <li><span>Denda</span><b>Rp <?= number_format($denda, 0, ',', '.') ?></b></li>
        </ul>
    </section>
<?php else: ?>
    <section class="hero">
        <div class="hero-text">
            <div class="kicker">Ringkasan</div>
            <h2>Ruang kerja<br><em><?= e(ucfirst($u['role'])) ?></em>.</h2>
            <p class="hero-sub">Kondisi perpustakaan hari ini, apa pun peranmu.</p>
            <a class="btn btn-primary" href="<?= $u['role'] === 'operator' ? 'buku.php' : ($u['role'] === 'admin' ? 'users.php' : 'sirkulasi.php') ?>">Buka Kerja Utama</a>
        </div>
        <ul class="hero-tickets">
            <li><span>Menunggu</span><b><?= $menunggu ?></b></li>
            <li><span>Dipinjam</span><b><?= $dipinjam ?></b></li>
            <li><span>Terlambat</span><b><?= $telat ?></b></li>
        </ul>
    </section>
<?php endif; ?>

<?php if ($u['role'] === 'pengguna'): ?>
    <div class="grid grid-3">
        <div class="card stat"><div class="label">Sudah Selesai</div><div class="value"><?= $selesai ?></div></div>
        <div class="card stat"><div class="label">Sisa Kuota Pinjam</div><div class="value"><?= max(0, 3 - $aktif) ?><span class="muted" style="font-size:16px"> / 3</span></div></div>
        <div class="card stat"><div class="label">Jatuh Tempo Terdekat</div><div class="value small"><?= e($dekat ?? 'Tidak ada') ?></div></div>
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
