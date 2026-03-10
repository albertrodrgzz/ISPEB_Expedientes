<?php
// Script to simulate a login POST request to index.php
$url = 'http://localhost/APP3/index.php'; // assuming XAMPP is running, but let's just include the file instead.
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['login'] = '1';
$_POST['username'] = 'arodriguez';
$_POST['password'] = '123456';

// Let's see what happens before the redirect
ob_start();
require_once __DIR__ . '/index.php';
$output = ob_get_clean();

echo "Error variable: " . (isset($error) ? $error : 'Not set') . "\n";
echo "Session: \n";
print_r($_SESSION);
