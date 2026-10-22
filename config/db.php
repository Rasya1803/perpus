<?php
// Koneksi XAMPP default. Ubah bila user/password MySQL berbeda.
$pdo = new PDO('mysql:host=127.0.0.1;dbname=perpus;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
]);
