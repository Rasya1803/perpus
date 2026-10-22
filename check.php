<?php
// Cek logika denda. Jalankan: C:\xampp\php\php.exe check.php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/config/functions.php';

$kasus = [
    // [jatuh tempo, tanggal kembali, denda harapan]
    ['2026-01-10', '2026-01-10', 0],   // tepat waktu
    ['2026-01-10', '2026-01-09', 0],   // lebih awal
    ['2026-01-10', '2026-01-11', 1000], // telat 1 hari
    ['2026-01-10', '2026-01-17', 7000], // telat 7 hari
];

foreach ($kasus as [$jatuh, $kembali, $harap]) {
    $hasil = hitung_denda($jatuh, $kembali);
    if ($hasil !== $harap) {
        fwrite(STDERR, "GAGAL: $jatuh -> $kembali = $hasil, harusnya $harap\n");
        exit(1);
    }
}

echo "OK: semua kasus denda lolos.\n";
