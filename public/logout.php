<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/helpers/Auth.php';
Auth::logout();
header('Location: ' . APP_URL . '/login.php');
exit;