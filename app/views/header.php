<?php
require_once __DIR__ . '/../models/Setting.php';
$settings = Setting::getAll();
$siteName = $settings['site_name'] ?? 'Our Church';
$primaryColor = $settings['primary_color'] ?? '#0d6efd';
$secondaryColor = $settings['secondary_color'] ?? '#6c757d';
$logoPath = !empty($settings['logo_path']) ? APP_URL . '/' . $settings['logo_path'] : null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Security::escape($siteName); ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS with dynamic variables -->
    <style>
        :root {
            --primary-color:
                <?php echo $primaryColor; ?>
            ;
            --secondary-color:
                <?php echo $secondaryColor; ?>
            ;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            filter: brightness(90%);
        }

        .bg-primary {
            background-color: var(--primary-color) !important;
        }

        .text-primary {
            color: var(--primary-color) !important;
        }

        .btn-outline-primary {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
        }

        .navbar-brand img {
            max-height: 50px;
        }

        footer {
            background: #1a1a2e;
            color: #eee;
            padding: 2rem 0;
            font-size: 0.9rem;
        }

        .footer-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .footer-column {
            flex: 1;
            min-width: 200px;
        }

        .footer-column h3 {
            color: #f0a500;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .footer-column ul {
            list-style: none;
            padding: 0;
        }

        .footer-column ul li {
            margin-bottom: 0.5rem;
        }

        .footer-column a {
            text-decoration: none;
        }

        .footer-column a:hover {
            color: #f0a500;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 1.5rem;
            border-top: 1px solid #333;
            margin-top: 1.5rem;
            font-size: 0.8rem;
        }

        .nav-item{
            color: #fff !important;
        }


        @media (max-width: 768px) {
            .footer-container {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/custom.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg " style="background-color: <?php echo $primaryColor; ?>;">
        <div class="container">
            <a class="navbar-brand" href="<?php echo APP_URL; ?>/index.php">
                <?php if ($logoPath): ?>
                    <img src="<?php echo $logoPath; ?>" alt="<?php echo Security::escape($siteName); ?>">
                <?php else: ?>
                    <?php echo Security::escape($siteName); ?>
                <?php endif; ?>
                <strong class="text-white">CGMSF</strong>
            </a>
            <button class="navbar-toggler bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon bg-white"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link text-white" href="<?php echo APP_URL; ?>/index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="<?php echo APP_URL; ?>/about.php">About</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="<?php echo APP_URL; ?>/events.php">Events</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="<?php echo APP_URL; ?>/media.php">Media</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="<?php echo APP_URL; ?>/contact.php">Contact</a></li>
                    <?php if (Auth::isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                                <?php echo Security::escape($_SESSION['user_name']); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/dashboard.php">Dashboard</a></li>
                                <?php if (Auth::isAdmin()): ?>
                                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/index.php">Admin
                                            Panel</a></li>
                                <?php endif; ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link text-white" href="<?php echo APP_URL; ?>/login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link text-white" href="<?php echo APP_URL; ?>/signup.php">Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <main class="container my-4">