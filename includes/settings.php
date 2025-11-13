<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

function setting(string $key, $default = null) {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            $stmt = $GLOBALS['db']->query('SELECT `key`, `value` FROM settings');
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $cache[$row['key']] = $row['value'];
            }
        } catch (Throwable $e) {
            // ignore if table missing
        }
    }
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    return env($key, $default);
}

function save_settings(array $pairs): void {
    $stmt = $GLOBALS['db']->prepare('INSERT INTO settings(`key`, `value`) VALUES(?, ?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)');
    foreach ($pairs as $k => $v) {
        $stmt->execute([$k, (string)$v]);
    }
}
