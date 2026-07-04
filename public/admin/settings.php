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
        // ---- Define all settings keys and their types ----
        $textSettings = [
            // General
            'site_name' => 'text',
            'primary_color' => 'text',
            'secondary_color' => 'text',
            'footer_text' => 'textarea',
            // Contact
            'contact_email' => 'text',
            'contact_phone' => 'text',
            'contact_address' => 'textarea',
            // About
            'about_content' => 'textarea',
            // Homepage welcome
            'home_welcome_title' => 'text',
            'home_welcome_text' => 'textarea',
            // Service times
            'service_times_sunday' => 'text',
            'service_times_wednesday' => 'text',
            // CTA
            'cta_title' => 'text',
            'cta_text' => 'textarea',
            'cta_btn_text' => 'text',
            'cta_btn_link' => 'text',
            // Social & Maps
            'facebook_url' => 'text',
            'youtube_url' => 'text',
            'instagram_url' => 'text',   // <-- NEW
            'whatsapp_url' => 'text',    // <-- NEW
            'tiktok_url' => 'text',      // <-- NEW
            'google_maps_embed' => 'textarea',
            // Vision & Mission
            'vision_title' => 'text',
            'vision_text' => 'textarea',
            'mission_title' => 'text',
            'mission_text' => 'textarea',
            // Ministries
            'ministries_title' => 'text',
            'ministry_1_name' => 'text',
            'ministry_1_desc' => 'textarea',
            'ministry_2_name' => 'text',
            'ministry_2_desc' => 'textarea',
            'ministry_3_name' => 'text',
            'ministry_3_desc' => 'textarea',
            'ministry_4_name' => 'text',
            'ministry_4_desc' => 'textarea',
            // Testimonials
            'testimonial_1_name' => 'text',
            'testimonial_1_text' => 'textarea',
            'testimonial_2_name' => 'text',
            'testimonial_2_text' => 'textarea',
            'testimonial_3_name' => 'text',
            'testimonial_3_text' => 'textarea',
            // Leaders (name, position, quote)
            'leader_1_name' => 'text',
            'leader_1_position' => 'text',
            'leader_1_quote' => 'textarea',
            'leader_2_name' => 'text',
            'leader_2_position' => 'text',
            'leader_2_quote' => 'textarea',
            'leader_3_name' => 'text',
            'leader_3_position' => 'text',
            'leader_3_quote' => 'textarea',
        ];

        // Save text/textarea fields
        foreach ($textSettings as $key => $type) {
            if (isset($_POST[$key])) {
                Setting::set($key, $_POST[$key], $type);
            }
        }

        // ---- Helper function to upload images ----
        function uploadSettingImage($file, $key) {
            if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return null;
            $uploadDir = __DIR__ . '/../../public/assets/uploads/settings/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $key . '_' . time() . '.' . $ext;
            $target = $uploadDir . $filename;
            if (move_uploaded_file($file['tmp_name'], $target)) {
                return 'assets/uploads/settings/' . $filename;
            }
            return null;
        }

        // ---- Logo upload ----
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $path = uploadSettingImage($_FILES['logo'], 'logo');
            if ($path) Setting::set('logo_path', $path, 'image');
            else $error = 'Failed to upload logo.';
        }

        // ---- Leader images (3) ----
        for ($i = 1; $i <= 3; $i++) {
            $field = "leader_{$i}_image";
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $path = uploadSettingImage($_FILES[$field], $field);
                if ($path) Setting::set($field, $path, 'image');
                else $error = "Failed to upload leader {$i} image.";
            }
        }

        if (!$error) {
            $success = 'Settings updated successfully!';
        }
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
                            <div><img src="<?php echo APP_URL . '/' . $settings['logo_path']; ?>" style="max-height:100px"></div>
                        <?php endif; ?><input type="file" name="logo" class="form-control" accept="image/*"></div>
                    <div class="mb-3"><label>Footer Text</label><textarea name="footer_text" class="form-control" rows="3"><?php echo Security::escape($settings['footer_text'] ?? ''); ?></textarea></div>
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
                            rows="2"><?php echo Security::escape($settings['contact_address'] ?? ''); ?></textarea></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Homepage Welcome Section</div>
        <div class="card-body">
            <div class="mb-3"><label>Welcome Title</label><input type="text" name="home_welcome_title"
                    class="form-control"
                    value="<?php echo Security::escape($settings['home_welcome_title'] ?? 'Welcome to Our Church'); ?>"></div>
            <div class="mb-3"><label>Welcome Text</label><textarea name="home_welcome_text" class="form-control"
                    rows="3"><?php echo Security::escape($settings['home_welcome_text'] ?? ''); ?></textarea></div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Service Times</div>
        <div class="card-body">
            <div class="mb-3"><label>Sunday Service</label><input type="text" name="service_times_sunday"
                    class="form-control"
                    value="<?php echo Security::escape($settings['service_times_sunday'] ?? 'Sunday Service: 10:00 AM'); ?>"></div>
            <div class="mb-3"><label>Wednesday Service/Prayer</label><input type="text" name="service_times_wednesday"
                    class="form-control"
                    value="<?php echo Security::escape($settings['service_times_wednesday'] ?? 'Wednesday Prayer: 7:00 PM'); ?>"></div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Call-to-Action Banner</div>
        <div class="card-body">
            <div class="mb-3"><label>CTA Title</label><input type="text" name="cta_title" class="form-control"
                    value="<?php echo Security::escape($settings['cta_title'] ?? 'Join Our Family'); ?>"></div>
            <div class="mb-3"><label>CTA Text</label><textarea name="cta_text" class="form-control"
                    rows="2"><?php echo Security::escape($settings['cta_text'] ?? ''); ?></textarea></div>
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
            <div class="mb-3"><label>Instagram URL</label><input type="url" name="instagram_url" class="form-control"
                    value="<?php echo Security::escape($settings['instagram_url'] ?? ''); ?>"></div>
            <div class="mb-3"><label>WhatsApp URL</label><input type="url" name="whatsapp_url" class="form-control"
                    value="<?php echo Security::escape($settings['whatsapp_url'] ?? ''); ?>"></div>
            <div class="mb-3"><label>TikTok URL</label><input type="url" name="tiktok_url" class="form-control"
                    value="<?php echo Security::escape($settings['tiktok_url'] ?? ''); ?>"></div>
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

    <!-- Vision & Mission -->
    <div class="card mb-4">
        <div class="card-header">Vision & Mission</div>
        <div class="card-body">
            <div class="mb-3"><label>Vision Title</label><input type="text" name="vision_title" class="form-control"
                    value="<?php echo Security::escape($settings['vision_title'] ?? 'Our Vision'); ?>"></div>
            <div class="mb-3"><label>Vision Text</label><textarea name="vision_text" class="form-control"
                    rows="3"><?php echo Security::escape($settings['vision_text'] ?? ''); ?></textarea></div>
            <div class="mb-3"><label>Mission Title</label><input type="text" name="mission_title" class="form-control"
                    value="<?php echo Security::escape($settings['mission_title'] ?? 'Our Mission'); ?>"></div>
            <div class="mb-3"><label>Mission Text</label><textarea name="mission_text" class="form-control"
                    rows="3"><?php echo Security::escape($settings['mission_text'] ?? ''); ?></textarea></div>
        </div>
    </div>

    <!-- Ministries -->
    <div class="card mb-4">
        <div class="card-header">Ministries</div>
        <div class="card-body">
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
        </div>
    </div>

    <!-- Testimonials -->
    <div class="card mb-4">
        <div class="card-header">Testimonials</div>
        <div class="card-body">
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
            <div class="row mt-2">
                <div class="col-md-6"><label>Testimonial 3 Name</label><input type="text" name="testimonial_3_name"
                        class="form-control"
                        value="<?php echo Security::escape($settings['testimonial_3_name'] ?? ''); ?>"><label>Text</label><textarea
                        name="testimonial_3_text" class="form-control"
                        rows="2"><?php echo Security::escape($settings['testimonial_3_text'] ?? ''); ?></textarea></div>
            </div>
        </div>
    </div>

    <!-- Leadership Team (NEW) -->
    <div class="card mb-4">
        <div class="card-header">Leadership Team</div>
        <div class="card-body">
            <?php for ($i = 1; $i <= 3; $i++): ?>
                <h5>Leader <?php echo $i; ?></h5>
                <div class="row">
                    <div class="col-md-4">
                        <label>Image</label>
                        <?php if (!empty($settings["leader_{$i}_image"])): ?>
                            <div><img src="<?php echo APP_URL . '/' . $settings["leader_{$i}_image"]; ?>" style="max-height:100px;"></div>
                        <?php endif; ?>
                        <input type="file" name="leader_<?php echo $i; ?>_image" class="form-control" accept="image/*">
                    </div>
                    <div class="col-md-4">
                        <label>Name</label>
                        <input type="text" name="leader_<?php echo $i; ?>_name" class="form-control"
                               value="<?php echo Security::escape($settings["leader_{$i}_name"] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label>Position</label>
                        <input type="text" name="leader_<?php echo $i; ?>_position" class="form-control"
                               value="<?php echo Security::escape($settings["leader_{$i}_position"] ?? ''); ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label>Quote / Description</label>
                    <textarea name="leader_<?php echo $i; ?>_quote" class="form-control"
                              rows="2"><?php echo Security::escape($settings["leader_{$i}_quote"] ?? ''); ?></textarea>
                </div>
                <?php if ($i < 3): ?><hr><?php endif; ?>
            <?php endfor; ?>
        </div>
    </div>

    <button type="submit" name="update_settings" class="btn btn-primary">Save All Settings</button>
    <a href="<?php echo APP_URL; ?>/admin/" class="btn btn-secondary">Back to Admin Dashboard</a>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>