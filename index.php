<?php
require __DIR__ . '/config/functions.php';
header('Location: ' . (user() ? 'dashboard.php' : 'login.php'));
exit;
