<?php
/**
 * Cron-friendly price check runner
 *
 * This script runs the price checker once and exits.
 * Suitable for cron jobs.
 *
 * Usage:
 * - Cron: * /30 * * * * /usr/bin/php /path/to/run_price_check.php
 * - Manual: php run_price_check.php
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/scraper.php';
require_once __DIR__ . '/includes/api_fetch.php';
require_once __DIR__ . '/includes/logger.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/includes/notify.php';

// Set time limit to prevent timeouts
set_time_limit(0);

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

echo "[" . date('Y-m-d H:i:s') . "] Starting price check...\n";

// Include and run the price checker
include __DIR__ . '/includes/price_checker.php';

echo "[" . date('Y-m-d H:i:s') . "] Price check completed.\n";
?>
