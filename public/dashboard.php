<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/helpers/Auth.php';
Auth::requireLogin();
include __DIR__ . '/../app/views/header.php';
?>
<h2>Welcome, <?php echo Security::escape($_SESSION['user_name']); ?></h2>
<div class="row mt-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">My Profile</h5>
                <p class="card-text">Update your personal information and profile picture.</p>
                <a href="#" class="btn btn-primary">Edit Profile</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">My CV</h5>
                <p class="card-text">Upload or update your CV.</p>
                <a href="#" class="btn btn-primary">Manage CV</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Change Password</h5>
                <p class="card-text">Update your login password.</p>
                <a href="#" class="btn btn-secondary">Change Password</a>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../app/views/footer.php'; ?>