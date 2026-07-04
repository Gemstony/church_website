<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/models/Setting.php';
require_once __DIR__ . '/../app/helpers/Security.php';

$settings = Setting::getAll(); // fetch all settings

$aboutContent = $settings['about_content'] ?? '<h2>About Our Church</h2><p>We are a community of believers dedicated to faith, love, and service. Join us for worship and fellowship.</p>';
$logoPath = $settings['logo_path'] ?? '';

include __DIR__ . '/../app/views/header.php';
?>

<style>
    .about-logo {
        max-height: 120px;
        width: auto;
        margin-bottom: 2rem;
    }
    .leader-card {
        transition: transform 0.2s;
        border: none;
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
        text-align: center;
        height: 100%;
    }
    .leader-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
    }
    .leader-card img {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 50%;
        margin: 1rem auto 0.5rem;
        border: 3px solid var(--primary-color, #0d6efd);
        padding: 3px;
    }
    .leader-card .card-title {
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    .leader-card .card-subtitle {
        font-size: 0.9rem;
        color: #6c757d;
        margin-bottom: 0.5rem;
    }
    .leader-card .card-text {
        font-size: 0.95rem;
        font-style: italic;
        color: #555;
    }
    .about-content {
        font-size: 1.1rem;
        line-height: 1.8;
    }
    @media (max-width: 768px) {
        .leader-card img {
            width: 120px;
            height: 120px;
        }
    }
</style>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <!-- Centered Logo -->
        <div class="text-center mb-4">
            <?php if (!empty($logoPath)): ?>
                <img src="<?php echo $logoPath; ?>" alt="Church Logo" class="about-logo">
            <?php else: ?>
                <h1 class="display-4"><?php echo Security::escape($settings['site_name'] ?? 'Our Church'); ?></h1>
            <?php endif; ?>
        </div>
        <h2 class="text-center mb-4">About Us</h2>

        <!-- About Content -->
        <div class="card shadow-sm border-0 mb-5">
            <div class="card-body p-5 about-content">
                <?php echo $aboutContent; ?>
            </div>
        </div>

        <!-- Leadership Team (Readers) -->
        <h2 class="text-center mb-4">Our Leadership Team</h2>
        <div class="row g-4">
            <?php
            $hasLeaders = false;
            for ($i = 1; $i <= 3; $i++) {
                $name = $settings["leader_{$i}_name"] ?? '';
                $position = $settings["leader_{$i}_position"] ?? '';
                $quote = $settings["leader_{$i}_quote"] ?? '';
                $image = $settings["leader_{$i}_image"] ?? '';
                if (!empty($name) || !empty($position)) {
                    $hasLeaders = true;
                    break;
                }
            }
            ?>
            <?php if (!$hasLeaders): ?>
                <div class="col-12 text-center text-muted">
                    <p>Leadership team information coming soon.</p>
                </div>
            <?php else: ?>
                <?php for ($i = 1; $i <= 3; $i++): ?>
                    <?php
                    $name = $settings["leader_{$i}_name"] ?? '';
                    $position = $settings["leader_{$i}_position"] ?? '';
                    $quote = $settings["leader_{$i}_quote"] ?? '';
                    $image = $settings["leader_{$i}_image"] ?? '';
                    if (empty($name) && empty($position)) continue; // skip empty leaders
                    ?>
                    <div class="col-md-4">
                        <div class="card leader-card h-100">
                            <?php if (!empty($image)): ?>
                                <img src="<?php echo APP_URL . '/' . $image; ?>" alt="<?php echo Security::escape($name); ?>">
                            <?php else: ?>
                                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mx-auto mt-3" style="width:150px;height:150px;font-size:3rem;">
                                    <?php echo strtoupper(substr($name, 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo Security::escape($name); ?></h5>
                                <h6 class="card-subtitle"><?php echo Security::escape($position); ?></h6>
                                <?php if (!empty($quote)): ?>
                                    <p class="card-text mt-2">“<?php echo Security::escape($quote); ?>”</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../app/views/footer.php'; ?>