<?php
require __DIR__ . '/config/functions.php';
$u = auth(['admin']);
$title = 'Laporan Peminjaman';

$status = $_GET['status'] ?? '';
$valid  = ['menunggu', 'dipinjam', 'dikembalikan', 'ditolak', 'dibatalkan'];
if (!in_array($status, $valid, true)) {
    $status = '';
}
$dari   = $_GET['dari'] ?? '';
$sampai = $_GET['sampai'] ?? '';

$where = [];
$params = [];
if ($status !== '') {
    $where[] = 'p.status = ?';
    $params[] = $status;
}
if ($dari !== '') {
    $where[] = 'DATE(p.dibuat) >= ?';
    $params[] = $dari;
}
if ($sampai !== '') {
    $where[] = 'DATE(p.dibuat) <= ?';
    $params[] = $sampai;
}
$sql = 'SELECT p.*, b.judul, u.nama, u.kelas FROM peminjaman p
        JOIN buku b ON b.id=p.buku_id JOIN users u ON u.id=p.user_id'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . ' ORDER BY p.id DESC';

$st = $pdo->prepare($sql);
$st->execute($params);
$data = $st->fetchAll();

if (isset($_GET['csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan-peminjaman-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Anggota', 'Kelas', 'Buku', 'Tgl Pinjam', 'Jatuh Tempo', 'Tgl Kembali', 'Status', 'Denda']);
    foreach ($data as $r) {
        fputcsv($out, [$r['id'], $r['nama'], $r['kelas'], $r['judul'], $r['tgl_pinjam'], $r['tgl_jatuh_tempo'], $r['tgl_kembali'], $r['status'], $r['denda']]);
    }
    fclose($out);
    exit;
}

$totalDenda = array_sum(array_column($data, 'denda'));

require __DIR__ . '/includes/header.php';
?>

<div class="grid grid-3">
    <div class="card stat"><div class="label">Baris Data</div><div class="value"><?= count($data) ?></div></div>
    <div class="card stat"><div class="label">Total Denda</div><div class="value small">Rp <?= number_format($totalDenda, 0, ',', '.') ?></div></div>
    <div class="card stat"><div class="label">Belum Kembali</div><div class="value"><?= count(array_filter($data, fn($r) => $r['status'] === 'dipinjam')) ?></div></div>
</div>

<div class="card">
    <form class="inline-form" method="get">
        <select name="status">
            <option value="">Semua status</option>
            <?php foreach ($valid as $s): ?>
                <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="dari" value="<?= e($dari) ?>" title="Dari tanggal">
        <input type="date" name="sampai" value="<?= e($sampai) ?>" title="Sampai tanggal">
        <button class="btn" type="submit">Terapkan</button>
        <a class="btn" href="laporan.php">Reset</a>
        <a class="btn btn-primary" href="laporan.php?<?= e(http_build_query(['status' => $status, 'dari' => $dari, 'sampai' => $sampai, 'csv' => 1])) ?>">Export CSV</a>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Anggota</th><th>Buku</th><th>Pinjam</th><th>Jatuh Tempo</th><th>Kembali</th><th>Status</th><th>Denda</th></tr></thead>
            <tbody>
            <?php if (!$data): ?>
                <tr><td colspan="7" class="empty">Tidak ada data pada filter ini.</td></tr>
            <?php endif; ?>
            <?php foreach ($data as $r): ?>
                <tr>
                    <td><?= e($r['nama']) ?><?= $r['kelas'] ? ' <span class="muted">&middot; ' . e($r['kelas']) . '</span>' : '' ?></td>
                    <td><?= e($r['judul']) ?></td>
                    <td class="nowrap"><?= e($r['tgl_pinjam'] ?? '-') ?></td>
                    <td class="nowrap"><?= e($r['tgl_jatuh_tempo'] ?? '-') ?></td>
                    <td class="nowrap"><?= e($r['tgl_kembali'] ?? '-') ?></td>
                    <td><?= status_badge($r['status']) ?></td>
                    <td class="nowrap"><?= $r['denda'] ? 'Rp ' . number_format($r['denda'], 0, ',', '.') : '-' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
