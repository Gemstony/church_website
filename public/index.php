<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/helpers/Security.php';
require_once __DIR__ . '/../app/helpers/Auth.php';
require_once __DIR__ . '/../app/models/Event.php';
require_once __DIR__ . '/../app/models/Media.php';
require_once __DIR__ . '/../app/models/Slider.php';
require_once __DIR__ . '/../app/models/Setting.php';
require_once __DIR__ . '/../app/models/AdminManager.php'; // <-- FIX 1: Added

$settings = Setting::getAll();
$slides = Slider::getAll(true);
$upcomingEvents = Event::getUpcoming(4);
$recentMedia = Media::getRecent(4);

// For registration badges (safely handle missing method)
$registeredMap = [];
if (Auth::isLoggedIn() && method_exists('Event', 'getUserRegistrationStatuses')) {
    $eventIds = array_column($upcomingEvents, 'id');
    if (!empty($eventIds)) {
        $registeredMap = Event::getUserRegistrationStatuses($eventIds, Auth::userId());
    }
}

// Get stats for counters (safely)
$stats = ['members' => 0, 'events' => 0, 'media' => 0];
if (class_exists('AdminManager')) {
    $stats = AdminManager::getStats();
}
$mediaCount = $stats['media'] ?? 0;
$memberCount = $stats['members'] ?? 0;
$eventCount = $stats['events'] ?? 0;

include __DIR__ . '/../app/views/header.php';
?>

