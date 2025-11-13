<?php
function load_env(string $path): void {
    if (!is_file($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        $pos = strpos($line, '=');
        if ($pos === false) continue;
        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));
        if ((strlen($val) >= 2) && (($val[0] === '"' && $val[strlen($val)-1] === '"') || ($val[0] === "'" && $val[strlen($val)-1] === "'"))) {
            $val = substr($val, 1, -1);
        }
        $_ENV[$key] = $val;
        $_SERVER[$key] = $val;
        putenv($key.'='.$val);
    }
}
function env(string $key, $default = null) {
    $v = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return ($v === false || $v === null || $v === '') ? $default : $v;
}
// Auto-load from project root .env if present
$projectRoot = realpath(__DIR__ . '/..');
if ($projectRoot && is_file($projectRoot.'/.env')) {
    load_env($projectRoot.'/.env');
}
