<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/scraper.php';
require_once __DIR__ . '/../includes/api_fetch.php';
require_once __DIR__ . '/../includes/logger.php';

$product_id = (int)($_GET['product_id'] ?? 0);
if ($product_id <= 0) { json_err('Invalid product'); }

// Ensure user tracks this product
$stmt = $db->prepare('SELECT p.* FROM products p JOIN wishlist w ON w.product_id=p.id WHERE p.id=? AND w.user_id=? LIMIT 1');
$stmt->execute([$product_id, $_SESSION['user_id']]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) { json_err('Not found', 404); }

try {
    $newPrice = api_fetch_price($product['url']);
    if ($newPrice === null) {
        $meta = scrape_product($product['url']);
        $newPrice = (float)($meta['current_price'] ?? 0);
    }
    if ($newPrice <= 0) { json_err('Unable to fetch price'); }

    if ((float)$product['current_price'] !== (float)$newPrice) {
        $upd = $db->prepare('UPDATE products SET current_price=?, last_checked=NOW() WHERE id=?');
        $upd->execute([$newPrice, $product_id]);
        $ph = $db->prepare('INSERT INTO price_history(product_id, price, checked_at) VALUES (?, ?, NOW())');
        $ph->execute([$product_id, $newPrice]);
        app_log("Product {$product_id} updated from {$product['current_price']} -> {$newPrice}");
    } else {
        $upd = $db->prepare('UPDATE products SET last_checked=NOW() WHERE id=?');
        $upd->execute([$product_id]);
    }

    json_ok(['current_price' => (float)$newPrice]);
} catch (\Throwable $e) {
    json_err('Refresh failed');
}
