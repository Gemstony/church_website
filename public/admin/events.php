<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/helpers/Security.php';
require_once __DIR__ . '/../../app/models/Event.php';

Auth::requireAdmin();

$success = $_GET['msg'] ?? '';
$error = '';
$debugInfo = [];
$action = $_GET['action'] ?? 'list';
$eventId = (int)($_GET['id'] ?? 0);
$debugInfo['request_method'] = $_SERVER['REQUEST_METHOD'] ?? '';
$debugInfo['has_save_event'] = isset($_POST['save_event']);
$debugInfo['post_keys'] = array_keys($_POST);

// Handle Add/Edit POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['save_event']) || isset($_POST['event_id']) || isset($_POST['title']) || isset($_POST['event_date']))) {
    $debugInfo['request_method'] = 'POST';
    $debugInfo['post_keys'] = array_keys($_POST);
    $debugInfo['files_keys'] = array_keys($_FILES);

    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
        $debugInfo['csrf'] = 'invalid';
    } else {
        $debugInfo['csrf'] = 'valid';
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $eventDate = trim($_POST['event_date'] ?? '');
        $eventEndDate = trim($_POST['event_end_date'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $eventIdFromForm = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;

        $debugInfo['submitted_title'] = $title;
        $debugInfo['submitted_event_date'] = $eventDate;
        $debugInfo['submitted_event_end_date'] = $eventEndDate;
        $debugInfo['submitted_location'] = $location;
        $debugInfo['submitted_event_id'] = $eventIdFromForm;

        if ($title === '' || $eventDate === '') {
            $error = 'Title and start date are required.';
            $debugInfo['validation'] = 'failed';
        } else {
            $debugInfo['validation'] = 'passed';
            $eventDate = str_replace('T', ' ', $eventDate);
            $eventEndDate = $eventEndDate !== '' ? str_replace('T', ' ', $eventEndDate) : null;

            $data = [
                'title' => $title,
                'description' => $description,
                'event_date' => $eventDate,
                'event_end_date' => $eventEndDate,
                'location' => $location,
                'created_by' => Auth::userId(),
                'image' => null
            ];

            if ($eventIdFromForm > 0) {
                $data['id'] = $eventIdFromForm;
                $existingEvent = Event::find($eventIdFromForm);
                if ($existingEvent) {
                    $data['image'] = $existingEvent['image'] ?? null;
                }
            }

            if (isset($_FILES['image']) && is_array($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($_FILES['image']['type'], $allowed)) {
                    $error = 'Image must be JPG, PNG, GIF, or WEBP.';
                } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                    $error = 'Image max size 2MB.';
                } else {
                    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $filename = 'event_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $uploadDir = __DIR__ . '/../../public/assets/uploads/events/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $target = $uploadDir . $filename;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                        $data['image'] = 'assets/uploads/events/' . $filename;

                        if ($eventIdFromForm > 0) {
                            $oldEvent = Event::find($eventIdFromForm);
                            if ($oldEvent && !empty($oldEvent['image'])) {
                                $oldFile = __DIR__ . '/../../public/' . $oldEvent['image'];
                                if (file_exists($oldFile)) {
                                    unlink($oldFile);
                                }
                            }
                        }
                    } else {
                        $error = 'Failed to upload image.';
                    }
                }
            }

            if (!$error) {
                try {
                    $debugInfo['save_payload'] = $data;
                    if (Event::save($data)) {
                        $success = $eventIdFromForm > 0 ? 'Event updated successfully.' : 'Event created successfully.';
                        $debugInfo['save_result'] = 'success';
                        header('Location: events.php?msg=' . urlencode($success));
                        exit;
                    }
                    $error = 'Failed to save event.';
                    $debugInfo['save_result'] = 'failed';
                } catch (Throwable $e) {
                    $error = 'Database error: ' . $e->getMessage();
                    $debugInfo['exception'] = $e->getMessage();
                    $debugInfo['trace'] = $e->getTraceAsString();
                }
            }
        }
    }
}

