<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$product_id = (int)($_GET['product_id'] ?? 0);
if ($product_id <= 0) { json_err('Invalid product'); }

// Ensure the user has access via wishlist
$chk = $db->prepare('SELECT 1 FROM wishlist WHERE product_id=? AND user_id=? LIMIT 1');
$chk->execute([$product_id, $_SESSION['user_id']]);
if (!$chk->fetch()) { json_err('Not found', 404); }

$stmt = $db->prepare('SELECT price, checked_at FROM price_history WHERE product_id=? ORDER BY checked_at ASC');
$stmt->execute([$product_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
json_ok($rows);
