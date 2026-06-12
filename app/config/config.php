<?php
require_once __DIR__ . '/../../vendor/autoload.php'; 
// app/config/config.php
function loadEnv($path) {
    if (!file_exists($path)) {
        die(".env file not found at: " . $path);
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) continue;
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        // Remove surrounding quotes
        if (preg_match('/^"(.*)"$/', $value, $matches)) $value = $matches[1];
        elseif (preg_match("/^'(.*)'$/", $value, $matches)) $value = $matches[1];
        $_ENV[$key] = $value;
        putenv(sprintf('%s=%s', $key, $value));
    }
}
loadEnv(__DIR__ . '/../../.env');

// Define constants
define('DB_HOST', getenv('DB_HOST'));
define('DB_NAME', getenv('DB_NAME'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASS', getenv('DB_PASS'));
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost');
define('APP_ENV', getenv('APP_ENV') ?: 'development');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set error reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ========== LOAD HELPER CLASSES ==========
// This ensures Security and Auth are always available
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/Auth.php';