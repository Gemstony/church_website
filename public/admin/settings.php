<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/helpers/Security.php';
require_once __DIR__ . '/../../app/models/Setting.php';
Auth::requireAdmin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        // Text/color settings
        $textSettings = [
            'site_name',
            'primary_color',
            'secondary_color',
            'footer_text',
            'contact_email',
            'contact_phone',
            'contact_address',
            'about_content',
            // Homepage welcome section
            'home_welcome_title',
            'home_welcome_text',
            // Service times
            'service_times_sunday',
            'service_times_wednesday',
            // CTA
            'cta_title',
            'cta_text',
            'cta_btn_text',
            'cta_btn_link',
            // Social
            'facebook_url',
            'youtube_url',
            // Google Maps
            'google_maps_embed'
        ];
        foreach ($textSettings as $key) {
            if (isset($_POST[$key])) {
                $type = (in_array($key, ['about_content', 'home_welcome_text', 'cta_text', 'google_maps_embed'])) ? 'textarea' : 'text';
                Setting::set($key, $_POST[$key], $type);
            }
        }
        // Logo upload
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/assets/uploads/settings/';
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0777, true);
            $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $filename = 'logo_' . time() . '.' . $ext;
            $target = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $target)) {
                $relativePath = 'assets/uploads/settings/' . $filename;
                Setting::set('logo_path', $relativePath, 'image');
            } else {
                $error = 'Failed to upload logo.';
            }
        }
        $success = 'Settings updated successfully!';
    }
}

$settings = Setting::getAll();
$csrf_token = Security::generateCSRFToken();

