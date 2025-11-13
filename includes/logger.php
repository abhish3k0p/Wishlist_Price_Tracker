<?php
function app_log(string $message): void {
    $dir = __DIR__ . '/../logs';
    if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
    $file = $dir . '/system.log';
    $line = '[' . date('Y-m-d H:i:s') . "] " . $message . "\n";
    @file_put_contents($file, $line, FILE_APPEND);
}