<!-- AOS CSS & JS -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<style>
    :root {
        --primary:
            <?php echo $settings['primary_color'] ?? '#0d6efd'; ?>
        ;
        --secondary:
            <?php echo $settings['secondary_color'] ?? '#6c757d'; ?>
        ;
    }

    .hero-slider .carousel-item {
        height: 90vh;
        min-height: 500px;
        background-size: cover;
        background-position: center;
        position: relative;
    }

    .hero-slider .carousel-item::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.55);
        z-index: 1;
    }

    .hero-slider .carousel-caption {
        z-index: 2;
        bottom: 25%;
    }

    .hero-slider .carousel-caption h1 {
        font-size: 3.5rem;
        font-weight: 700;
    }

    @media (max-width: 768px) {
        .hero-slider .carousel-item {
            height: 60vh;
            min-height: 400px;
        }

        .hero-slider .carousel-caption h1 {
            font-size: 1.8rem;
        }
    }

    .section-title {
        text-align: center;
        margin-bottom: 3rem;
        position: relative;
        font-weight: 700;
    }

    .section-title:after {
        content: '';
        display: block;
        width: 70px;
        height: 4px;
        background: var(--primary);
        margin: 0.8rem auto 0;
        border-radius: 2px;
    }

    .event-card,
    .media-card,
    .ministry-card {
        transition: all 0.3s ease;
        border-radius: 16px;
        overflow: hidden;
        cursor: pointer;
        height: 100%;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    }

    .event-card:hover,
    .media-card:hover,
    .ministry-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    }

    .counter-box {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        padding: 2rem;
        border-radius: 20px;
        color: white;
        text-align: center;
    }

    .counter-number {
        font-size: 3rem;
        font-weight: 800;
        margin-bottom: 0;
    }

    .testimonial-card {
        background: #f8f9fa;
        border-radius: 24px;
        padding: 2rem;
        text-align: center;
        margin: 1rem;
    }

    .newsletter-section {
        background: linear-gradient(135deg, #1e2a3a 0%, #0f172a 100%);
        color: white;
        border-radius: 24px;
        padding: 3rem 2rem;
    }

    .vision-mission-card {
        border-left: 5px solid var(--primary);
        background: white;
        padding: 1.5rem;
        border-radius: 16px;
        height: 100%;
    }

    .btn-primary {
        background: var(--primary);
        border: none;
    }

    .btn-primary:hover {
        background: var(--secondary);
    }

    .social-icons a {
    color: #333;
    transition: all 0.3s ease;
    display: inline-block;
}
.social-icons a:hover {
    transform: translateY(-3px);
    opacity: 0.8;
}
.social-icons .fa-facebook:hover { color: #1877f2; }
.social-icons .fa-youtube:hover { color: #ff0000; }
.social-icons .fa-instagram:hover { color: #e4405f; }
.social-icons .fa-whatsapp:hover { color: #25d366; }
.social-icons .fa-tiktok:hover { color: #000000; }
</style>

<!-- Hero Slider (unchanged, works) -->
<?php if (!empty($slides)): ?>
    <div id="heroCarousel" class="carousel slide hero-slider" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <?php foreach ($slides as $index => $slide): ?>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?php echo $index; ?>"
                    class="<?php echo $index === 0 ? 'active' : ''; ?>"></button>
            <?php endforeach; ?>
        </div>
        <div class="carousel-inner">
            <?php foreach ($slides as $index => $slide): ?>
                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>"
                    style="background-image: url('<?php echo APP_URL . '/' . $slide['image']; ?>');">
                    <div class="carousel-caption  d-md-block">
                        <?php if ($slide['title']): ?>
                            <h1 data-aos="fade-up"><?php echo Security::escape($slide['title']); ?></h1><?php endif; ?>
                        <?php if ($slide['subtitle']): ?>
                            <p data-aos="fade-up" data-aos-delay="100"><?php echo Security::escape($slide['subtitle']); ?></p>
                        <?php endif; ?>
                        <?php if ($slide['btn_text'] && $slide['btn_link']): ?>
                            <a href="<?php echo Security::escape($slide['btn_link']); ?>" class="btn btn-light btn-lg mt-3"
                                data-aos="fade-up" data-aos-delay="200"><?php echo Security::escape($slide['btn_text']); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev"><span
                class="carousel-control-prev-icon"></span></button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next"><span
                class="carousel-control-next-icon"></span></button>
    </div>
<?php endif; ?>
<!-- Church Name -->
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm text-center py-4">
                <h1 class="mb-0" style="color: <?php echo $primaryColor; ?>;">CHRIST GOSPEL MESSENGERS STUDENTS
                    FELLOWSHIP (CGMSF)</h1>
            </div>
        </div>
    </div>
</div>
<!-- Vision & Mission -->
<div class="container my-5 py-4">
    <div class="row g-4">
        <div class="col-md-6" data-aos="fade-right">
            <div class="vision-mission-card">
                <h3><i class="fas fa-eye"></i>
                    <?php echo Security::escape($settings['vision_title'] ?? 'Our Vision'); ?></h3>
                <p><?php echo nl2br(Security::escape($settings['vision_text'] ?? '')); ?></p>
            </div>
        </div>
        <div class="col-md-6" data-aos="fade-left">
            <div class="vision-mission-card">
                <h3><i class="fas fa-bullseye"></i>
                    <?php echo Security::escape($settings['mission_title'] ?? 'Our Mission'); ?></h3>
                <p><?php echo nl2br(Security::escape($settings['mission_text'] ?? '')); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Counters -->
<div class="container my-5">
    <div class="row g-4 text-center">
        <div class="col-md-4 " data-aos="zoom-in">
            <div class="counter-box"><i class="fas fa-users fa-2x mb-2"></i>
                <h3 class="counter-number" data-target="<?php echo $memberCount; ?>">0</h3>
                <p>Active Members</p>
            </div>
        </div>
        <div class="col-md-4 " data-aos="zoom-in" data-aos-delay="100">
            <div class="counter-box"><i class="fas fa-calendar-alt fa-2x mb-2"></i>
                <h3 class="counter-number" data-target="<?php echo $eventCount; ?>">0</h3>
                <p>Events Hosted</p>
            </div>
        </div>
        <div class="col-md-4 " data-aos="zoom-in" data-aos-delay="200">
            <div class="counter-box"><i class="fas fa-photo-video fa-2x mb-2"></i>
                <h3 class="counter-number" data-target="<?php echo $mediaCount; ?>">0</h3>
                <p>Media Items</p>
            </div>
        </div>

    </div>
</div>

<!-- Upcoming Events -->
<div class="container my-5">
    <h2 class="section-title" data-aos="fade-up">Upcoming Events</h2>
    <div class="row g-4">
        <?php if (empty($upcomingEvents)): ?>
            <div class="col-12 text-center">
                <p>No upcoming events.</p>
            </div>
        <?php else: ?>
            <?php foreach ($upcomingEvents as $key => $event): ?>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="<?php echo $key * 50; ?>">
                    <a href="event-details.php?id=<?php echo $event['id']; ?>" class="text-decoration-none">
                        <div class="card event-card h-100">
                            <img src="<?php echo APP_URL . '/' . ($event['image'] ?? 'assets/images/default-event.jpg'); ?>"
                                class="card-img-top" style="height: 180px; object-fit: cover;"
                                onerror="this.src='https://placehold.co/400x200?text=Event'">
                            <div class="card-body">
                                <?php if (Auth::isLoggedIn() && isset($registeredMap[$event['id']])): ?>
                                    <span class="badge bg-success mb-2"><i class="fas fa-check-circle"></i> Registered</span>
                                <?php endif; ?>
                                <h5 class="card-title"><?php echo Security::escape($event['title']); ?></h5>
                                <p class="card-text text-muted small"><i class="far fa-calendar-alt"></i>
                                    <?php echo date('M j, Y g:i A', strtotime($event['event_date'])); ?></p>
                                <p class="card-text"><?php echo Security::escape(substr($event['description'], 0, 80)); ?>...
                                </p>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="text-center mt-4"><a href="events.php" class="btn btn-outline-primary btn-lg">View All Events</a></div>
</div>

<!-- Ministries Section (shortened for brevity, but keep as before) -->
<div class="container my-5">
    <h2 class="section-title"><?php echo Security::escape($settings['ministries_title'] ?? 'Our Ministries'); ?></h2>
    <div class="row g-4">
        <div class="col-md-6 col-lg-3" data-aos="flip-left">
            <div class="ministry-card card text-center p-3">
                <div class="card-body"><i class="fas fa-child fa-3x" style="color:var(--primary);"></i>
                    <h5 class="mt-3">
                        <?php echo Security::escape($settings['ministry_1_name'] ?? 'Children’s Ministry'); ?>
                    </h5>
                    <p><?php echo Security::escape($settings['ministry_1_desc'] ?? ''); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3" data-aos="flip-left" data-aos-delay="100">
            <div class="ministry-card card text-center p-3">
                <div class="card-body"><i class="fas fa-users fa-3x" style="color:var(--primary);"></i>
                    <h5 class="mt-3"><?php echo Security::escape($settings['ministry_2_name'] ?? 'Youth Ministry'); ?>
                    </h5>
                    <p><?php echo Security::escape($settings['ministry_2_desc'] ?? ''); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3" data-aos="flip-left" data-aos-delay="200">
            <div class="ministry-card card text-center p-3">
                <div class="card-body"><i class="fas fa-music fa-3x" style="color:var(--primary);"></i>
                    <h5 class="mt-3"><?php echo Security::escape($settings['ministry_3_name'] ?? 'Worship Team'); ?>
                    </h5>
                    <p><?php echo Security::escape($settings['ministry_3_desc'] ?? ''); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3" data-aos="flip-left" data-aos-delay="300">
            <div class="ministry-card card text-center p-3">
                <div class="card-body"><i class="fas fa-globe fa-3x" style="color:var(--primary);"></i>
                    <h5 class="mt-3">
                        <?php echo Security::escape($settings['ministry_4_name'] ?? 'Outreach & Missions'); ?>
                    </h5>
                    <p><?php echo Security::escape($settings['ministry_4_desc'] ?? ''); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Latest Media -->
<div class="container my-5">
    <h2 class="section-title">Latest Media</h2>
    <div class="row g-4">
        <?php if (empty($recentMedia)): ?>
            <div class="col-12 text-center">
                <p>No media yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($recentMedia as $media): ?>
                <div class="col-md-6 col-lg-3" data-aos="zoom-in">
                    <div class="card media-card h-100" data-bs-toggle="modal" data-bs-target="#mediaModal"
                        data-title="<?php echo Security::escape($media['title']); ?>"
                        data-description="<?php echo Security::escape($media['description']); ?>"
                        data-file="<?php echo APP_URL . '/' . $media['file_path']; ?>"
                        data-type="<?php echo $media['file_type']; ?>">
                        <?php if ($media['file_type'] === 'image'): ?>
                            <img src="<?php echo APP_URL . '/' . $media['file_path']; ?>" class="card-img-top"
                                style="height: 180px; object-fit: cover;">
                        <?php else: ?>
                            <div style="position:relative"><video src="<?php echo APP_URL . '/' . $media['file_path']; ?>"
                                    preload="metadata" style="width:100%;height:180px;object-fit:cover;"></video><i
                                    class="fas fa-play-circle"
                                    style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:40px;color:white;text-shadow:0 0 5px black;"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h6><?php echo Security::escape($media['title']); ?></h6>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="text-center mt-4"><a href="media.php" class="btn btn-outline-primary btn-lg">View Full Gallery</a></div>
</div>

<!-- Testimonials Carousel (simplified) -->
<div class="container my-5">
    <h2 class="section-title">What Our Members Say</h2>
    <div id="testimonialCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
            <div class="carousel-item active">
                <div class="testimonial-card"><i class="fas fa-quote-left"></i>
                    <p class="mt-3"><?php echo Security::escape($settings['testimonial_1_text'] ?? 'Great church!'); ?>
                    </p>
                    <h5>- <?php echo Security::escape($settings['testimonial_1_name'] ?? 'John Doe'); ?></h5>
                </div>
            </div>
            <div class="carousel-item">
                <div class="testimonial-card"><i class="fas fa-quote-left"></i>
                    <p class="mt-3">
                        <?php echo Security::escape($settings['testimonial_2_text'] ?? 'Amazing community.'); ?>
                    </p>
                    <h5>- <?php echo Security::escape($settings['testimonial_2_name'] ?? 'Jane Smith'); ?></h5>
                </div>
            </div>
            <div class="carousel-item">
                <div class="testimonial-card"><i class="fas fa-quote-left"></i>
                    <p class="mt-3">
                        <?php echo Security::escape($settings['testimonial_3_text'] ?? 'Love the worship.'); ?>
                    </p>
                    <h5>- <?php echo Security::escape($settings['testimonial_3_name'] ?? 'Michael Johnson'); ?></h5>
                </div>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel"
            data-bs-slide="prev"><span class="carousel-control-prev-icon bg-dark rounded-circle p-2"></span></button>
        <button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel"
            data-bs-slide="next"><span class="carousel-control-next-icon bg-dark rounded-circle p-2"></span></button>
    </div>
</div>

<!-- Service Times & Map -->
<div class="container my-5">
    <div class="row g-4 align-items-center">
        <div class="col-md-6">
            <div class="bg-light p-4 rounded-4">
                <h3><i class="fas fa-clock"></i> Service Times</h3>
                <p><?php echo nl2br(Security::escape($settings['service_times_sunday'] ?? 'Sunday 10am')); ?></p>
                <p><?php echo nl2br(Security::escape($settings['service_times_wednesday'] ?? 'Wednesday 7pm')); ?></p>
                <hr>
                <p><i class="fas fa-map-marker-alt"></i>
                    <?php echo Security::escape($settings['contact_address'] ?? 'Address'); ?></p>
            </div>
        </div>
        <div class="col-md-6">
            <?php if (!empty($settings['google_maps_embed'])): ?>
                <div class="ratio ratio-16x9">
                    <?php
                    // Removes fixed width/height from the iframe string so Bootstrap can control it
                    $map_iframe = preg_replace('/width="\d+"/', 'width="100%"', $settings['google_maps_embed']);
                    $map_iframe = preg_replace('/height="\d+"/', 'height="100%"', $map_iframe);
                    echo $map_iframe;
                    ?>
                </div>
            <?php else: ?>
                <div
                    class="bg-secondary text-white p-5 text-center rounded-4 h-100 d-flex align-items-center justify-content-center">
                    Map
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- CTA Banner -->
<div class="container my-5">
    <div class="cta-section"
        style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; border-radius: 2rem; padding: 3rem; text-align: center;">
        <h2><?php echo Security::escape($settings['cta_title'] ?? 'Join Our Family'); ?></h2>
        <p><?php echo Security::escape($settings['cta_text'] ?? ''); ?></p>
        <a href="<?php echo APP_URL; ?>/contact.php"
            class="btn btn-light btn-lg mt-3"><?php echo Security::escape($settings['cta_btn_text'] ?? 'Contact Us'); ?></a>
    </div>
</div>


<!-- Social Icons (if any) -->
<!-- Social Icons -->
<?php
$hasSocial = (
    !empty($settings['facebook_url']) ||
    !empty($settings['youtube_url']) ||
    !empty($settings['instagram_url']) ||
    !empty($settings['whatsapp_url']) ||
    !empty($settings['tiktok_url'])
);
?>
<?php if ($hasSocial): ?>
    <div class="container text-center my-4">
        <div class="social-icons">
            <?php if (!empty($settings['facebook_url'])): ?>
                <a href="<?php echo Security::escape($settings['facebook_url']); ?>" target="_blank" class="mx-2"
                    aria-label="Facebook">
                    <i class="fab fa-facebook fa-2x"></i>
                </a>
            <?php endif; ?>

            <?php if (!empty($settings['youtube_url'])): ?>
                <a href="<?php echo Security::escape($settings['youtube_url']); ?>" target="_blank" class="mx-2"
                    aria-label="YouTube">
                    <i class="fab fa-youtube fa-2x"></i>
                </a>
            <?php endif; ?>

            <?php if (!empty($settings['instagram_url'])): ?>
                <a href="<?php echo Security::escape($settings['instagram_url']); ?>" target="_blank" class="mx-2"
                    aria-label="Instagram">
                    <i class="fab fa-instagram fa-2x"></i>
                </a>
            <?php endif; ?>

            <?php if (!empty($settings['whatsapp_url'])): ?>
                <a href="<?php echo Security::escape($settings['whatsapp_url']); ?>" target="_blank" class="mx-2"
                    aria-label="WhatsApp">
                    <i class="fab fa-whatsapp fa-2x"></i>
                </a>
            <?php endif; ?>

            <?php if (!empty($settings['tiktok_url'])): ?>
                <a href="<?php echo Security::escape($settings['tiktok_url']); ?>" target="_blank" class="mx-2"
                    aria-label="TikTok">
                    <i class="fab fa-tiktok fa-2x"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Media Modal -->
<div class="modal fade" id="mediaModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mediaModalTitle"></h5><button type="button" class="btn-close"
                    data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div id="mediaModalContent"></div>
                <p id="mediaModalDesc" class="mt-3 text-muted"></p>
            </div>
        </div>
    </div>
</div>

<script>
    AOS.init({ duration: 800, once: true });
    const counters = document.querySelectorAll('.counter-number');
    counters.forEach(c => { const update = () => { const target = parseInt(c.dataset.target); const current = parseInt(c.innerText); const inc = Math.ceil(target / 20); if (current < target) { c.innerText = current + inc; setTimeout(update, 30); } else c.innerText = target; }; update(); });
    const mediaModal = document.getElementById('mediaModal');
    mediaModal.addEventListener('show.bs.modal', function (e) {
        const card = e.relatedTarget;
        document.getElementById('mediaModalTitle').innerText = card.dataset.title;
        document.getElementById('mediaModalDesc').innerText = card.dataset.description;
        const container = document.getElementById('mediaModalContent');
        if (card.dataset.type === 'image') container.innerHTML = `<img src="${card.dataset.file}" class="img-fluid">`;
        else container.innerHTML = `<video src="${card.dataset.file}" controls autoplay class="w-100"></video>`;
    });
    mediaModal.addEventListener('hidden.bs.modal', function () { const container = document.getElementById('mediaModalContent'); const video = container.querySelector('video'); if (video) video.pause(); container.innerHTML = ''; });
    document.getElementById('newsletterForm')?.addEventListener('submit', function (e) { e.preventDefault(); const email = document.getElementById('newsletterEmail').value; const msgDiv = document.getElementById('newsletterMsg'); msgDiv.innerHTML = '<div class="alert alert-success">Thank you for subscribing!</div>'; this.reset(); });
</script>

<?php include __DIR__ . '/../app/views/footer.php'; ?>