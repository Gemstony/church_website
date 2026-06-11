<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/helpers/Security.php';
require_once __DIR__ . '/../app/models/Setting.php';
require_once __DIR__ . '/../app/models/ContactMessage.php';

$success = '';
$error = '';

$contactEmail = Setting::get('contact_email', 'info@ourchurch.org');
$contactPhone = Setting::get('contact_phone', '+1 234 567 8900');
$contactAddress = Setting::get('contact_address', '123 Faith Street, City, Country');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $message = trim($_POST['message'] ?? '');
        
        if (empty($name) || empty($email) || empty($message)) {
            $error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($message) < 10) {
            $error = 'Message must be at least 10 characters.';
        } else {
            if (ContactMessage::create($name, $email, $message)) {
                $success = 'Thank you for contacting us. We will get back to you soon.';
                // Clear form
                $_POST = [];
            } else {
                $error = 'Something went wrong. Please try again later.';
            }
        }
    }
}

$csrf_token = Security::generateCSRFToken();
include __DIR__ . '/../app/views/header.php';
?>

<div class="row g-5">
    <div class="col-md-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-4">
                <h3 class="h4 mb-3">📍 Visit Us</h3>
                <p class="text-muted"><?php echo Security::escape($contactAddress); ?></p>
                
                <h3 class="h4 mt-4 mb-3">📞 Call Us</h3>
                <p class="text-muted"><?php echo Security::escape($contactPhone); ?></p>
                
                <h3 class="h4 mt-4 mb-3">✉️ Email Us</h3>
                <p class="text-muted"><?php echo Security::escape($contactEmail); ?></p>
            </div>
        </div>
    </div>
    
    <div class="col-md-7">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h3 class="h4 mb-3">Send us a Message</h3>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo Security::escape($success); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo Security::escape($error); ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label">Your Name *</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo Security::escape($_POST['name'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo Security::escape($_POST['email'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message *</label>
                        <textarea class="form-control" id="message" name="message" rows="5" required><?php echo Security::escape($_POST['message'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary px-4">Send Message</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../app/views/footer.php'; ?>