include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Site Settings</h1>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo Security::escape($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo Security::escape($error); ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">General Settings</div>
                <div class="card-body">
                    <div class="mb-3"><label>Site Name</label><input type="text" name="site_name" class="form-control"
                            value="<?php echo Security::escape($settings['site_name'] ?? ''); ?>"></div>
                    <div class="mb-3"><label>Primary Color (hex)</label><input type="color" name="primary_color"
                            class="form-control form-control-color"
                            value="<?php echo Security::escape($settings['primary_color'] ?? '#0d6efd'); ?>"></div>
                    <div class="mb-3"><label>Secondary Color (hex)</label><input type="color" name="secondary_color"
                            class="form-control form-control-color"
                            value="<?php echo Security::escape($settings['secondary_color'] ?? '#6c757d'); ?>"></div>
                    <div class="mb-3"><label>Logo Image</label><?php if (!empty($settings['logo_path'])): ?>
                            <div><img src="<?php echo APP_URL . '/' . $settings['logo_path']; ?>" style="max-height:100px">
                            </div><?php endif; ?><input type="file" name="logo" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3"><label>Footer Text</label><input type="text" name="footer_text"
                            class="form-control"
                            value="<?php echo Security::escape($settings['footer_text'] ?? ''); ?>"></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">Contact Information</div>
                <div class="card-body">
                    <div class="mb-3"><label>Contact Email</label><input type="email" name="contact_email"
                            class="form-control"
                            value="<?php echo Security::escape($settings['contact_email'] ?? ''); ?>"></div>
                    <div class="mb-3"><label>Contact Phone</label><input type="text" name="contact_phone"
                            class="form-control"
                            value="<?php echo Security::escape($settings['contact_phone'] ?? ''); ?>"></div>
                    <div class="mb-3"><label>Contact Address</label><textarea name="contact_address"
                            class="form-control"
                            rows="2"><?php echo Security::escape($settings['contact_address'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Homepage Welcome Section</div>
        <div class="card-body">
            <div class="mb-3"><label>Welcome Title</label><input type="text" name="home_welcome_title"
                    class="form-control"
                    value="<?php echo Security::escape($settings['home_welcome_title'] ?? 'Welcome to Our Church'); ?>">
            </div>
            <div class="mb-3"><label>Welcome Text</label><textarea name="home_welcome_text" class="form-control"
                    rows="3"><?php echo Security::escape($settings['home_welcome_text'] ?? 'We are a community of faith, love, and service.'); ?></textarea>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Service Times</div>
        <div class="card-body">
            <div class="mb-3"><label>Sunday Service</label><input type="text" name="service_times_sunday"
                    class="form-control"
                    value="<?php echo Security::escape($settings['service_times_sunday'] ?? 'Sunday Service: 10:00 AM'); ?>">
            </div>
            <div class="mb-3"><label>Wednesday Service/Prayer</label><input type="text" name="service_times_wednesday"
                    class="form-control"
                    value="<?php echo Security::escape($settings['service_times_wednesday'] ?? 'Wednesday Prayer: 7:00 PM'); ?>">
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Call-to-Action Banner</div>
        <div class="card-body">
            <div class="mb-3"><label>CTA Title</label><input type="text" name="cta_title" class="form-control"
                    value="<?php echo Security::escape($settings['cta_title'] ?? 'Join Our Family'); ?>"></div>
            <div class="mb-3"><label>CTA Text</label><textarea name="cta_text" class="form-control"
                    rows="2"><?php echo Security::escape($settings['cta_text'] ?? 'Become part of our growing community.'); ?></textarea>
            </div>
            <div class="mb-3"><label>CTA Button Text</label><input type="text" name="cta_btn_text" class="form-control"
                    value="<?php echo Security::escape($settings['cta_btn_text'] ?? 'Contact Us'); ?>"></div>
            <div class="mb-3"><label>CTA Button Link</label><input type="text" name="cta_btn_link" class="form-control"
                    value="<?php echo Security::escape($settings['cta_btn_link'] ?? '/contact.php'); ?>"></div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Social Media & Maps</div>
        <div class="card-body">
            <div class="mb-3"><label>Facebook URL</label><input type="url" name="facebook_url" class="form-control"
                    value="<?php echo Security::escape($settings['facebook_url'] ?? ''); ?>"></div>
            <div class="mb-3"><label>YouTube URL</label><input type="url" name="youtube_url" class="form-control"
                    value="<?php echo Security::escape($settings['youtube_url'] ?? ''); ?>"></div>
            <div class="mb-3"><label>Google Maps Embed Code (iframe)</label><textarea name="google_maps_embed"
                    class="form-control"
                    rows="3"><?php echo Security::escape($settings['google_maps_embed'] ?? ''); ?></textarea></div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">About Us Content</div>
        <div class="card-body">
            <textarea name="about_content" class="form-control"
                rows="8"><?php echo Security::escape($settings['about_content'] ?? ''); ?></textarea>
        </div>
    </div>

    <hr>
    <h4>Homepage – Vision & Mission</h4>
    <div class="mb-3"><label>Vision Title</label><input type="text" name="vision_title" class="form-control"
            value="<?php echo Security::escape($settings['vision_title'] ?? 'Our Vision'); ?>"></div>
    <div class="mb-3"><label>Vision Text</label><textarea name="vision_text" class="form-control"
            rows="3"><?php echo Security::escape($settings['vision_text'] ?? ''); ?></textarea></div>
    <div class="mb-3"><label>Mission Title</label><input type="text" name="mission_title" class="form-control"
            value="<?php echo Security::escape($settings['mission_title'] ?? 'Our Mission'); ?>"></div>
    <div class="mb-3"><label>Mission Text</label><textarea name="mission_text" class="form-control"
            rows="3"><?php echo Security::escape($settings['mission_text'] ?? ''); ?></textarea></div>

    <hr>
    <h4>Homepage – Ministries</h4>
    <div class="mb-3"><label>Ministries Section Title</label><input type="text" name="ministries_title"
            class="form-control"
            value="<?php echo Security::escape($settings['ministries_title'] ?? 'Our Ministries'); ?>"></div>
    <div class="row">
        <div class="col-md-6"><label>Ministry 1 Name</label><input type="text" name="ministry_1_name"
                class="form-control"
                value="<?php echo Security::escape($settings['ministry_1_name'] ?? ''); ?>"><label>Description</label><textarea
                name="ministry_1_desc" class="form-control"
                rows="2"><?php echo Security::escape($settings['ministry_1_desc'] ?? ''); ?></textarea></div>
        <div class="col-md-6"><label>Ministry 2 Name</label><input type="text" name="ministry_2_name"
                class="form-control"
                value="<?php echo Security::escape($settings['ministry_2_name'] ?? ''); ?>"><label>Description</label><textarea
                name="ministry_2_desc" class="form-control"
                rows="2"><?php echo Security::escape($settings['ministry_2_desc'] ?? ''); ?></textarea></div>
    </div>
    <div class="row mt-2">
        <div class="col-md-6"><label>Ministry 3 Name</label><input type="text" name="ministry_3_name"
                class="form-control"
                value="<?php echo Security::escape($settings['ministry_3_name'] ?? ''); ?>"><label>Description</label><textarea
                name="ministry_3_desc" class="form-control"
                rows="2"><?php echo Security::escape($settings['ministry_3_desc'] ?? ''); ?></textarea></div>
        <div class="col-md-6"><label>Ministry 4 Name</label><input type="text" name="ministry_4_name"
                class="form-control"
                value="<?php echo Security::escape($settings['ministry_4_name'] ?? ''); ?>"><label>Description</label><textarea
                name="ministry_4_desc" class="form-control"
                rows="2"><?php echo Security::escape($settings['ministry_4_desc'] ?? ''); ?></textarea></div>
    </div>

    <hr>
    <h4>Homepage – Testimonials</h4>
    <div class="row">
        <div class="col-md-6"><label>Testimonial 1 Name</label><input type="text" name="testimonial_1_name"
                class="form-control"
                value="<?php echo Security::escape($settings['testimonial_1_name'] ?? ''); ?>"><label>Text</label><textarea
                name="testimonial_1_text" class="form-control"
                rows="2"><?php echo Security::escape($settings['testimonial_1_text'] ?? ''); ?></textarea></div>
        <div class="col-md-6"><label>Testimonial 2 Name</label><input type="text" name="testimonial_2_name"
                class="form-control"
                value="<?php echo Security::escape($settings['testimonial_2_name'] ?? ''); ?>"><label>Text</label><textarea
                name="testimonial_2_text" class="form-control"
                rows="2"><?php echo Security::escape($settings['testimonial_2_text'] ?? ''); ?></textarea></div>
    </div>
    <div class="row mt-2 mb-4">
        <div class="col-md-6"><label>Testimonial 3 Name</label><input type="text" name="testimonial_3_name"
                class="form-control"
                value="<?php echo Security::escape($settings['testimonial_3_name'] ?? ''); ?>"><label>Text</label><textarea
                name="testimonial_3_text" class="form-control"
                rows="2"><?php echo Security::escape($settings['testimonial_3_text'] ?? ''); ?></textarea></div>
    </div>

    <button type="submit" name="update_settings" class="btn btn-primary">Save All Settings</button>
    <a href="<?php echo APP_URL; ?>/admin/" class="btn btn-secondary">Back to Admin Dashboard</a>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>