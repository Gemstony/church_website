<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/models/Event.php';

// Get start and end parameters from FullCalendar (optional)
$start = $_GET['start'] ?? null;
$end = $_GET['end'] ?? null;

$events = Event::getAll();

// Format for FullCalendar
$formatted = [];
foreach ($events as $event) {
    $formatted[] = [
        'id' => $event['id'],
        'title' => $event['title'],
        'start' => $event['start'],
        'end' => $event['end'] ?? null,
        'url' => APP_URL . '/event-details.php?id=' . $event['id'], // optional detail page
        'description' => $event['description']
    ];
}

echo json_encode($formatted);