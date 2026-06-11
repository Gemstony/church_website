<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/helpers/Auth.php';
require_once __DIR__ . '/../app/helpers/Security.php';
require_once __DIR__ . '/../app/models/Setting.php';

$settings = Setting::getAll();
$welcomeText = $settings['home_welcome'] ?? 'Welcome to Our Church';
include __DIR__ . '/../app/views/header.php';
?>
<div class="jumbotron bg-light p-5 rounded">
    <h1 class="display-4"><?php echo Security::escape($welcomeText); ?></h1>
    <p class="lead">We are a community of faith, love, and service.</p>
    <hr class="my-4">
    <p>Join us for worship, events, and fellowship.</p>
    <a class="btn btn-primary btn-lg" href="<?php echo APP_URL; ?>/events.php" role="button">View Events</a>
</div>
<?php include __DIR__ . '/../app/views/footer.php'; ?>