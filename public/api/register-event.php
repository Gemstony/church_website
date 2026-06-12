<?php
// No spaces before this line
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

try {
    require_once __DIR__ . '/../../app/config/config.php';
    require_once __DIR__ . '/../../app/config/database.php'; // ADD THIS LINE
    require_once __DIR__ . '/../../app/helpers/Auth.php';
    require_once __DIR__ . '/../../app/helpers/Security.php';
    
    header('Content-Type: application/json');
    
    if (!Auth::isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please login to register.']);
        exit;
    }
    
    $event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!Security::verifyCSRFToken($csrf_token)) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit;
    }
    
    if ($event_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid event.']);
        exit;
    }
    
    if (empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'Phone number is required.']);
        exit;
    }
    if (!preg_match('/^[0-9+\-\s()]{8,20}$/', $phone)) {
        echo json_encode(['success' => false, 'message' => 'Invalid phone number format.']);
        exit;
    }
    
    $db = Database::getConnection();
    
    $stmt = $db->prepare("SELECT id, event_date FROM events WHERE id = :id");
    $stmt->execute([':id' => $event_id]);
    $event = $stmt->fetch();
    if (!$event) {
        echo json_encode(['success' => false, 'message' => 'Event not found.']);
        exit;
    }
    if (strtotime($event['event_date']) < time()) {
        echo json_encode(['success' => false, 'message' => 'Event has already passed.']);
        exit;
    }
    
    $stmt = $db->prepare("SELECT id FROM event_registrations WHERE event_id = :event_id AND user_id = :user_id");
    $stmt->execute([':event_id' => $event_id, ':user_id' => Auth::userId()]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You have already registered for this event.']);
        exit;
    }
    
    $stmt = $db->prepare("INSERT INTO event_registrations (event_id, user_id, phone) VALUES (:event_id, :user_id, :phone)");
    $result = $stmt->execute([
        ':event_id' => $event_id,
        ':user_id' => Auth::userId(),
        ':phone' => $phone
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Successfully registered for the event!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
} catch (Error $e) {
    echo json_encode(['success' => false, 'message' => 'PHP Error: ' . $e->getMessage()]);
}

ob_end_flush();