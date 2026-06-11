<?php
require_once __DIR__ . '/../app/config/database.php';

try {
    $db = Database::getConnection();
    echo "✅ Connected to database: " . DB_NAME . " successfully!";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}