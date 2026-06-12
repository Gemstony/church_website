<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../app/config/config.php';
    require_once __DIR__ . '/../../app/config/database.php'; // ADD THIS LINE
    require_once __DIR__ . '/../../app/helpers/Auth.php';
    
    if (!Auth::isAdmin()) {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    $db = Database::getConnection();
    
    // Member registrations per month (last 6 months)
    $memberLabels = [];
    $memberValues = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'member' AND DATE_FORMAT(created_at, '%Y-%m') = :month");
        $stmt->execute([':month' => $month]);
        $memberLabels[] = date('M Y', strtotime($month . '-01'));
        $memberValues[] = (int)$stmt->fetch()['count'];
    }
    
    // Event registrations per month (last 6 months)
    $regLabels = [];
    $regValues = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM event_registrations WHERE DATE_FORMAT(registered_at, '%Y-%m') = :month");
        $stmt->execute([':month' => $month]);
        $regLabels[] = date('M Y', strtotime($month . '-01'));
        $regValues[] = (int)$stmt->fetch()['count'];
    }
    
    echo json_encode([
        'members' => ['labels' => $memberLabels, 'values' => $memberValues],
        'registrations' => ['labels' => $regLabels, 'values' => $regValues]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
} catch (Error $e) {
    echo json_encode(['error' => $e->getMessage()]);
}