<?php
while (true) {
    echo "Running price check at " . date('Y-m-d H:i:s') . "\n";
    include __DIR__ . '/price_checker.php';
    sleep(3600); // 1 hour
}
