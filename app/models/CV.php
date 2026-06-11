<?php
require_once __DIR__ . '/../config/database.php';

class CV {
    /**
     * Get CV for a specific user
     */
    public static function getByUserId($userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, file_name, file_path, uploaded_at, updated_at FROM cvs WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch();
    }
    
    /**
     * Upload or replace CV for a user
     */
    public static function upload($userId, $fileTmpPath, $originalName) {
        // Validate file type (PDF, DOCX)
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf', 'docx'])) {
            return ['success' => false, 'error' => 'Only PDF and DOCX files are allowed.'];
        }
        
        // Validate file size (max 5MB)
        if (filesize($fileTmpPath) > 5 * 1024 * 1024) {
            return ['success' => false, 'error' => 'File size must be less than 5MB.'];
        }
        
        // Generate unique filename
        $newFileName = 'cv_' . $userId . '_' . time() . '.' . $ext;
        $uploadDir = __DIR__ . '/../../public/assets/uploads/cvs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $targetPath = $uploadDir . $newFileName;
        
        // Delete old CV file if exists
        $oldCV = self::getByUserId($userId);
        if ($oldCV && file_exists(__DIR__ . '/../../public/' . $oldCV['file_path'])) {
            unlink(__DIR__ . '/../../public/' . $oldCV['file_path']);
        }
        
        // Move new file
        if (!move_uploaded_file($fileTmpPath, $targetPath)) {
            return ['success' => false, 'error' => 'Failed to save file.'];
        }
        
        $relativePath = 'assets/uploads/cvs/' . $newFileName;
        $db = Database::getConnection();
        
        if ($oldCV) {
            // Update existing
            $stmt = $db->prepare("UPDATE cvs SET file_name = :file_name, file_path = :file_path, updated_at = NOW() WHERE user_id = :user_id");
            $result = $stmt->execute([
                ':file_name' => $originalName,
                ':file_path' => $relativePath,
                ':user_id' => $userId
            ]);
        } else {
            // Insert new
            $stmt = $db->prepare("INSERT INTO cvs (user_id, file_name, file_path) VALUES (:user_id, :file_name, :file_path)");
            $result = $stmt->execute([
                ':user_id' => $userId,
                ':file_name' => $originalName,
                ':file_path' => $relativePath
            ]);
        }
        
        if ($result) {
            return ['success' => true, 'message' => 'CV uploaded successfully.'];
        } else {
            return ['success' => false, 'error' => 'Database error.'];
        }
    }
    
    /**
     * Delete CV for a user
     */
    public static function delete($userId) {
        $cv = self::getByUserId($userId);
        if (!$cv) {
            return false;
        }
        // Delete file from disk
        $filePath = __DIR__ . '/../../public/' . $cv['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        // Delete from database
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM cvs WHERE user_id = :user_id");
        return $stmt->execute([':user_id' => $userId]);
    }
}