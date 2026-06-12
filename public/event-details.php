<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/helpers/Security.php';
require_once __DIR__ . '/../app/helpers/Auth.php';
require_once __DIR__ . '/../app/models/Event.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$event = Event::find($id);

if (!$event) {
    header('Location: ' . APP_URL . '/events.php');
    exit;
}

$csrf_token = Security::generateCSRFToken();
$isRegistered = false;
if (Auth::isLoggedIn()) {
    $isRegistered = Event::isUserRegistered($event['id'], Auth::userId());
}

include __DIR__ . '/../app/views/header.php';
?>

<!-- SweetAlert2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="row">
    <div class="col-xl mx-auto">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/events.php">Events</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo Security::escape($event['title']); ?>
                </li>
            </ol>
        </nav>

        <div class="card shadow-sm border-0">
            <?php if ($event['image']): ?>
                <img src="<?php echo APP_URL . '/' . $event['image']; ?>" class="card-img-top"
                    alt="<?php echo Security::escape($event['title']); ?>" style="max-height: 400px; object-fit: cover;">
            <?php endif; ?>
            <div class="card-body p-4 p-lg-5">
                <h1 class="h2 mb-3"><?php echo Security::escape($event['title']); ?></h1>

                <div class="mb-4 text-muted">
                    <div class="mb-2">
                        <i class="far fa-calendar-alt"></i>
                        <strong>Date & Time:</strong>
                        <?php echo date('F j, Y g:i A', strtotime($event['event_date'])); ?>
                        <?php if ($event['event_end_date']): ?>
                            – <?php echo date('F j, Y g:i A', strtotime($event['event_end_date'])); ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($event['location']): ?>
                        <div class="mb-2">
                            <i class="fas fa-map-marker-alt"></i>
                            <strong>Location:</strong> <?php echo Security::escape($event['location']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="event-description">
                    <?php echo nl2br(Security::escape($event['description'])); ?>
                </div>

                <?php if (Auth::isLoggedIn()): ?>
                    <div class="mt-5">
                        <?php if ($isRegistered): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> You have already registered for this event.
                            </div>
                            <button class="btn btn-secondary btn-lg" disabled>Already Registered</button>
                        <?php else: ?>
                            <button class="btn btn-primary btn-lg" id="registerBtn" data-event-id="<?php echo $event['id']; ?>">
                                Register for this Event
                            </button>
                        <?php endif; ?>
                        <a href="<?php echo APP_URL; ?>/events.php" class="btn btn-secondary ">
                            Back to Events
                        </a>
                        <div id="registerMsg" class="mt-2"></div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mt-4">
                        <a href="<?php echo APP_URL; ?>/login.php">Login</a> or <a
                            href="<?php echo APP_URL; ?>/signup.php">create an account</a> to register for this event.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('registerBtn')?.addEventListener('click', async function () {
        const eventId = this.dataset.eventId;
        const btn = this;

        const { value: phone } = await Swal.fire({
            title: 'Registration',
            text: 'Please enter your phone number for event communication:',
            input: 'tel',
            inputPlaceholder: 'e.g., 07XXX XXX XXX',
            inputAttributes: { 'aria-label': 'Phone number' },
            showCancelButton: true,
            confirmButtonText: 'Register',
            cancelButtonText: 'Cancel',
            preConfirm: (phoneValue) => {
                if (!phoneValue || phoneValue.trim() === '') {
                    Swal.showValidationMessage('Phone number is required');
                    return false;
                }
                const phoneRegex = /^[0-9+\-\s()]{8,20}$/;
                if (!phoneRegex.test(phoneValue.trim())) {
                    Swal.showValidationMessage('Please enter a valid phone number (8-20 digits, +, -, spaces, parentheses)');
                    return false;
                }
                return phoneValue.trim();
            }
        });

        if (!phone) return;

        btn.disabled = true;
        btn.innerHTML = 'Registering...';

        const formData = new FormData();
        formData.append('event_id', eventId);
        formData.append('phone', phone);
        formData.append('csrf_token', '<?php echo $csrf_token; ?>');

        try {
            const response = await fetch('<?php echo APP_URL; ?>/api/register-event.php', {
                method: 'POST',
                body: formData
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            if (data.success) {
                await Swal.fire({ icon: 'success', title: 'Registered!', text: data.message });
                document.getElementById('registerMsg').innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                btn.remove();
                // Reload to show registered badge (optional)
                setTimeout(() => location.reload(), 1500);
            } else {
                await Swal.fire({ icon: 'error', title: 'Registration Failed', text: data.message });
                btn.disabled = false;
                btn.innerHTML = 'Register for this Event';
            }
        } catch (err) {
            console.error(err);
            await Swal.fire({ icon: 'error', title: 'Error', text: 'Network error. Please try again.' });
            btn.disabled = false;
            btn.innerHTML = 'Register for this Event';
        }
    });
</script>

<?php include __DIR__ . '/../app/views/footer.php'; ?>