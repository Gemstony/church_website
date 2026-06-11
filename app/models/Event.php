<?php
require_once __DIR__ . '/../config/database.php';

class Event {
    /**
     * Get all events (for calendar)
     */
    public static function getAll($limit = null, $offset = 0) {
        $db = Database::getConnection();
        $sql = "SELECT id, title, description, event_date as start, event_end_date as end, location, image 
                FROM events 
                ORDER BY event_date ASC";
        if ($limit) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }
        $stmt = $db->prepare($sql);
        if ($limit) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get upcoming events (for list view)
     */
public static function getUpcoming($limit = 10) {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT id, title, description, event_date, event_end_date, location, image 
                          FROM events 
                          WHERE DATE(event_date) >= CURDATE() 
                          ORDER BY event_date ASC 
                          LIMIT :limit");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
    
    /**
     * Get a single event by ID
     */
    public static function find($id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM events WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Create or update event (admin later)
     */
    public static function save($data) {
        $db = Database::getConnection();
        if (isset($data['id']) && $data['id']) {
            $stmt = $db->prepare("UPDATE events SET title=:title, description=:description, event_date=:event_date, event_end_date=:event_end_date, location=:location, image=:image WHERE id=:id");
            return $stmt->execute($data);
        } else {
            $stmt = $db->prepare("INSERT INTO events (title, description, event_date, event_end_date, location, image, created_by) VALUES (:title, :description, :event_date, :event_end_date, :location, :image, :created_by)");
            return $stmt->execute($data);
        }
    }
}