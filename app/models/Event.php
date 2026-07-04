<?php
require_once __DIR__ . '/../config/database.php';

class Event
{
    /**
     * Get all events (for calendar)
     */
    public static function getAll($limit = null, $offset = 0)
    {
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
    public static function getUpcoming($limit = 10)
    {
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
    public static function find($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM events WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Create or update event (admin later)
     */
    /**
     * Create or update event (admin)
     */
/**
 * Create or update event (admin)
 */
public static function save($data)
{
    $db = Database::getConnection();

    if (isset($data['id']) && $data['id']) {
        // UPDATE
        $sql = "UPDATE events SET 
                title = :title, 
                description = :description, 
                event_date = :event_date, 
                event_end_date = :event_end_date, 
                location = :location, 
                image = :image 
                WHERE id = :id";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':id' => $data['id'],
            ':title' => $data['title'],
            ':description' => $data['description'],
            ':event_date' => $data['event_date'],
            ':event_end_date' => $data['event_end_date'],
            ':location' => $data['location'],
            ':image' => $data['image'] ?? null  // Allow null
        ]);
    } else {
        // INSERT
        $sql = "INSERT INTO events (title, description, event_date, event_end_date, location, image, created_by) 
                VALUES (:title, :description, :event_date, :event_end_date, :location, :image, :created_by)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':title' => $data['title'],
            ':description' => $data['description'],
            ':event_date' => $data['event_date'],
            ':event_end_date' => $data['event_end_date'],
            ':location' => $data['location'],
            ':image' => $data['image'] ?? null,  // Allow null
            ':created_by' => $data['created_by']
        ]);
    }
}


    /**
     * Get all registrations for a specific event
     */
    public static function getRegistrations($eventId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
        SELECT u.id, u.full_name, u.email, u.profile_pic, er.phone, er.registered_at
        FROM event_registrations er
        JOIN users u ON er.user_id = u.id
        WHERE er.event_id = :event_id
        ORDER BY er.registered_at DESC
    ");
        $stmt->execute([':event_id' => $eventId]);
        return $stmt->fetchAll();
    }

    /**
     * Get registration count for an event
     */
    public static function getRegistrationCount($eventId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM event_registrations WHERE event_id = :event_id");
        $stmt->execute([':event_id' => $eventId]);
        return $stmt->fetch()['count'];
    }

    /**
     * Delete an event (and its registrations – foreign key cascade)
     */
    public static function delete($id)
    {
        $db = Database::getConnection();
        // First, delete associated image file if exists
        $event = self::find($id);
        if ($event && $event['image']) {
            $file = __DIR__ . '/../../public/' . $event['image'];
            if (file_exists($file))
                unlink($file);
        }
        $stmt = $db->prepare("DELETE FROM events WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }


    /**
     * Check if a user is registered for an event
     */
    public static function isUserRegistered($eventId, $userId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id FROM event_registrations WHERE event_id = :event_id AND user_id = :user_id");
        $stmt->execute([':event_id' => $eventId, ':user_id' => $userId]);
        return $stmt->fetch() ? true : false;
    }

    /**
     * Get registration status for multiple events for a user
     * Returns associative array event_id => bool
     */
/**
 * Get registration status for multiple events
 */
public static function getUserRegistrationStatuses($eventIds, $userId) {
    if (empty($eventIds)) return [];
    $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT DISTINCT event_id FROM event_registrations WHERE event_id IN ($placeholders) AND user_id = ?");
    $params = array_merge($eventIds, [$userId]);
    $stmt->execute($params);
    $registered = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return array_fill_keys($registered, true);
}
}