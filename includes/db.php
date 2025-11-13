<?php
require_once __DIR__ . '/config.php';
try {
    $host = env('DB_HOST', 'localhost');
    $name = env('DB_NAME', 'wishlist_tracker');
    $user = env('DB_USER', 'root');
    $pass = env('DB_PASS', '');
    $charset = env('DB_CHARSET', 'utf8mb4');
    $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";
    $db = new PDO($dsn, $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Database connection failed.';
    exit;
}
