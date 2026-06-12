<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/helpers/Security.php';
require_once __DIR__ . '/../app/helpers/Auth.php';
require_once __DIR__ . '/../app/models/Event.php';

$upcomingEvents = Event::getUpcoming(20);
$view = $_GET['view'] ?? 'list';

// Get registration status for logged-in user
$registeredMap = [];
if (Auth::isLoggedIn()) {
    $eventIds = array_column($upcomingEvents, 'id');
    if (!empty($eventIds)) {
        $registeredMap = Event::getUserRegistrationStatuses($eventIds, Auth::userId());
    }
}

include __DIR__ . '/../app/views/header.php';
?>

<style>
    .event-card {
        transition: transform 0.2s;
        margin-bottom: 1.5rem;
    }
    .event-card:hover {
        transform: translateY(-5px);
    }
    .fc-day-today {
        background-color: rgba(13, 110, 253, 0.05) !important;
    }
    .btn-toggle {
        margin-bottom: 1.5rem;
    }
    .reg-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 10;
    }
    .card-img-top, .bg-light {
        position: relative;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Events</h1>
    <div class="btn-group btn-toggle" role="group">
        <a href="?view=list" class="btn <?php echo $view === 'list' ? 'btn-primary' : 'btn-outline-primary'; ?>">List View</a>
        <a href="?view=calendar" class="btn <?php echo $view === 'calendar' ? 'btn-primary' : 'btn-outline-primary'; ?>">Calendar View</a>
    </div>
</div>

<?php if ($view === 'list'): ?>
    <div class="row">
        <?php if (empty($upcomingEvents)): ?>
            <div class="col-12">
                <div class="alert alert-info">No upcoming events at this time. Please check back later.</div>
            </div>
        <?php else: ?>
            <?php foreach ($upcomingEvents as $event): ?>
                <div class="col-md-6 col-lg-4">
                    <a href="event-details.php?id=<?php echo $event['id']; ?>" class="text-decoration-none">
                        <div class="card event-card shadow-sm h-100">
                            <!-- Registration Badge -->
                            <?php if (Auth::isLoggedIn()): ?>
                                <?php if (isset($registeredMap[$event['id']])): ?>
                                    <span class="badge bg-success reg-badge"><i class="fas fa-check-circle"></i> Registered</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary reg-badge">Not Registered</span>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if ($event['image']): ?>
                                <img src="<?php echo APP_URL . '/' . $event['image']; ?>" class="card-img-top" alt="<?php echo Security::escape($event['title']); ?>" style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-light text-center py-5" style="height: 200px;">
                                    <i class="fas fa-calendar-alt fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo Security::escape($event['title']); ?></h5>
                                <p class="card-text text-muted small">
                                    <i class="far fa-calendar-alt"></i>
                                    <?php echo date('F j, Y g:i A', strtotime($event['event_date'])); ?>
                                    <?php if ($event['event_end_date']): ?>
                                        – <?php echo date('F j, Y g:i A', strtotime($event['event_end_date'])); ?>
                                    <?php endif; ?>
                                </p>
                                <?php if ($event['location']): ?>
                                    <p class="card-text text-muted small"><i class="fas fa-map-marker-alt"></i> <?php echo Security::escape($event['location']); ?></p>
                                <?php endif; ?>
                                <p class="card-text"><?php echo nl2br(Security::escape(substr($event['description'], 0, 150))); ?>...</p>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php else: ?>
    <!-- Calendar View (unchanged, but could be enhanced similarly) -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.js"></script>
    <div id="calendar" style="min-height: 500px;"></div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listWeek'
                },
                events: '<?php echo APP_URL; ?>/api/events.php',
                eventClick: function (info) {
                    info.jsEvent.preventDefault();
                    if (info.event.url) {
                        window.location.href = info.event.url;
                    } else {
                        alert(info.event.title + '\n' + (info.event.extendedProps.description || ''));
                    }
                },
                loading: function (isLoading) {
                    if (isLoading) {
                        document.getElementById('calendar').innerHTML = '<div class="text-center p-5">Loading events...</div>';
                    }
                }
            });
            calendar.render();
        });
    </script>
<?php endif; ?>

<?php include __DIR__ . '/../app/views/footer.php'; ?>