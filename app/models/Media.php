<?php
require_once __DIR__ . '/../config/database.php';

class Media {
    /**
     * Get all media items
     */
    public static function getAll($limit = 50, $offset = 0) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM media_gallery ORDER BY uploaded_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get recent media for homepage
     */
    public static function getRecent($limit = 6) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM media_gallery ORDER BY uploaded_at DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Insert new media record
     */
    public static function create($title, $description, $filePath, $fileType, $uploadedBy) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO media_gallery (title, description, file_path, file_type, uploaded_by) VALUES (:title, :description, :file_path, :file_type, :uploaded_by)");
        return $stmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':file_path' => $filePath,
            ':file_type' => $fileType,
            ':uploaded_by' => $uploadedBy
        ]);
    }
    
    /**
     * Delete media (admin only)
     */
    public static function delete($id) {
        $db = Database::getConnection();
        // First get file path to delete from disk
        $stmt = $db->prepare("SELECT file_path FROM media_gallery WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $media = $stmt->fetch();
        if ($media && file_exists(__DIR__ . '/../../public/' . $media['file_path'])) {
            unlink(__DIR__ . '/../../public/' . $media['file_path']);
        }
        // Delete from database
        $stmt = $db->prepare("DELETE FROM media_gallery WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}