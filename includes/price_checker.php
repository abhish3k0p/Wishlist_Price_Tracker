<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/scraper.php';
require_once __DIR__ . '/api_fetch.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/notify.php';

/**
 * Logs a message to both the application log and error log
 */
function log_message($productId, $message, $type = 'info') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$type] [Product $productId] $message" . PHP_EOL;
    
    // Log to application log
    error_log($logMessage, 3, __DIR__ . '/../logs/price_checker.log');
    
    // Also log to PHP error log
    error_log($logMessage);
    
    // Log to database if it's an error or important info
    if (in_array($type, ['error', 'warning', 'alert'])) {
        try {
            $stmt = $GLOBALS['db']->prepare(
                'INSERT INTO scrape_logs(product_id, status, message, checked_at) VALUES (?, ?, ?, NOW())'
            );
            $stmt->execute([$productId, $type, $message]);
        } catch (Exception $e) {
            error_log("Failed to log to database: " . $e->getMessage());
        }
    }
}

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

// Set time limit to prevent timeouts
set_time_limit(0);

// Fetch all products that need to be checked
$products = $db->query('SELECT * FROM products')->fetchAll(PDO::FETCH_ASSOC);
$totalProducts = count($products);

log_message(0, "Starting price check for $totalProducts products");

foreach ($products as $index => $p) {
    $pid = (int)$p['id'];
    $url = $p['url'];
    $currentPrice = (float)$p['current_price'];
    
    log_message($pid, "Processing product: {$p['name']} (Current price: $currentPrice)");
    
    try {
        // Start transaction for this product
        $db->beginTransaction();
        
        // Get the latest price
        $price = api_fetch_price($url);
        if ($price === null) {
            log_message($pid, "API fetch failed, falling back to web scraping");
            $meta = scrape_product($url);
            $price = (float)($meta['current_price'] ?? 0);
        }

        if ($price <= 0) {
            throw new Exception("Invalid price returned: $price");
        }
        
        $price = round($price, 2); // Ensure consistent decimal places
        $priceChanged = (abs($currentPrice - $price) > 0.01); // Account for floating point precision
        
        log_message($pid, "New price: $price (Changed: " . ($priceChanged ? 'Yes' : 'No') . ")");

        // Update product and insert price history if price changed
        $upd = $db->prepare('UPDATE products SET current_price=?, last_checked=NOW() WHERE id=?');
        $upd->execute([$price, $pid]);
        
        if ($priceChanged) {
            $ph = $db->prepare('INSERT INTO price_history(product_id, price, checked_at) VALUES (?, ?, NOW())');
            $ph->execute([$pid, $price]);
            log_message($pid, "Price history updated");

            // Check for price drop alerts - send on every qualifying price drop
            $wq = $db->prepare('SELECT w.id as wid, w.user_id, w.target_price, w.alert_sent, u.email, u.name, p.name as pname, p.image_url
                               FROM wishlist w
                               JOIN users u ON u.id = w.user_id
                               JOIN products p ON p.id = w.product_id
                               WHERE w.product_id=? AND ? <= w.target_price');
            $wq->execute([$pid, $price]);
            $wishlistItems = $wq->fetchAll(PDO::FETCH_ASSOC);

            $alertsSent = 0;
            foreach ($wishlistItems as $item) {
                $user = [
                    'email' => $item['email'],
                    'name' => $item['name']
                ];
                $product = [
                    'name' => $item['pname'],
                    'url' => $url,
                    'image_url' => $item['image_url']
                ];

                log_message($pid, "Sending price alert to {$user['email']} for {$product['name']}");

                try {
                    $sent = sendPriceAlert($user, $product, $currentPrice, $price);

                    if ($sent) {
                        // Mark as sent and update with the current price
                        $mu = $db->prepare('UPDATE wishlist SET alert_sent=1, alert_sent_at=NOW(), last_alert_price=? WHERE id=?');
                        $mu->execute([$price, (int)$item['wid']]);
                        $alertsSent++;
                        log_message($pid, "✅ Price alert sent to {$user['email']}", 'alert');

                        // Log successful notification (optional - notifications table may not exist)
                        // Uncomment below if notifications table is created
                        /*
                        $notify = $db->prepare('INSERT INTO notifications (user_id, product_id, type, message, sent_at)
                                              VALUES (?, ?, ?, ?, NOW())');
                        $message = "Price alert: {$product['name']} is now at ₹{$price}";
                        $notify->execute([$item['user_id'], $pid, 'price_alert', $message]);
                        */
                    } else {
                        log_message($pid, "❌ Failed to send price alert to {$user['email']}", 'error');
                    }
                } catch (Exception $e) {
                    log_message($pid, "❌ Error sending alert to {$user['email']}: " . $e->getMessage(), 'error');
                    continue; // Continue with next user
                }
            }
        } else {
            $alertsSent = 0;
        }
        // Commit the transaction
        $db->commit();

        log_message($pid, "Completed processing. Alerts sent: $alertsSent");
        
    } catch (\Throwable $e) {
        // Rollback on error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        
        $errorMsg = "Error processing product: " . $e->getMessage();
        log_message($pid, $errorMsg, 'error');
        
        // Log the full exception for debugging
        error_log("Exception in price checker: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    }
    
    // Add a small delay between products to avoid overwhelming the server
    // but only if there are more products to process
    if ($index < $totalProducts - 1) {
        usleep(500000); // 0.5 second delay
    }
}

log_message(0, "Price check completed. Processed $totalProducts products.");
