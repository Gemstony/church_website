<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/helpers/Security.php';
require_once __DIR__ . '/../../app/models/CV.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn()) {
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

if (!isset($_FILES['cv_file']) || $_FILES['cv_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']);
    exit;
}

$result = CV::upload(Auth::userId(), $_FILES['cv_file']['tmp_name'], $_FILES['cv_file']['name']);
echo json_encode($result);