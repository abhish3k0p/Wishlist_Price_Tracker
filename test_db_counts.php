<?php
require_once 'includes/db.php';

echo "Database counts:\n";

try {
    $stmt = $db->query('SELECT COUNT(*) as count FROM users');
    $result = $stmt->fetch();
    echo "Users: " . $result['count'] . "\n";

    $stmt = $db->query('SELECT COUNT(*) as count FROM products');
    $result = $stmt->fetch();
    echo "Products: " . $result['count'] . "\n";

    $stmt = $db->query('SELECT COUNT(*) as count FROM wishlist');
    $result = $stmt->fetch();
    echo "Wishlist items: " . $result['count'] . "\n";

    if ($result['count'] > 0) {
        echo "\nSample wishlist item:\n";
        $stmt = $db->query('SELECT w.*, u.email, u.name, p.name as product_name, p.current_price FROM wishlist w JOIN users u ON u.id = w.user_id JOIN products p ON p.id = w.product_id LIMIT 1');
        $item = $stmt->fetch();
        if ($item) {
            echo "User: {$item['email']} ({$item['name']})\n";
            echo "Product: {$item['product_name']}\n";
            echo "Current Price: {$item['current_price']}\n";
            echo "Target Price: {$item['target_price']}\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
