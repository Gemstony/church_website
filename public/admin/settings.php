<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/helpers/Security.php';
require_once __DIR__ . '/../../app/models/Setting.php';
Auth::requireAdmin();

$success = '';
$error = '';

// Handle logo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        // Update text/color settings
        $textSettings = ['site_name', 'primary_color', 'secondary_color', 'footer_text', 'contact_email', 'contact_phone', 'contact_address', 'about_content'];
        foreach ($textSettings as $key) {
            if (isset($_POST[$key])) {
                Setting::set($key, $_POST[$key], $key === 'about_content' ? 'textarea' : 'text');
            }
        }
        // Handle logo upload
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/assets/uploads/settings/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
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
// Fetch current settings
$settings = Setting::getAll();
$csrf_token = Security::generateCSRFToken();

include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Site Settings</h1>
</div>
    <h1>Site Settings</h1>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo Security::escape($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo Security::escape($error); ?></div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <div class="mb-3">
            <label>Site Name</label>
            <input type="text" name="site_name" class="form-control" value="<?php echo Security::escape($settings['site_name'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label>Primary Color (hex)</label>
            <input type="color" name="primary_color" class="form-control form-control-color" value="<?php echo Security::escape($settings['primary_color'] ?? '#0d6efd'); ?>">
        </div>
        <div class="mb-3">
            <label>Secondary Color (hex)</label>
            <input type="color" name="secondary_color" class="form-control form-control-color" value="<?php echo Security::escape($settings['secondary_color'] ?? '#6c757d'); ?>">
        </div>
        <div class="mb-3">
            <label>Logo Image</label>
            <?php if (!empty($settings['logo_path'])): ?>
                <div><img src="<?php echo APP_URL . '/' . $settings['logo_path']; ?>" style="max-height:100px"></div>
            <?php endif; ?>
            <input type="file" name="logo" class="form-control" accept="image/*">
        </div>
        <div class="mb-3">
            <label>Footer Text</label>
            <input type="text" name="footer_text" class="form-control" value="<?php echo Security::escape($settings['footer_text'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label>Contact Email</label>
            <input type="email" name="contact_email" class="form-control" value="<?php echo Security::escape($settings['contact_email'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label>Contact Phone</label>
            <input type="text" name="contact_phone" class="form-control" value="<?php echo Security::escape($settings['contact_phone'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label>Contact Address</label>
            <textarea name="contact_address" class="form-control" rows="2"><?php echo Security::escape($settings['contact_address'] ?? ''); ?></textarea>
        </div>
        <div class="mb-3">
            <label>About Us Content (HTML allowed)</label>
            <textarea name="about_content" class="form-control" rows="8"><?php echo Security::escape($settings['about_content'] ?? ''); ?></textarea>
        </div>
        <button type="submit" name="update_settings" class="btn btn-primary">Save All Settings</button>
        <a href="<?php echo APP_URL; ?>/admin/" class="btn btn-secondary">Back to Admin Dashboard</a>
    </form>
<?php include __DIR__ . '/includes/footer.php'; ?>