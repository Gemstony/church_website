<?php
class Auth {
    public static function login($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_pic'] = $user['profile_pic'] ?? null;
    }
    
    public static function logout() {
        $_SESSION = [];
        session_destroy();
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public static function isAdmin() {
        return self::isLoggedIn() && $_SESSION['user_role'] === 'admin';
    }
    
    public static function userId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public static function userRole() {
        return $_SESSION['user_role'] ?? null;
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: ' . APP_URL . '/login.php');
            exit;
        }
    }
    
    public static function requireAdmin() {
        if (!self::isAdmin()) {
            header('Location: ' . APP_URL . '/dashboard.php');
            exit;
        }
    }
}