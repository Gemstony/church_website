<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/models/Setting.php';
require_once __DIR__ . '/../app/helpers/Security.php';

$aboutContent = Setting::get('about_content', '<h2>About Our Church</h2><p>We are a community of believers dedicated to faith, love, and service. Join us for worship and fellowship.</p>');

include __DIR__ . '/../app/views/header.php';
?>

<div class="row">
    <div class="col-xl mx-auto">
        <div class="card shadow-sm border-0">
            <div class="card-body p-5">
                <?php echo $aboutContent; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../app/views/footer.php'; ?>