<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/models/AdminManager.php';

Auth::requireAdmin();

$stats = AdminManager::getStats();

// Get additional stats
$db = Database::getConnection();
$stmt = $db->query("SELECT COUNT(*) as count FROM cvs");
$stats['cvs'] = $stmt->fetch()['count'];
$stmt = $db->query("SELECT COUNT(*) as count FROM event_registrations");
$stats['registrations'] = $stmt->fetch()['count'];

// Recent activity
$recentMembers = $db->query("SELECT id, full_name, email, created_at FROM users WHERE role = 'member' ORDER BY created_at DESC LIMIT 5")->fetchAll();
$upcomingEvents = $db->query("SELECT id, title, event_date FROM events WHERE event_date >= NOW() ORDER BY event_date ASC LIMIT 5")->fetchAll();
$recentMessages = $db->query("SELECT id, name, email, submitted_at FROM contact_messages ORDER BY submitted_at DESC LIMIT 5")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
    .stat-card { transition: transform 0.2s; }
    .stat-card:hover { transform: translateY(-5px); }
    .activity-list { max-height: 300px; overflow-y: auto; }
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary shadow-sm stat-card">
            <div class="card-body">
                <h5 class="card-title">Total Members</h5>
                <h2 class="display-6"><?php echo $stats['members']; ?></h2>
                <a href="members.php" class="text-white text-decoration-none">Manage →</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success shadow-sm stat-card">
            <div class="card-body">
                <h5 class="card-title">Events</h5>
                <h2 class="display-6"><?php echo $stats['events']; ?></h2>
                <a href="events.php" class="text-white text-decoration-none">Manage →</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info shadow-sm stat-card">
            <div class="card-body">
                <h5 class="card-title">Media Items</h5>
                <h2 class="display-6"><?php echo $stats['media']; ?></h2>
                <a href="media.php" class="text-white text-decoration-none">Manage →</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning shadow-sm stat-card">
            <div class="card-body">
                <h5 class="card-title">Unread Messages</h5>
                <h2 class="display-6"><?php echo $stats['unread_messages']; ?></h2>
                <a href="messages.php" class="text-white text-decoration-none">View →</a>
            </div>
        </div>
    </div>
</div>

<!-- Second Row of Stats -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-secondary shadow-sm stat-card">
            <div class="card-body">
                <h5 class="card-title">CVs Uploaded</h5>
                <h2 class="display-6"><?php echo $stats['cvs']; ?></h2>
                <a href="reports.php?export=cvs" class="text-white text-decoration-none">Export →</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-dark shadow-sm stat-card">
            <div class="card-body">
                <h5 class="card-title">Event Registrations</h5>
                <h2 class="display-6"><?php echo $stats['registrations']; ?></h2>
                <a href="reports.php" class="text-white text-decoration-none">Reports →</a>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Member Growth (Last 6 Months)</div>
            <div class="card-body">
                <canvas id="memberChart" height="200"></canvas>
                <div id="memberChartError" class="text-danger small mt-2" style="display:none;"></div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Event Registrations (Last 6 Months)</div>
            <div class="card-body">
                <canvas id="registrationChart" height="200"></canvas>
                <div id="regChartError" class="text-danger small mt-2" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity & Quick Actions -->
<div class="row g-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">Recent Members</div>
            <div class="card-body activity-list">
                <?php if (empty($recentMembers)): ?>
                    <p class="text-muted">No members yet.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentMembers as $m): ?>
                            <li class="list-group-item">
                                <strong><?php echo Security::escape($m['full_name']); ?></strong><br>
                                <small><?php echo Security::escape($m['email']); ?></small><br>
                                <small class="text-muted">Joined: <?php echo date('M j, Y', strtotime($m['created_at'])); ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <div class="mt-2"><a href="members.php" class="btn btn-sm btn-outline-primary">View all members</a></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header bg-success text-white">Upcoming Events</div>
            <div class="card-body activity-list">
                <?php if (empty($upcomingEvents)): ?>
                    <p class="text-muted">No upcoming events.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($upcomingEvents as $e): ?>
                            <li class="list-group-item">
                                <strong><?php echo Security::escape($e['title']); ?></strong><br>
                                <small><?php echo date('M j, Y g:i A', strtotime($e['event_date'])); ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <div class="mt-2"><a href="events.php" class="btn btn-sm btn-outline-success">View all events</a></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header bg-warning text-dark">Recent Messages</div>
            <div class="card-body activity-list">
                <?php if (empty($recentMessages)): ?>
                    <p class="text-muted">No messages yet.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentMessages as $msg): ?>
                            <li class="list-group-item">
                                <strong><?php echo Security::escape($msg['name']); ?></strong><br>
                                <small><?php echo Security::escape($msg['email']); ?></small><br>
                                <small class="text-muted"><?php echo date('M j, Y', strtotime($msg['submitted_at'])); ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <div class="mt-2"><a href="messages.php" class="btn btn-sm btn-outline-warning">View all messages</a></div>
            </div>
        </div>
    </div>
</div>
<script>
    (function() {
        async function loadCharts() {
            const memberChartCanvas = document.getElementById('memberChart');
            const regChartCanvas = document.getElementById('registrationChart');
            const memberErrorDiv = document.getElementById('memberChartError');
            const regErrorDiv = document.getElementById('regChartError');
            
            if (!memberChartCanvas || !regChartCanvas) return;
            
            try {
                const response = await fetch('<?php echo APP_URL; ?>/api/chart-data.php');
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                const data = await response.json();
                if (data.error) {
                    throw new Error(data.error);
                }
                // Member chart
                new Chart(memberChartCanvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: data.members.labels,
                        datasets: [{
                            label: 'New Members',
                            data: data.members.values,
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13,110,253,0.1)',
                            fill: true,
                            tension: 0.3
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: true }
                });
                // Registration chart
                new Chart(regChartCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: data.registrations.labels,
                        datasets: [{
                            label: 'Registrations',
                            data: data.registrations.values,
                            backgroundColor: '#28a745',
                            borderRadius: 5
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: true }
                });
            } catch (err) {
                console.error('Chart error:', err);
                const errorMsg = 'Error: ' + err.message;
                if (memberErrorDiv) memberErrorDiv.innerText = errorMsg;
                if (regErrorDiv) regErrorDiv.innerText = errorMsg;
                memberErrorDiv.style.display = 'block';
                regErrorDiv.style.display = 'block';
            }
        }
        
        // Wait for Chart.js to be defined
        if (typeof Chart !== 'undefined') {
            loadCharts();
        } else {
            window.addEventListener('load', function() {
                if (typeof Chart !== 'undefined') {
                    loadCharts();
                } else {
                    console.error('Chart.js not loaded');
                    document.getElementById('memberChartError').innerText = 'Chart.js library failed to load.';
                    document.getElementById('memberChartError').style.display = 'block';
                }
            });
        }
    })();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>