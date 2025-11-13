<?php
/**
 * Send Product Notifications
 *
 * This script sends email notifications to users when product prices drop below their target price.
 * Can be run manually or via cron job.
 *
 * Usage:
 * - Web: send_notification.php?user_id=1&product_id=1
 * - CLI: php send_notification.php --user_id=1 --product_id=1
 * - CLI: php send_notification.php --check_all (checks all products for all users)
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/includes/logger.php';

// Detect CLI mode
define('CLI_MODE', php_sapi_name() === 'cli');

// Set content type for web access
if (!CLI_MODE) {
    header('Content-Type: application/json');
}

/**
 * Send notification for a specific user-product combination
 */
function sendUserProductNotification($userId, $productId) {
    global $db;

    try {
        // Get user details
        $userStmt = $db->prepare('SELECT id, name, email FROM users WHERE id = ?');
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception("User not found: $userId");
        }

        // Get product details
        $productStmt = $db->prepare('SELECT id, name, url, image_url, current_price FROM products WHERE id = ?');
        $productStmt->execute([$productId]);
        $product = $productStmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception("Product not found: $productId");
        }

        // Check if user has this product in wishlist
        $wishlistStmt = $db->prepare('SELECT target_price, alert_sent FROM wishlist WHERE user_id = ? AND product_id = ?');
        $wishlistStmt->execute([$userId, $productId]);
        $wishlistItem = $wishlistStmt->fetch(PDO::FETCH_ASSOC);

        if (!$wishlistItem) {
            throw new Exception("Product not in user's wishlist");
        }

        $currentPrice = (float)$product['current_price'];
        $targetPrice = (float)$wishlistItem['target_price'];

        // Check if price is below target
        if ($currentPrice > $targetPrice) {
            return [
                'success' => false,
                'message' => "Current price ($currentPrice) is not below target price ($targetPrice)"
            ];
        }

        // Get old price from price history (most recent before current)
        $oldPriceStmt = $db->prepare('
            SELECT price FROM price_history
            WHERE product_id = ?
            ORDER BY checked_at DESC
            LIMIT 1 OFFSET 1
        ');
        $oldPriceStmt->execute([$productId]);
        $oldPriceRow = $oldPriceStmt->fetch(PDO::FETCH_ASSOC);
        $oldPrice = $oldPriceRow ? (float)$oldPriceRow['price'] : $currentPrice;

        // Prepare user and product data for email
        $userData = [
            'email' => $user['email'],
            'name' => $user['name']
        ];

        $productData = [
            'name' => $product['name'],
            'url' => $product['url']
        ];

        // Send email
        $emailSent = sendPriceAlert($userData, $productData, $oldPrice, $currentPrice);

        if ($emailSent) {
            // Update wishlist to mark alert as sent
            $updateStmt = $db->prepare('
                UPDATE wishlist
                SET alert_sent = 1, alert_sent_at = NOW(), last_alert_price = ?
                WHERE user_id = ? AND product_id = ?
            ');
            $updateStmt->execute([$currentPrice, $userId, $productId]);

            return [
                'success' => true,
                'message' => "Notification sent to {$user['email']} for {$product['name']}",
                'user' => $userData,
                'product' => $productData,
                'prices' => ['old' => $oldPrice, 'new' => $currentPrice, 'target' => $targetPrice]
            ];
        } else {
            return [
                'success' => false,
                'message' => "Failed to send email to {$user['email']}"
            ];
        }

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => "Error: " . $e->getMessage()
        ];
    }
}

/**
 * Check all products and send notifications to all eligible users
 */
function checkAllNotifications() {
    global $db;

    try {
        // Get all wishlist items where current price <= target price and alert not sent recently
        $stmt = $db->prepare('
            SELECT
                w.id as wishlist_id,
                w.user_id,
                w.product_id,
                w.target_price,
                w.alert_sent,
                u.name as user_name,
                u.email as user_email,
                p.name as product_name,
                p.url as product_url,
                p.current_price
            FROM wishlist w
            JOIN users u ON u.id = w.user_id
            JOIN products p ON p.id = w.product_id
            WHERE p.current_price <= w.target_price
            ORDER BY w.user_id, w.product_id
        ');

        $stmt->execute();
        $eligibleItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        $sentCount = 0;

        foreach ($eligibleItems as $item) {
            $result = sendUserProductNotification($item['user_id'], $item['product_id']);
            $results[] = $result;

            if ($result['success']) {
                $sentCount++;
            }
        }

        return [
            'success' => true,
            'message' => "Checked " . count($eligibleItems) . " items, sent $sentCount notifications",
            'results' => $results
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => "Error checking all notifications: " . $e->getMessage()
        ];
    }
}

// Handle CLI arguments
if (CLI_MODE) {
    $options = getopt('', ['user_id:', 'product_id:', 'check_all']);

    if (isset($options['check_all'])) {
        $result = checkAllNotifications();
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
        exit($result['success'] ? 0 : 1);
    }

    if (!isset($options['user_id']) || !isset($options['product_id'])) {
        echo "Usage:\n";
        echo "  php send_notification.php --user_id=<id> --product_id=<id>\n";
        echo "  php send_notification.php --check_all\n";
        exit(1);
    }

    $result = sendUserProductNotification($options['user_id'], $options['product_id']);
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
    exit($result['success'] ? 0 : 1);
}

// Handle web requests
$userId = $_GET['user_id'] ?? null;
$productId = $_GET['product_id'] ?? null;
$checkAll = isset($_GET['check_all']);

if ($checkAll) {
    $result = checkAllNotifications();
} elseif ($userId && $productId) {
    $result = sendUserProductNotification($userId, $productId);
} else {
    $result = [
        'success' => false,
        'message' => 'Missing parameters. Use: ?user_id=X&product_id=Y or ?check_all=1'
    ];
}

echo json_encode($result, JSON_PRETTY_PRINT);
?>
