<?php
require_once 'includes/db.php';
require_once 'includes/mailer.php';
require_once 'includes/logger.php';

// Simulate the exact conditions from price_checker.php
$pid = 1;
$price = 13000.00;
$currentPrice = 14499.00; // Simulate the old price
$url = 'https://www.flipkart.com/vivo-t4x-5g-marine-blue-128-gb/p/itm017656bdd097b?pid=MOBBH9JUSTWEMVADU&param=3838&ctx=eyJjYXJkQ29udGV4dCI6eyJhdHRyaWJ1dGVzIjp7InZhbHVlQ2FsbG91dCI6eyJtdWx0aVZhbHVlZEF0dHJpYnV0ZSI6eyJrZXkiOiJ2YWx1ZUNhbGxvdXQiLCJpbmZlcmVuY2VUeXBlIjoiVkFMVUVfQ0FMTE9VVCIsInZhbHVlcyI6WyJGcm9tIOKCuTEzLDk5OSoiXSwidmFsdWVUeXBlIjoiTVVMVElfVkFMVUVEIn19LCJ0aXRsZSI6eyJtdWx0aVZhbHVlZEF0dHJpYnV0ZSI6eyJrZXkiOiJ0aXRsZSIsImluZmVyZW5jZVR5cGUiOiJUSVRMRSIsInZhbHVlcyI6WyJWaXZvIFQ0eCA1RyJdLCJ2YWx1ZVR5cGUiOiJNVUxUSV9fWQUxVRUQifX0sImhlcm9QaWQiOnsic2luZ2xlVmFsdWVBdHRyaWJ1dGUiOnsia2V5IjoiaGVyb1BpZCIsImluZmVyZW5jZVR5cGUiOiJQSUQiLCJ2YWx1ZSI6Ik1PQkg5SlVTVFdFTVZBRFUiLCJ2YWx1ZVR5cGUiOiJTSU5HTEVfVkFMVUVEIn19fX19';

// Query wishlist items that should trigger alert
$wq = $GLOBALS['db']->prepare('SELECT w.id as wid, w.user_id, w.target_price, w.alert_sent, u.email, u.name, p.name as pname, p.image_url
                   FROM wishlist w
                   JOIN users u ON u.id = w.user_id
                   JOIN products p ON p.id = w.product_id
                   WHERE w.product_id=? AND ? <= w.target_price');
$wq->execute([$pid, $price]);
$wishlistItems = $wq->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($wishlistItems) . " wishlist items that should trigger alerts:\n";

foreach ($wishlistItems as $item) {
    echo "- User: {$item['email']}, Target: {$item['target_price']}, Alert sent: {$item['alert_sent']}\n";

    $user = [
        'email' => $item['email'],
        'name' => $item['name']
    ];
    $product = [
        'name' => $item['pname'],
        'url' => $url,
        'image_url' => $item['image_url']
    ];

    echo "Attempting to send alert to {$user['email']}...\n";

    try {
        $sent = sendPriceAlert($user, $product, $currentPrice, $price);

        if ($sent) {
            echo "✅ Alert sent successfully!\n";

            // Mark as sent
            $mu = $GLOBALS['db']->prepare('UPDATE wishlist SET alert_sent=1, alert_sent_at=NOW(), last_alert_price=? WHERE id=?');
            $mu->execute([$price, (int)$item['wid']]);

            // Log the notification
            $notify = $GLOBALS['db']->prepare('INSERT INTO notifications (user_id, product_id, type, message, created_at)
                                  VALUES (?, ?, ?, ?, NOW())');
            $message = "Price alert: {$product['name']} is now at ₹{$price}";
            $notify->execute([$item['user_id'], $pid, 'price_alert', $message]);

            echo "✅ Database updated\n";
        } else {
            echo "❌ Failed to send alert\n";
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}
