<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/models/Event.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$eventId = (int)($_GET['event_id'] ?? 0);
if ($eventId <= 0) {
    echo json_encode([]);
    exit;
}

$registrations = Event::getRegistrations($eventId);
echo json_encode($registrations);