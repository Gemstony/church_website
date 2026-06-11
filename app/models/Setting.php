<?php
require_once __DIR__ . '/../config/database.php';

class Setting {
    private static $cache = null;
    
    public static function getAll() {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $db = Database::getConnection();
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        self::$cache = $settings;
        return $settings;
    }
    
    public static function get($key, $default = null) {
        $settings = self::getAll();
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
    
    /**
     * Update or insert a setting (simplified to avoid PDO placeholder issues)
     */
    public static function set($key, $value, $type = 'text') {
        $db = Database::getConnection();
        
        // First check if the setting already exists
        $checkStmt = $db->prepare("SELECT id FROM settings WHERE setting_key = :key");
        $checkStmt->execute([':key' => $key]);
        $exists = $checkStmt->fetch();
        
        if ($exists) {
            // Update existing
            $stmt = $db->prepare("UPDATE settings SET setting_value = :value, setting_type = :type WHERE setting_key = :key");
            $result = $stmt->execute([
                ':value' => $value,
                ':type'  => $type,
                ':key'   => $key
            ]);
        } else {
            // Insert new
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES (:key, :value, :type)");
            $result = $stmt->execute([
                ':key'   => $key,
                ':value' => $value,
                ':type'  => $type
            ]);
        }
        
        // Clear cache after modification
        self::$cache = null;
        return $result;
    }
}