<?php
/**
 * Apply database schema updates
 *
 * This script applies the necessary database schema changes for the notification system.
 */

require_once __DIR__ . '/includes/db.php';

echo "Applying database schema updates...\n";

$updates = [
    // Add missing columns to wishlist table
    "ALTER TABLE wishlist ADD COLUMN alert_sent_at DATETIME NULL AFTER alert_sent",
    "ALTER TABLE wishlist ADD COLUMN last_alert_price DECIMAL(10,2) NULL AFTER alert_sent_at",

    // Create notifications table (optional)
    "CREATE TABLE IF NOT EXISTS notifications (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        product_id INT UNSIGNED NOT NULL,
        type VARCHAR(50) NOT NULL DEFAULT 'price_alert',
        message TEXT NOT NULL,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id, sent_at),
        INDEX (product_id, sent_at),
        CONSTRAINT fk_n_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_n_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB"
];

$successCount = 0;
$errorCount = 0;

foreach ($updates as $sql) {
    try {
        $db->exec($sql);
        echo "✅ Applied: " . substr($sql, 0, 50) . "...\n";
        $successCount++;
    } catch (Exception $e) {
        echo "❌ Failed: " . substr($sql, 0, 50) . "... - " . $e->getMessage() . "\n";
        $errorCount++;
    }
}

echo "\nSummary: $successCount successful, $errorCount failed\n";

if ($errorCount == 0) {
    echo "All schema updates applied successfully!\n";
} else {
    echo "Some updates failed. Please check the errors above.\n";
}
?>
