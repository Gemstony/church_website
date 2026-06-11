<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/helpers/Security.php';
require_once __DIR__ . '/../../app/models/Media.php';

header('Content-Type: application/json');

if (!Auth::isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit;
}

if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'error' => 'CSRF token invalid']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit;
}

if (Media::delete($id)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Delete failed']);
}