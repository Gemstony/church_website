<?php
// Ensure config is loaded
if (!defined('APP_URL')) {
    require_once __DIR__ . '/../../../app/config/config.php';
}
// Load required models and helpers for the header
require_once __DIR__ . '/../../../app/models/Setting.php';
require_once __DIR__ . '/../../../app/helpers/Security.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo Security::escape(Setting::get('site_name', 'Church Site')); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-size: .875rem;
        }
        /* Sidebar styling - will be hidden on mobile by default */
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: -280px; /* hidden off-screen */
            width: 280px;
            z-index: 1040;
            background-color: #f8f9fa;
            transition: left 0.3s ease;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            overflow-y: auto;
        }
        .sidebar.show {
            left: 0;
        }
        /* Overlay when sidebar is open on mobile */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1030;
            display: none;
        }
        .sidebar-overlay.show {
            display: block;
        }
        /* Main content adjustment */
        main {
            padding-top: 1.5rem;
            transition: margin-left 0.3s ease;
        }
        /* On larger screens, keep sidebar visible */
        @media (min-width: 768px) {
            .sidebar {
                left: 0;
                width: 260px;
            }
            main {
                margin-left: 260px;
            }
            .sidebar-overlay {
                display: none !important;
            }
            .navbar-toggler {
                display: none;
            }
        }
        @media (max-width: 767px) {
            main {
                margin-left: 0;
            }
            .navbar-toggler {
                display: inline-block;
            }
        }
        .nav-link.active {
            color: #0d6efd;
            font-weight: 500;
        }
        .navbar-brand {
            font-weight: bold;
        }
    </style>
</head>
<body>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="position-sticky pt-3">
        <div class="px-3 mb-3">
            <h5 class="text-dark">CGMSF Admin</h5>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link text-dark" href="index.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link text-dark" href="members.php"><i class="fas fa-users me-2"></i> Members</a></li>
            <li class="nav-item"><a class="nav-link text-dark" href="events.php"><i class="fas fa-calendar me-2"></i> Events</a></li>
            <li class="nav-item"><a class="nav-link text-dark" href="media.php"><i class="fas fa-photo-video me-2"></i> Media Gallery</a></li>
            <li class="nav-item"><a class="nav-link text-dark" href="messages.php"><i class="fas fa-envelope me-2"></i> Contact Messages</a></li>
            <li class="nav-item"><a class="nav-link text-dark" href="reports.php"><i class="fas fa-chart-line me-2"></i> Reports</a></li>
            <li class="nav-item"><a class="nav-link text-dark" href="slides.php"><i class="fas fa-image me-2"></i> Slider</a></li>
            <li class="nav-item"><a class="nav-link text-dark" href="settings.php"><i class="fas fa-cog me-2"></i> Site Settings</a></li>
            <li class="nav-item"><a class="nav-link text-dark" href="<?php echo APP_URL; ?>/index.php"><i class="fas fa-arrow-left me-2"></i> Back To site</a></li>
            <li class="nav-item"><a class="nav-link text-dark" href="<?php echo APP_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
        </ul>
    </div>
</nav>

<!-- Top Navbar with Toggle Button -->
<nav class="navbar navbar-light bg-light border-bottom d-md-none">
    <div class="container-fluid">
        <button class="btn btn-link" type="button" id="sidebarToggleBtn">
            <i class="fas fa-bars fa-lg"></i>
        </button>
        <span class="navbar-brand mb-0 h5">CGMSF Admin</span>
        <div></div> <!-- placeholder for flex alignment -->
    </div>
</nav>

<main class="p-4">