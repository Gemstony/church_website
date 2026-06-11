<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/helpers/Security.php';
require_once __DIR__ . '/../app/models/Event.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$event = Event::find($id);

if (!$event) {
    header('Location: ' . APP_URL . '/events.php');
    exit;
}

include __DIR__ . '/../app/views/header.php';
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/events.php">Events</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo Security::escape($event['title']); ?></li>
            </ol>
        </nav>

        <div class="card shadow-sm border-0">
            <?php if ($event['image']): ?>
                <img src="<?php echo APP_URL . '/' . $event['image']; ?>" class="card-img-top" alt="<?php echo Security::escape($event['title']); ?>" style="max-height: 400px; object-fit: cover;">
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
                        <button class="btn btn-primary btn-lg" id="registerBtn" data-event-id="<?php echo $event['id']; ?>">
                            Register for this Event
                        </button>
                        <div id="registerMsg" class="mt-2"></div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mt-4">
                        <a href="<?php echo APP_URL; ?>/login.php">Login</a> or <a href="<?php echo APP_URL; ?>/signup.php">create an account</a> to register for this event.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // AJAX registration (will be implemented when we add event_registrations table)
    document.getElementById('registerBtn')?.addEventListener('click', function() {
        let btn = this;
        let eventId = btn.dataset.eventId;
        btn.disabled = true;
        btn.innerHTML = 'Registering...';
        fetch('<?php echo APP_URL; ?>/api/register-event.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'event_id=' + eventId
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('registerMsg').innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                btn.remove();
            } else {
                document.getElementById('registerMsg').innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                btn.disabled = false;
                btn.innerHTML = 'Register for this Event';
            }
        })
        .catch(err => {
            document.getElementById('registerMsg').innerHTML = '<div class="alert alert-danger">Error. Please try again.</div>';
            btn.disabled = false;
            btn.innerHTML = 'Register for this Event';
        });
    });
</script>

<?php include __DIR__ . '/../app/views/footer.php'; ?>