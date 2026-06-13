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
public static function getAll($search = '', $page = 1, $perPage = 20)
{
    $db = Database::getConnection();
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT * FROM contact_messages";
    $params = [];

    if (!empty($search)) {
        $sql .= " WHERE name LIKE :nameSearch
                  OR email LIKE :emailSearch
                  OR message LIKE :messageSearch";

        $params[':nameSearch'] = "%$search%";
        $params[':emailSearch'] = "%$search%";
        $params[':messageSearch'] = "%$search%";
    }

    $sql .= " ORDER BY submitted_at DESC LIMIT $perPage OFFSET $offset";

    $stmt = $db->prepare($sql);

    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }

    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count query
    $countSql = "SELECT COUNT(*) as total FROM contact_messages";
    
    if (!empty($search)) {
        $countSql .= " WHERE name LIKE :nameSearch
                       OR email LIKE :emailSearch
                       OR message LIKE :messageSearch";
    }

    $countStmt = $db->prepare($countSql);

    if (!empty($search)) {
        $countStmt->bindValue(':nameSearch', "%$search%");
        $countStmt->bindValue(':emailSearch', "%$search%");
        $countStmt->bindValue(':messageSearch', "%$search%");
    }

    $countStmt->execute();
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    return [
        'messages' => $messages,
        'total' => $total,
        'page' => $page,
        'perPage' => $perPage
    ];
}
    
    /**
     * Mark message as read
     */
    public static function markRead($id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Delete a message
     */
    public static function delete($id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM contact_messages WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Get unread message count (for admin dashboard)
     */
    public static function getUnreadCount() {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0");
        return $stmt->fetch()['count'];
    }
}