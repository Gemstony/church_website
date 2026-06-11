<?php
require_once __DIR__ . '/../config/database.php';

class User {
    /**
     * Register a new member
     */
    public static function register($email, $password, $full_name) {
        $db = Database::getConnection();
        // Check if email exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) return false;
        
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $sql = "INSERT INTO users (email, password_hash, full_name, role) VALUES (?, ?, ?, 'member')";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$email, $hashed, $full_name]);
    }
    
    /**
     * Authenticate user by email/password
     * Returns user array (without password) on success, false otherwise
     */
    public static function authenticate($email, $password) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, email, password_hash, full_name, role, profile_pic FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            unset($user['password_hash']);
            return $user;
        }
        return false;
    }
    
    /**
     * Get user by ID
     */
    public static function find($id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, email, full_name, role, profile_pic, is_active FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Update user profile (name, profile pic)
     */
    public static function updateProfile($id, $full_name, $profile_pic = null) {
        $db = Database::getConnection();
        if ($profile_pic) {
            $stmt = $db->prepare("UPDATE users SET full_name = ?, profile_pic = ? WHERE id = ?");
            return $stmt->execute([$full_name, $profile_pic, $id]);
        } else {
            $stmt = $db->prepare("UPDATE users SET full_name = ? WHERE id = ?");
            return $stmt->execute([$full_name, $id]);
        }
    }
    
    /**
     * Change password
     */
    public static function changePassword($id, $old_password, $new_password) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($old_password, $user['password_hash'])) {
            return false;
        }
        $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        return $stmt->execute([$new_hash, $id]);
    }
}