<?php
require __DIR__ . '/config/functions.php';
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
