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
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <a class="brand" href="index.php"><span class="brand-mark">P</span> Perpustakaan</a>
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
            <div style="margin-top:12px">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required>
            </div>
            <button class="btn btn-primary" type="submit">Masuk</button>
        </form>

        <div class="auth-hint">
            Akun demo &mdash; admin/admin123 &middot; petugas/petugas123 &middot; operator/operator123 &middot; siswa/siswa123
        </div>
    </div>
</div>
</body>
</html>
