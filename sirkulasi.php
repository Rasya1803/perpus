<?php
require __DIR__ . '/config/functions.php';
$u = auth(['petugas', 'admin']);
$title = 'Sirkulasi';
$hariIni = date('Y-m-d');
$batas = date('Y-m-d', strtotime('+7 days'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = post('aksi');
    $id = (int) post('id');

    if ($aksi === 'setujui') {
        try {
            $pdo->beginTransaction();
            $st = $pdo->prepare('SELECT * FROM peminjaman WHERE id=? FOR UPDATE');
            $st->execute([$id]);
            $p = $st->fetch();
            if (!$p || $p['status'] !== 'menunggu') {
                throw new RuntimeException('Ajuan tidak valid atau sudah diproses.');
            }
            $st = $pdo->prepare('SELECT stok_tersedia FROM buku WHERE id=? FOR UPDATE');
            $st->execute([$p['buku_id']]);
            if ((int) $st->fetchColumn() < 1) {
                throw new RuntimeException('Stok buku habis.');
            }
            $pdo->prepare('UPDATE buku SET stok_tersedia = stok_tersedia - 1 WHERE id=?')->execute([$p['buku_id']]);
            $pdo->prepare("UPDATE peminjaman SET status='dipinjam', tgl_pinjam=?, tgl_jatuh_tempo=? WHERE id=?")
                ->execute([$hariIni, $batas, $id]);
            $pdo->commit();
            back('sirkulasi.php', 'Pinjaman disetujui. Jatuh tempo ' . date('d M Y', strtotime($batas)) . '.');
        } catch (RuntimeException $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            back('sirkulasi.php', $ex->getMessage(), 'err');
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            back('sirkulasi.php', 'Terjadi kesalahan server saat memproses data.', 'err');
        }
    }

    if ($aksi === 'tolak') {
        $st = $pdo->prepare("UPDATE peminjaman SET status='ditolak' WHERE id=? AND status='menunggu'");
        $st->execute([$id]);
        back('sirkulasi.php', $st->rowCount() ? 'Ajuan ditolak.' : 'Ajuan tidak valid.', $st->rowCount() ? 'ok' : 'err');
    }

    if ($aksi === 'kembali') {
        try {
            $pdo->beginTransaction();
            $st = $pdo->prepare('SELECT * FROM peminjaman WHERE id=? FOR UPDATE');
            $st->execute([$id]);
            $p = $st->fetch();
            if (!$p || $p['status'] !== 'dipinjam') {
                throw new RuntimeException('Data pinjaman tidak valid.');
            }
            $denda = hitung_denda($p['tgl_jatuh_tempo'], $hariIni);
            $pdo->prepare("UPDATE peminjaman SET status='dikembalikan', tgl_kembali=?, denda=? WHERE id=?")
                ->execute([$hariIni, $denda, $id]);
            $pdo->prepare('UPDATE buku SET stok_tersedia = stok_tersedia + 1 WHERE id=?')->execute([$p['buku_id']]);
            $pdo->commit();
            $pesan = 'Pengembalian dicatat.' . ($denda ? ' Denda Rp ' . number_format($denda, 0, ',', '.') : ' Tanpa denda.');
            back('sirkulasi.php', $pesan);
        } catch (RuntimeException $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            back('sirkulasi.php', $ex->getMessage(), 'err');
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            back('sirkulasi.php', 'Terjadi kesalahan server saat memproses data.', 'err');
        }
    }
}

$st = $pdo->prepare("SELECT p.*, b.judul, b.stok_tersedia, u.nama, u.kelas FROM peminjaman p
                     JOIN buku b ON b.id=p.buku_id JOIN users u ON u.id=p.user_id
                     WHERE p.status='menunggu' ORDER BY p.id ASC");
$st->execute();
$menunggu = $st->fetchAll();

$st = $pdo->prepare("SELECT p.*, b.judul, u.nama, u.kelas FROM peminjaman p
                     JOIN buku b ON b.id=p.buku_id JOIN users u ON u.id=p.user_id
                     WHERE p.status='dipinjam' ORDER BY p.tgl_jatuh_tempo ASC");
$st->execute();
$dipinjam = $st->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="card">
    <div class="card-head"><h2>Menunggu Persetujuan</h2><span class="muted"><?= count($menunggu) ?> ajuan</span></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Tanggal Ajuan</th><th>Anggota</th><th>Buku</th><th>Stok</th><th></th></tr></thead>
            <tbody>
            <?php if (!$menunggu): ?>
                <tr><td colspan="5" class="empty">Tidak ada ajuan baru.</td></tr>
            <?php endif; ?>
            <?php foreach ($menunggu as $r): ?>
                <tr>
                    <td class="nowrap"><?= e(date('d M Y', strtotime($r['dibuat']))) ?></td>
                    <td><?= e($r['nama']) ?><?= $r['kelas'] ? ' <span class="muted">&middot; ' . e($r['kelas']) . '</span>' : '' ?></td>
                    <td><?= e($r['judul']) ?></td>
                    <td><?= $r['stok_tersedia'] > 0 ? '<span class="badge green">tersedia</span>' : '<span class="badge red">habis</span>' ?></td>
                    <td class="nowrap">
                        <div class="actions">
                            <form method="post" style="display:inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="aksi" value="setujui">
                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                <button class="btn btn-sm btn-success" type="submit">Setujui</button>
                            </form>
                            <form method="post" style="display:inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="aksi" value="tolak">
                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                <button class="btn btn-sm btn-danger" type="submit">Tolak</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-head"><h2>Sedang Dipinjam</h2><span class="muted"><?= count($dipinjam) ?> buku</span></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Anggota</th><th>Buku</th><th>Tanggal Pinjam</th><th>Jatuh Tempo</th><th></th></tr></thead>
            <tbody>
            <?php if (!$dipinjam): ?>
                <tr><td colspan="5" class="empty">Tidak ada pinjaman aktif.</td></tr>
            <?php endif; ?>
            <?php foreach ($dipinjam as $r): ?>
                <tr>
                    <td><?= e($r['nama']) ?><?= $r['kelas'] ? ' <span class="muted">&middot; ' . e($r['kelas']) . '</span>' : '' ?></td>
                    <td><?= e($r['judul']) ?></td>
                    <td class="nowrap"><?= e($r['tgl_pinjam']) ?></td>
                    <td class="nowrap">
                        <?= e($r['tgl_jatuh_tempo']) ?>
                        <?php if ($r['tgl_jatuh_tempo'] < $hariIni): ?>
                            <span class="badge red">Telat</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="post" style="display:inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="aksi" value="kembali">
                            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                            <button class="btn btn-sm btn-primary" type="submit">Kembalikan</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