// Handle Delete
if ($action === 'delete' && $eventId > 0) {
    if (!isset($_GET['csrf_token']) || !Security::verifyCSRFToken($_GET['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        if (Event::delete($eventId)) {
            $success = 'Event deleted successfully.';
        } else {
            $error = 'Delete failed.';
        }
    }
    header('Location: events.php?msg=' . urlencode($success ?: $error));
    exit;
}



// Get all events for listing
$events = Event::getAll();

$csrf_token = Security::generateCSRFToken();
include __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Events Management</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#eventModal" data-mode="add">+ Add New Event</button>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo Security::escape($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo Security::escape($error); ?></div>
<?php endif; ?>


<!-- Events Table -->
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Image</th>
                <th>Title</th>
                <th>Date & Time</th>
                <th>Location</th>
                <th>Registrations</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($events)): ?>
                <tr><td colspan="7" class="text-center">No events found.</td></tr>
            <?php else: ?>
                <?php foreach ($events as $event): ?>
                    <?php $regCount = Event::getRegistrationCount($event['id']); ?>
                    <tr>
                        <td><?php echo $event['id']; ?></td>
                        <td>
                            <?php if ($event['image']): ?>
                                <img src="<?php echo APP_URL . '/' . $event['image']; ?>" width="50" height="50" style="object-fit: cover; border-radius: 4px;">
                            <?php else: ?>
                                <span class="text-muted">No image</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo Security::escape($event['title']); ?></td>
                        <td><?php echo date('M j, Y g:i A', strtotime($event['start'])); ?></td>
                        <td><?php echo Security::escape($event['location']); ?></td>
                        <td>
                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#registrationsModal" data-event-id="<?php echo $event['id']; ?>" data-event-title="<?php echo Security::escape($event['title']); ?>">
                                <?php echo $regCount; ?> registrations
                            </button>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#eventModal" data-mode="edit" data-id="<?php echo $event['id']; ?>" data-title="<?php echo Security::escape($event['title']); ?>" data-description="<?php echo Security::escape($event['description']); ?>" data-start="<?php echo $event['start']; ?>" data-end="<?php echo $event['end']; ?>" data-location="<?php echo Security::escape($event['location']); ?>" data-image="<?php echo $event['image']; ?>">Edit</button>
                            <a href="events.php?action=delete&id=<?php echo $event['id']; ?>&csrf_token=<?php echo $csrf_token; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this event permanently?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add/Edit Event Modal -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add/Edit Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="event_id" id="event_id">
                    <div class="mb-3">
                        <label>Title *</label>
                        <input type="text" name="title" id="event_title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Description</label>
                        <textarea name="description" id="event_description" class="form-control" rows="4"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Start Date & Time *</label>
                            <input type="datetime-local" name="event_date" id="event_start" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>End Date & Time (optional)</label>
                            <input type="datetime-local" name="event_end_date" id="event_end" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Location</label>
                        <input type="text" name="location" id="event_location" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Event Image (optional)</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <div id="current_image" class="mt-2"></div>
                        <small class="text-muted">Max 2MB. JPG, PNG, GIF, WEBP.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="save_event_btn" name="save_event" value="1" class="btn btn-primary">Save Event</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Registrations Modal -->
<div class="modal fade" id="registrationsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrations for <span id="regEventTitle"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="registrationsList">
                <div class="text-center">Loading...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Populate edit modal
    const eventModal = document.getElementById('eventModal');
    eventModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const mode = button.dataset.mode;
        if (mode === 'edit') {
            document.getElementById('event_id').value = button.dataset.id;
            document.getElementById('event_title').value = button.dataset.title;
            document.getElementById('event_description').value = button.dataset.description;
            // Format datetime-local from MySQL datetime
            const start = button.dataset.start.replace(' ', 'T').slice(0, 16);
            document.getElementById('event_start').value = start;
            if (button.dataset.end) {
                const end = button.dataset.end.replace(' ', 'T').slice(0, 16);
                document.getElementById('event_end').value = end;
            } else {
                document.getElementById('event_end').value = '';
            }
            document.getElementById('event_location').value = button.dataset.location;
            const imgDiv = document.getElementById('current_image');
            if (button.dataset.image) {
                imgDiv.innerHTML = '<img src="<?php echo APP_URL; ?>/' + button.dataset.image + '" style="max-height:80px"> <small>Current image (will be replaced if you upload a new one)</small>';
            } else {
                imgDiv.innerHTML = '';
            }
        } else {
            // Add mode: clear form
            document.getElementById('event_id').value = '';
            document.getElementById('event_title').value = '';
            document.getElementById('event_description').value = '';
            document.getElementById('event_start').value = '';
            document.getElementById('event_end').value = '';
            document.getElementById('event_location').value = '';
            document.getElementById('current_image').innerHTML = '';
        }
    });

    // Load registrations via AJAX
    const regModal = document.getElementById('registrationsModal');
    regModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const eventId = button.dataset.eventId;
        const eventTitle = button.dataset.eventTitle;
        document.getElementById('regEventTitle').innerText = eventTitle;
        const listDiv = document.getElementById('registrationsList');
        listDiv.innerHTML = '<div class="text-center">Loading...</div>';
        fetch('<?php echo APP_URL; ?>/api/event-registrations.php?event_id=' + eventId)
            .then(res => res.json())
            .then(data => {
                if (data.length === 0) {
                    listDiv.innerHTML = '<p class="text-muted">No registrations yet.</p>';
                } else {
                    let html = '<ul class="list-group">';
                    data.forEach(reg => {
                        html += `<li class="list-group-item"><strong>${escapeHtml(reg.full_name)}</strong><br><small>${escapeHtml(reg.email)}</small><br><small class="text-muted">Registered: ${new Date(reg.registered_at).toLocaleString()}</small></li>`;
                    });
                    html += '</ul>';
                    listDiv.innerHTML = html;
                }
            })
            .catch(() => {
                listDiv.innerHTML = '<div class="alert alert-danger">Failed to load registrations.</div>';
            });
    });

    const eventForm = document.querySelector('#eventModal form');
    const saveEventBtn = document.getElementById('save_event_btn');

    if (eventForm && saveEventBtn) {
        eventForm.addEventListener('submit', function() {
            saveEventBtn.disabled = true;
            saveEventBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...';
        });
    }

    function escapeHtml(str) {
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>