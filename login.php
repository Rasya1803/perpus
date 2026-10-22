<?php
require __DIR__ . '/config/functions.php';

if (user()) {
    header('Location: dashboard.php');
    exit;
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $st = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $st->execute([post('username')]);
    $u = $st->fetch();
    // Password plaintext sesuai permintaan.
    if ($u && $u['password'] === post('password')) {
        session_regenerate_id(true);
        $_SESSION['uid'] = $u['id'];
        header('Location: dashboard.php');
        exit;
    }
    $err = 'Username atau password salah.';
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk &middot; Perpustakaan Sekolah</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:SOFT,WONK,ital,opsz,wght@0..100,0..1,0,9..144,100..900;1,9..144,300..900&family=Karla:ital,wght@0,300..800;1,300..800&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css?v=<?= @filemtime(__DIR__ . '/assets/style.css') ?: '1' ?>">
</head>
<body>
<div class="auth-wrap">
    <aside class="auth-aside">
        <div>
            <span class="brand-mark">P</span>
            <h2>Perpustakaan<br>Sekolah, <em>tanpa ribet</em>.</h2>
            <p>Satu tempat untuk katalog, peminjaman, dan pengembalian. Setiap peran punya ruang kerja sendiri.</p>
            <div class="spines-lg"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
        </div>
        <div class="auth-facts">
            <div><span>Peran</span><b>4</b></div>
            <div><span>Peminjaman</span><b>7</b><span style="display:inline">hari</span></div>
            <div><span>Denda</span><b>1.000</b><span style="display:inline">/hari</span></div>
        </div>
    </aside>

    <main class="auth-main">
        <div class="auth-card">
            <div class="brand">
                <span class="brand-mark">P</span>
                <span>
                    <span class="brand-name">Perpustakaan</span>
                    <span class="brand-sub">Sekolah</span>
                </span>
            </div>
            <p class="sub">Masuk dengan akun yang diberikan sekolah.</p>

            <?php if ($err): ?>
                <div class="alert err"><?= e($err) ?></div>
            <?php endif; ?>

            <form method="post" autocomplete="off">
                <?= csrf_field() ?>
                <div>
                    <label for="username">Username</label>
                    <input id="username" name="username" required autofocus>
                </div>
                <div style="margin-top:14px">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required>
                </div>
                <button class="btn btn-primary" type="submit">Masuk</button>
            </form>

            <div class="auth-hint">
                Akun demo<br>
                <b>admin</b> / admin123 &nbsp;&middot;&nbsp; <b>petugas</b> / petugas123<br>
                <b>operator</b> / operator123 &nbsp;&middot;&nbsp; <b>siswa</b> / siswa123
            </div>
        </div>
    </main>
</div>
</body>
</html>
