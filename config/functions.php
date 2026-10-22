<?php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function user(): ?array
{
    global $pdo;
    if (empty($_SESSION['uid'])) {
        return null;
    }
    $st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([$_SESSION['uid']]);
    return $st->fetch() ?: null;
}

function auth(array $roles = []): array
{
    $u = user();
    if (!$u) {
        header('Location: login.php');
        exit;
    }
    if ($roles && !in_array($u['role'], $roles, true)) {
        http_response_code(403);
        exit('Akses ditolak.');
    }
    return $u;
}

function post(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $token = (string) ($_POST['csrf'] ?? '');
    if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(400);
        exit('Token CSRF tidak valid.');
    }
}

function flash(?string $msg = null, string $type = 'ok'): ?array
{
    if ($msg !== null) {
        $_SESSION['flash'] = [$msg, $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function back(string $url, string $msg, string $type = 'ok'): void
{
    flash($msg, $type);
    header('Location: ' . $url);
    exit;
}

/** Denda Rp1.000 per hari telat. */
function hitung_denda(string $jatuh, string $kembali): int
{
    $selisih = (strtotime($kembali) - strtotime($jatuh)) / 86400;
    return max(0, (int) ceil($selisih)) * 1000;
}

function status_badge(string $status): string
{
    $warna = [
        'menunggu'      => 'gold',
        'dipinjam'      => 'blue',
        'dikembalikan'  => 'green',
        'ditolak'       => 'red',
        'dibatalkan'    => 'grey',
    ];
    return '<span class="badge ' . ($warna[$status] ?? 'grey') . '">' . e(ucfirst($status)) . '</span>';
}
