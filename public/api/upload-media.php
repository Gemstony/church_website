<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/models/Media.php';

header('Content-Type: application/json');

// Only admin can upload
if (!Auth::isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// CSRF check
if (!isset($_POST['csrf_token']) || !Security::verifyCSRFToken($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'error' => 'CSRF token invalid']);
    exit;
}

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
if (empty($title)) {
    echo json_encode(['success' => false, 'error' => 'Title is required']);
    exit;
}

if (!isset($_FILES['media_file']) || $_FILES['media_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'File upload failed']);
    exit;
}

$file = $_FILES['media_file'];
$allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$allowedVideoTypes = ['video/mp4', 'video/webm'];
$maxSize = 20 * 1024 * 1024; // 20MB

if (!in_array($file['type'], array_merge($allowedImageTypes, $allowedVideoTypes))) {
    echo json_encode(['success' => false, 'error' => 'Only JPG, PNG, GIF, WEBP images or MP4, WEBM videos allowed']);
    exit;
}

if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'error' => 'File too large (max 20MB)']);
    exit;
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'media_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
$uploadDir = __DIR__ . '/../assets/uploads/media/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}
$targetPath = $uploadDir . $filename;
if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $relativePath = 'assets/uploads/media/' . $filename;
    $fileType = in_array($file['type'], $allowedImageTypes) ? 'image' : 'video';
    $result = Media::create($title, $description, $relativePath, $fileType, Auth::userId());
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Media uploaded successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save file']);
}