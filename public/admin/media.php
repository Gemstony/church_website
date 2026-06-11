<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/models/AdminManager.php';

Auth::requireAdmin();

$stats = AdminManager::getStats();

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
</div>

<div class="row g-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Total Members</h5>
                <h2 class="display-6"><?php echo $stats['members']; ?></h2>
                <a href="members.php" class="text-white text-decoration-none">Manage →</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Events</h5>
                <h2 class="display-6"><?php echo $stats['events']; ?></h2>
                <a href="events.php" class="text-white text-decoration-none">Manage →</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Media Items</h5>
                <h2 class="display-6"><?php echo $stats['media']; ?></h2>
                <a href="media.php" class="text-white text-decoration-none">Manage →</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Unread Messages</h5>
                <h2 class="display-6"><?php echo $stats['unread_messages']; ?></h2>
                <a href="messages.php" class="text-white text-decoration-none">View →</a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Quick Actions</div>
            <div class="card-body">
                <a href="members.php?action=add" class="btn btn-outline-primary m-1">Add Member</a>
                <a href="events.php?action=add" class="btn btn-outline-success m-1">Add Event</a>
                <a href="media.php" class="btn btn-outline-info m-1">Upload Media</a>
                <a href="settings.php" class="btn btn-outline-secondary m-1">Site Settings</a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Recent Activity</div>
            <div class="card-body">
                <p class="text-muted">Latest signups, events, and messages will appear here soon.</p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>