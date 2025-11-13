<?php
// test_email.php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/includes/logger.php';

// Test database connection
try {
    $db->query('SELECT 1');
    echo "✅ Database connection successful\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test email configuration
$testEmail = 'abhi2k0p@gmail.com'; // Replace with your email
$testUser = [
    'email' => $testEmail,
    'name' => 'Test User'
];

$testProduct = [
    'name' => 'Test Product',
    'url' => 'http://localhost/Wishlist_Price_Tracker/public/product.php?product_id=1'
];

echo "Sending test email to: $testEmail\n";

// Test sending email
try {
    $result = sendPriceAlert($testUser, $testProduct, 100.00, 80.00);
    
    if ($result) {
        echo "✅ Test email sent successfully!\n";
        echo "Please check your inbox (and spam folder) for the test email.\n";
    } else {
        echo "❌ Failed to send test email. No exception was thrown, but the email wasn't sent.\n";
        echo "Check your SMTP settings and server logs for more information.\n";
    }
} catch (Exception $e) {
    echo "❌ Error sending test email:\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}