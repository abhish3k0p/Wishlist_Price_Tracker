<?php
require_once 'includes/db.php';

// Update the price to trigger alert
$update = $db->prepare('UPDATE products SET current_price = ? WHERE id = ?');
$update->execute([13000, 1]);

echo 'Price updated to 13000. Now run price checker.' . "\n";
