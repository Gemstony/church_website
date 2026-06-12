<?php
require_once __DIR__ . '/../config/database.php';

class Slider {
    public static function getAll($activeOnly = true) {
        $db = Database::getConnection();
        $sql = "SELECT * FROM slides";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY `order` ASC, id ASC";
        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    }
    
    public static function find($id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM slides WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    public static function create($data, $imageFile) {
        $db = Database::getConnection();
        $imagePath = self::uploadImage($imageFile);
        if (!$imagePath) return false;
        $stmt = $db->prepare("INSERT INTO slides (image, title, subtitle, btn_text, btn_link, `order`, is_active) VALUES (:image, :title, :subtitle, :btn_text, :btn_link, :order, :is_active)");
        return $stmt->execute([
            ':image' => $imagePath,
            ':title' => $data['title'],
            ':subtitle' => $data['subtitle'],
            ':btn_text' => $data['btn_text'],
            ':btn_link' => $data['btn_link'],
            ':order' => $data['order'],
            ':is_active' => $data['is_active']
        ]);
    }
    
    public static function update($id, $data, $imageFile = null) {
        $db = Database::getConnection();
        $slide = self::find($id);
        if (!$slide) return false;
        $imagePath = $slide['image'];
        if ($imageFile && $imageFile['error'] === UPLOAD_ERR_OK) {
            $oldFile = __DIR__ . '/../../public/' . $slide['image'];
            if (file_exists($oldFile)) unlink($oldFile);
            $imagePath = self::uploadImage($imageFile);
            if (!$imagePath) return false;
        }
        $stmt = $db->prepare("UPDATE slides SET image = :image, title = :title, subtitle = :subtitle, btn_text = :btn_text, btn_link = :btn_link, `order` = :order, is_active = :is_active WHERE id = :id");
        return $stmt->execute([
            ':id' => $id,
            ':image' => $imagePath,
            ':title' => $data['title'],
            ':subtitle' => $data['subtitle'],
            ':btn_text' => $data['btn_text'],
            ':btn_link' => $data['btn_link'],
            ':order' => $data['order'],
            ':is_active' => $data['is_active']
        ]);
    }
    
    public static function delete($id) {
        $db = Database::getConnection();
        $slide = self::find($id);
        if ($slide && $slide['image']) {
            $file = __DIR__ . '/../../public/' . $slide['image'];
            if (file_exists($file)) unlink($file);
        }
        $stmt = $db->prepare("DELETE FROM slides WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    
    private static function uploadImage($file) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed)) return false;
        if ($file['size'] > 2 * 1024 * 1024) return false;
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'slide_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $uploadDir = __DIR__ . '/../../public/assets/uploads/slides/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $target = $uploadDir . $filename;
        if (move_uploaded_file($file['tmp_name'], $target)) {
            return 'assets/uploads/slides/' . $filename;
        }
        return false;
    }
}