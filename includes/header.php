<?php
require_once __DIR__ . '/../config/functions.php';
$u = auth();
$current = basename($_SERVER['PHP_SELF']);
$menu = ['dashboard.php' => 'Dashboard'];
if ($u['role'] === 'pengguna') {
    $menu += ['katalog.php' => 'Katalog Buku', 'pinjaman_saya.php' => 'Pinjaman Saya'];
} elseif ($u['role'] === 'petugas') {
    $menu += ['sirkulasi.php' => 'Sirkulasi'];
} elseif ($u['role'] === 'operator') {
    $menu += ['buku.php' => 'Kelola Buku'];
} elseif ($u['role'] === 'admin') {
    $menu += ['users.php' => 'Kelola User', 'buku.php' => 'Kelola Buku', 'laporan.php' => 'Laporan'];
}
$menu['profil.php'] = 'Profil Saya';
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Perpustakaan') ?> &middot; Perpustakaan Sekolah</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <a class="brand" href="dashboard.php"><span class="brand-mark">P</span> Perpustakaan</a>
        <nav class="nav">
            <span class="nav-label">Menu</span>
            <?php foreach ($menu as $href => $label): ?>
                <a href="<?= e($href) ?>" class="<?= $current === $href ? 'active' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="side-foot">
            <strong><?= e($u['nama']) ?></strong>
            <span class="role"><?= e($u['role']) ?></span>
            <a href="logout.php">Keluar</a>
        </div>
    </aside>
    <main class="main">
        <div class="topbar">
            <h1><?= e($title ?? 'Dashboard') ?></h1>
            <span class="who"><?= e(date('d M Y')) ?></span>
        </div>
        <?php if ($f = flash()): ?>
            <div class="alert <?= e($f[1]) ?>"><?= e($f[0]) ?></div>
        <?php endif; ?>
