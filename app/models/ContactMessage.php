<?php
require_once __DIR__ . '/../config/database.php';

class ContactMessage {
    /**
     * Save a contact message
     */
    public static function create($name, $email, $message) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO contact_messages (name, email, message) VALUES (:name, :email, :message)");
        return $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':message' => $message
        ]);
    }
    
    /**
     * Fetch all messages (for admin later)
     */
    public static function getAll($limit = 100, $offset = 0) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM contact_messages ORDER BY submitted_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Mark message as read
     */
    public static function markRead($id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}