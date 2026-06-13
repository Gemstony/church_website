<?php
require_once __DIR__ . '/../config/database.php';

class AdminManager {
    /**
     * Get total counts for dashboard
     */
    public static function getStats() {
        $db = Database::getConnection();
        $stats = [];
        
        // Total members
        $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'member'");
        $stats['members'] = $stmt->fetch()['count'];
        
        // Total events
        $stmt = $db->query("SELECT COUNT(*) as count FROM events");
        $stats['events'] = $stmt->fetch()['count'];
        
        // Total media items
        $stmt = $db->query("SELECT COUNT(*) as count FROM media_gallery");
        $stats['media'] = $stmt->fetch()['count'];
        
        // Unread contact messages
        $stmt = $db->query("SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0");
        $stats['unread_messages'] = $stmt->fetch()['count'];
        
        return $stats;
    }
    
    /**
     * Get all members with pagination and search
     */
    public static function getMembers($search = '', $page = 1, $perPage = 10) {
        $db = Database::getConnection();
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT id, email, full_name, role, profile_pic, is_active, created_at 
                FROM users";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (full_name LIKE :search OR email LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $members = $stmt->fetchAll();
        
        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM users";
        if (!empty($search)) {
            $countSql .= " AND (full_name LIKE :search OR email LIKE :search)";
        }
        $countStmt = $db->prepare($countSql);
        if (!empty($search)) {
            $countStmt->bindValue(':search', "%$search%");
        }
        $countStmt->execute();
        $total = $countStmt->fetch()['total'];
        
        return ['members' => $members, 'total' => $total, 'page' => $page, 'perPage' => $perPage];
    }
    
    /**
     * Add new member (admin creates)
     */
    public static function addMember($email, $password, $full_name) {
        $db = Database::getConnection();
        // Check if email exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) return ['success' => false, 'error' => 'Email already exists.'];
        
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO users (email, password_hash, full_name, role) VALUES (:email, :password, :full_name, 'member')");
        $result = $stmt->execute([
            ':email' => $email,
            ':password' => $hashed,
            ':full_name' => $full_name
        ]);
        return $result ? ['success' => true] : ['success' => false, 'error' => 'Database error.'];
    }
    
    /**
     * Update member details
     */
/**
 * Update member details
 */
public static function updateMember($id, $full_name, $email, $role, $is_active) {
    $db = Database::getConnection();
    $stmt = $db->prepare("UPDATE users SET full_name = :full_name, email = :email, role = :role, is_active = :is_active WHERE id = :id");
    return $stmt->execute([
        ':id' => $id,
        ':full_name' => $full_name,
        ':email' => $email,
        ':role' => $role,
        ':is_active' => $is_active
    ]);
}
    
    /**
     * Delete member (and their CV, profile pic)
     */
    public static function deleteMember($id) {
        $db = Database::getConnection();
        // Get member's CV and profile pic to delete files
        $stmt = $db->prepare("SELECT profile_pic FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        if ($user && $user['profile_pic']) {
            $file = __DIR__ . '/../../public/' . $user['profile_pic'];
            if (file_exists($file)) unlink($file);
        }
        $stmt = $db->prepare("SELECT file_path FROM cvs WHERE user_id = :id");
        $stmt->execute([':id' => $id]);
        $cv = $stmt->fetch();
        if ($cv && $cv['file_path']) {
            $file = __DIR__ . '/../../public/' . $cv['file_path'];
            if (file_exists($file)) unlink($file);
        }
        // Delete user (cascade will delete CV and profile via foreign keys)
        $stmt = $db->prepare("DELETE FROM users WHERE id = :id AND role != 'admin'");
        return $stmt->execute([':id' => $id]);
    }
}