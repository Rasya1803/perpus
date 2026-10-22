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
$kicker = $kicker ?? ($u['role'] === 'pengguna' ? 'Anggota' : 'Panel ' . ucfirst($u['role']));
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Perpustakaan') ?> &middot; Perpustakaan Sekolah</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:SOFT,WONK,ital,opsz,wght@0..100,0..1,0,9..144,100..900;1,9..144,300..900&family=Karla:ital,wght@0,300..800;1,300..800&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css?v=<?= @filemtime(__DIR__ . '/../assets/style.css') ?: '1' ?>">
</head>
<body>
<div class="layout role-<?= e($u['role']) ?>">
    <aside class="sidebar">
        <a class="brand" href="dashboard.php">
            <span class="brand-mark">P</span>
            <span class="brand-name">Perpustakaan</span>
            <span class="brand-sub">Sekolah &middot; Arsip Kertas</span>
            <span class="spines"><i></i><i></i><i></i><i></i><i></i><i></i></span>
        </a>
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
        <header class="masthead">
            <div class="masthead-text">
                <div class="kicker"><?= e($kicker) ?></div>
                <h1 class="masthead-title"><?= e($title ?? 'Dashboard') ?></h1>
                <div class="masthead-rule"></div>
            </div>
            <div class="stamp">
                <span class="stamp-date"><?= e(date('d M Y')) ?></span>
                <span class="stamp-role"><?= e($u['role']) ?></span>
            </div>
        </header>
        <?php if ($f = flash()): ?>
            <div class="alert <?= e($f[1]) ?>"><?= e($f[0]) ?></div>
        <?php endif; ?>
