<?php
require __DIR__ . '/includes/bootstrap.php';  // also rejects requests without a valid CSRF token

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit();
